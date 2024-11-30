<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

class EmployeeAllowanceDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(EmployeeAllowance $employeeAllowance): ActionResult
    {
        $query = "
            INSERT INTO employee_allowances (
                employee_id ,
                allowance_id,
                amount
            )
            VALUES (
                :employee_id ,
                :allowance_id,
                :amount
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id" , $employeeAllowance->getEmployeeId() , Helper::getPdoParameterType($employeeAllowance->getEmployeeId() ));
            $statement->bindValue(":allowance_id", $employeeAllowance->getAllowanceId(), Helper::getPdoParameterType($employeeAllowance->getAllowanceId()));
            $statement->bindValue(":amount"      , $employeeAllowance->getAmount()     , Helper::getPdoParameterType($employeeAllowance->getAmount()     ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while assigning the allowance to employee. " .
                      "Exception: {$exception->getMessage()}");

            if ( (int) $exception->getCode() === ErrorCode::DUPLICATE_ENTRY->value) {
                return ActionResult::DUPLICATE_ENTRY_ERROR;
            }

            return ActionResult::FAILURE;
        }
    }

    public function fetchAll(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        $tableColumns = [
            "id"                       => "employee_allowance.id           AS id"                      ,
            "employee_id"              => "employee_allowance.employee_id  AS employee_id"             ,

            "allowance_id"             => "employee_allowance.allowance_id AS allowance_id"            ,
            "allowance_name"           => "allowance.name                  AS allowance_name"          ,
            "allowance_is_taxable"     => "allowance.is_taxable            AS allowance_is_taxable"    ,
            "allowance_frequency"      => "allowance.frequency             AS allowance_frequency"     ,
            "allowance_status"         => "allowance.status                AS allowance_status"        ,
            "allowance_effective_date" => "allowance.effective_date        AS allowance_effective_date",
            "allowance_end_date"       => "allowance.end_date              AS allowance_end_date"      ,

            "amount"                   => "employee_allowance.amount       AS amount"                  ,
            "created_at"               => "employee_allowance.created_at   AS created_at"              ,
            "deleted_at"               => "employee_allowance.deleted_at   AS employee_deleted_at"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("allowance_name"          , $selectedColumns) ||
            array_key_exists("allowance_is_taxable"    , $selectedColumns) ||
            array_key_exists("allowance_frequency"     , $selectedColumns) ||
            array_key_exists("allowance_status"        , $selectedColumns) ||
            array_key_exists("allowance_effective_date", $selectedColumns) ||
            array_key_exists("allowance_end_date"      , $selectedColumns)) {
            $joinClauses = "
                LEFT JOIN
                    allowances AS allowance
                ON
                    employee_allowance.allowance_id = allowance.id
            ";
        }

        $queryParameters = [];

        $whereClauses = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "employee_allowance.deleted_at IS NULL";
        } else {
            foreach ($filterCriteria as $filterCriterion) {
                $column   = $filterCriterion["column"  ];
                $operator = $filterCriterion["operator"];

                switch ($operator) {
                    case "="   :
                    case "LIKE":
                        $whereClauses   [] = "{$column} {$operator} ?";
                        $queryParameters[] = $filterCriterion["value"];
                        break;

                    case "IS NULL":
                        $whereClauses[] = "{$column} {$operator}";
                        break;

                    case "BETWEEN":
                        $whereClauses   [] = "{$column} {$operator} ? AND ?";
                        $queryParameters[] = $filterCriterion["lower_bound"];
                        $queryParameters[] = $filterCriterion["upper_bound"];
                        break;

                    default:
                        // Do nothing
                }
            }
        }

        $orderByClauses = [];

        if ( ! empty($sortCriteria)) {
            foreach ($sortCriteria as $sortCriterion) {
                $column = $sortCriterion["column"];
                $direction = $sortCriterion["direction"];
                $orderByClauses[] = "{$column} {$direction}";
            }
        }

        $limitClause = "";
        if ($limit !== null) {
            $limitClause = " LIMIT ?";
            $queryParameters[] = $limit;
        }

        $offsetClause = "";
        if ($offset !== null) {
            $offsetClause = " OFFSET ?";
            $queryParameters[] = $offset;
        }

        $query = "
            SELECT SQL_CALC_FOUND_ROWS
                " . implode(", ", $selectedColumns) . "
            FROM
                employee_allowances AS employee_allowance
            {$joinClauses}
            WHERE
                " . implode(" AND ", $whereClauses) . "
            " . (!empty($orderByClauses) ? "ORDER BY " . implode(", ", $orderByClauses) : "") . "
            {$limitClause}
            {$offsetClause}
        ";

        try {
            $statement = $this->pdo->prepare($query);

            foreach ($queryParameters as $index => $parameter) {
                $statement->bindValue($index + 1, $parameter, Helper::getPdoParameterType($parameter));
            }

            $statement->execute();

            $resultSet = [];
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $resultSet[] = $row;
            }

            $countStatement = $this->pdo->query("SELECT FOUND_ROWS()");
            $totalRowCount = $countStatement->fetchColumn();

            return [
                "result_set"      => $resultSet,
                "total_row_count" => $totalRowCount
            ];

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching employee allowances. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int $employeeAllowanceId): ActionResult
    {
        return $this->softDelete($employeeAllowanceId);
    }

    private function softDelete(int $employeeAllowanceId): ActionResult
    {
        $query = '
            UPDATE employee_allowances
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
                id = :employee_allowance_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':employee_allowance_id', $employeeAllowanceId, PDO::PARAM_INT);

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while deleting the employee allowance. ' .
                    'Exception: ' . $exception->getMessage());

            return ActionResult::FAILURE;
        }
    }
}
