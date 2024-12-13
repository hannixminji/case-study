<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

class PayrollGroupDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(PayrollGroup $payrollGroup): ActionResult
    {
        $query = "
            INSERT INTO payroll_groups (
                name         ,
                pay_frequency,
                status
            )
            VALUES (
                :name         ,
                :pay_frequency,
                :status
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"         , $payrollGroup->getName()        , Helper::getPdoParameterType($payrollGroup->getName()        ));
            $statement->bindValue(":pay_frequency", $payrollGroup->getPayFrequency(), Helper::getPdoParameterType($payrollGroup->getPayFrequency()));
            $statement->bindValue(":status"       , $payrollGroup->getStatus()      , Helper::getPdoParameterType($payrollGroup->getStatus()      ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the payroll group. " .
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
            "id"            => "payroll_group.id            AS id"           ,
            "name"          => "payroll_group.name          AS name"         ,
            "pay_frequency" => "payroll_group.pay_frequency AS pay_frequency",
            "status"        => "payroll_group.status        AS status"       ,
            "created_at"    => "payroll_group.created_at    AS created_at"   ,
            "updated_at"    => "payroll_group.updated_at    AS updated_at"   ,
            "deleted_at"    => "payroll_group.deleted_at    AS deleted_at"   ,
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $queryParameters = [];

        $whereClauses = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "payroll_group.status <> 'Archived'";
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

                if (isset($sortCriterion["direction"])) {
                    $direction = $sortCriterion["direction"];
                    $orderByClauses[] = "{$column} {$direction}";
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
            SELECT SQL_CALC_FOUND_ROWS
                " . implode(", ", $selectedColumns) . "
            FROM
                payroll_groups AS payroll_group
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

            $countStatement = $this->pdo->query("SELECT FOUND_ROWS()");
            $totalRowCount = $countStatement->fetchColumn();

            return [
                "result_set"      => $resultSet    ,
                "total_row_count" => $totalRowCount
            ];

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching the payroll groups. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(PayrollGroup $payrollGroup): ActionResult
    {
        $query = "
            UPDATE payroll_groups
            SET
                name          = :name         ,
                pay_frequency = :pay_frequency,
                status        = :status
            WHERE
                id = :payroll_group_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"            , $payrollGroup->getName()        , Helper::getPdoParameterType($payrollGroup->getName()        ));
            $statement->bindValue(":pay_frequency"   , $payrollGroup->getPayFrequency(), Helper::getPdoParameterType($payrollGroup->getPayFrequency()));
            $statement->bindValue(":status"          , $payrollGroup->getStatus()      , Helper::getPdoParameterType($payrollGroup->getStatus()      ));
            $statement->bindValue(":payroll_group_id", $payrollGroup->getId()          , Helper::getPdoParameterType($payrollGroup->getId()          ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while updating the payroll group. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int $payrollGroupId): ActionResult
    {
        return $this->softDelete($payrollGroupId);
    }

    private function softDelete(int $payrollGroupId): ActionResult
    {
        $query = "
            UPDATE payroll_groups
            SET
                status     = 'Archived'       ,
                deleted_at = CURRENT_TIMESTAMP
            WHERE
                id = :payroll_group_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":payroll_group_id", $payrollGroupId, Helper::getPdoParameterType($payrollGroupId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while deleting the payroll group. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
