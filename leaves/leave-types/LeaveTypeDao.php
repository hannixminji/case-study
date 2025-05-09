<?php

require_once __DIR__ . "/LeaveType.php"                        ;

require_once __DIR__ . "/../../includes/Helper.php"            ;
require_once __DIR__ . "/../../includes/enums/ActionResult.php";

class LeaveTypeDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(LeaveType $leaveType): ActionResult
    {
        $query = "
            INSERT INTO leave_types (
                name                  ,
                maximum_number_of_days,
                is_paid               ,
                is_encashable         ,
                description           ,
                status
            )
            VALUES (
                :name                  ,
                :maximum_number_of_days,
                :is_paid               ,
                :is_encashable         ,
                :description           ,
                :status
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"                  , $leaveType->getName()               , Helper::getPdoParameterType($leaveType->getName()               ));
            $statement->bindValue(":maximum_number_of_days", $leaveType->getMaximumNumberOfDays(), Helper::getPdoParameterType($leaveType->getMaximumNumberOfDays()));
            $statement->bindValue(":is_paid"               , $leaveType->isPaid()                , Helper::getPdoParameterType($leaveType->isPaid()                ));
            $statement->bindValue(":is_encashable"         , $leaveType->isEncashable()          , Helper::getPdoParameterType($leaveType->isEncashable()          ));
            $statement->bindValue(":description"           , $leaveType->getDescription()        , Helper::getPdoParameterType($leaveType->getDescription()        ));
            $statement->bindValue(":status"                , $leaveType->getStatus()             , Helper::getPdoParameterType($leaveType->getStatus()             ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the leave type. " .
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
            "id"                     => "leave_type.id                     AS id"                    ,
            "name"                   => "leave_type.name                   AS name"                  ,
            "maximum_number_of_days" => "leave_type.maximum_number_of_days AS maximum_number_of_days",
            "is_paid"                => "leave_type.is_paid                AS is_paid"               ,
            "is_encashable"          => "leave_type.is_encashable          AS is_encashable"         ,
            "description"            => "leave_type.description            AS description"           ,
            "status"                 => "leave_type.status                 AS status"                ,
            "created_at"             => "leave_type.created_at             AS created_at"            ,
            "updated_at"             => "leave_type.updated_at             AS updated_at"            ,
            "deleted_at"             => "leave_type.deleted_at             AS deleted_at"            ,
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "leave_type.deleted_at IS NULL";

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
                leave_types AS leave_type
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
                        COUNT(leave_type.id)
                    FROM
                        leave_types AS leave_type
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
            error_log("Database Error: An error occurred while fetching the leave types. " .
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
                if (is_array($condition) && ! isset($filterCriterion["operator"])) {
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
                    case ">"   :
                    case "<"   :
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

    public function update(LeaveType $leaveType): ActionResult
    {
        $query = "
            UPDATE leave_types
            SET
                name                   = :name                  ,
                maximum_number_of_days = :maximum_number_of_days,
                is_paid                = :is_paid               ,
                is_encashable          = :is_encashable         ,
                description            = :description           ,
                status                 = :status
            WHERE
        ";

        if (filter_var($leaveType->getId(), FILTER_VALIDATE_INT) !== false) {
            $query .= "id = :leave_type_id";
        } else {
            $query .= "SHA2(id, 256) = :leave_type_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"                  , $leaveType->getName()               , Helper::getPdoParameterType($leaveType->getName()               ));
            $statement->bindValue(":maximum_number_of_days", $leaveType->getMaximumNumberOfDays(), Helper::getPdoParameterType($leaveType->getMaximumNumberOfDays()));
            $statement->bindValue(":is_paid"               , $leaveType->isPaid()                , Helper::getPdoParameterType($leaveType->isPaid()                ));
            $statement->bindValue(":is_encashable"         , $leaveType->isEncashable()          , Helper::getPdoParameterType($leaveType->isEncashable()          ));
            $statement->bindValue(":description"           , $leaveType->getDescription()        , Helper::getPdoParameterType($leaveType->getDescription()        ));
            $statement->bindValue(":status"                , $leaveType->getStatus()             , Helper::getPdoParameterType($leaveType->getStatus()             ));

            $statement->bindValue(":leave_type_id"         , $leaveType->getId()                 , Helper::getPdoParameterType($leaveType->getId()                 ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while updating the leave type. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $leaveTypeId): ActionResult
    {
        return $this->softDelete($leaveTypeId);
    }

    private function softDelete(int|string $leaveTypeId): ActionResult
    {
        $query = "
            UPDATE leave_types
            SET
                status     = 'Archived'       ,
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if (filter_var($leaveTypeId, FILTER_VALIDATE_INT) !== false) {
            $query .= "id = :leave_type_id";
        } else {
            $query .= "SHA2(id, 256) = :leave_type_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":leave_type_id", $leaveTypeId, Helper::getPdoParameterType($leaveTypeId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while deleting the leave type. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
