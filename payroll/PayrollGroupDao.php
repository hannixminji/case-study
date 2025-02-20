<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

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
                name                      ,
                payroll_frequency         ,
                day_of_weekly_cutoff      ,
                day_of_biweekly_cutoff    ,
                semi_monthly_first_cutoff ,
                semi_monthly_second_cutoff,
                payday_offset             ,
                payday_adjustment         ,
                status
            )
            VALUES (
                :name                      ,
                :payroll_frequency         ,
                :day_of_weekly_cutoff      ,
                :day_of_biweekly_cutoff    ,
                :semi_monthly_first_cutoff ,
                :semi_monthly_second_cutoff,
                :payday_offset             ,
                :payday_adjustment         ,
                :status
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"                      , $payrollGroup->getName()                   , Helper::getPdoParameterType($payrollGroup->getName()                   ));
            $statement->bindValue(":payroll_frequency"         , $payrollGroup->getPayrollFrequency()       , Helper::getPdoParameterType($payrollGroup->getPayrollFrequency()       ));
            $statement->bindValue(":day_of_weekly_cutoff"      , $payrollGroup->getDayOfWeeklyCutoff()      , Helper::getPdoParameterType($payrollGroup->getDayOfWeeklyCutoff()      ));
            $statement->bindValue(":day_of_biweekly_cutoff"    , $payrollGroup->getDayOfBiweeklyCutoff()    , Helper::getPdoParameterType($payrollGroup->getDayOfBiweeklyCutoff()    ));
            $statement->bindValue(":semi_monthly_first_cutoff" , $payrollGroup->getSemiMonthlyFirstCutoff() , Helper::getPdoParameterType($payrollGroup->getSemiMonthlyFirstCutoff() ));
            $statement->bindValue(":semi_monthly_second_cutoff", $payrollGroup->getSemiMonthlySecondCutoff(), Helper::getPdoParameterType($payrollGroup->getSemiMonthlySecondCutoff()));
            $statement->bindValue(":payday_offset"             , $payrollGroup->getPaydayOffset()           , Helper::getPdoParameterType($payrollGroup->getPaydayOffset()           ));
            $statement->bindValue(":payday_adjustment"         , $payrollGroup->getPaydayAdjustment()       , Helper::getPdoParameterType($payrollGroup->getPaydayAdjustment()       ));
            $statement->bindValue(":status"                    , $payrollGroup->getStatus()                 , Helper::getPdoParameterType($payrollGroup->getStatus()                 ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the payroll group. " .
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
            "id"                         => "payroll_group.id                         AS id"                        ,
            "name"                       => "payroll_group.name                       AS name"                      ,
            "payroll_frequency"          => "payroll_group.payroll_frequency          AS payroll_frequency"         ,
            "day_of_weekly_cutoff"       => "payroll_group.day_of_weekly_cutoff       AS day_of_weekly_cutoff"      ,
            "day_of_biweekly_cutoff"     => "payroll_group.day_of_biweekly_cutoff     AS day_of_biweekly_cutoff"    ,
            "semi_monthly_first_cutoff"  => "payroll_group.semi_monthly_first_cutoff  AS semi_monthly_first_cutoff" ,
            "semi_monthly_second_cutoff" => "payroll_group.semi_monthly_second_cutoff AS semi_monthly_second_cutoff",
            "payday_offset"              => "payroll_group.payday_offset              AS payday_offset"             ,
            "payday_adjustment"          => "payroll_group.payday_adjustment          AS payday_adjustment"         ,
            "status"                     => "payroll_group.status                     AS status"                    ,
            "created_at"                 => "payroll_group.created_at                 AS created_at"                ,
            "updated_at"                 => "payroll_group.updated_at                 AS updated_at"                ,
            "deleted_at"                 => "payroll_group.deleted_at                 AS deleted_at"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "payroll_group.deleted_at IS NULL";

        } else {
            foreach ($filterCriteria as $filterCriterion) {
                $column   = $filterCriterion["column"  ];
                $operator = $filterCriterion["operator"];
                $boolean  = isset($filterCriterion["boolean"])
                    ? strtoupper($filterCriterion["boolean"])
                    : 'AND';

                switch ($operator) {
                    case "="   :
                    case "!="  :
                    case "LIKE":
                        $whereClauses    [] = "{$column} {$operator} ?";
                        $queryParameters [] = $filterCriterion["value"];

                        $filterParameters[] = $filterCriterion["value"];

                        break;

                    case "IS NOT NULL":
                    case "IS NULL"    :
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

                $whereClauses[] = " {$boolean}";
            }
        }

        if (in_array(trim(end($whereClauses)), ['AND', 'OR'], true)) {
            array_pop($whereClauses);
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
                payroll_groups AS payroll_group
            WHERE
                " . implode(" ", $whereClauses) . "
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
                        COUNT(payroll_group.id)
                    FROM
                        payroll_groups AS payroll_group
                    WHERE
                        " . implode(" ", $whereClauses) . "
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
                name                       = :name                      ,
                payroll_frequency          = :payroll_frequency         ,
                day_of_weekly_cutoff       = :day_of_weekly_cutoff      ,
                day_of_biweekly_cutoff     = :day_of_biweekly_cutoff    ,
                semi_monthly_first_cutoff  = :semi_monthly_first_cutoff ,
                semi_monthly_second_cutoff = :semi_monthly_second_cutoff,
                payday_offset              = :payday_offset             ,
                payday_adjustment          = :payday_adjustment         ,
                status                     = :status
            WHERE
        ";

        if ( ! ctype_digit( (string) $payrollGroup->getId())) {
            $query .= " SHA2(id, 256) = :payroll_group_id";
        } else {
            $query .= " id = :payroll_group_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"                      , $payrollGroup->getName()                   , Helper::getPdoParameterType($payrollGroup->getName()                   ));
            $statement->bindValue(":payroll_frequency"         , $payrollGroup->getPayrollFrequency()       , Helper::getPdoParameterType($payrollGroup->getPayrollFrequency()       ));
            $statement->bindValue(":day_of_weekly_cutoff"      , $payrollGroup->getDayOfWeeklyCutoff()      , Helper::getPdoParameterType($payrollGroup->getDayOfWeeklyCutoff()      ));
            $statement->bindValue(":day_of_biweekly_cutoff"    , $payrollGroup->getDayOfBiweeklyCutoff()    , Helper::getPdoParameterType($payrollGroup->getDayOfBiweeklyCutoff()    ));
            $statement->bindValue(":semi_monthly_first_cutoff" , $payrollGroup->getSemiMonthlyFirstCutoff() , Helper::getPdoParameterType($payrollGroup->getSemiMonthlyFirstCutoff() ));
            $statement->bindValue(":semi_monthly_second_cutoff", $payrollGroup->getSemiMonthlySecondCutoff(), Helper::getPdoParameterType($payrollGroup->getSemiMonthlySecondCutoff()));
            $statement->bindValue(":payday_offset"             , $payrollGroup->getPaydayOffset()           , Helper::getPdoParameterType($payrollGroup->getPaydayOffset()           ));
            $statement->bindValue(":payday_adjustment"         , $payrollGroup->getPaydayAdjustment()       , Helper::getPdoParameterType($payrollGroup->getPaydayAdjustment()       ));
            $statement->bindValue(":status"                    , $payrollGroup->getStatus()                 , Helper::getPdoParameterType($payrollGroup->getStatus()                 ));

            $statement->bindValue(":payroll_group_id"          , $payrollGroup->getId()                     , Helper::getPdoParameterType($payrollGroup->getId()                     ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while updating the payroll group. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $payrollGroupId): ActionResult
    {
        return $this->softDelete($payrollGroupId);
    }

    private function softDelete(int|string $payrollGroupId): ActionResult
    {
        $query = "
            UPDATE payroll_groups
            SET
                status     = 'Archived'       ,
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if ( ! ctype_digit( (string) $payrollGroupId)) {
            $query .= " SHA2(id, 256) = :payroll_group_id";
        } else {
            $query .= " id = :payroll_group_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":payroll_group_id", $payrollGroupId, Helper::getPdoParameterType($payrollGroupId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while deleting the payroll group. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
