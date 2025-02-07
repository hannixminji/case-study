<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

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

            return ActionResult::FAILURE;
        }
    }

    public function fetchAll(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): ActionResult|array {

        $tableColumns = [
            "id"                  => "employee_allowance.id           AS id"                 ,
            "employee_id"         => "employee_allowance.employee_id  AS employee_id"        ,
            "allowance_id"        => "employee_allowance.allowance_id AS allowance_id"       ,
            "amount"              => "employee_allowance.amount       AS amount"             ,
            "created_at"          => "employee_allowance.created_at   AS created_at"         ,
            "deleted_at"          => "employee_allowance.deleted_at   AS employee_deleted_at",

            "allowance_name"      => "allowance.name                  AS allowance_name"     ,
            "allowance_frequency" => "allowance.frequency             AS allowance_frequency",
            "allowance_status"    => "allowance.status                AS allowance_status"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("allowance_name"     , $selectedColumns) ||
            array_key_exists("allowance_frequency", $selectedColumns) ||
            array_key_exists("allowance_status"   , $selectedColumns)) {

            $joinClauses = "
                LEFT JOIN
                    allowances AS allowance
                ON
                    employee_allowance.allowance_id = allowance.id
            ";
        }

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "employee_allowance.deleted_at IS NULL";
        } else {
            foreach ($filterCriteria as $filterCriterion) {
                $column   = $filterCriterion["column"  ];
                $operator = $filterCriterion["operator"];

                switch ($operator) {
                    case "="   :
                    case "LIKE":
                        $whereClauses    [] = "{$column} {$operator} ?";
                        $queryParameters [] = $filterCriterion["value"];

                        $filterParameters[] = $filterCriterion["value"];

                        break;

                    case "IS NULL":
                        $whereClauses[] = "{$column} {$operator}";

                        break;

                    case "BETWEEN":
                        $whereClauses    [] = "{$column} {$operator} ? AND ?";
                        $queryParameters [] = $filterCriterion["lower_bound"];
                        $queryParameters [] = $filterCriterion["upper_bound"];

                        $filterParameters[] = $filterCriterion["lower_bound"];
                        $filterParameters[] = $filterCriterion["upper_bound"];

                        break;
                }
            }
        }

        $orderByClauses = [];

        if ( ! empty($sortCriteria)) {
            foreach ($sortCriteria as $sortCriterion) {
                $column = $sortCriterion["column"];

                if (isset($sortCriterion["direction"])) {
                    $direction = $sortCriterion["direction"];
                    $orderByClauses[] = "{$column} {$direction}";

                } elseif (isset($sortCriterion["custom_order"])) {
                    $customOrder = $sortCriterion["custom_order"];
                    $caseExpressions = ["CASE {$column}"];

                    foreach ($customOrder as $priority => $value) {
                        $caseExpressions[] = "WHEN ? THEN {$priority}";
                        $queryParameters[] = $value;
                    }

                    $caseExpressions[] = "ELSE " . count($caseExpressions) . " END";
                    $orderByClauses[] = implode(" ", $caseExpressions);
                }
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
            SELECT
                " . implode(", ", $selectedColumns) . "
            FROM
                employee_allowances AS employee_allowance
            {$joinClauses}
            WHERE
                " . implode(" AND ", $whereClauses) . "
            " . ( ! empty($orderByClauses) ? "ORDER BY " . implode(", ", $orderByClauses) : "") . "
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

            $totalRowCount = null;

            if ($includeTotalRowCount) {
                $totalRowCountQuery = "
                    SELECT
                        COUNT(employee_allowance.id)
                    FROM
                        employee_allowances AS employee_allowance
                    {$joinClauses}
                    WHERE
                        " . implode(" AND ", $whereClauses) . "
                ";

                $countStatement = $this->pdo->prepare($totalRowCountQuery);

                foreach ($filterParameters as $index => $parameter) {
                    $countStatement->bindValue($index + 1, $parameter, Helper::getPdoParameterType($parameter));
                }

                $countStatement->execute();

                $totalRowCount = $countStatement->fetchColumn();
            }

            return [
                "result_set"      => $resultSet    ,
                "total_row_count" => $totalRowCount
            ];

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching employee allowances. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $employeeAllowanceId, bool $isHashedId = false): ActionResult
    {
        return $this->softDelete($employeeAllowanceId, $isHashedId);
    }

    private function softDelete(int|string $employeeAllowanceId, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE employee_allowances
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :employee_allowance_id";
        } else {
            $query .= " id = :employee_allowance_id";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_allowance_id", $employeeAllowanceId, Helper::getPdoParameterType($employeeAllowanceId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while fetching employee allowances. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
