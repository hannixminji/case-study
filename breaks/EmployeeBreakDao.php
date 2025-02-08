<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

class EmployeeBreakDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(EmployeeBreak $employeeBreak): ActionResult
    {
        $query = "
            INSERT INTO employee_breaks (
                attendance_id            ,
                break_schedule_history_id,
                start_time               ,
                end_time                 ,
                break_duration_in_minutes,
                created_at
            )
            VALUES (
                :attendance_id            ,
                :break_schedule_history_id,
                :start_time               ,
                :end_time                 ,
                :break_duration_in_minutes,
                :created_at
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":attendance_id"            , $employeeBreak->getAttendanceId()          , Helper::getPdoParameterType($employeeBreak->getAttendanceId()          ));
            $statement->bindValue(":break_schedule_history_id", $employeeBreak->getBreakScheduleHistoryId(), Helper::getPdoParameterType($employeeBreak->getBreakScheduleHistoryId()));
            $statement->bindValue(":start_time"               , $employeeBreak->getStartTime()             , Helper::getPdoParameterType($employeeBreak->getStartTime()             ));
            $statement->bindValue(":end_time"                 , $employeeBreak->getEndTime()               , Helper::getPdoParameterType($employeeBreak->getEndTime()               ));
            $statement->bindValue(":break_duration_in_minutes", $employeeBreak->getBreakDurationInMinutes(), Helper::getPdoParameterType($employeeBreak->getBreakDurationInMinutes()));
            $statement->bindValue(":created_at"               , $employeeBreak->getCreatedAt()             , Helper::getPdoParameterType($employeeBreak->getCreatedAt()             ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the employee break. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function breakIn(EmployeeBreak $employeeBreak): ActionResult
    {
        $query = "
            INSERT INTO employee_breaks (
                attendance_id            ,
                break_schedule_history_id,
                start_time               ,
                created_at
            )
            VALUES (
                :attendance_id            ,
                :break_schedule_history_id,
                :start_time               ,
                :created_at
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":attendance_id"            , $employeeBreak->getAttendanceId()          , Helper::getPdoParameterType($employeeBreak->getAttendanceId()          ));
            $statement->bindValue(":break_schedule_history_id", $employeeBreak->getBreakScheduleHistoryId(), Helper::getPdoParameterType($employeeBreak->getBreakScheduleHistoryId()));
            $statement->bindValue(":start_time"               , $employeeBreak->getStartTime()             , Helper::getPdoParameterType($employeeBreak->getStartTime()             ));
            $statement->bindValue(":created_at"               , $employeeBreak->getCreatedAt()             , Helper::getPdoParameterType($employeeBreak->getCreatedAt()             ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while recording the break in. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function breakOut(EmployeeBreak $employeeBreak): ActionResult
    {
        $query = "
            UPDATE employee_breaks
            SET
                end_time                  = :end_time                 ,
                break_duration_in_minutes = :break_duration_in_minutes
            WHERE
                id = :employee_break_id
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":end_time"                 , $employeeBreak->getEndTime()               , Helper::getPdoParameterType($employeeBreak->getEndTime()               ));
            $statement->bindValue(":break_duration_in_minutes", $employeeBreak->getBreakDurationInMinutes(), Helper::getPdoParameterType($employeeBreak->getBreakDurationInMinutes()));

            $statement->bindValue(":employee_break_id"        , $employeeBreak->getId()                    , Helper::getPdoParameterType($employeeBreak->getId()                    ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while recording the break out. " .
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
            "id"                                                      => "employee_break.id                                       AS id"                                                     ,
            "attendance_id"                                           => "employee_break.attendance_id                            AS attendance_id"                                          ,
            "break_schedule_history_id"                               => "employee_break.break_schedule_history_id                AS break_schedule_history_id"                              ,
            "start_time"                                              => "employee_break.start_time                               AS start_time"                                             ,
            "end_time"                                                => "employee_break.end_time                                 AS end_time"                                               ,
            "break_duration_in_minutes"                               => "employee_break.break_duration_in_minutes                AS break_duration_in_minutes"                              ,
            "created_at"                                              => "employee_break.created_at                               AS created_at"                                             ,
            "updated_at"                                              => "employee_break.updated_at                               AS updated_at"                                             ,
            "deleted_at"                                              => "employee_break.deleted_at                               AS deleted_at"                                             ,

            "total_break_duration_in_minutes"                         => "SUM(employee_break.break_duration_in_minutes)           AS total_break_duration_in_minutes"                        ,

            "attendance_date"                                         => "attendance.date                                         AS attendance_date"                                        ,
            "attendance_check_in_time"                                => "attendance.check_in_time                                AS attendance_check_in_time"                               ,
            "attendance_check_out_time"                               => "attendance.check_out_time                               AS attendance_check_out_time"                              ,

            "break_schedule_history_break_schedule_id"                => "break_schedule_history.break_schedule_id                AS break_schedule_history_break_schedule_id"               ,
            "break_schedule_history_work_schedule_history_id"         => "break_schedule_history.work_schedule_history_id         AS break_schedule_history_work_schedule_history_id"        ,
            "break_schedule_history_break_type_history_id"            => "break_schedule_history.break_type_history_id            AS break_schedule_history_break_type_history_id"           ,
            "break_schedule_history_start_time"                       => "break_schedule_history.start_time                       AS break_schedule_history_start_time"                      ,
            "break_schedule_history_end_time"                         => "break_schedule_history.end_time                         AS break_schedule_history_end_time"                        ,
            "break_schedule_history_is_flexible"                      => "break_schedule_history.is_flexible                      AS break_schedule_history_is_flexible"                     ,
            "break_schedule_history_earliest_start_time"              => "break_schedule_history.earliest_start_time              AS break_schedule_history_earliest_start_time"             ,
            "break_schedule_history_latest_end_time"                  => "break_schedule_history.latest_end_time                  AS break_schedule_history_latest_end_time"                 ,
            "break_schedule_history_active_at"                        => "break_schedule_history.active_at                        AS break_schedule_history_active_at"                       ,

            "break_schedule_id"                                       => "break_schedule.id                                       AS break_schedule_id"                                      ,
            "break_schedule_work_schedule_id"                         => "break_schedule.work_schedule_id                         AS break_schedule_work_schedule_id"                        ,
            "break_schedule_break_type_id"                            => "break_schedule.break_type_id                            AS break_schedule_break_type_id"                           ,
            "break_schedule_start_time"                               => "break_schedule.start_time                               AS break_schedule_start_time"                              ,
            "break_schedule_end_time"                                 => "break_schedule.end_time                                 AS break_schedule_end_time"                                ,
            "break_schedule_is_flexible"                              => "break_schedule.is_flexible                              AS break_schedule_is_flexible"                             ,
            "break_schedule_earliest_start_time"                      => "break_schedule.earliest_start_time                      AS break_schedule_earliest_start_time"                     ,
            "break_schedule_latest_end_time"                          => "break_schedule.latest_end_time                          AS break_schedule_latest_end_time"                         ,
            "break_schedule_created_at"                               => "break_schedule.created_at                               AS break_schedule_created_at"                              ,
            "break_schedule_updated_at"                               => "break_schedule.updated_at                               AS break_schedule_updated_at"                              ,
            "break_schedule_deleted_at"                               => "break_schedule.deleted_at                               AS break_schedule_deleted_at"                              ,

            "work_schedule_history_id"                                => "work_schedule_history.id                                AS work_schedule_history_id"                               ,
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

            "work_schedule_id"                                        => "work_schedule.id                                       AS work_schedule_id"                                        ,
            "work_schedule_employee_id"                               => "work_schedule.employee_id                              AS work_schedule_employee_id"                               ,
            "work_schedule_start_time"                                => "work_schedule.start_time                               AS work_schedule_start_time"                                ,
            "work_schedule_end_time"                                  => "work_schedule.end_time                                 AS work_schedule_end_time"                                  ,
            "work_schedule_is_flextime"                               => "work_schedule.is_flextime                              AS work_schedule_is_flextime"                               ,
            "work_schedule_total_hours_per_week"                      => "work_schedule.total_hours_per_week                     AS work_schedule_total_hours_per_week"                      ,
            "work_schedule_total_work_hours"                          => "work_schedule.total_work_hours                         AS work_schedule_total_work_hours"                          ,
            "work_schedule_start_date"                                => "work_schedule.start_date                               AS work_schedule_start_date"                                ,
            "work_schedule_recurrence_rule"                           => "work_schedule.recurrence_rule                          AS work_schedule_recurrence_rule"                           ,
            "work_schedule_created_at"                                => "work_schedule.created_at                               AS work_schedule_created_at"                                ,
            "work_schedule_updated_at"                                => "work_schedule.updated_at                               AS work_schedule_updated_at"                                ,
            "work_schedule_deleted_at"                                => "work_schedule.deleted_at                               AS work_schedule_deleted_at"                                ,

            "break_type_history_id"                                   => "break_type_history.id                                   AS break_type_history_id"                                  ,
            "break_type_history_break_type_id"                        => "break_type_history.break_type_id                        AS break_type_history_break_type_id"                       ,
            "break_type_history_name"                                 => "break_type_history.name                                 AS break_type_history_name"                                ,
            "break_type_history_duration_in_minutes"                  => "break_type_history.duration_in_minutes                  AS break_type_history_duration_in_minutes"                 ,
            "break_type_history_is_paid"                              => "break_type_history.is_paid                              AS break_type_history_is_paid"                             ,
            "break_type_history_is_require_break_in_out"              => "break_type_history.is_require_break_in_and_break_out    AS break_type_history_is_require_break_in_out"             ,
            "break_type_history_active_at"                            => "break_type_history.active_at                            AS break_type_history_active_at"                           ,

            "break_type_id"                                           => "break_type.id                                          AS break_type_id"                                           ,
            "break_type_name"                                         => "break_type.name                                        AS break_type_name"                                         ,
            "break_type_duration_in_minutes"                          => "break_type.duration_in_minutes                         AS break_type_duration_in_minutes"                          ,
            "break_type_is_paid"                                      => "break_type.is_paid                                     AS break_type_is_paid"                                      ,
            "break_type_is_require_break_in_out"                      => "break_type.is_require_break_in_and_break_out           AS break_type_is_require_break_in_out"                      ,
            "break_type_created_at"                                   => "break_type.created_at                                  AS break_type_created_at"                                   ,
            "break_type_updated_at"                                   => "break_type.updated_at                                  AS break_type_updated_at"                                   ,
            "break_type_deleted_at"                                   => "break_type.deleted_at                                  AS break_type_deleted_at"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("attendance_date"          , $selectedColumns) ||
            array_key_exists("attendance_check_in_time" , $selectedColumns) ||
            array_key_exists("attendance_check_out_time", $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    attendance AS attendance
                ON
                    employee_break.attendance_id = attendance.id
            ";
        }

        if (array_key_exists("break_schedule_history_break_schedule_id"               , $selectedColumns) ||
            array_key_exists("break_schedule_history_work_schedule_history_id"        , $selectedColumns) ||
            array_key_exists("break_schedule_history_break_type_history_id"           , $selectedColumns) ||
            array_key_exists("break_schedule_history_start_time"                      , $selectedColumns) ||
            array_key_exists("break_schedule_history_end_time"                        , $selectedColumns) ||
            array_key_exists("break_schedule_history_is_flexible"                     , $selectedColumns) ||
            array_key_exists("break_schedule_history_earliest_start_time"             , $selectedColumns) ||
            array_key_exists("break_schedule_history_latest_end_time"                 , $selectedColumns) ||
            array_key_exists("break_schedule_history_active_at"                       , $selectedColumns) ||

            array_key_exists("break_schedule_id"                                      , $selectedColumns) ||
            array_key_exists("break_schedule_work_schedule_id"                        , $selectedColumns) ||
            array_key_exists("break_schedule_break_type_id"                           , $selectedColumns) ||
            array_key_exists("break_schedule_start_time"                              , $selectedColumns) ||
            array_key_exists("break_schedule_end_time"                                , $selectedColumns) ||
            array_key_exists("break_schedule_is_flexible"                             , $selectedColumns) ||
            array_key_exists("break_schedule_earliest_start_time"                     , $selectedColumns) ||
            array_key_exists("break_schedule_latest_end_time"                         , $selectedColumns) ||
            array_key_exists("break_schedule_created_at"                              , $selectedColumns) ||
            array_key_exists("break_schedule_updated_at"                              , $selectedColumns) ||
            array_key_exists("break_schedule_deleted_at"                              , $selectedColumns) ||

            array_key_exists("work_schedule_history_id"                               , $selectedColumns) ||
            array_key_exists("work_schedule_history_work_schedule_id"                 , $selectedColumns) ||
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

            array_key_exists("work_schedule_id"                                       , $selectedColumns) ||
            array_key_exists("work_schedule_employee_id"                              , $selectedColumns) ||
            array_key_exists("work_schedule_start_time"                               , $selectedColumns) ||
            array_key_exists("work_schedule_end_time"                                 , $selectedColumns) ||
            array_key_exists("work_schedule_is_flextime"                              , $selectedColumns) ||
            array_key_exists("work_schedule_total_hours_per_week"                     , $selectedColumns) ||
            array_key_exists("work_schedule_total_work_hours"                         , $selectedColumns) ||
            array_key_exists("work_schedule_start_date"                               , $selectedColumns) ||
            array_key_exists("work_schedule_recurrence_rule"                          , $selectedColumns) ||
            array_key_exists("work_schedule_created_at"                               , $selectedColumns) ||
            array_key_exists("work_schedule_updated_at"                               , $selectedColumns) ||
            array_key_exists("work_schedule_deleted_at"                               , $selectedColumns) ||

            array_key_exists("break_type_history_id"                                  , $selectedColumns) ||
            array_key_exists("break_type_history_break_type_id"                       , $selectedColumns) ||
            array_key_exists("break_type_history_name"                                , $selectedColumns) ||
            array_key_exists("break_type_history_duration_in_minutes"                 , $selectedColumns) ||
            array_key_exists("break_type_history_is_paid"                             , $selectedColumns) ||
            array_key_exists("break_type_history_is_require_break_in_out"             , $selectedColumns) ||
            array_key_exists("break_type_history_active_at"                           , $selectedColumns) ||

            array_key_exists("break_type_id"                                          , $selectedColumns) ||
            array_key_exists("break_type_name"                                        , $selectedColumns) ||
            array_key_exists("break_type_duration_in_minutes"                         , $selectedColumns) ||
            array_key_exists("break_type_is_paid"                                     , $selectedColumns) ||
            array_key_exists("break_type_is_require_break_in_out"                     , $selectedColumns) ||
            array_key_exists("break_type_created_at"                                  , $selectedColumns) ||
            array_key_exists("break_type_updated_at"                                  , $selectedColumns) ||
            array_key_exists("break_type_deleted_at"                                  , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    break_schedules_history AS break_schedule_history
                ON
                    employee_break.break_schedule_history_id = break_schedule_history.id
            ";
        }

        if (array_key_exists("break_schedule_id"                 , $selectedColumns) ||
            array_key_exists("break_schedule_work_schedule_id"   , $selectedColumns) ||
            array_key_exists("break_schedule_break_type_id"      , $selectedColumns) ||
            array_key_exists("break_schedule_start_time"         , $selectedColumns) ||
            array_key_exists("break_schedule_end_time"           , $selectedColumns) ||
            array_key_exists("break_schedule_is_flexible"        , $selectedColumns) ||
            array_key_exists("break_schedule_earliest_start_time", $selectedColumns) ||
            array_key_exists("break_schedule_latest_end_time"    , $selectedColumns) ||
            array_key_exists("break_schedule_created_at"         , $selectedColumns) ||
            array_key_exists("break_schedule_updated_at"         , $selectedColumns) ||
            array_key_exists("break_schedule_deleted_at"         , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    break_schedules AS break_schedule
                ON
                    break_schedule_history.break_schedule_id = break_schedule.id
            ";
        }

        if (array_key_exists("work_schedule_history_id"                               , $selectedColumns) ||
            array_key_exists("work_schedule_history_work_schedule_id"                 , $selectedColumns) ||
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

            array_key_exists("work_schedule_id"                                       , $selectedColumns) ||
            array_key_exists("work_schedule_employee_id"                              , $selectedColumns) ||
            array_key_exists("work_schedule_start_time"                               , $selectedColumns) ||
            array_key_exists("work_schedule_end_time"                                 , $selectedColumns) ||
            array_key_exists("work_schedule_is_flextime"                              , $selectedColumns) ||
            array_key_exists("work_schedule_total_hours_per_week"                     , $selectedColumns) ||
            array_key_exists("work_schedule_total_work_hours"                         , $selectedColumns) ||
            array_key_exists("work_schedule_start_date"                               , $selectedColumns) ||
            array_key_exists("work_schedule_recurrence_rule"                          , $selectedColumns) ||
            array_key_exists("work_schedule_created_at"                               , $selectedColumns) ||
            array_key_exists("work_schedule_updated_at"                               , $selectedColumns) ||
            array_key_exists("work_schedule_deleted_at"                               , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    work_schedules_history AS work_schedule_history
                ON
                    break_schedule_history.work_schedule_history_id = work_schedule_history.id
            ";
        }

        if (array_key_exists("work_schedule_id"                  , $selectedColumns) ||
            array_key_exists("work_schedule_employee_id"         , $selectedColumns) ||
            array_key_exists("work_schedule_start_time"          , $selectedColumns) ||
            array_key_exists("work_schedule_end_time"            , $selectedColumns) ||
            array_key_exists("work_schedule_is_flextime"         , $selectedColumns) ||
            array_key_exists("work_schedule_total_hours_per_week", $selectedColumns) ||
            array_key_exists("work_schedule_total_work_hours"    , $selectedColumns) ||
            array_key_exists("work_schedule_start_date"          , $selectedColumns) ||
            array_key_exists("work_schedule_recurrence_rule"     , $selectedColumns) ||
            array_key_exists("work_schedule_created_at"          , $selectedColumns) ||
            array_key_exists("work_schedule_updated_at"          , $selectedColumns) ||
            array_key_exists("work_schedule_deleted_at"          , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    work_schedules AS work_schedule
                ON
                    work_schedule_history.id = work_schedule.id
            ";
        }

        if (array_key_exists("break_type_history_id"                     , $selectedColumns) ||
            array_key_exists("break_type_history_break_type_id"          , $selectedColumns) ||
            array_key_exists("break_type_history_name"                   , $selectedColumns) ||
            array_key_exists("break_type_history_duration_in_minutes"    , $selectedColumns) ||
            array_key_exists("break_type_history_is_paid"                , $selectedColumns) ||
            array_key_exists("break_type_history_is_require_break_in_out", $selectedColumns) ||
            array_key_exists("break_type_history_active_at"              , $selectedColumns) ||

            array_key_exists("break_type_id"                             , $selectedColumns) ||
            array_key_exists("break_type_name"                           , $selectedColumns) ||
            array_key_exists("break_type_duration_in_minutes"            , $selectedColumns) ||
            array_key_exists("break_type_is_paid"                        , $selectedColumns) ||
            array_key_exists("break_type_is_require_break_in_out"        , $selectedColumns) ||
            array_key_exists("break_type_created_at"                     , $selectedColumns) ||
            array_key_exists("break_type_updated_at"                     , $selectedColumns) ||
            array_key_exists("break_type_deleted_at"                     , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    break_types_history AS break_type_history
                ON
                    break_schedule_history.break_type_history_id = break_type_history.id
            ";
        }

        if (array_key_exists("break_type_id"                     , $selectedColumns) ||
            array_key_exists("break_type_name"                   , $selectedColumns) ||
            array_key_exists("break_type_duration_in_minutes"    , $selectedColumns) ||
            array_key_exists("break_type_is_paid"                , $selectedColumns) ||
            array_key_exists("break_type_is_require_break_in_out", $selectedColumns) ||
            array_key_exists("break_type_created_at"             , $selectedColumns) ||
            array_key_exists("break_type_updated_at"             , $selectedColumns) ||
            array_key_exists("break_type_deleted_at"             , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    break_types AS break_type
                ON
                    break_type_history.id = break_type.id
            ";
        }

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "employee_break.deleted_at IS NULL";
        } else {
            foreach ($filterCriteria as $filterCriterion) {
                $column   = $filterCriterion["column"  ];
                $operator = $filterCriterion["operator"];

                switch ($operator) {
                    case "="   :
                    case "LIKE":
                        $whereClauses    [] = "{$column} {$operator} ?";
                        $queryParameters [] = $filterCriterion["value"];

                        $filterParameters[] = $filterCriterion["value"];

                        break;

                    case "IS NULL":
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
                employee_breaks AS employee_break
            {$joinClauses}
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

            $totalRowCount = null;

            if ($includeTotalRowCount) {
                $totalRowCountQuery = "
                    SELECT
                        COUNT(employee_break.id)
                    FROM
                        employee_breaks AS employee_break
                    {$joinClauses}
                    WHERE
                        " . implode(" AND ", $whereClauses) . "
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
            error_log("Database Error: An error occurred while fetching employee breaks. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(EmployeeBreak $employeeBreak, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE employee_breaks
            SET
                attendance_id             = :attendance_id            ,
                break_schedule_history_id = :break_schedule_history_id,
                start_time                = :start_time               ,
                end_time                  = :end_time                 ,
                break_duration_in_minutes = :break_duration_in_minutes,
                created_at                = :created_at
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :employee_break_id";
        } else {
            $query .= " id = :employee_break_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":attendance_id"            , $employeeBreak->getAttendanceId()          , Helper::getPdoParameterType($employeeBreak->getAttendanceId()          ));
            $statement->bindValue(":break_schedule_history_id", $employeeBreak->getBreakScheduleHistoryId(), Helper::getPdoParameterType($employeeBreak->getBreakScheduleHistoryId()));
            $statement->bindValue(":start_time"               , $employeeBreak->getStartTime()             , Helper::getPdoParameterType($employeeBreak->getStartTime()             ));
            $statement->bindValue(":end_time"                 , $employeeBreak->getEndTime()               , Helper::getPdoParameterType($employeeBreak->getEndTime()               ));
            $statement->bindValue(":break_duration_in_minutes", $employeeBreak->getBreakDurationInMinutes(), Helper::getPdoParameterType($employeeBreak->getBreakDurationInMinutes()));
            $statement->bindValue(":created_at"               , $employeeBreak->getCreatedAt()             , Helper::getPdoParameterType($employeeBreak->getCreatedAt()             ));

            $statement->bindValue(":employee_break_id"        , $employeeBreak->getId()                    , Helper::getPdoParameterType($employeeBreak->getId()                    ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while updating the employee break record. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $employeeBreakId, bool $isHashedId = false): ActionResult
    {
        return $this->softDelete($employeeBreakId, $isHashedId);
    }

    private function softDelete(int|string $employeeBreakId, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE employee_breaks
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :employee_break_id";
        } else {
            $query .= " id = :employee_break_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_break_id", $employeeBreakId, Helper::getPdoParameterType($employeeBreakId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while deleting the employee break. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
