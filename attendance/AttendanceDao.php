<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

class AttendanceDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function checkIn(Attendance $attendance): ActionResult
    {
        $query = "
            INSERT INTO attendance (
                work_schedule_history_id,
                date                    ,
                check_in_time           ,
                late_check_in           ,
                attendance_status
            )
            VALUES (
                :work_schedule_history_id,
                :date                    ,
                :check_in_time           ,
                :late_check_in           ,
                :attendance_status
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":work_schedule_history_id", $attendance->getWorkScheduleHistoryId(), Helper::getPdoParameterType($attendance->getWorkScheduleHistoryId()));
            $statement->bindValue(":date"                    , $attendance->getDate()                 , Helper::getPdoParameterType($attendance->getDate()                 ));
            $statement->bindValue(":check_in_time"           , $attendance->getCheckInTime()          , Helper::getPdoParameterType($attendance->getCheckInTime()          ));
            $statement->bindValue(":late_check_in"           , $attendance->getLateCheckIn()          , Helper::getPdoParameterType($attendance->getLateCheckIn()          ));
            $statement->bindValue(":attendance_status"       , $attendance->getAttendanceStatus()     , Helper::getPdoParameterType($attendance->getAttendanceStatus()     ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while checking in the attendance. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function checkOut(Attendance $attendance): ActionResult
    {
        $query = "
            UPDATE attendance
            SET
                check_out_time                  = :check_out_time                 ,
                total_break_duration_in_minutes = :total_break_duration_in_minutes,
                total_hours_worked              = :total_hours_worked             ,
                early_check_out                 = :early_check_out                ,
                overtime_hours                  = :overtime_hours                 ,
                attendance_status               = :attendance_status
            WHERE
                id = :attendance_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":check_out_time"                 , $attendance->getCheckOutTime()               , Helper::getPdoParameterType($attendance->getCheckOutTime()               ));
            $statement->bindValue(":total_break_duration_in_minutes", $attendance->getTotalBreakDurationInMinutes(), Helper::getPdoParameterType($attendance->getTotalBreakDurationInMinutes()));
            $statement->bindValue(":total_hours_worked"             , $attendance->getTotalHoursWorked()           , Helper::getPdoParameterType($attendance->getTotalHoursWorked()           ));
            $statement->bindValue(":early_check_out"                , $attendance->getEarlyCheckOut()              , Helper::getPdoParameterType($attendance->getEarlyCheckOut()              ));
            $statement->bindValue(":overtime_hours"                 , $attendance->getOvertimeHours()              , Helper::getPdoParameterType($attendance->getOvertimeHours()              ));
            $statement->bindValue(":attendance_status"              , $attendance->getAttendanceStatus()           , Helper::getPdoParameterType($attendance->getAttendanceStatus()           ));

            $statement->bindValue(":attendance_id"                  , $attendance->getId()                         , Helper::getPdoParameterType($attendance->getId()                         ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while checking out in the attendance. " .
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
            "id"                                                      => "attendance.id                                           AS id"                                                     ,
            "work_schedule_history_id"                                => "attendance.work_schedule_history_id                     AS work_schedule_history_id"                               ,
            "date"                                                    => "attendance.date                                         AS date"                                                   ,
            "check_in_time"                                           => "attendance.check_in_time                                AS check_in_time"                                          ,
            "check_out_time"                                          => "attendance.check_out_time                               AS check_out_time"                                         ,
            "total_break_duration_in_minutes"                         => "attendance.total_break_duration_in_minutes              AS total_break_duration_in_minutes"                        ,
            "total_hours_worked"                                      => "attendance.total_hours_worked                           AS total_hours_worked"                                     ,
            "late_check_in"                                           => "attendance.late_check_in                                AS late_check_in"                                          ,
            "early_check_out"                                         => "attendance.early_check_out                              AS early_check_out"                                        ,
            "overtime_hours"                                          => "attendance.overtime_hours                               AS overtime_hours"                                         ,
            "is_overtime_approved"                                    => "attendance.is_overtime_approved                         AS is_overtime_approved"                                   ,
            "attendance_status"                                       => "attendance.attendance_status                            AS attendance_status"                                      ,
            "remarks"                                                 => "attendance.remarks                                      AS remarks"                                                ,
            "is_processed_for_next_payroll"                           => "attendance.is_processed_for_next_payroll                AS is_processed_for_next_payroll"                          ,
            "created_at"                                              => "attendance.created_at                                   AS created_at"                                             ,
            "updated_at"                                              => "attendance.updated_at                                   AS updated_at"                                             ,
            "deleted_at"                                              => "attendance.deleted_at                                   AS deleted_at"                                             ,

            "day_of_the_week"                                         => "DAYOFWEEK(attendance.date)                              AS day_of_the_week"                                        ,

            "work_schedule_history_work_schedule_id"                  => "work_schedule_history.work_schedule_id                  AS work_schedule_history_work_schedule_id"                 ,
            "work_schedule_history_employee_id"                       => "work_schedule_history.employee_id                       AS work_schedule_history_employee_id"                      ,
            "work_schedule_history_start_time"                        => "work_schedule_history.start_time                        AS work_schedule_history_start_time"                       ,
            "work_schedule_history_end_time"                          => "work_schedule_history.end_time                          AS work_schedule_history_end_time"                         ,
            "work_schedule_history_is_flextime"                       => "work_schedule_history.is_flextime                       AS work_schedule_history_is_flextime"                      ,
            "work_schedule_history_total_hours_per_week"              => "work_schedule_history.total_hours_per_week              AS work_schedule_history_total_hours_per_week"             ,
            "work_schedule_history_total_work_hours"                  => "work_schedule_history.total_work_hours                  AS work_schedule_history_total_work_hours"                 ,
            "work_schedule_history_start_date"                        => "work_schedule_history.start_date                        AS work_schedule_history_start_date"                       ,
            "work_schedule_history_recurrence_rule"                   => "work_schedule_history.recurrence_rule                   AS work_schedule_history_recurrence_rule"                  ,
            "work_schedule_history_grace_period"                      => "work_schedule_history.grace_period                      AS work_schedule_history_grace_period"                     ,
            "work_schedule_history_minutes_can_check_in_before_shift" => "work_schedule_history.minutes_can_check_in_before_shift AS work_schedule_history_minutes_can_check_in_before_shift",
            "work_schedule_history_active_at"                         => "work_schedule_history.active_at                         AS work_schedule_history_active_at"                        ,

            "employee_full_name"                                      => "employee.full_name                                      AS employee_full_name"                                     ,
            "employee_code"                                           => "employee.employee_code                                  AS employee_code"                                          ,
            "employee_job_title_id"                                   => "employee.job_title_id                                   AS employee_job_title_id"                                  ,
            "employee_department_id"                                  => "employee.department_id                                  AS employee_department_id"                                 ,
            "employee_supervisor_id"                                  => "employee.supervisor_id                                  AS employee_supervisor_id"                                 ,
            "employee_deleted_at"                                     => "employee.deleted_at                                     AS employee_deleted_at"                                    ,

            "job_title"                                               => "job_title.title                                         AS job_title"                                              ,

            "department_name"                                         => "department.name                                         AS department_name"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns
                ));

        $joinClauses = "";

        if (array_key_exists("work_schedule_history_work_schedule_id"                 , $selectedColumns) ||
            array_key_exists("work_schedule_history_employee_id"                      , $selectedColumns) ||
            array_key_exists("work_schedule_history_start_time"                       , $selectedColumns) ||
            array_key_exists("work_schedule_history_end_time"                         , $selectedColumns) ||
            array_key_exists("work_schedule_history_is_flextime"                      , $selectedColumns) ||
            array_key_exists("work_schedule_history_total_hours_per_week"             , $selectedColumns) ||
            array_key_exists("work_schedule_history_total_work_hours"                 , $selectedColumns) ||
            array_key_exists("work_schedule_history_start_date"                       , $selectedColumns) ||
            array_key_exists("work_schedule_history_recurrence_rule"                  , $selectedColumns) ||
            array_key_exists("work_schedule_history_grace_period"                     , $selectedColumns) ||
            array_key_exists("work_schedule_history_minutes_can_check_in_before_shift", $selectedColumns) ||
            array_key_exists("work_schedule_history_active_at"                        , $selectedColumns) ||

            array_key_exists("employee_full_name"                                     , $selectedColumns) ||
            array_key_exists("employee_code"                                          , $selectedColumns) ||
            array_key_exists("employee_job_title_id"                                  , $selectedColumns) ||
            array_key_exists("employee_department_id"                                 , $selectedColumns) ||
            array_key_exists("employee_supervisor_id"                                 , $selectedColumns) ||
            array_key_exists("employee_deleted_at"                                    , $selectedColumns) ||

            array_key_exists("job_title"                                              , $selectedColumns) ||

            array_key_exists("department_name"                                        , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    work_schedules_history AS work_schedule_history
                ON
                    attendance.work_schedule_history_id = work_schedule_history.id
            ";
        }

        if (array_key_exists("employee_full_name"    , $selectedColumns) ||
            array_key_exists("employee_code"         , $selectedColumns) ||
            array_key_exists("employee_job_title_id" , $selectedColumns) ||
            array_key_exists("employee_department_id", $selectedColumns) ||
            array_key_exists("employee_supervisor_id", $selectedColumns) ||
            array_key_exists("employee_deleted_at"   , $selectedColumns) ||

            array_key_exists("job_title"             , $selectedColumns) ||

            array_key_exists("department_name"       , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    employees AS employee
                ON
                    work_schedule_history.employee_id = employee.id
            ";
        }

        if (array_key_exists("job_title", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    job_titles AS job_title
                ON
                    employee.job_title_id = job_title.id
            ";
        }

        if (array_key_exists("department_name", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    departments AS department
                ON
                    employee.department_id = department.id
            ";
        }

        $whereClauses    = [];
        $queryParameters = [];

        if ( ! empty($filterCriteria)) {
            foreach ($filterCriteria as $filterCriterion) {
                $column   = $filterCriterion["column"  ];
                $operator = $filterCriterion["operator"];

                switch ($operator) {
                    case "="   :
                    case "!="  :
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
                attendance
            {$joinClauses}
            " . (empty($whereClauses) ? "" : "WHERE " . implode(" AND ", $whereClauses)) . "
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
                        COUNT(attendance.id)
                    FROM
                        attendance AS attendance
                    {$joinClauses}
                    " . (empty($whereClauses) ? "" : "WHERE " . implode(" AND ", $whereClauses)) . "
                ";

                $countStatement = $this->pdo->prepare($totalRowCountQuery);

                foreach ($queryParameters as $index => $parameter) {
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
            error_log("Database Error: An error occurred while fetching the attendance records. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(Attendance $attendance, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE attendance
            SET
                check_in_time                   = :check_in_time                  ,
                check_out_time                  = :check_out_time                 ,
                total_break_duration_in_minutes = :total_break_duration_in_minutes,
                total_hours_worked              = :total_hours_worked             ,
                late_check_in                   = :late_check_in                  ,
                early_check_out                 = :early_check_out                ,
                overtime_hours                  = :overtime_hours                 ,
                is_overtime_approved            = :is_overtime_approved           ,
                attendance_status               = :attendance_status              ,
                remarks                         = :remarks
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :attendance_id";
        } else {
            $query .= " id = :attendance_id";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":check_in_time"                  , $attendance->getCheckInTime()                , Helper::getPdoParameterType($attendance->getCheckInTime()                ));
            $statement->bindValue(":check_out_time"                 , $attendance->getCheckOutTime()               , Helper::getPdoParameterType($attendance->getCheckOutTime()               ));
            $statement->bindValue(":total_break_duration_in_minutes", $attendance->getTotalBreakDurationInMinutes(), Helper::getPdoParameterType($attendance->getTotalBreakDurationInMinutes()));
            $statement->bindValue(":total_hours_worked"             , $attendance->getTotalHoursWorked()           , Helper::getPdoParameterType($attendance->getTotalHoursWorked()           ));
            $statement->bindValue(":late_check_in"                  , $attendance->getLateCheckIn()                , Helper::getPdoParameterType($attendance->getLateCheckIn()                ));
            $statement->bindValue(":early_check_out"                , $attendance->getEarlyCheckOut()              , Helper::getPdoParameterType($attendance->getEarlyCheckOut()              ));
            $statement->bindValue(":overtime_hours"                 , $attendance->getOvertimeHours()              , Helper::getPdoParameterType($attendance->getOvertimeHours()              ));
            $statement->bindValue(":is_overtime_approved"           , $attendance->isOvertimeApproved()            , Helper::getPdoParameterType($attendance->isOvertimeApproved()            ));
            $statement->bindValue(":attendance_status"              , $attendance->getAttendanceStatus()           , Helper::getPdoParameterType($attendance->getAttendanceStatus()           ));
            $statement->bindValue(":remarks"                        , $attendance->getRemarks()                    , Helper::getPdoParameterType($attendance->getRemarks()                    ));

            $statement->bindValue(":attendance_id"                  , $attendance->getId()                         , Helper::getPdoParameterType($attendance->getId()                         ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while updating the attendance record. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function approveOvertime(int|string $attendanceId, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE attendance
            SET
                is_overtime_approved = 1
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :attendance_id";
        } else {
            $query .= " id = :attendance_id";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":attendance_id", $attendanceId, Helper::getPdoParameterType($attendanceId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while approving overtime. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function markAsProcessedForNextPayroll(int $attendanceId): ActionResult
    {
        $query = "
            UPDATE attendance
            SET
                is_processed_for_next_payroll = 1
            WHERE
                id = :attendance_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":attendance_id", $attendanceId, Helper::getPdoParameterType($attendanceId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while marking attendance as processed for the next payroll. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $attendanceId, bool $isHashedId = false): ActionResult
    {
        return $this->softDelete($attendanceId, $isHashedId);
    }

    private function softDelete(int|string $attendanceId, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE attendance
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :attendance_id";
        } else {
            $query .= " id = :attendance_id";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":attendance_id", $attendanceId, Helper::getPdoParameterType($attendanceId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while deleting the attendance record. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
