<?php

require_once __DIR__ . "/../vendor/autoload.php"            ;

require_once __DIR__ . "/WorkSchedule.php"                  ;
require_once __DIR__ . "/WorkScheduleSnapshot.php"          ;

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

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
                employee_id         ,
                start_time          ,
                end_time            ,
                is_flextime         ,
                total_hours_per_week,
                total_work_hours    ,
                start_date          ,
                recurrence_rule
            )
            VALUES (
                :employee_id         ,
                :start_time          ,
                :end_time            ,
                :is_flextime         ,
                :total_hours_per_week,
                :total_work_hours    ,
                :start_date          ,
                :recurrence_rule
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"         , $workSchedule->getEmployeeId()       , Helper::getPdoParameterType($workSchedule->getEmployeeId()       ));
            $statement->bindValue(":start_time"          , $workSchedule->getStartTime()        , Helper::getPdoParameterType($workSchedule->getStartTime()        ));
            $statement->bindValue(":end_time"            , $workSchedule->getEndTime()          , Helper::getPdoParameterType($workSchedule->getEndTime()          ));
            $statement->bindValue(":is_flextime"         , $workSchedule->isFlextime()          , Helper::getPdoParameterType($workSchedule->isFlextime()          ));
            $statement->bindValue(":total_hours_per_week", $workSchedule->getTotalHoursPerWeek(), Helper::getPdoParameterType($workSchedule->getTotalHoursPerWeek()));
            $statement->bindValue(":total_work_hours"    , $workSchedule->getTotalWorkHours()   , Helper::getPdoParameterType($workSchedule->getTotalWorkHours()   ));
            $statement->bindValue(":start_date"          , $workSchedule->getStartDate()        , Helper::getPdoParameterType($workSchedule->getStartDate()        ));
            $statement->bindValue(":recurrence_rule"     , $workSchedule->getRecurrenceRule()   , Helper::getPdoParameterType($workSchedule->getRecurrenceRule()   ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the work schedule. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function createSnapshot(WorkScheduleSnapshot $workScheduleSnapshot): int|ActionResult
    {
        $query = "
            INSERT INTO work_schedule_snapshots (
                work_schedule_id                 ,
                employee_id                      ,
                start_time                       ,
                end_time                         ,
                is_flextime                      ,
                total_hours_per_week             ,
                total_work_hours                 ,
                start_date                       ,
                recurrence_rule                  ,
                grace_period                     ,
                minutes_can_check_in_before_shift
            )
            VALUES (
                :work_schedule_id                 ,
                :employee_id                      ,
                :start_time                       ,
                :end_time                         ,
                :is_flextime                      ,
                :total_hours_per_week             ,
                :total_work_hours                 ,
                :start_date                       ,
                :recurrence_rule                  ,
                :grace_period                     ,
                :minutes_can_check_in_before_shift
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":work_schedule_id"                 , $workScheduleSnapshot->getWorkScheduleId()    , Helper::getPdoParameterType($workScheduleSnapshot->getWorkScheduleId()    ));
            $statement->bindValue(":employee_id"                      , $workScheduleSnapshot->getEmployeeId()        , Helper::getPdoParameterType($workScheduleSnapshot->getEmployeeId()        ));
            $statement->bindValue(":start_time"                       , $workScheduleSnapshot->getStartTime()         , Helper::getPdoParameterType($workScheduleSnapshot->getStartTime()         ));
            $statement->bindValue(":end_time"                         , $workScheduleSnapshot->getEndTime()           , Helper::getPdoParameterType($workScheduleSnapshot->getEndTime()           ));
            $statement->bindValue(":is_flextime"                      , $workScheduleSnapshot->isFlextime()           , Helper::getPdoParameterType($workScheduleSnapshot->isFlextime()           ));
            $statement->bindValue(":total_hours_per_week"             , $workScheduleSnapshot->getTotalHoursPerWeek() , Helper::getPdoParameterType($workScheduleSnapshot->getTotalHoursPerWeek() ));
            $statement->bindValue(":total_work_hours"                 , $workScheduleSnapshot->getTotalWorkHours()    , Helper::getPdoParameterType($workScheduleSnapshot->getTotalWorkHours()    ));
            $statement->bindValue(":start_date"                       , $workScheduleSnapshot->getStartDate()         , Helper::getPdoParameterType($workScheduleSnapshot->getStartDate()         ));
            $statement->bindValue(":recurrence_rule"                  , $workScheduleSnapshot->getRecurrenceRule()    , Helper::getPdoParameterType($workScheduleSnapshot->getRecurrenceRule()    ));
            $statement->bindValue(":grace_period"                     , $workScheduleSnapshot->getGracePeriod()       , Helper::getPdoParameterType($workScheduleSnapshot->getGracePeriod()       ));
            $statement->bindValue(":minutes_can_check_in_before_shift", $workScheduleSnapshot->getEarlyCheckInWindow(), Helper::getPdoParameterType($workScheduleSnapshot->getEarlyCheckInWindow()));

            $statement->execute();

            $lastInsertId = $this->pdo->lastInsertId();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return $lastInsertId;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the work schedule snapshot. " .
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
            "id"                       => "work_schedule.id                   AS id"                      ,
            "employee_id"              => "work_schedule.employee_id          AS employee_id"             ,
            "start_time"               => "work_schedule.start_time           AS start_time"              ,
            "end_time"                 => "work_schedule.end_time             AS end_time"                ,
            "is_flextime"              => "work_schedule.is_flextime          AS is_flextime"             ,
            "total_hours_per_week"     => "work_schedule.total_hours_per_week AS total_hours_per_week"    ,
            "total_work_hours"         => "work_schedule.total_work_hours     AS total_work_hours"        ,
            "start_date"               => "work_schedule.start_date           AS start_date"              ,
            "recurrence_rule"          => "work_schedule.recurrence_rule      AS recurrence_rule"         ,
            "created_at"               => "work_schedule.created_at           AS created_at"              ,
            "updated_at"               => "work_schedule.updated_at           AS updated_at"              ,
            "deleted_at"               => "work_schedule.deleted_at           AS deleted_at"              ,

            "employee_rfid_uid"        => "employee.rfid_uid                  AS employee_rfid_uid"       ,
            "employee_full_name"       => "employee.full_name                 AS employee_full_name"      ,
            "employee_job_title_id"    => "employee.job_title_id              AS employee_job_title_id"   ,
            "employee_department_id"   => "employee.department_id             AS employee_department_id"  ,
            "employee_profile_picture" => "employee.profile_picture           AS employee_profile_picture",

            "employee_job_title"       => "job_title.title                    AS employee_job_title"      ,

            "employee_department_name" => "department.name                    AS employee_department_name"
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
            array_key_exists("employee_job_title_id"   , $selectedColumns) ||
            array_key_exists("employee_department_id"  , $selectedColumns) ||
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

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "work_schedule.deleted_at IS NULL";

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
            SELECT
                " . implode(", ", $selectedColumns) . "
            FROM
                work_schedules AS work_schedule
            {$joinClauses}
            WHERE
            " . (empty($whereClauses) ? "1=1" : implode(" ", $whereClauses)) . "
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
                        COUNT(work_schedule.id)
                    FROM
                        work_schedules AS work_schedule
                    {$joinClauses}
                    WHERE
                        " . (empty($whereClauses) ? "1=1" : implode(" ", $whereClauses)) . "
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
            error_log("Database Error: An error occurred while fetching the work schedules. " .
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

                $nestedConditions = "($nestedConditions)";

                $boolean = $filterCriterion[count($filterCriterion) - 1]["boolean"] ?? "AND";

                if ($index < $totalNumberOfConditions - 1) {
                    $nestedConditions .= " {$boolean}";
                }

                $subConditions[] = $nestedConditions;

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

    public function fetchLatestSnapshotById(int $workScheduleId): array|ActionResult
    {
        $query = "
            SELECT
                id                               ,
                start_time                       ,
                end_time                         ,
                is_flextime                      ,
                total_hours_per_week             ,
                total_work_hours                 ,
                start_date                       ,
                recurrence_rule                  ,
                grace_period                     ,
                minutes_can_check_in_before_shift
            FROM
                work_schedule_snapshots
            WHERE
                work_schedule_id = :work_schedule_id
            ORDER BY
                active_at DESC
            LIMIT 1
        ";

        try {
            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":work_schedule_id", $workScheduleId, Helper::getPdoParameterType($workScheduleId));

            $statement->execute();

            return $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching the latest work schedule snapshot. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function getRecurrenceDates(string $recurrenceRule, string $startDate, string $endDate): array|ActionResult
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

    private function parseRecurrenceRule(string $rule): array|ActionResult
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
                start_time           = :start_time          ,
                end_time             = :end_time            ,
                is_flextime          = :is_flextime         ,
                total_hours_per_week = :total_hours_per_week,
                total_work_hours     = :total_work_hours    ,
                start_date           = :start_date          ,
                recurrence_rule      = :recurrence_rule
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $workSchedule->getId())) {
            $query .= "id = :work_schedule_id";
        } else {
            $query .= "SHA2(id, 256) = :work_schedule_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":start_time"          , $workSchedule->getStartTime()        , Helper::getPdoParameterType($workSchedule->getStartTime()        ));
            $statement->bindValue(":end_time"            , $workSchedule->getEndTime()          , Helper::getPdoParameterType($workSchedule->getEndTime()          ));
            $statement->bindValue(":is_flextime"         , $workSchedule->isFlextime()          , Helper::getPdoParameterType($workSchedule->isFlextime()          ));
            $statement->bindValue(":total_hours_per_week", $workSchedule->getTotalHoursPerWeek(), Helper::getPdoParameterType($workSchedule->getTotalHoursPerWeek()));
            $statement->bindValue(":total_work_hours"    , $workSchedule->getTotalWorkHours()   , Helper::getPdoParameterType($workSchedule->getTotalWorkHours()   ));
            $statement->bindValue(":start_date"          , $workSchedule->getStartDate()        , Helper::getPdoParameterType($workSchedule->getStartDate()        ));
            $statement->bindValue(":recurrence_rule"     , $workSchedule->getRecurrenceRule()   , Helper::getPdoParameterType($workSchedule->getRecurrenceRule()   ));

            $statement->bindValue(":work_schedule_id"    , $workSchedule->getId()               , Helper::getPdoParameterType($workSchedule->getId()               ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while updating the work schedule. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $workScheduleId): ActionResult
    {
        return $this->softDelete($workScheduleId);
    }

    private function softDelete(int|string $workScheduleId): ActionResult
    {
        $query = "
            UPDATE work_schedules
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $workScheduleId)) {
            $query .= "id = :work_schedule_id";
        } else {
            $query .= "SHA2(id, 256) = :work_schedule_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":work_schedule_id", $workScheduleId, Helper::getPdoParameterType($workScheduleId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while deleting the work schedule. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
