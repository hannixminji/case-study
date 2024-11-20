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
                employee_id  ,
                leave_type_id,
                start_date   ,
                end_date     ,
                reason       ,
                status
            )
            VALUES (
                :employee_id  ,
                :leave_type_id,
                :start_date   ,
                :end_date     ,
                :reason       ,
                :status
            )
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':employee_id'  , $leaveRequest->getEmployeeId() , Helper::getPdoParameterType($leaveRequest->getEmployeeId() ));
            $statement->bindValue(':leave_type_id', $leaveRequest->getLeaveTypeId(), Helper::getPdoParameterType($leaveRequest->getLeaveTypeId()));
            $statement->bindValue(':start_date'   , $leaveRequest->getStartDate()  , Helper::getPdoParameterType($leaveRequest->getStartDate()  ));
            $statement->bindValue(':end_date'     , $leaveRequest->getEndDate()    , Helper::getPdoParameterType($leaveRequest->getEndDate()    ));
            $statement->bindValue(':reason'       , $leaveRequest->getReason()     , Helper::getPdoParameterType($leaveRequest->getReason()     ));
            $statement->bindValue(':status'       , $leaveRequest->getStatus()     , Helper::getPdoParameterType($leaveRequest->getStatus()     ));

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
            "id"                       => "leave_request.id               AS id"                      ,
            "employee_id"              => "leave_request.employee_id      AS employee_id"             ,
            "employee_full_name"       => "employee.full_name             AS employee_full_name"      ,
            "employee_department_id"   => "employee.department_id         AS employee_department_id"  ,
            "employee_department_name" => "department.name                AS employee_department_name",
            "employee_job_title_id"    => "employee.job_title_id          AS employee_job_title_id"   ,
            "employee_job_title"       => "job_title.title                AS employee_job_title"      ,
            "employee_supervisor_id"   => "employee.supervisor_id         AS employee_supervisor_id"  ,
            "employee_manager_id"      => "employee.manager_id            AS employee_manager_id"     ,
            "leave_type_id"            => "leave_request.leave_type_id    AS leave_type_id"           ,
            "leave_type_name"          => "leave_type.name                AS leave_type_name"         ,
            "start_date"               => "leave_request.start_date       AS start_date"              ,
            "end_date"                 => "leave_request.end_date         AS end_date"                ,
            "reason"                   => "leave_request.reason           AS reason"                  ,

            "status" => "
                CASE
                    WHEN leave_request.status = 'Canceled' THEN 'Canceled'
                    WHEN leave_request.status = 'Rejected' THEN 'Rejected'
                    WHEN CURDATE() >= leave_request.start_date AND leave_request.status = 'Pending' THEN 'Expired'
                    WHEN leave_request.status = 'Approved' AND CURDATE() BETWEEN leave_request.start_date AND leave_request.end_date THEN 'In Progress'
                    WHEN leave_request.status = 'Approved' AND CURDATE() > leave_request.end_date THEN 'Completed'
                    WHEN leave_request.status = 'Approved' AND CURDATE() < leave_request.start_date THEN 'Approved'
                    ELSE 'Pending'
                END AS status
            ",

            "approved_at"              => "leave_request.approved_at      AS approved_at",
            "approved_by"              => "approved_by_employee.full_name AS approved_by",
            "created_at"               => "leave_request.created_at       AS created_at" ,
            "updated_at"               => "leave_request.updated_at       AS updated_at" ,
            "deleted_at"               => "leave_request.deleted_at       AS deleted_at"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("employee_id", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    employees AS employee
                ON
                    leave_request.employee_id = employee.id
            ";
        }

        if (array_key_exists("employee_department_name", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    departments AS department
                ON
                    employee.department_id = department.id
            ";
        }

        if (array_key_exists("employee_job_title", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    job_titles AS job_title
                ON
                    employee.job_title_id = job_title.id
            ";
        }

        if (array_key_exists("leave_type_name", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    leave_types AS leave_type
                ON
                    leave_request.leave_type_id = leave_type.id
            ";
        }

        if (array_key_exists("approved_by", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    admins AS approved_by
                ON
                    leave_request.approved_by = approved_by.id
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

    public function update(LeaveRequest $leaveRequest): ActionResult
    {
        $query = '
            UPDATE leave_requests
            SET
                employee_id   = :employee_id  ,
                leave_type_id = :leave_type_id,
                start_date    = :start_date   ,
                end_date      = :end_date     ,
                reason        = :reason
            WHERE
                id = :leave_request_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':employee_id'     , $leaveRequest->getEmployeeId() , Helper::getPdoParameterType($leaveRequest->getEmployeeId() ));
            $statement->bindValue(':leave_type_id'   , $leaveRequest->getLeaveTypeId(), Helper::getPdoParameterType($leaveRequest->getLeaveTypeId()));
            $statement->bindValue(':start_date'      , $leaveRequest->getStartDate()  , Helper::getPdoParameterType($leaveRequest->getStartDate()  ));
            $statement->bindValue(':end_date'        , $leaveRequest->getEndDate()    , Helper::getPdoParameterType($leaveRequest->getEndDate()    ));
            $statement->bindValue(':reason'          , $leaveRequest->getReason()     , Helper::getPdoParameterType($leaveRequest->getReason()     ));
            $statement->bindValue(':leave_request_id', $leaveRequest->getId()         , Helper::getPdoParameterType($leaveRequest->getId()         ));

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

    public function updateStatus(int $leaveRequestId, string $status): ActionResult
    {
        $query = '
            UPDATE leave_requests
            SET
                status     = :status
            WHERE
                id = :leave_request_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':status'          , $status        , Helper::getPdoParameterType($status        ));
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

    public function isEmployeeOnLeave(int $employeeId): ActionResult|bool
    {
        $query = '
            SELECT
                COUNT(*)
            FROM
                leave_requests
            WHERE
                employee_id = :employee_id
            AND
                status = \'In Progress\'
            LIMIT 1
        ';

        try {
            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':employee_id', $employeeId, Helper::getPdoParameterType($employeeId));

            $statement->execute();

            return $statement->fetchColumn() > 0;

        } catch (PDOException $exception) {
            error_log('Database Error: An error occurred while checking if the employee is on leave. ' .
                      'Exception: ' . $exception->getMessage());

            return ActionResult::FAILURE;
        }
    }

    public function delete(int $leaveRequestId): ActionResult
    {
        return $this->softDelete($leaveRequestId);
    }

    private function softDelete(int $leaveRequestId): ActionResult
    {
        $query = '
            UPDATE leave_requests
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
                id = :leave_request_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

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
