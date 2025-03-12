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
                break_schedule_snapshot_id,
                start_time                ,
                end_time                  ,
                break_duration_in_minutes ,
                created_at
            )
            VALUES (
                :break_schedule_snapshot_id,
                :start_time                ,
                :end_time                  ,
                :break_duration_in_minutes ,
                :created_at
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":break_schedule_snapshot_id", $employeeBreak->getBreakScheduleSnapshotId(), Helper::getPdoParameterType($employeeBreak->getBreakScheduleSnapshotId()));
            $statement->bindValue(":start_time"                , $employeeBreak->getStartTime()              , Helper::getPdoParameterType($employeeBreak->getStartTime()              ));
            $statement->bindValue(":end_time"                  , $employeeBreak->getEndTime()                , Helper::getPdoParameterType($employeeBreak->getEndTime()                ));
            $statement->bindValue(":break_duration_in_minutes" , $employeeBreak->getBreakDurationInMinutes() , Helper::getPdoParameterType($employeeBreak->getBreakDurationInMinutes() ));
            $statement->bindValue(":created_at"                , $employeeBreak->getCreatedAt()              , Helper::getPdoParameterType($employeeBreak->getCreatedAt()              ));

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
                break_schedule_snapshot_id,
                start_time                ,
                created_at
            )
            VALUES (
                :break_schedule_snapshot_id,
                :start_time                ,
                :created_at
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":break_schedule_snapshot_id", $employeeBreak->getBreakScheduleSnapshotId(), Helper::getPdoParameterType($employeeBreak->getBreakScheduleSnapshotId()));
            $statement->bindValue(":start_time"                , $employeeBreak->getStartTime()              , Helper::getPdoParameterType($employeeBreak->getStartTime()              ));
            $statement->bindValue(":created_at"                , $employeeBreak->getCreatedAt()              , Helper::getPdoParameterType($employeeBreak->getCreatedAt()              ));

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
        ? array $groupByColumns       = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        $tableColumns = [
            "id"                                                       => "employee_break.id                                        AS id"                                                      ,
            "break_schedule_snapshot_id"                               => "employee_break.break_schedule_snapshot_id                AS break_schedule_snapshot_id"                              ,
            "start_time"                                               => "employee_break.start_time                                AS start_time"                                              ,
            "end_time"                                                 => "employee_break.end_time                                  AS end_time"                                                ,
            "break_duration_in_minutes"                                => "employee_break.break_duration_in_minutes                 AS break_duration_in_minutes"                               ,
            "created_at"                                               => "employee_break.created_at                                AS created_at"                                              ,
            "updated_at"                                               => "employee_break.updated_at                                AS updated_at"                                              ,
            "deleted_at"                                               => "employee_break.deleted_at                                AS deleted_at"                                              ,

            "break_schedule_snapshot_break_schedule_id"                => "break_schedule_snapshot.break_schedule_id                AS break_schedule_snapshot_break_schedule_id"               ,
            "break_schedule_snapshot_work_schedule_snapshot_id"        => "break_schedule_snapshot.work_schedule_snapshot_id        AS break_schedule_snapshot_work_schedule_snapshot_id"       ,
            "break_schedule_snapshot_break_type_snapshot_id"           => "break_schedule_snapshot.break_type_snapshot_id           AS break_schedule_snapshot_break_type_snapshot_id"          ,
            "break_schedule_snapshot_start_time"                       => "break_schedule_snapshot.start_time                       AS break_schedule_snapshot_start_time"                      ,
            "break_schedule_snapshot_end_time"                         => "break_schedule_snapshot.end_time                         AS break_schedule_snapshot_end_time"                        ,
            "break_schedule_snapshot_active_at"                        => "break_schedule_snapshot.active_at                        AS break_schedule_snapshot_active_at"                       ,

            "break_schedule_id"                                        => "break_schedule.id                                        AS break_schedule_id"                                       ,
            "break_schedule_work_schedule_id"                          => "break_schedule.work_schedule_id                          AS break_schedule_work_schedule_id"                         ,
            "break_schedule_break_type_id"                             => "break_schedule.break_type_id                             AS break_schedule_break_type_id"                            ,
            "break_schedule_start_time"                                => "break_schedule.start_time                                AS break_schedule_start_time"                               ,
            "break_schedule_end_time"                                  => "break_schedule.end_time                                  AS break_schedule_end_time"                                 ,
            "break_schedule_created_at"                                => "break_schedule.created_at                                AS break_schedule_created_at"                               ,
            "break_schedule_updated_at"                                => "break_schedule.updated_at                                AS break_schedule_updated_at"                               ,
            "break_schedule_deleted_at"                                => "break_schedule.deleted_at                                AS break_schedule_deleted_at"                               ,

            "work_schedule_snapshot_id"                                => "work_schedule_snapshot.id                                AS work_schedule_snapshot_id"                               ,
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

            "work_schedule_id"                                         => "work_schedule.id                                         AS work_schedule_id"                                        ,
            "work_schedule_employee_id"                                => "work_schedule.employee_id                                AS work_schedule_employee_id"                               ,
            "work_schedule_start_time"                                 => "work_schedule.start_time                                 AS work_schedule_start_time"                                ,
            "work_schedule_end_time"                                   => "work_schedule.end_time                                   AS work_schedule_end_time"                                  ,
            "work_schedule_is_flextime"                                => "work_schedule.is_flextime                                AS work_schedule_is_flextime"                               ,
            "work_schedule_total_hours_per_week"                       => "work_schedule.total_hours_per_week                       AS work_schedule_total_hours_per_week"                      ,
            "work_schedule_total_work_hours"                           => "work_schedule.total_work_hours                           AS work_schedule_total_work_hours"                          ,
            "work_schedule_start_date"                                 => "work_schedule.start_date                                 AS work_schedule_start_date"                                ,
            "work_schedule_recurrence_rule"                            => "work_schedule.recurrence_rule                            AS work_schedule_recurrence_rule"                           ,
            "work_schedule_created_at"                                 => "work_schedule.created_at                                 AS work_schedule_created_at"                                ,
            "work_schedule_updated_at"                                 => "work_schedule.updated_at                                 AS work_schedule_updated_at"                                ,
            "work_schedule_deleted_at"                                 => "work_schedule.deleted_at                                 AS work_schedule_deleted_at"                                ,

            "break_type_snapshot_id"                                   => "break_type_snapshot.id                                   AS break_type_snapshot_id"                                  ,
            "break_type_snapshot_break_type_id"                        => "break_type_snapshot.break_type_id                        AS break_type_snapshot_break_type_id"                       ,
            "break_type_snapshot_name"                                 => "break_type_snapshot.name                                 AS break_type_snapshot_name"                                ,
            "break_type_snapshot_duration_in_minutes"                  => "break_type_snapshot.duration_in_minutes                  AS break_type_snapshot_duration_in_minutes"                 ,
            "break_type_snapshot_is_paid"                              => "break_type_snapshot.is_paid                              AS break_type_snapshot_is_paid"                             ,
            "break_type_snapshot_is_require_break_in_out"              => "break_type_snapshot.is_require_break_in_and_break_out    AS break_type_snapshot_is_require_break_in_out"             ,
            "break_type_snapshot_active_at"                            => "break_type_snapshot.active_at                            AS break_type_snapshot_active_at"                           ,

            "break_type_id"                                            => "break_type.id                                            AS break_type_id"                                           ,
            "break_type_name"                                          => "break_type.name                                          AS break_type_name"                                         ,
            "break_type_duration_in_minutes"                           => "break_type.duration_in_minutes                           AS break_type_duration_in_minutes"                          ,
            "break_type_is_paid"                                       => "break_type.is_paid                                       AS break_type_is_paid"                                      ,
            "break_type_is_require_break_in_out"                       => "break_type.is_require_break_in_and_break_out             AS break_type_is_require_break_in_out"                      ,
            "break_type_created_at"                                    => "break_type.created_at                                    AS break_type_created_at"                                   ,
            "break_type_updated_at"                                    => "break_type.updated_at                                    AS break_type_updated_at"                                   ,
            "break_type_deleted_at"                                    => "break_type.deleted_at                                    AS break_type_deleted_at"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("break_schedule_snapshot_break_schedule_id"               , $selectedColumns) ||
            array_key_exists("break_schedule_snapshot_work_schedule_snapshot_id"       , $selectedColumns) ||
            array_key_exists("break_schedule_snapshot_break_type_snapshot_id"          , $selectedColumns) ||
            array_key_exists("break_schedule_snapshot_start_time"                      , $selectedColumns) ||
            array_key_exists("break_schedule_snapshot_end_time"                        , $selectedColumns) ||
            array_key_exists("break_schedule_snapshot_active_at"                       , $selectedColumns) ||

            array_key_exists("break_schedule_id"                                       , $selectedColumns) ||
            array_key_exists("break_schedule_work_schedule_id"                         , $selectedColumns) ||
            array_key_exists("break_schedule_break_type_id"                            , $selectedColumns) ||
            array_key_exists("break_schedule_start_time"                               , $selectedColumns) ||
            array_key_exists("break_schedule_end_time"                                 , $selectedColumns) ||
            array_key_exists("break_schedule_created_at"                               , $selectedColumns) ||
            array_key_exists("break_schedule_updated_at"                               , $selectedColumns) ||
            array_key_exists("break_schedule_deleted_at"                               , $selectedColumns) ||

            array_key_exists("work_schedule_snapshot_id"                               , $selectedColumns) ||
            array_key_exists("work_schedule_snapshot_work_schedule_id"                 , $selectedColumns) ||
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

            array_key_exists("work_schedule_id"                                        , $selectedColumns) ||
            array_key_exists("work_schedule_employee_id"                               , $selectedColumns) ||
            array_key_exists("work_schedule_start_time"                                , $selectedColumns) ||
            array_key_exists("work_schedule_end_time"                                  , $selectedColumns) ||
            array_key_exists("work_schedule_is_flextime"                               , $selectedColumns) ||
            array_key_exists("work_schedule_total_hours_per_week"                      , $selectedColumns) ||
            array_key_exists("work_schedule_total_work_hours"                          , $selectedColumns) ||
            array_key_exists("work_schedule_start_date"                                , $selectedColumns) ||
            array_key_exists("work_schedule_recurrence_rule"                           , $selectedColumns) ||
            array_key_exists("work_schedule_created_at"                                , $selectedColumns) ||
            array_key_exists("work_schedule_updated_at"                                , $selectedColumns) ||
            array_key_exists("work_schedule_deleted_at"                                , $selectedColumns) ||

            array_key_exists("break_type_snapshot_id"                                  , $selectedColumns) ||
            array_key_exists("break_type_snapshot_break_type_id"                       , $selectedColumns) ||
            array_key_exists("break_type_snapshot_name"                                , $selectedColumns) ||
            array_key_exists("break_type_snapshot_duration_in_minutes"                 , $selectedColumns) ||
            array_key_exists("break_type_snapshot_is_paid"                             , $selectedColumns) ||
            array_key_exists("break_type_snapshot_is_require_break_in_out"             , $selectedColumns) ||
            array_key_exists("break_type_snapshot_active_at"                           , $selectedColumns) ||

            array_key_exists("break_type_id"                                           , $selectedColumns) ||
            array_key_exists("break_type_name"                                         , $selectedColumns) ||
            array_key_exists("break_type_duration_in_minutes"                          , $selectedColumns) ||
            array_key_exists("break_type_is_paid"                                      , $selectedColumns) ||
            array_key_exists("break_type_is_require_break_in_out"                      , $selectedColumns) ||
            array_key_exists("break_type_created_at"                                   , $selectedColumns) ||
            array_key_exists("break_type_updated_at"                                   , $selectedColumns) ||
            array_key_exists("break_type_deleted_at"                                   , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    break_schedule_snapshots AS break_schedule_snapshot
                ON
                    employee_break.break_schedule_snapshot_id = break_schedule_snapshot.id
            ";
        }

        if (array_key_exists("break_schedule_id"                 , $selectedColumns) ||
            array_key_exists("break_schedule_work_schedule_id"   , $selectedColumns) ||
            array_key_exists("break_schedule_break_type_id"      , $selectedColumns) ||
            array_key_exists("break_schedule_start_time"         , $selectedColumns) ||
            array_key_exists("break_schedule_end_time"           , $selectedColumns) ||
            array_key_exists("break_schedule_created_at"         , $selectedColumns) ||
            array_key_exists("break_schedule_updated_at"         , $selectedColumns) ||
            array_key_exists("break_schedule_deleted_at"         , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    break_schedules AS break_schedule
                ON
                    break_schedule_snapshot.break_schedule_id = break_schedule.id
            ";
        }

        if (array_key_exists("work_schedule_snapshot_id"                               , $selectedColumns) ||
            array_key_exists("work_schedule_snapshot_work_schedule_id"                 , $selectedColumns) ||
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

            array_key_exists("work_schedule_id"                                        , $selectedColumns) ||
            array_key_exists("work_schedule_employee_id"                               , $selectedColumns) ||
            array_key_exists("work_schedule_start_time"                                , $selectedColumns) ||
            array_key_exists("work_schedule_end_time"                                  , $selectedColumns) ||
            array_key_exists("work_schedule_is_flextime"                               , $selectedColumns) ||
            array_key_exists("work_schedule_total_hours_per_week"                      , $selectedColumns) ||
            array_key_exists("work_schedule_total_work_hours"                          , $selectedColumns) ||
            array_key_exists("work_schedule_start_date"                                , $selectedColumns) ||
            array_key_exists("work_schedule_recurrence_rule"                           , $selectedColumns) ||
            array_key_exists("work_schedule_created_at"                                , $selectedColumns) ||
            array_key_exists("work_schedule_updated_at"                                , $selectedColumns) ||
            array_key_exists("work_schedule_deleted_at"                                , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    work_schedule_snapshots AS work_schedule_snapshot
                ON
                    break_schedule_snapshot.work_schedule_snapshot_id = work_schedule_snapshot.id
            ";
        }

        if (array_key_exists("employee_full_name"      , $selectedColumns) ||
            array_key_exists("employee_profile_picture", $selectedColumns) ||
            array_key_exists("employee_code"           , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    employees AS employee
                ON
                    work_schedule_snapshot.employee_id = employee.id
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
                    work_schedule_snapshot.id = work_schedule.id
            ";
        }

        if (array_key_exists("break_type_snapshot_id"                     , $selectedColumns) ||
            array_key_exists("break_type_snapshot_break_type_id"          , $selectedColumns) ||
            array_key_exists("break_type_snapshot_name"                   , $selectedColumns) ||
            array_key_exists("break_type_snapshot_duration_in_minutes"    , $selectedColumns) ||
            array_key_exists("break_type_snapshot_is_paid"                , $selectedColumns) ||
            array_key_exists("break_type_snapshot_is_require_break_in_out", $selectedColumns) ||
            array_key_exists("break_type_snapshot_active_at"              , $selectedColumns) ||

            array_key_exists("break_type_id"                              , $selectedColumns) ||
            array_key_exists("break_type_name"                            , $selectedColumns) ||
            array_key_exists("break_type_duration_in_minutes"             , $selectedColumns) ||
            array_key_exists("break_type_is_paid"                         , $selectedColumns) ||
            array_key_exists("break_type_is_require_break_in_out"         , $selectedColumns) ||
            array_key_exists("break_type_created_at"                      , $selectedColumns) ||
            array_key_exists("break_type_updated_at"                      , $selectedColumns) ||
            array_key_exists("break_type_deleted_at"                      , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    break_type_snapshots AS break_type_snapshot
                ON
                    break_schedule_snapshot.break_type_snapshot_id = break_type_snapshot.id
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
                    break_type_snapshot.id = break_type.id
            ";
        }

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "employee_break.deleted_at IS NULL";

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

        $groupByClause = "";
        if ( ! empty($groupByColumns)) {
            $groupByClause = "GROUP BY " . implode(", ", $groupByColumns);
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
                " . implode(" ", $whereClauses) . "
            {$groupByClause}
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
            error_log("Database Error: An error occurred while fetching employee breaks. " .
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

    public function update(EmployeeBreak $employeeBreak): ActionResult
    {
        $query = "
            UPDATE employee_breaks
            SET
                break_schedule_snapshot_id = :break_schedule_snapshot_id,
                start_time                 = :start_time                ,
                end_time                   = :end_time                  ,
                break_duration_in_minutes  = :break_duration_in_minutes ,
                created_at                 = :created_at
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", (string) $employeeBreak->getId())) {
            $query .= "id = :employee_break_id";
        } else {
            $query .= "SHA2(id, 256) = :employee_break_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":break_schedule_snapshot_id", $employeeBreak->getBreakScheduleSnapshotId(), Helper::getPdoParameterType($employeeBreak->getBreakScheduleSnapshotId()));
            $statement->bindValue(":start_time"                , $employeeBreak->getStartTime()              , Helper::getPdoParameterType($employeeBreak->getStartTime()              ));
            $statement->bindValue(":end_time"                  , $employeeBreak->getEndTime()                , Helper::getPdoParameterType($employeeBreak->getEndTime()                ));
            $statement->bindValue(":break_duration_in_minutes" , $employeeBreak->getBreakDurationInMinutes() , Helper::getPdoParameterType($employeeBreak->getBreakDurationInMinutes() ));
            $statement->bindValue(":created_at"                , $employeeBreak->getCreatedAt()              , Helper::getPdoParameterType($employeeBreak->getCreatedAt()              ));

            $statement->bindValue(":employee_break_id"         , $employeeBreak->getId()                     , Helper::getPdoParameterType($employeeBreak->getId()                     ));

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

    public function delete(int|string $employeeBreakId): ActionResult
    {
        return $this->softDelete($employeeBreakId);
    }

    private function softDelete(int|string $employeeBreakId): ActionResult
    {
        $query = "
            UPDATE employee_breaks
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", (string) $employeeBreakId)) {
            $query .= "id = :employee_break_id";
        } else {
            $query .= "SHA2(id, 256) = :employee_break_id";
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
