<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

class HolidayDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Holiday $holiday): ActionResult
    {
        $query = "
            INSERT INTO holidays (
                name                 ,
                start_date           ,
                end_date             ,
                is_paid              ,
                is_recurring_annually,
                description          ,
                status
            )
            VALUES (
                :name                 ,
                :start_date           ,
                :end_date             ,
                :is_paid              ,
                :is_recurring_annually,
                :description          ,
                :status
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"                 , $holiday->getName()               , Helper::getPdoParameterType($holiday->getName()               ));
            $statement->bindValue(":start_date"           , $holiday->getStartDate()          , Helper::getPdoParameterType($holiday->getStartDate()          ));
            $statement->bindValue(":end_date"             , $holiday->getEndDate()            , Helper::getPdoParameterType($holiday->getEndDate()            ));
            $statement->bindValue(":is_paid"              , $holiday->getIsPaid()             , Helper::getPdoParameterType($holiday->getIsPaid()             ));
            $statement->bindValue(":is_recurring_annually", $holiday->getIsRecurringAnnually(), Helper::getPdoParameterType($holiday->getIsRecurringAnnually()));
            $statement->bindValue(":description"          , $holiday->getDescription()        , Helper::getPdoParameterType($holiday->getDescription()        ));
            $statement->bindValue(":status"               , $holiday->getStatus()             , Helper::getPdoParameterType($holiday->getStatus()             ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the holiday. " .
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
            "id"                    => "holiday.id                    AS id"                   ,
            "name"                  => "holiday.name                  AS name"                 ,
            "start_date"            => "holiday.start_date            AS start_date"           ,
            "end_date"              => "holiday.end_date              AS end_date"             ,
            "is_paid"               => "holiday.is_paid               AS is_paid"              ,
            "is_recurring_annually" => "holiday.is_recurring_annually AS is_recurring_annually",
            "description"           => "holiday.description           AS description"          ,
            "status"                => "holiday.status                AS status"               ,
            "created_at"            => "holiday.created_at            AS created_at"           ,
            "updated_at"            => "holiday.updated_at            AS updated_at"           ,
            "deleted_at"            => "holiday.deleted_at            AS deleted_at"           ,
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
            $whereClauses[] = "holiday.deleted_at IS NULL";

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
                holidays AS holiday
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
                        COUNT(holiday.id)
                    FROM
                        holidays AS holiday
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
            error_log("Database Error: An error occurred while fetching the holidays. " .
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

    public function update(Holiday $holiday): ActionResult
    {
        $query = "
            UPDATE holidays
            SET
                name                  = :name                 ,
                start_date            = :start_date           ,
                end_date              = :end_date             ,
                is_paid               = :is_paid              ,
                is_recurring_annually = :is_recurring_annually,
                description           = :description          ,
                status                = :status
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $holiday->getId())) {
            $query .= "id = :holiday_id";
        } else {
            $query .= "SHA2(id, 256) = :holiday_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"                 , $holiday->getName()               , Helper::getPdoParameterType($holiday->getName()               ));
            $statement->bindValue(":start_date"           , $holiday->getStartDate()          , Helper::getPdoParameterType($holiday->getStartDate()          ));
            $statement->bindValue(":end_date"             , $holiday->getEndDate()            , Helper::getPdoParameterType($holiday->getEndDate()            ));
            $statement->bindValue(":is_paid"              , $holiday->getIsPaid()             , Helper::getPdoParameterType($holiday->getIsPaid()             ));
            $statement->bindValue(":is_recurring_annually", $holiday->getIsRecurringAnnually(), Helper::getPdoParameterType($holiday->getIsRecurringAnnually()));
            $statement->bindValue(":description"          , $holiday->getDescription()        , Helper::getPdoParameterType($holiday->getDescription()        ));
            $statement->bindValue(":status"               , $holiday->getStatus()             , Helper::getPdoParameterType($holiday->getStatus()             ));

            $statement->bindValue(":holiday_id"           , $holiday->getId()                 , Helper::getPdoParameterType($holiday->getId()                 ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while updating the holiday. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $holidayId): ActionResult
    {
        return $this->softDelete($holidayId);
    }

    private function softDelete(int|string $holidayId): ActionResult
    {
        $query = "
            UPDATE holidays
            SET
                status     = 'Archived'       ,
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if (preg_match("/^[1-9]\d*$/", $holidayId)) {
            $query .= "id = :holiday_id";
        } else {
            $query .= "SHA2(id, 256) = :holiday_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":holiday_id", $holidayId, Helper::getPdoParameterType($holidayId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while deleting the holiday. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
