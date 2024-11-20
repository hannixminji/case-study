<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

class ScheduleBreakDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(ScheduleBreak $scheduleBreak): ActionResult
    {
        $query = "
            INSERT INTO schedule_breaks (
                work_schedule_id,
                break_type_id   ,
                start_time
            )
            VALUES (
                :work_schedule_id,
                :break_type_id   ,
                :start_time
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":work_schedule_id", $scheduleBreak->getWorkScheduleId(), Helper::getPdoParameterType($scheduleBreak->getWorkScheduleId()));
            $statement->bindValue(":break_type_id"   , $scheduleBreak->getBreakTypeId()   , Helper::getPdoParameterType($scheduleBreak->getBreakTypeId()   ));
            $statement->bindValue(":start_time"      , $scheduleBreak->getStartTime()     , Helper::getPdoParameterType($scheduleBreak->getStartTime()     ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the schedule break. " .
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
            "id"                    => "schedule_break.id               AS id"                   ,
            "work_schedule_id"      => "schedule_break.work_schedule_id AS work_schedule_id"     ,

            "break_type_id"         => "schedule_break.break_type_id    AS break_type_id"        ,
            "break_type_name"       => "break_type.name                 AS break_type_name"      ,
            "break_type_duration"   => "break_type.duration_in_minutes  AS break_type_duration"  ,
            "break_type_is_paid"    => "break_type.is_paid              AS break_type_is_paid"   ,
            "break_type_deleted_at" => "break_type.deleted_at           AS break_type_deleted_at",

            "start_time"            => "schedule_break.start_time       AS start_time"           ,
            "created_at"            => "schedule_break.created_at       AS created_at"           ,
            "updated_at"            => "schedule_break.updated_at       AS updated_at"           ,
            "deleted_at"            => "schedule_break.deleted_at       AS deleted_at"           ,
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("break_type_name"      , $selectedColumns) ||
            array_key_exists("break_type_duration"  , $selectedColumns) ||
            array_key_exists("break_type_is_paid"   , $selectedColumns) ||
            array_key_exists("break_type_deleted_at", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    break_types AS break_type
                ON
                    schedule_break.break_type_id = break_type.id
            ";
        }

        $queryParameters = [];

        $whereClauses = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "schedule_break.deleted_at IS NULL";
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
                schedule_breaks AS schedule_break
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
            error_log("Database Error: An error occurred while fetching the schedule breaks. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(ScheduleBreak $scheduleBreak): ActionResult
    {
        $query = "
            UPDATE schedule_breaks
            SET
                start_time = :start_time
            WHERE
                id = :schedule_break_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":start_time"       , $scheduleBreak->getStartTime(), Helper::getPdoParameterType($scheduleBreak->getStartTime()));
            $statement->bindValue(":schedule_break_id", $scheduleBreak->getId()       , Helper::getPdoParameterType($scheduleBreak->getId()       ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while updating the schedule break. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int $scheduleBreakId): ActionResult
    {
        return $this->softDelete($scheduleBreakId);
    }

    public function deleteByWorkScheduleId(int $workScheduleId): ActionResult
    {
        $query = "
            UPDATE schedule_breaks
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
                work_schedule_id = :work_schedule_id
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

            error_log("Database Error: An error occurred while deleting schedule breaks by work schedule ID. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    private function softDelete(int $scheduleBreakId): ActionResult
    {
        $query = "
            UPDATE schedule_breaks
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
                id = :schedule_break_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":schedule_break_id", $scheduleBreakId, Helper::getPdoParameterType($scheduleBreakId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while deleting the schedule break. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
