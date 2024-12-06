<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

class EmployeeBreakDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function breakIn(EmployeeBreak $employeeBreak): ActionResult
    {
        $query = "
            INSERT INTO employee_breaks (
                break_schedule_id,
                start_time
            )
            VALUES (
                :break_schedule_id,
                :start_time
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":break_schedule_id", $employeeBreak->getBreakScheduleId(), Helper::getPdoParameterType($employeeBreak->getBreakScheduleId()));
            $statement->bindValue(":start_time"       , $employeeBreak->getStartTime()      , Helper::getPdoParameterType($employeeBreak->getStartTime()      ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

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
                end_time = :end_time,
                break_duration_in_minutes = :break_duration_in_minutes
            WHERE
                id = :employee_break_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":end_time"                 , $employeeBreak->getEndTime()               , Helper::getPdoParameterType($employeeBreak->getEndTime()               ));
            $statement->bindValue(":break_duration_in_minutes", $employeeBreak->getBreakDurationInMinutes(), Helper::getPdoParameterType($employeeBreak->getBreakDurationInMinutes()));
            $statement->bindValue(":employee_break_id"        , $employeeBreak->getId()                    , Helper::getPdoParameterType($employeeBreak->getId()                    ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while recording the break out. " .
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
            "id"                                => "employee_break.id                             AS id"                               ,
            "break_schedule_id"                 => "employee_break.break_schedule_id              AS break_schedule_id"                ,
            "start_time"                        => "employee_break.start_time                     AS start_time"                       ,
            "end_time"                          => "employee_break.end_time                       AS end_time"                         ,
            "break_duration_in_minutes"         => "employee_break.break_duration_in_minutes      AS break_duration_in_minutes"        ,
            "created_at"                        => "employee_break.created_at                     AS created_at"                       ,
            "updated_at"                        => "employee_break.updated_at                     AS updated_at"                       ,

            "total_break_duration_in_minutes"   => "SUM(employee_break.break_duration_in_minutes) AS total_break_duration_in_minutes"  ,

            "work_schedule_id"                  => "break_schedule.work_schedule_id               AS work_schedule_id"                 ,
            "break_type_id"                     => "break_schedule.break_type_id                  AS break_type_id"                    ,
            "break_schedule_start_time"         => "break_schedule.start_time                     AS break_schedule_start_time"        ,
            "break_schedule_is_flexible"        => "break_schedule.is_flexible                    AS is_flexible"                      ,
            "break_schedule_earliest_start_time"=> "break_schedule.earliest_start_time            AS break_schedule_earliest_start_time",
            "break_schedule_latest_end_time"    => "break_schedule.latest_end_time                AS break_schedule_latest_end_time"    ,

            "employee_id"                       => "work_schedule.employee_id                     AS employee_id"                      ,
            "work_schedule_start_time"          => "work_schedule.start_time                      AS work_schedule_start_time"         ,
            "work_schedule_end_time"            => "work_schedule.end_time                        AS work_schedule_end_time"           ,

            "break_type_name"                   => "break_type.name                               AS break_type_name"                  ,
            "break_type_duration_in_minutes"    => "break_type.duration_in_minutes                AS break_type_duration_in_minutes"   ,
            "break_type_is_paid"                => "break_type.is_paid                            AS break_type_is_paid"               ,
            "is_require_break_in_and_break_out" => "break_type.is_require_break_in_and_break_out  AS is_require_break_in_and_break_out"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("work_schedule_id"                 , $selectedColumns) ||
            array_key_exists("break_type_id"                    , $selectedColumns) ||
            array_key_exists("break_schedule_is_flexible"        , $selectedColumns) ||
            array_key_exists("break_schedule_earliest_start_time", $selectedColumns) ||
            array_key_exists("break_schedule_earliest_end_time"  , $selectedColumns) ||

            array_key_exists("employee_id"                      , $selectedColumns) ||
            array_key_exists("work_schedule_start_time"         , $selectedColumns) ||
            array_key_exists("work_schedule_end_time"           , $selectedColumns) ||

            array_key_exists("break_type_name"                  , $selectedColumns) ||
            array_key_exists("break_type_duration_in_minutes"   , $selectedColumns) ||
            array_key_exists("break_type_is_paid"               , $selectedColumns) ||
            array_key_exists("is_require_break_in_and_break_out", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    break_schedules AS break_schedule
                ON
                    employee_break.break_schedule_id = break_schedule.id
            ";
        }

        if (array_key_exists("employee_id"             , $selectedColumns) ||
            array_key_exists("work_schedule_start_time", $selectedColumns) ||
            array_key_exists("work_schedule_end_time"  , $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    work_schedules AS work_schedule
                ON
                    break_schedule.work_schedule_id = work_schedule.id
            ";
        }

        if (array_key_exists("break_type_name"                  , $selectedColumns) ||
            array_key_exists("break_type_duration_in_minutes"   , $selectedColumns) ||
            array_key_exists("break_type_is_paid"               , $selectedColumns) ||
            array_key_exists("is_require_break_in_and_break_out", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    break_types AS break_type
                ON
                    break_schedule.break_type_id = break_type.id
            ";
        }

        $queryParameters = [];

        $whereClauses = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "employee_breaks.deleted_at IS NULL";
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
                employee_breaks AS employee_break
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
            error_log("Database Error: An error occurred while fetching employee breaks. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function fetchOrderedEmployeeBreaks(int $workScheduleId, int $employeeId, string $startDate, string $endDate): ActionResult|array
    {
        $query = "
            SELECT
                employee_break.id AS id,
                employee_break.break_schedule_id         AS break_schedule_id                 ,
                employee_break.start_time                AS start_time                        ,
                employee_break.end_time                  AS end_time                          ,
                employee_break.break_duration_in_minutes AS break_duration_in_minutes         ,
                employee_break.created_at                AS created_at                        ,
                break_schedule.work_schedule_id          AS work_schedule_id                  ,
                break_schedule.break_type_id             AS break_type_id                     ,
                break_schedule.start_time                AS break_schedule_start_time         ,
                break_schedule.is_flexible               AS is_flexible                       ,
                break_schedule.earliest_start_time       AS break_schedule_earliest_start_time,
                break_schedule.latest_end_time           AS break_schedule_latest_end_time    ,
                work_schedule.employee_id                AS employee_id                       ,
                break_type.duration_in_minutes           AS break_type_duration_in_minutes    ,
                break_type.is_paid                       AS break_type_is_paid
            FROM
                employee_breaks AS employee_break
            LEFT JOIN
                break_schedules AS break_schedule
                ON employee_break.break_schedule_id = break_schedule.id
            LEFT JOIN
                work_schedules AS work_schedule
                ON break_schedule.work_schedule_id = work_schedule.id
            LEFT JOIN
                break_types AS break_type
                ON break_schedule.break_type_id = break_type.id
            WHERE
                break_schedule.work_schedule_id = :work_schedule_id
                AND work_schedule.employee_id = :employee_id
                AND employee_break.created_at BETWEEN :start_date AND :end_date
            ORDER BY
                CASE
                    WHEN break_schedule.start_time IS NULL THEN break_schedule.earliest_start_time
                    ELSE break_schedule.start_time
                END ASC,
                break_schedule.earliest_start_time ASC
        ";

        try {
            $statement = $this->pdo->prepare($query);
            
            $statement->bindValue(':work_schedule_id', $workScheduleId, Helper::getPdoParameterType($workScheduleId));
            $statement->bindValue(':employee_id'     , $employeeId    , Helper::getPdoParameterType($employeeId    ));
            $statement->bindValue(':start_date'      , $startDate     , Helper::getPdoParameterType($startDate     ));
            $statement->bindValue(':end_date'        , $endDate       , Helper::getPdoParameterType($endDate       ));

            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching the ordered employee breaks. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(EmployeeBreak $employeeBreak): ActionResult
    {
        $query = "
            UPDATE employee_breaks
            SET
                break_schedule_id         = :break_schedule_id        ,
                start_time                = :start_time               ,
                end_time                  = :end_time                 ,
                break_duration_in_minutes = :break_duration_in_minutes
            WHERE
                id = :employee_break_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":break_schedule_id"        , $employeeBreak->getBreakScheduleId()       , Helper::getPdoParameterType($employeeBreak->getBreakScheduleId()       ));
            $statement->bindValue(":start_time"               , $employeeBreak->getStartTime()             , Helper::getPdoParameterType($employeeBreak->getStartTime()             ));
            $statement->bindValue(":end_time"                 , $employeeBreak->getEndTime()               , Helper::getPdoParameterType($employeeBreak->getEndTime()               ));
            $statement->bindValue(":break_duration_in_minutes", $employeeBreak->getBreakDurationInMinutes(), Helper::getPdoParameterType($employeeBreak->getBreakDurationInMinutes()));
            $statement->bindValue(":employee_break_id"        , $employeeBreak->getId()                    , Helper::getPdoParameterType($employeeBreak->getId()                    ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while updating the employee break record. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
