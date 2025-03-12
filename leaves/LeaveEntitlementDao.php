<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

class LeaveEntitlementDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(LeaveEntitlement $leaveEntitlement): ActionResult
    {
        $isExistingQuery = "
            SELECT
                1
            FROM
                leave_entitlements
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $leaveEntitlement->getEmployeeId())) {
            $isExistingQuery .= "employee_id = :employee_id ";
        } else {
            $isExistingQuery .= "SHA2(employee_id, 256) = :employee_id ";
        }

        if (preg_match("/^[1-9]\d*$/", $leaveEntitlement->getLeaveTypeId())) {
            $isExistingQuery .= "AND leave_type_id = :leave_type_id";
        } else {
            $isExistingQuery .= "AND SHA2(leave_type_id, 256) = :leave_type_id";
        }

        $isExistingQuery .= "
            AND
                deleted_at IS NULL
        ";

        $insertQuery = "
            INSERT INTO leave_entitlements (
                employee_id            ,
                leave_type_id          ,
                number_of_entitled_days,
                number_of_days_taken   ,
                remaining_days
            ) VALUES (
                :employee_id            ,
                :leave_type_id          ,
                :number_of_entitled_days,
                :number_of_days_taken   ,
                :remaining_days
            )
        ";

        $updateQuery = "
            UPDATE leave_entitlements
            SET
                number_of_entitled_days = :number_of_entitled_days,
                number_of_days_taken    = :number_of_days_taken   ,
                remaining_days          = :remaining_days
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $leaveEntitlement->getEmployeeId())) {
            $updateQuery .= "employee_id = :employee_id ";
        } else {
            $updateQuery .= "SHA2(employee_id, 256) = :employee_id ";
        }

        if (preg_match("/^[1-9]\d*$/", $leaveEntitlement->getLeaveTypeId())) {
            $updateQuery .= "AND leave_type_id = :leave_type_id";
        } else {
            $updateQuery .= "AND SHA2(leave_type_id, 256) = :leave_type_id";
        }

        $updateQuery .= "
            AND
                deleted_at IS NULL
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $isExistingStatement = $this->pdo->prepare($isExistingQuery);

            $isExistingStatement->bindValue(':employee_id'  , $leaveEntitlement->getEmployeeId() , Helper::getPdoParameterType($leaveEntitlement->getEmployeeId() ));
            $isExistingStatement->bindValue(':leave_type_id', $leaveEntitlement->getLeaveTypeId(), Helper::getPdoParameterType($leaveEntitlement->getLeaveTypeId()));

            $isExistingStatement->execute();

            if ($isExistingStatement->rowCount() > 0) {
                $updateStatement = $this->pdo->prepare($updateQuery);

                $updateStatement->bindValue(':number_of_entitled_days', $leaveEntitlement->getNumberOfEntitledDays(), Helper::getPdoParameterType($leaveEntitlement->getNumberOfEntitledDays()));
                $updateStatement->bindValue(':number_of_days_taken'   , $leaveEntitlement->getNumberOfDaysTaken()   , Helper::getPdoParameterType($leaveEntitlement->getNumberOfDaysTaken()   ));
                $updateStatement->bindValue(':remaining_days'         , $leaveEntitlement->getRemainingDays()       , Helper::getPdoParameterType($leaveEntitlement->getRemainingDays()       ));
                $updateStatement->bindValue(':employee_id'            , $leaveEntitlement->getEmployeeId()          , Helper::getPdoParameterType($leaveEntitlement->getEmployeeId()          ));
                $updateStatement->bindValue(':leave_type_id'          , $leaveEntitlement->getLeaveTypeId()         , Helper::getPdoParameterType($leaveEntitlement->getLeaveTypeId()         ));

                $updateStatement->execute();

            } else {
                $insertStatement = $this->pdo->prepare($insertQuery);

                $insertStatement->bindValue(':employee_id'            , $leaveEntitlement->getEmployeeId()          , Helper::getPdoParameterType($leaveEntitlement->getEmployeeId()          ));
                $insertStatement->bindValue(':leave_type_id'          , $leaveEntitlement->getLeaveTypeId()         , Helper::getPdoParameterType($leaveEntitlement->getLeaveTypeId()         ));
                $insertStatement->bindValue(':number_of_entitled_days', $leaveEntitlement->getNumberOfEntitledDays(), Helper::getPdoParameterType($leaveEntitlement->getNumberOfEntitledDays()));
                $insertStatement->bindValue(':number_of_days_taken'   , $leaveEntitlement->getNumberOfDaysTaken()   , Helper::getPdoParameterType($leaveEntitlement->getNumberOfDaysTaken()   ));
                $insertStatement->bindValue(':remaining_days'         , $leaveEntitlement->getRemainingDays()       , Helper::getPdoParameterType($leaveEntitlement->getRemainingDays()       ));

                $insertStatement->execute();
            }

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating or updating the leave entitlement. " .
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
            "id"                       => "leave_entitlement.id                      AS id"                      ,
            "employee_id"              => "leave_entitlement.employee_id             AS employee_id"             ,
            "leave_type_id"            => "leave_entitlement.leave_type_id           AS leave_type_id"           ,
            "number_of_entitled_days"  => "leave_entitlement.number_of_entitled_days AS number_of_entitled_days" ,
            "number_of_days_taken"     => "leave_entitlement.number_of_days_taken    AS number_of_days_taken"    ,
            "remaining_days"           => "leave_entitlement.remaining_days          AS remaining_days"          ,
            "created_at"               => "leave_entitlement.created_at              AS created_at"              ,
            "deleted_at"               => "leave_entitlement.deleted_at              AS deleted_at"              ,

            "employee_first_name"      => "employee.first_name                       AS employee_first_name"     ,
            "employee_middle_name"     => "employee.middle_name                      AS employee_middle_name"    ,
            "employee_last_name"       => "employee.last_name                        AS employee_last_name"      ,

            "leave_type_name"          => "leave_type.name                           AS leave_type_name"         ,
            "leave_type_is_paid"       => "leave_type.is_paid                        AS leave_type_is_paid"      ,
            "leave_type_is_encashable" => "leave_type.is_encashable                  AS leave_type_is_encashable"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("employee_first_name" , $selectedColumns) ||
            array_key_exists("employee_middle_name", $selectedColumns) ||
            array_key_exists("employee_last_name"  , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    employees AS employee
                ON
                    leave_entitlement.employee_id = employee.id
            ";
        }

        if (array_key_exists("leave_type_name"         , $selectedColumns) ||
            array_key_exists("leave_type_is_paid"      , $selectedColumns) ||
            array_key_exists("leave_type_is_encashable", $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    leave_types AS leave_type
                ON
                    leave_entitlement.leave_type_id = leave_type.id
            ";
        }

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "leave_entitlement.deleted_at IS NULL";

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
                leave_entitlements AS leave_entitlement
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
                        COUNT(leave_entitlement.id)
                    FROM
                        leave_entitlements AS leave_entitlement
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
            error_log("Database Error: An error occurred while fetching leave entitlements. " .
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
                if (is_array($condition)) {
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

                $subConditions[] = "($nestedConditions)";

            } else {
                $column   = $filterCriterion["column"  ]         ;
                $operator = $filterCriterion["operator"]         ;
                $boolean  = $filterCriterion["boolean" ] ?? "AND";

                switch ($operator) {
                    case "="   :
                    case "!="  :
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

    public function updateBalance(LeaveEntitlement $leaveEntitlement): ActionResult
    {
        $query = "
            UPDATE leave_entitlements
            SET
                number_of_days_taken = :number_of_days_taken,
                remaining_days       = :remaining_days
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $leaveEntitlement->getEmployeeId())) {
            $query .= "employee_id = :employee_id ";
        } else {
            $query .= "SHA2(employee_id, 256) = :employee_id ";
        }

        if (preg_match("/^[1-9]\d*$/", $leaveEntitlement->getLeaveTypeId())) {
            $query .= "AND leave_type_id = :leave_type_id";
        } else {
            $query .= "AND SHA2(leave_type_id, 256) = :leave_type_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":number_of_days_taken", $leaveEntitlement->getNumberOfDaysTaken(), Helper::getPdoParameterType($leaveEntitlement->getNumberOfDaysTaken()));
            $statement->bindValue(":remaining_days"      , $leaveEntitlement->getRemainingDays()    , Helper::getPdoParameterType($leaveEntitlement->getRemainingDays()    ));

            $statement->bindValue(":employee_id"         , $leaveEntitlement->getEmployeeId()       , Helper::getPdoParameterType($leaveEntitlement->getEmployeeId()       ));
            $statement->bindValue(":leave_type_id"       , $leaveEntitlement->getLeaveTypeId()      , Helper::getPdoParameterType($leaveEntitlement->getLeaveTypeId()      ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while updating the leave entitlement. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function resetEmployeeAllLeaveBalances(int|string $employeeId)
    {
        $query = "
            UPDATE leave_entitlements
            SET
                number_of_days_taken = 0,
                remaining_days       = 0
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $employeeId)) {
            $query .= "employee_id = :employee_id";
        } else {
            $query .= "SHA2(employee_id, 256) = :employee_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id", $employeeId, Helper::getPdoParameterType($employeeId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while resetting all an employee's leave balances. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $leaveEntitlementId): ActionResult
    {
        return $this->softDelete($leaveEntitlementId);
    }

    private function softDelete(int|string $leaveEntitlementId): ActionResult
    {
        $query = "
            UPDATE leave_entitlements
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $leaveEntitlementId)) {
            $query .= "id = :leave_entitlement_id";
        } else {
            $query .= "SHA2(id, 256) = :leave_entitlement_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':leave_entitlement_id', $leaveEntitlementId, PDO::PARAM_INT);

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while deleting the leave entitlement. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
