<?php

require_once __DIR__ . "/vendor/autoload.php"               ;

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

use RRule\RRule;

class WorkScheduleDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(WorkSchedule $workSchedule): ActionResult
    {
        $query = "
            INSERT INTO work_schedules (
                employee_id          ,
                title                ,
                start_time           ,
                end_time             ,
                is_flexible          ,
                arrival_start_time   ,
                arrival_end_time     ,
                core_hours_start_time,
                core_hours_end_time  ,
                departure_start_time ,
                departure_end_time   ,
                total_hours_per_week ,
                start_date           ,
                recurrence_rule      ,
                note
            )
            VALUES (
                :employee_id          ,
                :title                ,
                :start_time           ,
                :end_time             ,
                :is_flexible          ,
                :arrival_start_time   ,
                :arrival_end_time     ,
                :core_hours_start_time,
                :core_hours_end_time  ,
                :departure_start_time ,
                :departure_end_time   ,
                :total_hours_per_week ,
                :start_date           ,
                :recurrence_rule      ,
                :note
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"          , $workSchedule->getEmployeeId()        , Helper::getPdoParameterType($workSchedule->getEmployeeId()        ));
            $statement->bindValue(":title"                , $workSchedule->getTitle()             , Helper::getPdoParameterType($workSchedule->getTitle()             ));
            $statement->bindValue(":start_time"           , $workSchedule->getStartTime()         , Helper::getPdoParameterType($workSchedule->getStartTime()         ));
            $statement->bindValue(":end_time"             , $workSchedule->getEndTime()           , Helper::getPdoParameterType($workSchedule->getEndTime()           ));
            $statement->bindValue(":is_flexible"          , $workSchedule->isFlexible()           , Helper::getPdoParameterType($workSchedule->isFlexible()           ));
            $statement->bindValue(":arrival_start_time"   , $workSchedule->getArrivalStartTime()  , Helper::getPdoParameterType($workSchedule->getArrivalStartTime()  ));
            $statement->bindValue(":arrival_end_time"     , $workSchedule->getArrivalEndTime()    , Helper::getPdoParameterType($workSchedule->getArrivalEndTime()    ));
            $statement->bindValue(":core_hours_start_time", $workSchedule->getCoreHoursStartTime(), Helper::getPdoParameterType($workSchedule->getCoreHoursStartTime()));
            $statement->bindValue(":core_hours_end_time"  , $workSchedule->getCoreHoursEndTime()  , Helper::getPdoParameterType($workSchedule->getCoreHoursEndTime()  ));
            $statement->bindValue(":departure_start_time" , $workSchedule->getDepartureStartTime(), Helper::getPdoParameterType($workSchedule->getDepartureStartTime()));
            $statement->bindValue(":departure_end_time"   , $workSchedule->getDepartureEndTime()  , Helper::getPdoParameterType($workSchedule->getDepartureEndTime()  ));
            $statement->bindValue(":total_hours_per_week" , $workSchedule->getTotalHoursPerWeek() , Helper::getPdoParameterType($workSchedule->getTotalHoursPerWeek() ));
            $statement->bindValue(":start_date"           , $workSchedule->getStartDate()         , Helper::getPdoParameterType($workSchedule->getStartDate()         ));
            $statement->bindValue(":recurrence_rule"      , $workSchedule->getRecurrenceRule()    , Helper::getPdoParameterType($workSchedule->getRecurrenceRule()    ));
            $statement->bindValue(":note"                 , $workSchedule->getNote()              , Helper::getPdoParameterType($workSchedule->getNote()              ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the work schedule. " .
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
            "id"                       => "work_schedule.id                    AS id"                      ,

            "employee_id"              => "work_schedule.employee_id           AS employee_id"             ,
            "employee_rfid_uid"        => "employee.rfid_uid                   AS employee_rfid_uid"       ,
            "employee_full_name"       => "employee.full_name                  AS employee_full_name"      ,
            "employee_job_title_id"    => "employee.job_title_id               AS employee_job_title_id"   ,
            "employee_job_title"       => "job_title.title                     AS employee_job_title"      ,
            "employee_department_id"   => "employee.department_id              AS employee_department_id"  ,
            "employee_department_name" => "department.name                     AS employee_department_name",
            "employee_profile_picture" => "employee.profile_picture            AS employee_profile_picture",

            "title"                    => "work_schedule.title                 AS title"                   ,
            "start_time"               => "work_schedule.start_time            AS start_time"              ,
            "end_time"                 => "work_schedule.end_time              AS end_time"                ,
            "is_flexible"              => "work_schedule.is_flexible           AS is_flexible"             ,
            "arrival_start_time"       => "work_schedule.arrival_start_time    AS arrival_start_time"      ,
            "arrival_end_time"         => "work_schedule.arrival_end_time      AS arrival_end_time"        ,
            "core_hours_start_time"    => "work_schedule.core_hours_start_time AS core_hours_start_time"   ,
            "core_hours_end_time"      => "work_schedule.core_hours_end_time   AS core_hours_end_time"     ,
            "departure_start_time"     => "work_schedule.departure_start_time  AS departure_start_time"    ,
            "departure_end_time"       => "work_schedule.departure_end_time    AS departure_end_time"      ,
            "total_hours_per_week"     => "work_schedule.total_hours_per_week  AS total_hours_per_week"    ,
            "start_date"               => "work_schedule.start_date            AS start_date"              ,
            "recurrence_rule"          => "work_schedule.recurrence_rule       AS recurrence_rule"         ,
            "note"                     => "work_schedule.note                  AS note"                    ,
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("employee_rfid_uid"       , $selectedColumns) ||
            array_key_exists("employee_full_name"      , $selectedColumns) ||
            array_key_exists("employee_job_title"      , $selectedColumns) ||
            array_key_exists("employee_department_name", $selectedColumns) ||
            array_key_exists("employee_profile_picture", $selectedColumns) ||

            array_key_exists("employee_job_title"      , $selectedColumns) ||

            array_key_exists("employee_department_name", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    employees AS employee
                ON
                    work_schedule.employee_id = employee.id
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
            $whereClauses[] = "work_schedule.deleted_at IS NULL";
        } else {
            foreach ($filterCriteria as $filterCriterion) {
                $column   = $filterCriterion["column"];
                $operator = $filterCriterion["operator"];

                switch ($operator) {
                    case "="   :
                    case "LIKE":
                        $whereClauses[] = "{$column} {$operator} ?";
                        $queryParameters[] = $filterCriterion["value"];
                        break;
                    case "BETWEEN":
                        $whereClauses[] = "{$column} {$operator} ? AND ?";
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
                work_schedules AS work_schedule
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
            error_log("Database Error: An error occurred while fetching the work schedules. " .
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

    public function getRecurrenceDates(string $recurrenceRule, string $startDate, string $endDate): array
    {
        $parsedRecurrenceRule = $this->parseRecurrenceRule($recurrenceRule);

        $recurrence = new RRule($parsedRecurrenceRule);

        $dates = [];

        foreach ($recurrence as $occurence) {
            $date = $occurence->format("Y-m-d");

            if ($date > $endDate) {
                return $dates;
            }

            if ($date >= $startDate) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    private function parseRecurrenceRule(string $rule): array
    {
        $parts = explode(";", $rule);

        $parsedRule = [];

        foreach ($parts as $part) {
            [$key, $value] = explode("=", $part, 2);

            $parsedRule[$key] = $value;
        }

        return $parsedRule;
    }

    public function update(WorkSchedule $workSchedule): ActionResult
    {
        $query = "
            UPDATE work_schedules
            SET
                employee_id           = :employee_id          ,
                title                 = :title                ,
                start_time            = :start_time           ,
                end_time              = :end_time             ,
                is_flexible           = :is_flexible          ,
                arrival_start_time    = :arrival_start_time   ,
                arrival_end_time      = :arrival_end_time     ,
                core_hours_start_time = :core_hours_start_time,
                core_hours_end_time   = :core_hours_end_time  ,
                departure_start_time  = :departure_start_time ,
                departure_end_time    = :departure_end_time   ,
                total_hours_per_week  = :total_hours_per_week ,
                start_date            = :start_date           ,
                recurrence_rule       = :recurrence_rule      ,
                note                  = :note
            WHERE
                id = :work_schedule_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"          , $workSchedule->getEmployeeId()        , Helper::getPdoParameterType($workSchedule->getEmployeeId()        ));
            $statement->bindValue(":title"                , $workSchedule->getTitle()             , Helper::getPdoParameterType($workSchedule->getTitle()             ));
            $statement->bindValue(":start_time"           , $workSchedule->getStartTime()         , Helper::getPdoParameterType($workSchedule->getStartTime()         ));
            $statement->bindValue(":end_time"             , $workSchedule->getEndTime()           , Helper::getPdoParameterType($workSchedule->getEndTime()           ));
            $statement->bindValue(":is_flexible"          , $workSchedule->isFlexible()           , Helper::getPdoParameterType($workSchedule->isFlexible()           ));
            $statement->bindValue(":arrival_start_time"   , $workSchedule->getArrivalStartTime()  , Helper::getPdoParameterType($workSchedule->getArrivalStartTime()  ));
            $statement->bindValue(":arrival_end_time"     , $workSchedule->getArrivalEndTime()    , Helper::getPdoParameterType($workSchedule->getArrivalEndTime()    ));
            $statement->bindValue(":core_hours_start_time", $workSchedule->getCoreHoursStartTime(), Helper::getPdoParameterType($workSchedule->getCoreHoursStartTime()));
            $statement->bindValue(":core_hours_end_time"  , $workSchedule->getCoreHoursEndTime()  , Helper::getPdoParameterType($workSchedule->getCoreHoursEndTime()  ));
            $statement->bindValue(":departure_start_time" , $workSchedule->getDepartureStartTime(), Helper::getPdoParameterType($workSchedule->getDepartureStartTime()));
            $statement->bindValue(":departure_end_time"   , $workSchedule->getDepartureEndTime()  , Helper::getPdoParameterType($workSchedule->getDepartureEndTime()  ));
            $statement->bindValue(":total_hours_per_week" , $workSchedule->getTotalHoursPerWeek() , Helper::getPdoParameterType($workSchedule->getTotalHoursPerWeek() ));
            $statement->bindValue(":start_date"           , $workSchedule->getStartDate()         , Helper::getPdoParameterType($workSchedule->getStartDate()         ));
            $statement->bindValue(":recurrence_rule"      , $workSchedule->getRecurrenceRule()    , Helper::getPdoParameterType($workSchedule->getRecurrenceRule()    ));
            $statement->bindValue(":note"                 , $workSchedule->getNote()              , Helper::getPdoParameterType($workSchedule->getNote()              ));
            $statement->bindValue(":work_schedule_id"     , $workSchedule->getId()                , Helper::getPdoParameterType($workSchedule->getId()                ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while updating the work schedule. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int $workScheduleId): ActionResult
    {
        return $this->softDelete($workScheduleId);
    }

    private function softDelete(int $workScheduleId): ActionResult
    {
        $query = "
            UPDATE work_schedules
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
                id = :work_schedule_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":work_schedule_id", $workScheduleId, Helper::getPdoParameterType($workScheduleId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while deleting the work schedule. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
