<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

class BreakScheduleDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(BreakSchedule $breakSchedule): ActionResult
    {
        $query = "
            INSERT INTO break_schedules (
                work_schedule_id   ,
                break_type_id      ,
                start_time         ,
                is_flexible        ,
                earliest_start_time,
                latest_end_time
            )
            VALUES (
                :work_schedule_id   ,
                :break_type_id      ,
                :start_time         ,
                :is_flexible        ,
                :earliest_start_time,
                :latest_end_time
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":work_schedule_id"   , $breakSchedule->getWorkScheduleId()   , Helper::getPdoParameterType($breakSchedule->getWorkScheduleId()   ));
            $statement->bindValue(":break_type_id"      , $breakSchedule->getBreakTypeId()      , Helper::getPdoParameterType($breakSchedule->getBreakTypeId()      ));
            $statement->bindValue(":start_time"         , $breakSchedule->getStartTime()        , Helper::getPdoParameterType($breakSchedule->getStartTime()        ));
            $statement->bindValue(":is_flexible"        , $breakSchedule->isFlexible()          , Helper::getPdoParameterType($breakSchedule->isFlexible()          ));
            $statement->bindValue(":earliest_start_time", $breakSchedule->getEarliestStartTime(), Helper::getPdoParameterType($breakSchedule->getEarliestStartTime()));
            $statement->bindValue(":latest_end_time"    , $breakSchedule->getLatestEndTime()    , Helper::getPdoParameterType($breakSchedule->getLatestEndTime()    ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the break schedule. " .
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
            "id"                                => "break_schedule.id                            AS id"                               ,
            "work_schedule_id"                  => "break_schedule.work_schedule_id              AS work_schedule_id"                 ,

            "break_type_id"                     => "break_schedule.break_type_id                 AS break_type_id"                    ,
            "break_type_name"                   => "break_type.name                              AS break_type_name"                  ,
            "break_type_duration_in_minutes"    => "break_type.duration_in_minutes               AS break_type_duration_in_minutes"   ,
            "break_type_is_paid"                => "break_type.is_paid                           AS break_type_is_paid"               ,
            "is_require_break_in_and_break_out" => "break_type.is_require_break_in_and_break_out AS is_require_break_in_and_break_out",
            "break_type_deleted_at"             => "break_type.deleted_at                        AS break_type_deleted_at"            ,

            "start_time"                        => "break_schedule.start_time                    AS start_time"                       ,
            "is_flexible"                       => "break_schedule.is_flexible                   AS is_flexible"                      ,
            "earliest_start_time"               => "break_schedule.earliest_start_time           AS earliest_start_time"              ,
            "latest_end_time"                   => "break_schedule.latest_end_time               AS latest_end_time"                  ,
            "created_at"                        => "break_schedule.created_at                    AS created_at"                       ,
            "updated_at"                        => "break_schedule.updated_at                    AS updated_at"                       ,
            "deleted_at"                        => "break_schedule.deleted_at                    AS deleted_at"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("break_type_name"                  , $selectedColumns) ||
            array_key_exists("break_type_duration_in_minutes"   , $selectedColumns) ||
            array_key_exists("break_type_is_paid"               , $selectedColumns) ||
            array_key_exists("is_require_break_in_and_break_out", $selectedColumns) ||
            array_key_exists("break_type_deleted_at"            , $selectedColumns)) {
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
            $whereClauses[] = "break_schedule.deleted_at IS NULL";
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
                break_schedules AS break_schedule
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
            error_log("Database Error: An error occurred while fetching the break schedules. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(BreakSchedule $breakSchedule, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE break_schedules
            SET
                start_time          = :start_time         ,
                is_flexible         = :is_flexible        ,
                earliest_start_time = :earliest_start_time,
                latest_end_time     = :latest_end_time
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :break_schedule_id";
        } else {
            $query .= " id = :break_schedule_id";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":start_time"         , $breakSchedule->getStartTime()        , Helper::getPdoParameterType($breakSchedule->getStartTime()        ));
            $statement->bindValue(":is_flexible"        , $breakSchedule->isFlexible()          , Helper::getPdoParameterType($breakSchedule->isFlexible()          ));
            $statement->bindValue(":earliest_start_time", $breakSchedule->getEarliestStartTime(), Helper::getPdoParameterType($breakSchedule->getEarliestStartTime()));
            $statement->bindValue(":latest_end_time"    , $breakSchedule->getLatestEndTime()    , Helper::getPdoParameterType($breakSchedule->getLatestEndTime()    ));
            $statement->bindValue(":break_schedule_id"  , $breakSchedule->getId()               , Helper::getPdoParameterType($breakSchedule->getId()               ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while updating the break schedule. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function fetchOrderedBreakSchedules(int|string $workScheduleId, bool $isHashedId = false): ActionResult|array
    {
        $query = "
            SELECT
                break_schedule.id                  As id                            ,
                break_type.duration_in_minutes     AS break_type_duration_in_minutes,
                break_type.is_paid                 As break_type_is_paid            ,
                break_type.deleted_at              AS break_type_deleted_at         ,
                break_schedule.start_time          AS start_time                    ,
                break_schedule.is_flexible         AS is_flexible                   ,
                break_schedule.earliest_start_time AS earliest_start_time           ,
                break_schedule.latest_end_time     As latest_end_time
            FROM
                break_schedules AS break_schedule
            LEFT JOIN
                break_types AS break_type
            ON
                break_schedule.break_type_id = break_type.id
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(break_schedule.work_schedule_id, 256) = :work_schedule_id";
        } else {
            $query .= " break_schedule.work_schedule_id = :work_schedule_id";
        }

        $query .= "
            AND
                break_type.deleted_at IS NULL
            AND
                break_schedule.deleted_at IS NULL
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

            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching the ordered break schedules. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function deleteByWorkScheduleId(int|string $workScheduleId, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE break_schedules
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(work_schedule_id, 256) = :work_schedule_id";
        } else {
            $query .= " work_schedule_id = :work_schedule_id";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":work_schedule_id", $workScheduleId, Helper::getPdoParameterType($workScheduleId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while deleting break schedules by work schedule ID. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $breakScheduleId, bool $isHashedId = false): ActionResult
    {
        return $this->softDelete($breakScheduleId, $isHashedId);
    }

    private function softDelete(int|string $breakScheduleId, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE break_schedules
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :break_schedule_id";
        } else {
            $query .= " id = :break_schedule_id";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":break_schedule_id", $breakScheduleId, Helper::getPdoParameterType($breakScheduleId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while deleting the break schedule. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
