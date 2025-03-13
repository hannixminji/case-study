<?php

require_once __DIR__ . "/BreakSchedule.php"                 ;
require_once __DIR__ . "/BreakScheduleSnapshot.php"         ;

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

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
                work_schedule_id,
                break_type_id   ,
                start_time      ,
                end_time
            )
            VALUES (
                :work_schedule_id,
                :break_type_id   ,
                :start_time      ,
                :end_time
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":work_schedule_id", $breakSchedule->getWorkScheduleId(), Helper::getPdoParameterType($breakSchedule->getWorkScheduleId()));
            $statement->bindValue(":break_type_id"   , $breakSchedule->getBreakTypeId()   , Helper::getPdoParameterType($breakSchedule->getBreakTypeId()   ));
            $statement->bindValue(":start_time"      , $breakSchedule->getStartTime()     , Helper::getPdoParameterType($breakSchedule->getStartTime()     ));
            $statement->bindValue(":end_time"        , $breakSchedule->getEndTime()       , Helper::getPdoParameterType($breakSchedule->getEndTime()       ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the break schedule. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function createSnapshot(BreakScheduleSnapshot $breakScheduleSnapshot): int|ActionResult
    {
        $query = "
            INSERT INTO break_schedule_snapshots (
                break_schedule_id        ,
                work_schedule_snapshot_id,
                break_type_snapshot_id   ,
                start_time               ,
                end_time
            )
            VALUES (
                :break_schedule_id        ,
                :work_schedule_snapshot_id,
                :break_type_snapshot_id   ,
                :start_time               ,
                :end_time
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":break_schedule_id"        , $breakScheduleSnapshot->getBreakScheduleId()       , Helper::getPdoParameterType($breakScheduleSnapshot->getBreakScheduleId()       ));
            $statement->bindValue(":work_schedule_snapshot_id", $breakScheduleSnapshot->getWorkScheduleSnapshotId(), Helper::getPdoParameterType($breakScheduleSnapshot->getWorkScheduleSnapshotId()));
            $statement->bindValue(":break_type_snapshot_id"   , $breakScheduleSnapshot->getBreakTypeSnapshotId()   , Helper::getPdoParameterType($breakScheduleSnapshot->getBreakTypeSnapshotId()   ));
            $statement->bindValue(":start_time"               , $breakScheduleSnapshot->getStartTime()             , Helper::getPdoParameterType($breakScheduleSnapshot->getStartTime()             ));
            $statement->bindValue(":end_time"                 , $breakScheduleSnapshot->getEndTime()               , Helper::getPdoParameterType($breakScheduleSnapshot->getEndTime()               ));

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

            error_log("Database Error: An error occurred while creating the break schedule snapshot. " .
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
            "id"                                => "break_schedule.id                            AS id"                               ,
            "work_schedule_id"                  => "break_schedule.work_schedule_id              AS work_schedule_id"                 ,
            "break_type_id"                     => "break_schedule.break_type_id                 AS break_type_id"                    ,
            "start_time"                        => "break_schedule.start_time                    AS start_time"                       ,
            "end_time"                          => "break_schedule.end_time                      AS end_time"                         ,
            "created_at"                        => "break_schedule.created_at                    AS created_at"                       ,
            "updated_at"                        => "break_schedule.updated_at                    AS updated_at"                       ,
            "deleted_at"                        => "break_schedule.deleted_at                    AS deleted_at"                       ,

            "break_type_name"                   => "break_type.name                              AS break_type_name"                  ,
            "break_type_duration_in_minutes"    => "break_type.duration_in_minutes               AS break_type_duration_in_minutes"   ,
            "break_type_is_paid"                => "break_type.is_paid                           AS break_type_is_paid"               ,
            "is_require_break_in_and_break_out" => "break_type.is_require_break_in_and_break_out AS is_require_break_in_and_break_out",
            "break_type_deleted_at"             => "break_type.deleted_at                        AS break_type_deleted_at"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        if (array_key_exists("is_recorded", $columns)) {
            $selectedColumns["is_recorded"] = $columns["is_recorded"];
        }

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

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "break_schedule.deleted_at IS NULL";

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
                break_schedules AS break_schedule
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
                        COUNT(break_schedule.id)
                    FROM
                        break_schedules AS break_schedule
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
            error_log("Database Error: An error occurred while fetching the break schedules. " .
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

    public function fetchLatestSnapshotById(int $breakScheduleId): array|ActionResult
    {
        $query = "
            SELECT
                id                       ,
                work_schedule_snapshot_id,
                break_type_snapshot_id   ,
                start_time               ,
                end_time
            FROM
                break_schedule_snapshots
            WHERE
                break_schedule_id = :break_schedule_id
            ORDER BY
                active_at DESC
            LIMIT 1
        ";

        try {
            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":break_schedule_id", $breakScheduleId, Helper::getPdoParameterType($breakScheduleId));

            $statement->execute();

            return $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching the latest break schedule snapshot. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(BreakSchedule $breakSchedule): ActionResult
    {
        $query = "
            UPDATE break_schedules
            SET
                start_time = :start_time,
                end_time   = :end_time
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $breakSchedule->getId())) {
            $query .= "id = :break_schedule_id";
        } else {
            $query .= "SHA2(id, 256) = :break_schedule_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":start_time"       , $breakSchedule->getStartTime(), Helper::getPdoParameterType($breakSchedule->getStartTime()));
            $statement->bindValue(":end_time"         , $breakSchedule->getEndTime()  , Helper::getPdoParameterType($breakSchedule->getEndTime()  ));

            $statement->bindValue(":break_schedule_id", $breakSchedule->getId()       , Helper::getPdoParameterType($breakSchedule->getId()       ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while updating the break schedule. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $breakScheduleId): ActionResult
    {
        return $this->softDelete($breakScheduleId);
    }

    private function softDelete(int|string $breakScheduleId): ActionResult
    {
        $query = "
            UPDATE break_schedules
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $breakScheduleId)) {
            $query .= "id = :break_schedule_id";
        } else {
            $query .= "SHA2(id, 256) = :break_schedule_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":break_schedule_id", $breakScheduleId, Helper::getPdoParameterType($breakScheduleId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while deleting the break schedule. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
