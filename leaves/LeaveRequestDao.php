<?php

require_once __DIR__ . '/../includes/Helper.php'            ;
require_once __DIR__ . '/../includes/enums/ActionResult.php';
require_once __DIR__ . '/../includes/enums/ErrorCode.php'   ;

class LeaveRequestDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(LeaveRequest $leaveRequest): ActionResult
    {
        $query = '
            INSERT INTO leave_requests (
                employee_id        ,
                leave_type_id      ,
                start_date         ,
                end_date           ,
                reason             ,
                status             ,
                created_by_employee,
                updated_by_employee
            )
            VALUES (
                :employee_id        ,
                :leave_type_id      ,
                :start_date         ,
                :end_date           ,
                :reason             ,
                :status             ,
                :created_by_employee,
                :updated_by_employee
            )
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':employee_id'        , $leaveRequest->getEmployeeId() , Helper::getPdoParameterType($leaveRequest->getEmployeeId() ));
            $statement->bindValue(':leave_type_id'      , $leaveRequest->getLeaveTypeId(), Helper::getPdoParameterType($leaveRequest->getLeaveTypeId()));
            $statement->bindValue(':start_date'         , $leaveRequest->getStartDate()  , Helper::getPdoParameterType($leaveRequest->getStartDate()  ));
            $statement->bindValue(':end_date'           , $leaveRequest->getEndDate()    , Helper::getPdoParameterType($leaveRequest->getEndDate()    ));
            $statement->bindValue(':reason'             , $leaveRequest->getReason()     , Helper::getPdoParameterType($leaveRequest->getReason()     ));
            $statement->bindValue(':status'             , $leaveRequest->getStatus()     , Helper::getPdoParameterType($leaveRequest->getStatus()     ));
            $statement->bindValue(':created_by_employee', $leaveRequest->getEmployeeId() , Helper::getPdoParameterType($leaveRequest->getEmployeeId() ));
            $statement->bindValue(':updated_by_employee', $leaveRequest->getEmployeeId() , Helper::getPdoParameterType($leaveRequest->getEmployeeId() ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while creating the leave request. ' .
                      'Exception: ' . $exception->getMessage());

            return ActionResult::FAILURE;
        }
    }

    public function fetchAll(
        ?array $columns        = null,
        ?array $filterCriteria = null,
        ?array $sortCriteria   = null,
        ?int   $limit          = null,
        ?int   $offset         = null
    ): ActionResult|array {
        $tableColumns = [
            "id"                               => "leave_request.id                   AS id"                              ,
            "employee_id"                      => "leave_request.employee_id          AS employee_id"                     ,
            "employee_first_name"              => "employee.first_name                AS employee_first_name"             ,
            "employee_middle_name"             => "employee.middle_name               AS employee_middle_name"            ,
            "employee_last_name"               => "employee.last_name                 AS employee_last_name"              ,
            "leave_type_id"                    => "leave_request.leave_type_id        AS leave_type_id"                   ,
            "leave_type_name"                  => "leave_type.name                    AS leave_type_name"                 ,
            "start_date"                       => "leave_request.start_date           AS start_date"                      ,
            "end_date"                         => "leave_request.end_date             AS end_date"                        ,
            "reason"                           => "leave_request.reason               AS reason"                          ,
            "status"                           => "leave_request.status               AS status"                          ,
            "approved_at"                      => "leave_request.approved_at          AS approved_at"                     ,
            "approved_by_admin_id"             => "leave_request.approved_by_admin    AS approved_by_admin_id"            ,
            "approved_by_admin_username"       => "approved_by_admin.username         AS approved_by_admin_username"      ,
            "approved_by_employee_id"          => "leave_request.approved_by_employee AS approved_by_employee_id"         ,
            "approved_by_employee_first_name"  => "approved_by_employee.first_name    AS approved_by_employee_first_name" ,
            "approved_by_employee_middle_name" => "approved_by_employee.middle_name   AS approved_by_employee_middle_name",
            "approved_by_employee_last_name"   => "approved_by_employee.last_name     AS approved_by_employee_last_name"  ,
            "created_at"                       => "leave_request.created_at           AS created_at"                      ,
            "created_by_employee_id"           => "leave_request.created_by_employee  AS created_by_employee_id"          ,
            "created_by_employee_first_name"   => "created_by_employee.first_name     AS created_by_employee_first_name"  ,
            "created_by_employee_middle_name"  => "created_by_employee.middle_name    AS created_by_employee_middle_name" ,
            "created_by_employee_last_name"    => "created_by_employee.last_name      AS created_by_employee_last_name"   ,
            "updated_at"                       => "leave_request.updated_at           AS updated_at"                      ,
            "updated_by_admin_id"              => "leave_request.updated_by_admin     AS updated_by_admin_id"             ,
            "updated_by_admin_username"        => "updated_by_admin.username          AS updated_by_admin_username"       ,
            "updated_by_employee_id"           => "leave_request.updated_by_employee  AS updated_by_employee_id"          ,
            "updated_by_employee_first_name"   => "updated_by_employee.first_name     AS updated_by_employee_first_name"  ,
            "updated_by_employee_middle_name"  => "updated_by_employee.middle_name    AS updated_by_employee_middle_name" ,
            "updated_by_employee_last_name"    => "updated_by_employee.last_name      AS updated_by_employee_last_name"   ,
            "deleted_at"                       => "leave_request.deleted_at           AS deleted_at"                      ,
            "deleted_by_admin_id"              => "leave_request.deleted_by_admin     AS deleted_by_admin_id"             ,
            "deleted_by_admin_username"        => "deleted_by_admin.username          AS deleted_by_admin_username"       ,
            "deleted_by_employee_id"           => "leave_request.deleted_by_employee  AS deleted_by_employee_id"          ,
            "deleted_by_employee_first_name"   => "deleted_by_employee.first_name     AS deleted_by_employee_first_name"  ,
            "deleted_by_employee_middle_name"  => "deleted_by_employee.middle_name    AS deleted_by_employee_middle_name" ,
            "deleted_by_employee_last_name"    => "deleted_by_employee.last_name      AS deleted_by_employee_last_name"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("employee_id"         , $selectedColumns) ||
            array_key_exists("employee_first_name" , $selectedColumns) ||
            array_key_exists("employee_middle_name", $selectedColumns) ||
            array_key_exists("employee_last_name"  , $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    employees AS employee
                ON
                    leave_request.employee_id = employee.id
            ";
        }

        if (array_key_exists("leave_type_id"  , $selectedColumns) ||
            array_key_exists("leave_type_name", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    leave_types AS leave_type
                ON
                    leave_request.leave_type_id = leave_type.id
            ";
        }

        if (array_key_exists("approved_by_admin_id"      , $selectedColumns) ||
            array_key_exists("approved_by_admin_username", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    admins AS approved_by_admin
                ON
                    leave_request.approved_by_admin = approved_by_admin.id
            ";
        }

        if (array_key_exists("approved_by_employee_id"         , $selectedColumns) ||
            array_key_exists("approved_by_employee_first_name" , $selectedColumns) ||
            array_key_exists("approved_by_employee_middle_name", $selectedColumns) ||
            array_key_exists("approved_by_employee_last_name"  , $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    employees AS approved_by_employee
                ON
                    leave_request.approved_by_employee = approved_by_employee.id
            ";
        }

        if (array_key_exists("created_by_employee_id"         , $selectedColumns) ||
            array_key_exists("created_by_employee_first_name" , $selectedColumns) ||
            array_key_exists("created_by_employee_middle_name", $selectedColumns) ||
            array_key_exists("created_by_employee_last_name"  , $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    employees AS created_by_employee
                ON
                    leave_request.created_by_employee = created_by_employee.id
            ";
        }

        if (array_key_exists("updated_by_admin_id"      , $selectedColumns) ||
            array_key_exists("updated_by_admin_username", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    admins AS updated_by_admin
                ON
                    leave_request.updated_by_admin = updated_by_admin.id
            ";
        }

        if (array_key_exists("updated_by_employee_id"         , $selectedColumns) ||
            array_key_exists("updated_by_employee_first_name" , $selectedColumns) ||
            array_key_exists("updated_by_employee_middle_name", $selectedColumns) ||
            array_key_exists("updated_by_employee_last_name"  , $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    employees AS updated_by_employee
                ON
                    leave_request.updated_by_employee = updated_by_employee.id
            ";
        }

        if (array_key_exists("deleted_by_admin_id"      , $selectedColumns) ||
            array_key_exists("deleted_by_admin_username", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    admins AS deleted_by_admin
                ON
                    leave_request.deleted_by_admin = deleted_by_admin.id
            ";
        }

        if (array_key_exists("deleted_by_employee_id"         , $selectedColumns) ||
            array_key_exists("deleted_by_employee_first_name" , $selectedColumns) ||
            array_key_exists("deleted_by_employee_middle_name", $selectedColumns) ||
            array_key_exists("deleted_by_employee_last_name"  , $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    employees AS deleted_by_employee
                ON
                    leave_request.deleted_by_employee = deleted_by_employee.id
            ";
        }

        $queryParameters = [];
        $whereClauses = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "leave_request.deleted_at IS NULL";
        } else {
            foreach ($filterCriteria as $filterCriterion) {
                $column   = $filterCriterion["column"  ];
                $operator = $filterCriterion["operator"];

                switch ($operator) {
                    case "=":
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

        $limitClause  = ($limit  !== null) ? " LIMIT :limit"   : "";
        $offsetClause = ($offset !== null) ? " OFFSET :offset" : "";

        $query = "
            SELECT SQL_CALC_FOUND_ROWS
                " . implode(", ", $selectedColumns) . "
            FROM
                leave_requests AS leave_request
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

            if ($limit !== null) {
                $statement->bindValue(":limit", $limit, Helper::getPdoParameterType($limit));
            }

            if ($offset !== null) {
                $statement->bindValue(":offset", $offset, Helper::getPdoParameterType($offset));
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
            error_log("Database Error: An error occurred while fetching the leave requests. " .
                    "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(LeaveRequest $leaveRequest, int $userId, bool $isAdmin): ActionResult
    {
        $query = '
            UPDATE leave_requests
            SET
                employee_id         = :employee_id        ,
                leave_type_id       = :leave_type_id      ,
                start_date          = :start_date         ,
                end_date            = :end_date           ,
                reason              = :reason             ,
                updated_by_admin    = :updated_by_admin   ,
                updated_by_employee = :updated_by_employee
            WHERE
                id = :leave_request_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':employee_id'  , $leaveRequest->getEmployeeId() , Helper::getPdoParameterType($leaveRequest->getEmployeeId() ));
            $statement->bindValue(':leave_type_id', $leaveRequest->getLeaveTypeId(), Helper::getPdoParameterType($leaveRequest->getLeaveTypeId()));
            $statement->bindValue(':start_date'   , $leaveRequest->getStartDate()  , Helper::getPdoParameterType($leaveRequest->getStartDate()  ));
            $statement->bindValue(':end_date'     , $leaveRequest->getEndDate()    , Helper::getPdoParameterType($leaveRequest->getEndDate()    ));
            $statement->bindValue(':reason'       , $leaveRequest->getReason()     , Helper::getPdoParameterType($leaveRequest->getReason()     ));

            if ($isAdmin) {
                $statement->bindValue(':updated_by_admin'   , $userId, Helper::getPdoParameterType($userId));
                $statement->bindValue(':updated_by_employee', null   , PDO::PARAM_NULL                     );
            } else {
                $statement->bindValue(':updated_by_admin'   , null   , PDO::PARAM_NULL                     );
                $statement->bindValue(':updated_by_employee', $userId, Helper::getPdoParameterType($userId));
            }

            $statement->bindValue(':leave_request_id', $leaveRequest->getId(), Helper::getPdoParameterType($leaveRequest->getId()));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while updating the leave request. ' .
                      'Exception: ' . $exception->getMessage());

            return ActionResult::FAILURE;
        }
    }

    public function updateStatus(int $leaveRequestId, string $status, int $userId, bool $isAdmin): ActionResult
    {
        $query = '
            UPDATE leave_requests
            SET
                status              = :status             ,
                updated_by_admin    = :updated_by_admin   ,
                updated_by_employee = :updated_by_employee
            WHERE
                id = :leave_request_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':status', $status, PDO::PARAM_STR);

            if ($isAdmin) {
                $statement->bindValue(':updated_by_admin'   , $userId, Helper::getPdoParameterType($userId));
                $statement->bindValue(':updated_by_employee', null   , PDO::PARAM_NULL                     );
            } else {
                $statement->bindValue(':updated_by_admin'   , null   , PDO::PARAM_NULL                     );
                $statement->bindValue(':updated_by_employee', $userId, Helper::getPdoParameterType($userId));
            }

            $statement->bindValue(':leave_request_id', $leaveRequestId, Helper::getPdoParameterType($leaveRequestId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while updating the status of the leave request. ' .
                      'Exception: ' . $exception->getMessage());

            return ActionResult::FAILURE;
        }
    }

    public function delete(int $leaveRequestId, int $userId, bool $isAdmin): ActionResult
    {
        return $this->softDelete($leaveRequestId, $userId, $isAdmin);
    }

    private function softDelete(int $leaveRequestId, int $userId, bool $isAdmin): ActionResult
    {
        $query = '
            UPDATE leave_requests
            SET
                deleted_at          = CURRENT_TIMESTAMP   ,
                deleted_by_admin    = :deleted_by_admin   ,
                deleted_by_employee = :deleted_by_employee
            WHERE
                id = :leave_request_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            if ($isAdmin) {
                $statement->bindValue(':deleted_by_admin'   , $userId, Helper::getPdoParameterType($userId));
                $statement->bindValue(':deleted_by_employee', null   , PDO::PARAM_NULL                     );
            } else {
                $statement->bindValue(':deleted_by_employee', $userId, Helper::getPdoParameterType($userId));
                $statement->bindValue(':deleted_by_admin'   , null   , PDO::PARAM_NULL                     );
            }

            $statement->bindValue(':leave_request_id', $leaveRequestId, Helper::getPdoParameterType($leaveRequestId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while deleting the leave request. ' .
                      'Exception: ' . $exception->getMessage());

            return ActionResult::FAILURE;
        }
    }
}
