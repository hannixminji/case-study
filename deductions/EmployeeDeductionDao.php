<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

class EmployeeDeductionDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(EmployeeDeduction $employeeDeduction): ActionResult
    {
        $query = "
            INSERT INTO employee_deductions (
                employee_id ,
                deduction_id,
                amount
            )
            VALUES (
                :employee_id ,
                :deduction_id,
                :amount
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id" , $employeeDeduction->getEmployeeId() , Helper::getPdoParameterType($employeeDeduction->getEmployeeId() ));
            $statement->bindValue(":deduction_id", $employeeDeduction->getDeductionId(), Helper::getPdoParameterType($employeeDeduction->getDeductionId()));
            $statement->bindValue(":amount"      , $employeeDeduction->getAmount()     , Helper::getPdoParameterType($employeeDeduction->getAmount()     ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while assigning the deduction to employee. " .
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
    ): array|ActionResult {

        $tableColumns = [
            "id"                  => "employee_deduction.id           AS id"                 ,
            "employee_id"         => "employee_deduction.employee_id  AS employee_id"        ,
            "deduction_id"        => "employee_deduction.deduction_id AS deduction_id"       ,
            "amount"              => "employee_deduction.amount       AS amount"             ,
            "created_at"          => "employee_deduction.created_at   AS created_at"         ,
            "deleted_at"          => "employee_deduction.deleted_at   AS deleted_at"         ,

            "deduction_name"      => "deduction.name                  AS deduction_name"     ,
            "deduction_frequency" => "deduction.frequency             AS deduction_frequency",
            "deduction_status"    => "deduction.status                AS deduction_status"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("deduction_name"          , $selectedColumns) ||
            array_key_exists("deduction_frequency"     , $selectedColumns) ||
            array_key_exists("deduction_status"        , $selectedColumns)) {

            $joinClauses = "
                LEFT JOIN
                    deductions AS deduction
                ON
                    employee_deduction.deduction_id = deduction.id
            ";
        }

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "employee_deduction.deleted_at IS NULL";

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
            SELECT
                " . implode(", ", $selectedColumns) . "
            FROM
                employee_deductions AS employee_deduction
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
                        COUNT(employee_deduction.id)
                    FROM
                        employee_deductions AS employee_deduction
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
            error_log("Database Error: An error occurred while fetching employee deductions. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $employeeDeductionId): ActionResult
    {
        return $this->softDelete($employeeDeductionId);
    }

    private function softDelete(int|string $employeeDeductionId): ActionResult
    {
        $query = "
            UPDATE employee_deductions
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if ( ! is_int($employeeDeductionId)) {
            $query .= " SHA2(id, 256) = :employee_deduction_id";
        } else {
            $query .= " id = :employee_deduction_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_deduction_id", $employeeDeductionId, Helper::getPdoParameterType($employeeDeductionId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while deleting the employee deduction. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
