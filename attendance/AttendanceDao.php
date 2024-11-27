<?php

require_once __DIR__ . '/../includes/Helper.php'            ;
require_once __DIR__ . '/../includes/enums/ActionResult.php';
require_once __DIR__ . '/../includes/enums/ErrorCode.php'   ;

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
                work_schedule_id ,
                date             ,
                check_in_time    ,
                late_check_in    ,
                attendance_status
            )
            VALUES (
                :work_schedule_id ,
                :date             ,
                :check_in_time    ,
                :late_check_in    ,
                :attendance_status,
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":work_schedule_id" , $attendance->getWorkScheduleId()  , Helper::getPdoParameterType($attendance->getWorkScheduleId()  ));
            $statement->bindValue(":date"             , $attendance->getDate()            , Helper::getPdoParameterType($attendance->getDate()            ));
            $statement->bindValue(":check_in_time"    , $attendance->getCheckInTime()     , Helper::getPdoParameterType($attendance->getCheckInTime()     ));
            $statement->bindValue(":late_check_in"    , $attendance->getLateCheckIn()     , Helper::getPdoParameterType($attendance->getLateCheckIn()     ));
            $statement->bindValue(":attendance_status", $attendance->getAttendanceStatus(), Helper::getPdoParameterType($attendance->getAttendanceStatus()));

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
        ?array $columns        = null,
        ?array $filterCriteria = null,
        ?array $sortCriteria   = null,
        ?int   $limit          = null,
        ?int   $offset         = null
    ): ActionResult|array {
        $tableColumns = [
            "id"                                => "attendance.id                              AS id"                               ,

            "work_schedule_id"                  => "attendance.work_schedule_id                AS work_schedule_id"                 ,
            "work_schedule_start_time"          => "",
            "work_schedule_end_time"            => "",
            "work_schedule_is_flexible"         => "work_schedule.is_flexible                  AS work_schedule_is_flexible"        ,
            "work_schedule_flexible_start_time" => "work_schedule.flexible_start_time          AS work_schedule_flexible_start_time",
            "work_schedule_flexible_end_time"   => "work_schedule.flexible_end_time            AS work_schedule_flexible_end_time"  ,

            "employee_id"                       => "work_schedule.employee_id                  AS employee_id"                      ,
            "employee_code"                     => "employee.code                              AS employee_code"                    ,
            "employee_full_name"                => "employee.full_name                         AS employee_full_name"               ,
            "employee_supervisor_id"            => "employee_supervisor_id                     AS employee_supervisor_id"           ,
            "employee_manager_id"               => "employee.manager_id                        AS employee_manager_id"              ,
            "department_id"                     => "department.id                              AS department_id"                    ,
            "department_name"                   => "department.name                            AS department_name"                  ,
            "job_title_id"                      => "job_title.id                               AS job_title_id"                     ,
            "job_title"                         => "job_title.title                            AS job_title"                        ,

            "date"                              => "attendance.date                            AS date"                             ,
            "day_of_the_week"                   => "DAYOFWEEK(attendance.date)                 AS day_of_the_week"                  ,
            "check_in_time"                     => "attendance.check_in_time                   AS check_in_time"                    ,
            "check_out_time"                    => "attendance.check_out_time                  AS check_out_time"                   ,
            "total_break_duration_in_minutes"   => "attendance.total_break_duration_in_minutes AS total_break_duration_in_minutes"  ,
            "total_hours_worked"                => "attendance.total_hours_worked              AS total_hours_worked"               ,
            "late_check_in"                     => "attendance.late_check_in                   AS late_check_in"                    ,
            "early_check_out"                   => "attendance.early_check_out                 AS early_check_out"                  ,
            "overtime_hours"                    => "attendance.overtime_hours                  AS overtime_hours"                   ,
            "is_overtime_approved"              => "attendance.is_overtime_approved            AS is_overtime_approved"             ,
            "attendance_status"                 => "attendance.attendance_status               AS attendance_status"                ,
            "remarks"                           => "attendance.remarks                         AS remarks"                          ,
            "created_at"                        => "attendance.created_at                      AS created_at"                       ,
            "updated_at"                        => "attendance.updated_at                      AS updated_at"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns
                ));

        $joinClauses = "";

        if (array_key_exists("work_schedule_is_flexible", $selectedColumns) ||

            array_key_exists("employee_id"              , $selectedColumns) ||
            array_key_exists("employee_code"            , $selectedColumns) ||
            array_key_exists("employee_full_name"       , $selectedColumns) ||
            array_key_exists("employee_supervisor_id"   , $selectedColumns) ||
            array_key_exists("employee_manager_id"      , $selectedColumns) ||

            array_key_exists("department_id"            , $selectedColumns) ||
            array_key_exists("department_name"          , $selectedColumns) ||

            array_key_exists("job_title_id"             , $selectedColumns) ||
            array_key_exists("job_title"                , $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    work_schedules AS work_schedule
                ON
                    attendance.work_schedule_id = work_schedule.id
            ";
        }


        if (array_key_exists("employee_code"     , $selectedColumns) ||
            array_key_exists("employee_full_name", $selectedColumns) ||

            array_key_exists("department_id"     , $selectedColumns) ||
            array_key_exists("department_name"   , $selectedColumns) ||

            array_key_exists("job_title_id"      , $selectedColumns) ||
            array_key_exists("job_title"         , $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    employees AS employee
                ON
                    work_schedule.employee_id = employee.id
            ";
        }

        if (array_key_exists("department_id"  , $selectedColumns) ||
            array_key_exists("department_name", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    departments AS department
                ON
                    employee.department_id = department.id
            ";
        }

        if (array_key_exists("job_title_id", $selectedColumns) ||
            array_key_exists("job_title"   , $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    job_titles AS job_title
                ON
                    employee.job_title_id = job_title.id
            ";
        }

        $queryParameters = [];

        $whereClauses = [];

        if ( ! empty($filterCriteria)) {
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
                attendances AS attendance
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
            error_log("Database Error: An error occurred while fetching the attendance records. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(Attendance $attendance): ActionResult
    {
        $query = "
            UPDATE attendance
            SET
                check_in_time      = :check_in_time     ,
                check_out_time     = :check_out_time    ,
                total_hours_worked = :total_hours_worked,
                late_check_in      = :late_check_in     ,
                early_check_out    = :early_check_out   ,
                overtime_hours     = :overtime_hours    ,
                attendance_status  = :attendance_status ,
                remarks            = :remarks
            WHERE
                id = :attendance_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":check_in_time"     , $attendance->getCheckInTime()     , Helper::getPdoParameterType($attendance->getCheckInTime()     ));
            $statement->bindValue(":check_out_time"    , $attendance->getCheckOutTime()    , Helper::getPdoParameterType($attendance->getCheckOutTime()    ));
            $statement->bindValue(":total_hours_worked", $attendance->getTotalHoursWorked(), Helper::getPdoParameterType($attendance->getTotalHoursWorked()));
            $statement->bindValue(":late_check_in"     , $attendance->getLateCheckIn()     , Helper::getPdoParameterType($attendance->getLateCheckIn()     ));
            $statement->bindValue(":early_check_out"   , $attendance->getEarlyCheckOut()   , Helper::getPdoParameterType($attendance->getEarlyCheckOut()   ));
            $statement->bindValue(":overtime_hours"    , $attendance->getOvertimeHours()   , Helper::getPdoParameterType($attendance->getOvertimeHours()   ));
            $statement->bindValue(":attendance_status" , $attendance->getAttendanceStatus(), Helper::getPdoParameterType($attendance->getAttendanceStatus()));
            $statement->bindValue(":remarks"           , $attendance->getRemarks()         , Helper::getPdoParameterType($attendance->getRemarks()         ));
            $statement->bindValue(":attendance_id"     , $attendance->getId()              , Helper::getPdoParameterType($attendance->getId()              ));

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

    public function approveOvertime(int $attendanceId): ActionResult
    {
        $query = "
            UPDATE attendance
            SET
                is_overtime_approved = 1
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

            error_log("Database Error: An error occurred while approving overtime. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
