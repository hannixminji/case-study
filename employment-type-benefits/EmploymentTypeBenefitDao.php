<?php

require_once __DIR__ . "/EmploymentTypeBenefit.php"         ;

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

class EmploymentTypeBenefitDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(EmploymentTypeBenefit $employeeTypeBenefit): ActionResult
    {
        $isBenefitAlreadyExists = $this->checkIfExists($employeeTypeBenefit);

        if ($isBenefitAlreadyExists === ActionResult::FAILURE) {
            return ActionResult::FAILURE;
        }

        if ($isBenefitAlreadyExists === true) {
            return ActionResult::SUCCESS;
        }

        $query = "
            INSERT INTO employment_type_benefits (
                employment_type,
                leave_type_id  ,
                allowance_id   ,
                deduction_id
            )
            VALUES (
                :employment_type,
                :leave_type_id  ,
                :allowance_id   ,
                :deduction_id
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employment_type", $employeeTypeBenefit->getEmploymentType(), Helper::getPdoParameterType($employeeTypeBenefit->getEmploymentType()));
            $statement->bindValue(":leave_type_id"  , $employeeTypeBenefit->getLeaveTypeId()   , Helper::getPdoParameterType($employeeTypeBenefit->getLeaveTypeId()   ));
            $statement->bindValue(":allowance_id"   , $employeeTypeBenefit->getAllowanceId()   , Helper::getPdoParameterType($employeeTypeBenefit->getAllowanceId()   ));
            $statement->bindValue(":deduction_id"   , $employeeTypeBenefit->getDeductionId()   , Helper::getPdoParameterType($employeeTypeBenefit->getDeductionId()   ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the employment type benefit. " .
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
            "id"                                => "employment_type_benefit.id              AS id"                               ,
            "employment_type"                   => "employment_type_benefit.employment_type AS employment_type"                  ,
            "leave_type_id"                     => "employment_type_benefit.leave_type_id   AS leave_type_id"                    ,
            "allowance_id"                      => "employment_type_benefit.allowance_id    AS allowance_id"                     ,
            "deduction_id"                      => "employment_type_benefit.deduction_id    AS deduction_id"                     ,
            "created_at"                        => "employment_type_benefit.created_at      AS created_at"                       ,
            "deleted_at"                        => "employment_type_benefit.deleted_at      AS deleted_at"                       ,

            "leave_type_name"                   => "leave_type.name                         AS leave_type_name"                  ,
            "leave_type_maximum_number_of_days" => "leave_type.maximum_number_of_days       AS leave_type_maximum_number_of_days",
            "leave_type_is_paid"                => "leave_type.is_paid                      AS leave_type_is_paid"               ,
            "leave_type_status"                 => "leave_type.status                       AS leave_type_status"                ,
            "leave_type_deleted_at"             => "leave_type.deleted_at                   AS leave_type_deleted_at"            ,

            "allowance_name"                    => "allowance.name                          AS allowance_name"                   ,
            "allowance_amount"                  => "allowance.amount                        AS allowance_amount"                 ,
            "allowance_frequency"               => "allowance.frequency                     AS allowance_frequency"              ,
            "allowance_status"                  => "allowance.status                        AS allowance_status"                 ,
            "allowance_deleted_at"              => "allowance.deleted_at                    AS allowance_deleted_at"             ,

            "deduction_name"                    => "deduction.name                          AS deduction_name"                   ,
            "deduction_amount"                  => "deduction.amount                        AS deduction_amount"                 ,
            "deduction_frequency"               => "deduction.frequency                     AS deduction_frequency"              ,
            "deduction_description"             => "deduction.description                   AS deduction_description"            ,
            "deduction_status"                  => "deduction.status                        AS deduction_status"                 ,
            "deduction_deleted_at"              => "deduction.deleted_at                    AS deduction_deleted_at"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("leave_type_name"                  , $selectedColumns) ||
            array_key_exists("leave_type_maximum_number_of_days", $selectedColumns) ||
            array_key_exists("leave_type_is_paid"               , $selectedColumns) ||
            array_key_exists("leave_type_status"                , $selectedColumns) ||
            array_key_exists("leave_type_deleted_at"            , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    leave_types AS leave_type
                ON
                    employment_type_benefit.leave_type_id = leave_type.id
            ";
        }

        if (array_key_exists("allowance_name"      , $selectedColumns) ||
            array_key_exists("allowance_amount"    , $selectedColumns) ||
            array_key_exists("allowance_frequency" , $selectedColumns) ||
            array_key_exists("allowance_status"    , $selectedColumns) ||
            array_key_exists("allowance_deleted_at", $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    allowances AS allowance
                ON
                    employment_type_benefit.allowance_id = allowance.id
            ";
        }

        if (array_key_exists("deduction_name"       , $selectedColumns) ||
            array_key_exists("deduction_amount"     , $selectedColumns) ||
            array_key_exists("deduction_frequency"  , $selectedColumns) ||
            array_key_exists("deduction_description", $selectedColumns) ||
            array_key_exists("deduction_status"     , $selectedColumns) ||
            array_key_exists("deduction_deleted_at" , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    deductions AS deduction
                ON
                    employment_type_benefit.deduction_id = deduction.id
            ";
        }

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "employment_type_benefit.deleted_at IS NULL";

        } else {
            $whereClauses[] = $this->buildFilterCriteria(
                filterCriteria  : $filterCriteria  ,
                queryParameters : $queryParameters ,
                filterParameters: $filterParameters
            );
        }

        if (in_array(trim(end($whereClauses)), ["AND", "OR"], true)) {
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
                employment_type_benefits AS employment_type_benefit
            {$joinClauses}
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
                        COUNT(employment_type_benefit.id)
                    FROM
                        employment_type_benefits AS employment_type_benefit
                    {$joinClauses}
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
            error_log("Database Error: An error occurred while fetching employment type benefits. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    private function buildFilterCriteria(
        array  $filterCriteria  ,
        array &$queryParameters ,
        array &$filterParameters
    ): string {

        $totalNumberOfConditions = count($filterCriteria);
        $subConditions           = []                    ;

        foreach ($filterCriteria as $index => $filterCriterion) {
            $isNestedCondition = false;

            foreach ($filterCriterion as $condition) {
                if (is_array($condition) && ! isset($filterCriterion["operator"])) {
                    $isNestedCondition = true;

                    break;
                }
            }

            if ($isNestedCondition) {
                $nestedConditions = $this->buildFilterCriteria(
                    filterCriteria  : $filterCriterion ,
                    queryParameters : $queryParameters ,
                    filterParameters: $filterParameters
                );

                $nestedConditions = "($nestedConditions)";

                $boolean = $filterCriterion[count($filterCriterion) - 1]["boolean"] ?? "AND";

                if ($index < $totalNumberOfConditions - 1) {
                    $nestedConditions .= " {$boolean}";
                }

                $subConditions[] = $nestedConditions;

            } else {
                $column   = $filterCriterion["column"  ]         ;
                $operator = $filterCriterion["operator"]         ;
                $boolean  = $filterCriterion["boolean" ] ?? "AND";

                switch ($operator) {
                    case "="   :
                    case "!="  :
                    case ">"   :
                    case "<"   :
                    case ">="  :
                    case "<="  :
                    case "LIKE":
                        $subCondition = "{$column} {$operator} ?";

                        $queryParameters [] = $filterCriterion["value"];
                        $filterParameters[] = $filterCriterion["value"];

                        break;

                    case "IS NULL"    :
                    case "IS NOT NULL":
                        $subCondition = "{$column} {$operator}";

                        break;

                    case "BETWEEN":
                        $subCondition = "{$column} {$operator} ? AND ?";

                        $queryParameters [] = $filterCriterion["lower_bound"];
                        $queryParameters [] = $filterCriterion["upper_bound"];

                        $filterParameters[] = $filterCriterion["lower_bound"];
                        $filterParameters[] = $filterCriterion["upper_bound"];

                        break;

                    case "IN":
                        $valueList = $filterCriterion["value_list"];

                        if ( ! empty($valueList)) {
                            $placeholders = implode(", ", array_fill(0, count($valueList), "?"));

                            $subCondition     = "{$column} IN ({$placeholders})"          ;

                            $queryParameters  = array_merge($queryParameters , $valueList);
                            $filterParameters = array_merge($filterParameters, $valueList);
                        }

                        break;
                }

                if ($index < $totalNumberOfConditions - 1) {
                    $subCondition .= " {$boolean}";
                }

                $subConditions[] = $subCondition;
            }
        }

        return implode(" ", $subConditions);
    }

    public function checkIfExists(EmploymentTypeBenefit $benefit): bool|ActionResult
    {
        $query = "
            SELECT
                1
            FROM
                employment_type_benefits
            WHERE
                employment_type = :employment_type
            AND
                (leave_type_id = :leave_type_id OR :leave_type_id IS NULL)
            AND
                (allowance_id  = :allowance_id  OR :allowance_id  IS NULL)
            AND
                (deduction_id  = :deduction_id  OR :deduction_id  IS NULL)
            AND
                deleted_at IS NULL
        ";

        try {
            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employment_type", $benefit->getEmploymentType(), Helper::getPdoParameterType($benefit->getEmploymentType()));
            $statement->bindValue(":leave_type_id"  , $benefit->getLeaveTypeId()   , Helper::getPdoParameterType($benefit->getLeaveTypeId()   ));
            $statement->bindValue(":allowance_id"   , $benefit->getAllowanceId()   , Helper::getPdoParameterType($benefit->getAllowanceId()   ));
            $statement->bindValue(":deduction_id"   , $benefit->getDeductionId()   , Helper::getPdoParameterType($benefit->getDeductionId()   ));

            $statement->execute();

            return $statement->rowCount() > 0;

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while checking for existing employment type benefit. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $employmentTypeBenefitId): ActionResult
    {
        return $this->softDelete($employmentTypeBenefitId);
    }

    private function softDelete(int|string $employmentTypeBenefitId): ActionResult
    {
        $query = "
            UPDATE employment_type_benefits
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $employmentTypeBenefitId)) {
            $query .= "id = :employment_benefit_id";
        } else {
            $query .= "SHA2(id, 256) = :employment_benefit_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employment_benefit_id", $employmentTypeBenefitId, Helper::getPdoParameterType($employmentTypeBenefitId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while deleting the employment type benefit. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
