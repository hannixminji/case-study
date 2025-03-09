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

    public function create(Attendance $attendance): ActionResult
    {
        $query = "
            INSERT INTO attendance (
                work_schedule_snapshot_id      ,
                date                           ,
                check_in_time                  ,
                check_out_time                 ,
                total_break_duration_in_minutes,
                total_hours_worked             ,
                late_check_in                  ,
                early_check_out                ,
                overtime_hours                 ,
                is_overtime_approved           ,
                attendance_status              ,
                remarks
            )
            VALUES (
                :work_schedule_snapshot_id      ,
                :date                           ,
                :check_in_time                  ,
                :check_out_time                 ,
                :total_break_duration_in_minutes,
                :total_hours_worked             ,
                :late_check_in                  ,
                :early_check_out                ,
                :overtime_hours                 ,
                :is_overtime_approved           ,
                :attendance_status              ,
                :remarks
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":work_schedule_snapshot_id"      , $attendance->getWorkScheduleSnapshotId()     , Helper::getPdoParameterType($attendance->getWorkScheduleSnapshotId()     ));
            $statement->bindValue(":date"                           , $attendance->getDate()                       , Helper::getPdoParameterType($attendance->getDate()                       ));
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

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the attendance record. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function checkIn(Attendance $attendance): ActionResult
    {
        $query = "
            INSERT INTO attendance (
                work_schedule_snapshot_id,
                date                     ,
                check_in_time            ,
                late_check_in            ,
                attendance_status
            )
            VALUES (
                :work_schedule_snapshot_id,
                :date                     ,
                :check_in_time            ,
                :late_check_in            ,
                :attendance_status
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":work_schedule_snapshot_id", $attendance->getWorkScheduleSnapshotId(), Helper::getPdoParameterType($attendance->getWorkScheduleSnapshotId()));
            $statement->bindValue(":date"                     , $attendance->getDate()                  , Helper::getPdoParameterType($attendance->getDate()                  ));
            $statement->bindValue(":check_in_time"            , $attendance->getCheckInTime()           , Helper::getPdoParameterType($attendance->getCheckInTime()           ));
            $statement->bindValue(":late_check_in"            , $attendance->getLateCheckIn()           , Helper::getPdoParameterType($attendance->getLateCheckIn()           ));
            $statement->bindValue(":attendance_status"        , $attendance->getAttendanceStatus()      , Helper::getPdoParameterType($attendance->getAttendanceStatus()      ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

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

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":check_out_time"                 , $attendance->getCheckOutTime()               , Helper::getPdoParameterType($attendance->getCheckOutTime()               ));
            $statement->bindValue(":total_break_duration_in_minutes", $attendance->getTotalBreakDurationInMinutes(), Helper::getPdoParameterType($attendance->getTotalBreakDurationInMinutes()));
            $statement->bindValue(":total_hours_worked"             , $attendance->getTotalHoursWorked()           , Helper::getPdoParameterType($attendance->getTotalHoursWorked()           ));
            $statement->bindValue(":early_check_out"                , $attendance->getEarlyCheckOut()              , Helper::getPdoParameterType($attendance->getEarlyCheckOut()              ));
            $statement->bindValue(":overtime_hours"                 , $attendance->getOvertimeHours()              , Helper::getPdoParameterType($attendance->getOvertimeHours()              ));
            $statement->bindValue(":attendance_status"              , $attendance->getAttendanceStatus()           , Helper::getPdoParameterType($attendance->getAttendanceStatus()           ));

            $statement->bindValue(":attendance_id"                  , $attendance->getId()                         , Helper::getPdoParameterType($attendance->getId()                         ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

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
    ): array|ActionResult {

        $tableColumns = [
            "id"                                                       => "attendance.id                                            AS id"                                                      ,
            "work_schedule_snapshot_id"                                => "attendance.work_schedule_snapshot_id                     AS work_schedule_snapshot_id"                               ,
            "date"                                                     => "attendance.date                                          AS date"                                                    ,
            "check_in_time"                                            => "attendance.check_in_time                                 AS check_in_time"                                           ,
            "check_out_time"                                           => "attendance.check_out_time                                AS check_out_time"                                          ,
            "total_break_duration_in_minutes"                          => "attendance.total_break_duration_in_minutes               AS total_break_duration_in_minutes"                         ,
            "total_hours_worked"                                       => "attendance.total_hours_worked                            AS total_hours_worked"                                      ,
            "late_check_in"                                            => "attendance.late_check_in                                 AS late_check_in"                                           ,
            "early_check_out"                                          => "attendance.early_check_out                               AS early_check_out"                                         ,
            "overtime_hours"                                           => "attendance.overtime_hours                                AS overtime_hours"                                          ,
            "is_overtime_approved"                                     => "attendance.is_overtime_approved                          AS is_overtime_approved"                                    ,
            "attendance_status"                                        => "attendance.attendance_status                             AS attendance_status"                                       ,
            "remarks"                                                  => "attendance.remarks                                       AS remarks"                                                 ,
            "is_processed_for_next_payroll"                            => "attendance.is_processed_for_next_payroll                 AS is_processed_for_next_payroll"                           ,
            "created_at"                                               => "attendance.created_at                                    AS created_at"                                              ,
            "updated_at"                                               => "attendance.updated_at                                    AS updated_at"                                              ,
            "deleted_at"                                               => "attendance.deleted_at                                    AS deleted_at"                                              ,

            "day_of_the_week"                                          => "DAYOFWEEK(attendance.date)                               AS day_of_the_week"                                         ,

            "work_schedule_snapshot_work_schedule_id"                  => "work_schedule_snapshot.work_schedule_id                  AS work_schedule_snapshot_work_schedule_id"                 ,
            "work_schedule_snapshot_employee_id"                       => "work_schedule_snapshot.employee_id                       AS work_schedule_snapshot_employee_id"                      ,
            "work_schedule_snapshot_start_time"                        => "work_schedule_snapshot.start_time                        AS work_schedule_snapshot_start_time"                       ,
            "work_schedule_snapshot_end_time"                          => "work_schedule_snapshot.end_time                          AS work_schedule_snapshot_end_time"                         ,
            "work_schedule_snapshot_is_flextime"                       => "work_schedule_snapshot.is_flextime                       AS work_schedule_snapshot_is_flextime"                      ,
            "work_schedule_snapshot_total_hours_per_week"              => "work_schedule_snapshot.total_hours_per_week              AS work_schedule_snapshot_total_hours_per_week"             ,
            "work_schedule_snapshot_total_work_hours"                  => "work_schedule_snapshot.total_work_hours                  AS work_schedule_snapshot_total_work_hours"                 ,
            "work_schedule_snapshot_start_date"                        => "work_schedule_snapshot.start_date                        AS work_schedule_snapshot_start_date"                       ,
            "work_schedule_snapshot_recurrence_rule"                   => "work_schedule_snapshot.recurrence_rule                   AS work_schedule_snapshot_recurrence_rule"                  ,
            "work_schedule_snapshot_grace_period"                      => "work_schedule_snapshot.grace_period                      AS work_schedule_snapshot_grace_period"                     ,
            "work_schedule_snapshot_minutes_can_check_in_before_shift" => "work_schedule_snapshot.minutes_can_check_in_before_shift AS work_schedule_snapshot_minutes_can_check_in_before_shift",
            "work_schedule_snapshot_active_at"                         => "work_schedule_snapshot.active_at                         AS work_schedule_snapshot_active_at"                        ,

            "employee_full_name"                                       => "employee.full_name                                       AS employee_full_name"                                      ,
            "employee_profile_picture"                                 => "employee.profile_picture                                 AS employee_profile_picture"                                ,
            "employee_code"                                            => "employee.employee_code                                   AS employee_code"                                           ,
            "employee_job_title_id"                                    => "employee.job_title_id                                    AS employee_job_title_id"                                   ,
            "employee_department_id"                                   => "employee.department_id                                   AS employee_department_id"                                  ,
            "employee_supervisor_id"                                   => "employee.supervisor_id                                   AS employee_supervisor_id"                                  ,
            "employee_deleted_at"                                      => "employee.deleted_at                                      AS employee_deleted_at"                                     ,

            "job_title"                                                => "job_title.title                                          AS job_title"                                               ,

            "department_name"                                          => "department.name                                          AS department_name"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns
                ));

        $joinClauses = "";

        if (array_key_exists("work_schedule_snapshot_work_schedule_id"                 , $selectedColumns) ||
            array_key_exists("work_schedule_snapshot_employee_id"                      , $selectedColumns) ||
            array_key_exists("work_schedule_snapshot_start_time"                       , $selectedColumns) ||
            array_key_exists("work_schedule_snapshot_end_time"                         , $selectedColumns) ||
            array_key_exists("work_schedule_snapshot_is_flextime"                      , $selectedColumns) ||
            array_key_exists("work_schedule_snapshot_total_hours_per_week"             , $selectedColumns) ||
            array_key_exists("work_schedule_snapshot_total_work_hours"                 , $selectedColumns) ||
            array_key_exists("work_schedule_snapshot_start_date"                       , $selectedColumns) ||
            array_key_exists("work_schedule_snapshot_recurrence_rule"                  , $selectedColumns) ||
            array_key_exists("work_schedule_snapshot_grace_period"                     , $selectedColumns) ||
            array_key_exists("work_schedule_snapshot_minutes_can_check_in_before_shift", $selectedColumns) ||
            array_key_exists("work_schedule_snapshot_active_at"                        , $selectedColumns) ||

            array_key_exists("employee_full_name"                                      , $selectedColumns) ||
            array_key_exists("employee_profile_picture"                                , $selectedColumns) ||
            array_key_exists("employee_code"                                           , $selectedColumns) ||
            array_key_exists("employee_job_title_id"                                   , $selectedColumns) ||
            array_key_exists("employee_department_id"                                  , $selectedColumns) ||
            array_key_exists("employee_supervisor_id"                                  , $selectedColumns) ||
            array_key_exists("employee_deleted_at"                                     , $selectedColumns) ||

            array_key_exists("job_title"                                               , $selectedColumns) ||

            array_key_exists("department_name"                                         , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    work_schedule_snapshots AS work_schedule_snapshot
                ON
                    attendance.work_schedule_snapshot_id = work_schedule_snapshot.id
            ";
        }

        if (array_key_exists("employee_full_name"      , $selectedColumns) ||
            array_key_exists("employee_profile_picture", $selectedColumns) ||
            array_key_exists("employee_code"           , $selectedColumns) ||
            array_key_exists("employee_job_title_id"   , $selectedColumns) ||
            array_key_exists("employee_department_id"  , $selectedColumns) ||
            array_key_exists("employee_supervisor_id"  , $selectedColumns) ||
            array_key_exists("employee_deleted_at"     , $selectedColumns) ||

            array_key_exists("job_title"               , $selectedColumns) ||

            array_key_exists("department_name"         , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    employees AS employee
                ON
                    work_schedule_snapshot.employee_id = employee.id
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

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "attendance.deleted_at is NULL";

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
                    case "<="  :
                    case ">="  :
                    case "LIKE":
                        $whereClauses    [] = "{$column} {$operator} ?";
                        $queryParameters [] = $filterCriterion["value"];

                        $filterParameters[] = $filterCriterion["value"];

                        break;

                    case "IS NULL"    :
                    case "IS NOT NULL":
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
                attendance
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
                        COUNT(attendance.id)
                    FROM
                        attendance AS attendance
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
            error_log("Database Error: An error occurred while fetching the attendance records. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function fetchEmployeeLastAttendanceRecord(int $employeeId, string $currentDateTime): array|ActionResult
    {
        $query = "
            SELECT
                attendance.id                                            AS id                                                      ,
                attendance.work_schedule_snapshot_id                     AS work_schedule_snapshot_id                               ,
                attendance.date                                          AS date                                                    ,
                attendance.check_in_time                                 AS check_in_time                                           ,
                attendance.check_out_time                                AS check_out_time                                          ,
                attendance.total_break_duration_in_minutes               AS total_break_duration_in_minutes                         ,
                attendance.total_hours_worked                            AS total_hours_worked                                      ,
                attendance.late_check_in                                 AS late_check_in                                           ,
                attendance.early_check_out                               AS early_check_out                                         ,
                attendance.overtime_hours                                AS overtime_hours                                          ,
                attendance.is_overtime_approved                          AS is_overtime_approved                                    ,
                attendance.attendance_status                             AS attendance_status                                       ,
                attendance.remarks                                       AS remarks                                                 ,

                work_schedule_snapshot.work_schedule_id                  AS work_schedule_snapshot_work_schedule_id                 ,
                work_schedule_snapshot.start_time                        AS work_schedule_snapshot_start_time                       ,
                work_schedule_snapshot.end_time                          AS work_schedule_snapshot_end_time                         ,
                work_schedule_snapshot.is_flextime                       AS work_schedule_snapshot_is_flextime                      ,
                work_schedule_snapshot.total_hours_per_week              AS work_schedule_snapshot_total_hours_per_week             ,
                work_schedule_snapshot.total_work_hours                  AS work_schedule_snapshot_total_work_hours                 ,
                work_schedule_snapshot.start_date                        AS work_schedule_snapshot_start_date                       ,
                work_schedule_snapshot.recurrence_rule                   AS work_schedule_snapshot_recurrence_rule                  ,
                work_schedule_snapshot.grace_period                      AS work_schedule_snapshot_grace_period                     ,
                work_schedule_snapshot.minutes_can_check_in_before_shift AS work_schedule_snapshot_minutes_can_check_in_before_shift
            FROM
                attendance
            LEFT JOIN
                work_schedule_snapshots AS work_schedule_snapshot
            ON
                attendance.work_schedule_snapshot_id = work_schedule_snapshot.id
            WHERE
                attendance.deleted_at IS NULL
            AND
                work_schedule_snapshot.employee_id = :employee_id
            AND (
                (attendance.check_in_time <= :current_date_time AND attendance.check_out_time <= :current_date_time)
                OR
                (attendance.check_in_time <= :current_date_time AND attendance.check_out_time IS NULL)
            )
            ORDER BY
                attendance.date          DESC,
                attendance.check_in_time DESC
            LIMIT 1
        ";

        try {
            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"      , $employeeId     , Helper::getPdoParameterType($employeeId     ));
            $statement->bindValue(":current_date_time", $currentDateTime, Helper::getPdoParameterType($currentDateTime));

            $statement->execute();

            return $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching the employee last attendance record. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(Attendance $attendance): ActionResult
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

        if (preg_match("/^[1-9]\d*$/", $attendance->getId())) {
            $query .= "id = :attendance_id";
        } else {
            $query .= "SHA2(id, 256) = :attendance_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

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

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while updating the attendance record. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function updateStatusByDate(string $status, string $date): ActionResult
    {
        $query = "
            UPDATE attendance
            SET
                attendance_status = :attendance_status
            WHERE
                deleted_at IS NULL
            AND
                date = :date
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":attendance_status", $status, Helper::getPdoParameterType($status));

            $statement->bindValue(":date"             , $date  , Helper::getPdoParameterType($date  ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while updating the attendance status for all records. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function approveOvertime(int|string $attendanceId): ActionResult
    {
        $query = "
            UPDATE attendance
            SET
                is_overtime_approved = 1
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $attendanceId)) {
            $query .= "id = :attendance_id";
        } else {
            $query .= "SHA2(id, 256) = :attendance_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":attendance_id", $attendanceId, Helper::getPdoParameterType($attendanceId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

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

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":attendance_id", $attendanceId, Helper::getPdoParameterType($attendanceId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while marking attendance as processed for the next payroll. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $attendanceId): ActionResult
    {
        return $this->softDelete($attendanceId);
    }

    private function softDelete(int|string $attendanceId): ActionResult
    {
        $query = "
            UPDATE attendance
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $attendanceId)) {
            $query .= "id = :attendance_id";
        } else {
            $query .= "SHA2(id, 256) = :attendance_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":attendance_id", $attendanceId, Helper::getPdoParameterType($attendanceId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while deleting the attendance record. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
