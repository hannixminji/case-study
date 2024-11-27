<?php

require_once __DIR__ . '/../includes/Helper.php'            ;
require_once __DIR__ . '/../includes/enums/ActionResult.php';
require_once __DIR__ . '/../includes/enums/ErrorCode.php'   ;

class LeaveEntitlementDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(LeaveEntitlement $leaveEntitlement): ActionResult
    {
        $query = '
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
            ON DUPLICATE KEY UPDATE
                number_of_entitled_days = VALUES(number_of_entitled_days),
                number_of_days_taken    = VALUES(number_of_days_taken   ),
                remaining_days          = VALUES(remaining_days         ),
                updated_at              = CURRENT_TIMESTAMP
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':employee_id'            , $leaveEntitlement->getEmployeeId()          , Helper::getPdoParameterType($leaveEntitlement->getEmployeeId()          ));
            $statement->bindValue(':leave_type_id'          , $leaveEntitlement->getLeaveTypeId()         , Helper::getPdoParameterType($leaveEntitlement->getLeaveTypeId()         ));
            $statement->bindValue(':number_of_entitled_days', $leaveEntitlement->getNumberOfEntitledDays(), Helper::getPdoParameterType($leaveEntitlement->getNumberOfEntitledDays()));
            $statement->bindValue(':number_of_days_taken'   , $leaveEntitlement->getNumberOfDaysTaken()   , Helper::getPdoParameterType($leaveEntitlement->getNumberOfDaysTaken()   ));
            $statement->bindValue(':remaining_days'         , $leaveEntitlement->getRemainingDays()       , Helper::getPdoParameterType($leaveEntitlement->getRemainingDays()       ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while creating or updating the leave entitlement. ' .
                      'Exception: ' . $exception->getMessage());

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
            "id"                      => "leave_entitlement.id                      AS id"                     ,
            "employee_id"             => "leave_entitlement.employee_id             AS employee_id"            ,
            "employee_first_name"     => "employee.first_name                       AS employee_first_name"    ,
            "employee_middle_name"    => "employee.middle_name                      AS employee_middle_name"   ,
            "employee_last_name"      => "employee.last_name                        AS employee_last_name"     ,
            "leave_type_id"           => "leave_entitlement.leave_type_id           AS leave_type_id"          ,
            "leave_type_name"         => "leave_type.name                           AS leave_type_name"        ,
            "number_of_entitled_days" => "leave_entitlement.number_of_entitled_days AS number_of_entitled_days",
            "number_of_days_taken"    => "leave_entitlement.number_of_days_taken    AS number_of_days_taken"   ,
            "remaining_days"          => "leave_entitlement.remaining_days          AS remaining_days"         ,
            "created_at"              => "leave_entitlement.created_at              AS created_at"             ,
            "deleted_at"              => "leave_entitlement.deleted_at              AS deleted_at"
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

        if (array_key_exists("leave_type_name", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    leave_types AS leave_type
                ON
                    leave_entitlement.leave_type_id = leave_type.id
            ";
        }

        $queryParameters = [];

        $whereClauses = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "leave_entitlement.deleted_at IS NULL";
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

        if (!empty($sortCriteria)) {
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
            SELECT SQL_CALC_FOUND_ROWS
                " . implode(", ", $selectedColumns) . "
            FROM
                leave_entitlements AS leave_entitlement
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
                "result_set"      => $resultSet    ,
                "total_row_count" => $totalRowCount
            ];

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching leave entitlements. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int $leaveEntitlementId): ActionResult
    {
        return $this->softDelete($leaveEntitlementId);
    }

    private function softDelete(int $leaveEntitlementId): ActionResult
    {
        $query = '
            UPDATE leave_entitlements
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
                id = :leave_entitlement_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':leave_entitlement_id', $leaveEntitlementId, PDO::PARAM_INT);

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while deleting the leave entitlement. ' .
                    'Exception: ' . $exception->getMessage());

            return ActionResult::FAILURE;
        }
    }
}
