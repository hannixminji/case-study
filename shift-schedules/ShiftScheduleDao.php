<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

class ShiftScheduleDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(ShiftSchedule $shiftSchedule): ActionResult
    {
        $query = "
            INSERT INTO shift_schedules (
                employee_id          ,
                shift_title          ,
                start_time           ,
                end_time             ,
                is_flexible          ,
                flexible_start_time  ,
                flexible_end_time    ,
                core_hours_start_time,
                core_hours_end_time  ,
                total_hours_per_week ,
                start_date           ,
                recurrence_pattern   ,
                note
            )
            VALUES (
                :employee_id          ,
                :shift_title          ,
                :start_time           ,
                :end_time             ,
                :is_flexible          ,
                :flexible_start_time  ,
                :flexible_end_time    ,
                :core_hours_start_time,
                :core_hours_end_time  ,
                :total_hours_per_week ,
                :start_date           ,
                :recurrence_pattern   ,
                :note
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"          , $shiftSchedule->getEmployeeId()        , Helper::getPdoParameterType($shiftSchedule->getEmployeeId()        ));
            $statement->bindValue(":shift_title"          , $shiftSchedule->getShiftTitle()        , Helper::getPdoParameterType($shiftSchedule->getShiftTitle()        ));
            $statement->bindValue(":start_time"           , $shiftSchedule->getStartTime()         , Helper::getPdoParameterType($shiftSchedule->getStartTime()         ));
            $statement->bindValue(":end_time"             , $shiftSchedule->getEndTime()           , Helper::getPdoParameterType($shiftSchedule->getEndTime()           ));
            $statement->bindValue(":is_flexible"          , $shiftSchedule->isFlexible()           , Helper::getPdoParameterType($shiftSchedule->isFlexible()           ));
            $statement->bindValue(":flexible_start_time"  , $shiftSchedule->getFlexibleStartTime() , Helper::getPdoParameterType($shiftSchedule->getFlexibleStartTime() ));
            $statement->bindValue(":flexible_end_time"    , $shiftSchedule->getFlexibleEndTime()   , Helper::getPdoParameterType($shiftSchedule->getFlexibleEndTime()   ));
            $statement->bindValue(":core_hours_start_time", $shiftSchedule->getCoreHoursStartTime(), Helper::getPdoParameterType($shiftSchedule->getCoreHoursStartTime()));
            $statement->bindValue(":core_hours_end_time"  , $shiftSchedule->getCoreHoursEndTime()  , Helper::getPdoParameterType($shiftSchedule->getCoreHoursEndTime()  ));
            $statement->bindValue(":total_hours_per_week" , $shiftSchedule->getTotalHoursPerWeek() , Helper::getPdoParameterType($shiftSchedule->getTotalHoursPerWeek() ));
            $statement->bindValue(":start_date"           , $shiftSchedule->getStartDate()         , Helper::getPdoParameterType($shiftSchedule->getStartDate()         ));
            $statement->bindValue(":recurrence_pattern"   , $shiftSchedule->getRecurrencePattern() , Helper::getPdoParameterType($shiftSchedule->getRecurrencePattern() ));
            $statement->bindValue(":note"                 , $shiftSchedule->getNote()              , Helper::getPdoParameterType($shiftSchedule->getNote()              ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the shift schedule. " .
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
            "id"                       => "shift_schedule.id                    AS id"                      ,

            "employee_id"              => "shift_schedule.employee_id           AS employee_id"             ,
            "employee_full_name"       => "employee.full_name                   AS employee_full_name"      ,
            "employee_job_title_id"    => "employee.job_title_id                AS employee_job_title_id"   ,
            "employee_job_title"       => "job_title.title                      AS employee_job_title"      ,
            "employee_department_id"   => "employee.department_id               AS employee_department_id"  ,
            "employee_department_name" => "department.name                      AS employee_department_name",
            "employee_profile_picture" => "employee.profile_picture             AS employee_profile_picture",

            "shift_title"              => "shift_schedule.shift_title           AS shift_title"             ,
            "start_time"               => "shift_schedule.start_time            AS start_time"              ,
            "end_time"                 => "shift_schedule.end_time              AS end_time"                ,
            "is_flexible"              => "shift_schedule.is_flexible           AS is_flexible"             ,
            "flexible_start_time"      => "shift_schedule.flexible_start_time   AS flexible_start_time"     ,
            "flexible_end_time"        => "shift_schedule.flexible_end_time     AS flexible_end_time"       ,
            "core_hours_start_time"    => "shift_schedule.core_hours_start_time AS core_hours_start_time"   ,
            "core_hours_end_time"      => "shift_schedule.core_hours_end_time   AS core_hours_end_time"     ,
            "total_hours_per_week"     => "shift_schedule.total_hours_per_week  AS total_hours_per_week"    ,
            "start_date"               => "shift_schedule.start_date            AS start_date"              ,
            "recurrence_pattern"       => "shift_schedule.recurrence_pattern    AS recurrence_pattern"      ,
            "note"                     => "shift_schedule.note                  AS note"                    ,
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("employee_full_name"      , $selectedColumns) ||
            array_key_exists("employee_job_title"      , $selectedColumns) ||
            array_key_exists("employee_department_name", $selectedColumns) ||
            array_key_exists("employee_profile_picture", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    employees AS employee
                ON
                    shift_schedule.employee_id = employee.id
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

        if (array_key_exists("employee_department_name", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    departments AS department
                ON
                    employee.department_id = department.id
            ";
        }

        $queryParameters = [];

        $whereClauses = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "shift_schedule.deleted_at IS NULL";
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
                shift_schedules AS shift_schedule
            {$joinClauses}
            WHERE
            " . (empty($whereClauses) ? "1=1" : implode(" AND ", $whereClauses)) . "
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
            error_log("Database Error: An error occurred while fetching the shift schedules. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function getLastInsertId(): ActionResult|int
    {
        try {
            return (int) $this->pdo->lastInsertId();

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while retrieving the last inserted ID. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(ShiftSchedule $shiftSchedule): ActionResult
    {
        $query = "
            UPDATE shift_schedules
            SET
                employee_id           = :employee_id          ,
                shift_title           = :shift_title          ,
                start_time            = :start_time           ,
                end_time              = :end_time             ,
                is_flexible           = :is_flexible          ,
                flexible_start_time   = :flexible_start_time  ,
                flexible_end_time     = :flexible_end_time    ,
                core_hours_start_time = :core_hours_start_time,
                core_hours_end_time   = :core_hours_end_time  ,
                total_hours_per_week  = :total_hours_per_week ,
                start_date            = :start_date           ,
                recurrence_pattern    = :recurrence_pattern   ,
                note                  = :note
            WHERE
                id = :shift_schedule_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"          , $shiftSchedule->getEmployeeId()        , Helper::getPdoParameterType($shiftSchedule->getEmployeeId()        ));
            $statement->bindValue(":shift_title"          , $shiftSchedule->getShiftTitle()        , Helper::getPdoParameterType($shiftSchedule->getShiftTitle()        ));
            $statement->bindValue(":start_time"           , $shiftSchedule->getStartTime()         , Helper::getPdoParameterType($shiftSchedule->getStartTime()         ));
            $statement->bindValue(":end_time"             , $shiftSchedule->getEndTime()           , Helper::getPdoParameterType($shiftSchedule->getEndTime()           ));
            $statement->bindValue(":is_flexible"          , $shiftSchedule->isFlexible()           , Helper::getPdoParameterType($shiftSchedule->isFlexible()           ));
            $statement->bindValue(":flexible_start_time"  , $shiftSchedule->getFlexibleStartTime() , Helper::getPdoParameterType($shiftSchedule->getFlexibleStartTime() ));
            $statement->bindValue(":flexible_end_time"    , $shiftSchedule->getFlexibleEndTime()   , Helper::getPdoParameterType($shiftSchedule->getFlexibleEndTime()   ));
            $statement->bindValue(":core_hours_start_time", $shiftSchedule->getCoreHoursStartTime(), Helper::getPdoParameterType($shiftSchedule->getCoreHoursStartTime()));
            $statement->bindValue(":core_hours_end_time"  , $shiftSchedule->getCoreHoursEndTime()  , Helper::getPdoParameterType($shiftSchedule->getCoreHoursEndTime()  ));
            $statement->bindValue(":total_hours_per_week" , $shiftSchedule->getTotalHoursPerWeek() , Helper::getPdoParameterType($shiftSchedule->getTotalHoursPerWeek() ));
            $statement->bindValue(":start_date"           , $shiftSchedule->getStartDate()         , Helper::getPdoParameterType($shiftSchedule->getStartDate()         ));
            $statement->bindValue(":recurrence_pattern"   , $shiftSchedule->getRecurrencePattern() , Helper::getPdoParameterType($shiftSchedule->getRecurrencePattern() ));
            $statement->bindValue(":note"                 , $shiftSchedule->getNote()              , Helper::getPdoParameterType($shiftSchedule->getNote()              ));
            $statement->bindValue(":shift_schedule_id"    , $shiftSchedule->getId()                , Helper::getPdoParameterType($shiftSchedule->getId()                ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while updating the shift schedule. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int $shiftScheduleId): ActionResult
    {
        return $this->softDelete($shiftScheduleId);
    }

    private function softDelete(int $shiftScheduleId): ActionResult
    {
        $query = "
            UPDATE shift_schedules
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
                id = :shift_schedule_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":shift_schedule_id", $shiftScheduleId, Helper::getPdoParameterType($shiftScheduleId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while deleting the shift schedule. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
