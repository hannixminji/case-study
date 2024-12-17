<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

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

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"                             , $breakType->getName()                    , Helper::getPdoParameterType($breakType->getName()                    ));
            $statement->bindValue(":duration_in_minutes"              , $breakType->getDurationInMinutes()       , Helper::getPdoParameterType($breakType->getDurationInMinutes()       ));
            $statement->bindValue(":is_paid"                          , $breakType->isPaid()                     , Helper::getPdoParameterType($breakType->isPaid()                     ));
            $statement->bindValue(":is_require_break_in_and_break_out", $breakType->isRequireBreakInAndBreakOut(), Helper::getPdoParameterType($breakType->isRequireBreakInAndBreakOut()));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the break type. " .
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

        $queryParameters = [];

        $whereClauses = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "break_type.deleted_at IS NULL";
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
                break_types AS break_type
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
            error_log("Database Error: An error occurred while fetching the break types. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(BreakType $breakType, bool $isHashedId = false): ActionResult
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

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :break_type_id";
        } else {
            $query .= " id = :break_type_id";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"                             , $breakType->getName()                    , Helper::getPdoParameterType($breakType->getName()                    ));
            $statement->bindValue(":duration_in_minutes"              , $breakType->getDurationInMinutes()       , Helper::getPdoParameterType($breakType->getDurationInMinutes()       ));
            $statement->bindValue(":is_paid"                          , $breakType->isPaid()                     , Helper::getPdoParameterType($breakType->isPaid()                     ));
            $statement->bindValue(":break_type_id"                    , $breakType->getId()                      , Helper::getPdoParameterType($breakType->getId()                      ));
            $statement->bindValue(":is_require_break_in_and_break_out", $breakType->isRequireBreakInAndBreakOut(), Helper::getPdoParameterType($breakType->isRequireBreakInAndBreakOut()));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while updating the break type. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $breakTypeId, bool $isHashedId = false): ActionResult
    {
        return $this->softDelete($breakTypeId, $isHashedId);
    }

    private function softDelete(int|string $breakTypeId, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE break_types
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :break_type_id";
        } else {
            $query .= " id = :break_type_id";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":break_type_id", $breakTypeId, Helper::getPdoParameterType($breakTypeId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while deleting the break type. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
