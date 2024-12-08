<?php

require_once __DIR__ . "/../vendor/autoload.php"            ;

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

use RRule\RSet;

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
                is_flextime          ,
                core_hours_start_time,
                core_hours_end_time  ,
                total_hours_per_week ,
                total_work_hours     ,
                start_date           ,
                recurrence_rule      ,
                note
            )
            VALUES (
                :employee_id          ,
                :title                ,
                :start_time           ,
                :end_time             ,
                :is_flextime          ,
                :core_hours_start_time,
                :core_hours_end_time  ,
                :total_hours_per_week ,
                :total_work_hours     ,
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
            $statement->bindValue(":is_flextime"          , $workSchedule->isFlextime()           , Helper::getPdoParameterType($workSchedule->isFlextime()           ));
            $statement->bindValue(":core_hours_start_time", $workSchedule->getCoreHoursStartTime(), Helper::getPdoParameterType($workSchedule->getCoreHoursStartTime()));
            $statement->bindValue(":core_hours_end_time"  , $workSchedule->getCoreHoursEndTime()  , Helper::getPdoParameterType($workSchedule->getCoreHoursEndTime()  ));
            $statement->bindValue(":total_hours_per_week" , $workSchedule->getTotalHoursPerWeek() , Helper::getPdoParameterType($workSchedule->getTotalHoursPerWeek() ));
            $statement->bindValue(":total_work_hours"     , $workSchedule->getTotalWorkHours()    , Helper::getPdoParameterType($workSchedule->getTotalWorkHours()    ));
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
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
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
            "is_flextime"              => "work_schedule.is_flextime           AS is_flextime"             ,
            "core_hours_start_time"    => "work_schedule.core_hours_start_time AS core_hours_start_time"   ,
            "core_hours_end_time"      => "work_schedule.core_hours_end_time   AS core_hours_end_time"     ,
            "total_hours_per_week"     => "work_schedule.total_hours_per_week  AS total_hours_per_week"    ,
            "total_work_hours"         => "work_schedule.total_work_hours      AS total_work_hours"        ,
            "start_date"               => "work_schedule.start_date            AS start_date"              ,
            "recurrence_rule"          => "work_schedule.recurrence_rule       AS recurrence_rule"         ,
            "note"                     => "work_schedule.note                  AS note"                    ,
            "created_at"               => "work_schedule.created_at            AS created_at"              ,
            "updated_at"               => "work_schedule.updated_at            AS updated_at"              ,
            "deleted_at"               => "work_schedule.deleted_at            AS deleted_at"
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
            array_key_exists("employee_department_name", $selectedColumns) ||
            array_key_exists("employee_profile_picture", $selectedColumns) ||

            array_key_exists("employee_job_title_id"   , $selectedColumns) ||
            array_key_exists("employee_job_title"      , $selectedColumns) ||

            array_key_exists("employee_department_id"  , $selectedColumns) ||
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
                $column   = $filterCriterion["column"  ];
                $operator = $filterCriterion["operator"];

                switch ($operator) {
                    case "="   :
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
        if ($limit !== null && $limit > 0) {
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

    public function getRecurrenceDates(string $recurrenceRule, string $startDate, string $endDate): ActionResult|array
    {
        try {
            $recurrenceSet = new RSet();

            $datesToExclude = "";

            if (strpos($recurrenceRule, "EXDATE=") !== false) {

                list($recurrenceRule, $datesToExclude) = explode("EXDATE=", $recurrenceRule);
                $datesToExclude = rtrim($datesToExclude, ";");
            }

            $parsedRecurrenceRule = $this->parseRecurrenceRule($recurrenceRule);
            $recurrenceSet->addRRule($parsedRecurrenceRule);

            if ( ! empty($datesToExclude)) {
                $excludeDates = explode(",", $datesToExclude);

                foreach ($excludeDates as $excludedDate) {
                    $recurrenceSet->addExDate($excludedDate);
                }
            }

            $startDate = (new DateTime($startDate))->format("Y-m-d");
            $endDate   = (new DateTime($endDate  ))->format("Y-m-d");

            $dates = [];

            foreach ($recurrenceSet as $occurence) {
                $date = $occurence->format("Y-m-d");

                if ($date > $endDate) {
                    return $dates;
                }

                if ($date >= $startDate && $date <= $endDate) {
                    $dates[] = $date;
                }
            }

            return $dates;

        } catch (InvalidArgumentException $exception) {
            error_log("Invalid Argument Error: An error occurred while processing the recurrence rule. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;

        } catch (Exception $exception) {
            error_log("General Error: An error occurred while processing the recurrence dates. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    private function parseRecurrenceRule(string $rule): ActionResult|array
    {
        try {
            $rule = rtrim($rule, ';');

            $parts = explode(';', $rule);

            $parsedRule = [];

            foreach ($parts as $part) {
                [$key, $value] = explode('=', $part, 2);

                $parsedRule[$key] = $value;
            }

            return $parsedRule;

        } catch (Exception $exception) {
            error_log("Parsing Error: An error occurred while parsing the recurrence rule. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
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
                is_flextime           = :is_flextime          ,
                core_hours_start_time = :core_hours_start_time,
                core_hours_end_time   = :core_hours_end_time  ,
                total_hours_per_week  = :total_hours_per_week ,
                total_work_hours      = :total_work_hours     ,
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
            $statement->bindValue(":is_flextime"          , $workSchedule->isFlextime()           , Helper::getPdoParameterType($workSchedule->isFlextime()           ));
            $statement->bindValue(":core_hours_start_time", $workSchedule->getCoreHoursStartTime(), Helper::getPdoParameterType($workSchedule->getCoreHoursStartTime()));
            $statement->bindValue(":core_hours_end_time"  , $workSchedule->getCoreHoursEndTime()  , Helper::getPdoParameterType($workSchedule->getCoreHoursEndTime()  ));
            $statement->bindValue(":total_hours_per_week" , $workSchedule->getTotalHoursPerWeek() , Helper::getPdoParameterType($workSchedule->getTotalHoursPerWeek() ));
            $statement->bindValue(":total_work_hours"     , $workSchedule->getTotalWorkHours()    , Helper::getPdoParameterType($workSchedule->getTotalWorkHours()    ));
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
