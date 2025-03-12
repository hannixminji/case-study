<?php

require_once __DIR__ . "/BreakType.php"                     ;
require_once __DIR__ . "/BreakTypeSnapshot.php"             ;

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

class BreakTypeDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(BreakType $breakType): ActionResult
    {
        $query = "
            INSERT INTO break_types (
                name                             ,
                duration_in_minutes              ,
                is_paid                          ,
                is_require_break_in_and_break_out
            )
            VALUES (
                :name                             ,
                :duration_in_minutes              ,
                :is_paid                          ,
                :is_require_break_in_and_break_out
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"                             , $breakType->getName()                    , Helper::getPdoParameterType($breakType->getName()                    ));
            $statement->bindValue(":duration_in_minutes"              , $breakType->getDurationInMinutes()       , Helper::getPdoParameterType($breakType->getDurationInMinutes()       ));
            $statement->bindValue(":is_paid"                          , $breakType->isPaid()                     , Helper::getPdoParameterType($breakType->isPaid()                     ));
            $statement->bindValue(":is_require_break_in_and_break_out", $breakType->isRequireBreakInAndBreakOut(), Helper::getPdoParameterType($breakType->isRequireBreakInAndBreakOut()));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the break type. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function createSnapshot(BreakTypeSnapshot $breakTypeSnapshot): int|ActionResult
    {
        $query = "
            INSERT INTO break_type_snapshots (
                break_type_id                    ,
                name                             ,
                duration_in_minutes              ,
                is_paid                          ,
                is_require_break_in_and_break_out
            )
            VALUES (
                :break_type_id                    ,
                :name                             ,
                :duration_in_minutes              ,
                :is_paid                          ,
                :is_require_break_in_and_break_out
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":break_type_id"                    , $breakTypeSnapshot->getBreakTypeId()             , Helper::getPdoParameterType($breakTypeSnapshot->getBreakTypeId()             ));
            $statement->bindValue(":name"                             , $breakTypeSnapshot->getName()                    , Helper::getPdoParameterType($breakTypeSnapshot->getName()                    ));
            $statement->bindValue(":duration_in_minutes"              , $breakTypeSnapshot->getDurationInMinutes()       , Helper::getPdoParameterType($breakTypeSnapshot->getDurationInMinutes()       ));
            $statement->bindValue(":is_paid"                          , $breakTypeSnapshot->isPaid()                     , Helper::getPdoParameterType($breakTypeSnapshot->isPaid()                     ));
            $statement->bindValue(":is_require_break_in_and_break_out", $breakTypeSnapshot->isRequireBreakInAndBreakOut(), Helper::getPdoParameterType($breakTypeSnapshot->isRequireBreakInAndBreakOut()));

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

            error_log("Database Error: An error occurred while creating the break type snapshot. " .
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
            "id"                                => "break_type.id                                AS id"                               ,
            "name"                              => "break_type.name                              AS name"                             ,
            "duration_in_minutes"               => "break_type.duration_in_minutes               AS duration_in_minutes"              ,
            "is_paid"                           => "break_type.is_paid                           AS is_paid"                          ,
            "is_require_break_in_and_break_out" => "break_type.is_require_break_in_and_break_out AS is_require_break_in_and_break_out",
            "created_at"                        => "break_type.created_at                        AS created_at"                       ,
            "updated_at"                        => "break_type.updated_at                        AS updated_at"                       ,
            "deleted_at"                        => "break_type.deleted_at                        AS deleted_at"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns
                ));

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "break_type.deleted_at IS NULL";

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
                break_types AS break_type
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
                        COUNT(break_type.id)
                    FROM
                        break_types AS break_type
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
            error_log("Database Error: An error occurred while fetching the break types. " .
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

    public function fetchLatestSnapshotById(int $breakTypeId): array|ActionResult
    {
        $query = "
            SELECT
                id                               ,
                name                             ,
                duration_in_minutes              ,
                is_paid                          ,
                is_require_break_in_and_break_out
            FROM
                break_type_snapshots
            WHERE
                break_type_id = :break_type_id
            ORDER BY
                active_at DESC
            LIMIT 1
        ";

        try {
            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":break_type_id", $breakTypeId, Helper::getPdoParameterType($breakTypeId));

            $statement->execute();

            return $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching the break type snapshot. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(BreakType $breakType): ActionResult
    {
        $query = "
            UPDATE break_types
            SET
                name                              = :name                             ,
                duration_in_minutes               = :duration_in_minutes              ,
                is_paid                           = :is_paid                          ,
                is_require_break_in_and_break_out = :is_require_break_in_and_break_out
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $breakType->getId())) {
            $query .= "id = :break_type_id";
        } else {
            $query .= "SHA2(id, 256) = :break_type_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"                             , $breakType->getName()                    , Helper::getPdoParameterType($breakType->getName()                    ));
            $statement->bindValue(":duration_in_minutes"              , $breakType->getDurationInMinutes()       , Helper::getPdoParameterType($breakType->getDurationInMinutes()       ));
            $statement->bindValue(":is_paid"                          , $breakType->isPaid()                     , Helper::getPdoParameterType($breakType->isPaid()                     ));
            $statement->bindValue(":is_require_break_in_and_break_out", $breakType->isRequireBreakInAndBreakOut(), Helper::getPdoParameterType($breakType->isRequireBreakInAndBreakOut()));

            $statement->bindValue(":break_type_id"                    , $breakType->getId()                      , Helper::getPdoParameterType($breakType->getId()                      ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while updating the break type. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $breakTypeId): ActionResult
    {
        return $this->softDelete($breakTypeId);
    }

    private function softDelete(int|string $breakTypeId): ActionResult
    {
        $query = "
            UPDATE break_types
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $breakTypeId)) {
            $query .= "id = :break_type_id";
        } else {
            $query .= "SHA2(id, 256) = :break_type_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":break_type_id", $breakTypeId, Helper::getPdoParameterType($breakTypeId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while deleting the break type. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
