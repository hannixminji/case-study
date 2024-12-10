<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

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

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"                 , $holiday->getName()               , Helper::getPdoParameterType($holiday->getName()               ));
            $statement->bindValue(":start_date"           , $holiday->getStartDate()          , Helper::getPdoParameterType($holiday->getStartDate()          ));
            $statement->bindValue(":end_date"             , $holiday->getEndDate()            , Helper::getPdoParameterType($holiday->getEndDate()            ));
            $statement->bindValue(":is_paid"              , $holiday->getIsPaid()             , Helper::getPdoParameterType($holiday->getIsPaid()             ));
            $statement->bindValue(":is_recurring_annually", $holiday->getIsRecurringAnnually(), Helper::getPdoParameterType($holiday->getIsRecurringAnnually()));
            $statement->bindValue(":description"          , $holiday->getDescription()        , Helper::getPdoParameterType($holiday->getDescription()        ));
            $statement->bindValue(":status"               , $holiday->getStatus()             , Helper::getPdoParameterType($holiday->getStatus()             ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the holiday. " .
                      "Exception: {$exception->getMessage()}");

            if ( (int) $exception->getCode() === ErrorCode::DUPLICATE_ENTRY->value) {
                return ActionResult::DUPLICATE_ENTRY_ERROR;
            }

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

        $queryParameters = [];

        $whereClauses = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "holiday.status <> 'Archived'";
        } else {
            foreach ($filterCriteria as $filterCriterion) {
                $column   = $filterCriterion["column"  ];
                $operator = $filterCriterion["operator"];

                switch ($operator) {
                    case "="   :
                    case ">="  :
                    case "<="  :
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
                holidays AS holiday
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
            error_log("Database Error: An error occurred while fetching the holidays. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
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
                id = :holiday_id
        ";

        try {
            $this->pdo->beginTransaction();

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

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while updating the holiday. " .
                      "Exception: {$exception->getMessage()}");

            if ( (int) $exception->getCode() === ErrorCode::DUPLICATE_ENTRY->value) {
                return ActionResult::DUPLICATE_ENTRY_ERROR;
            }

            return ActionResult::FAILURE;
        }
    }

    public function delete(int $holidayId): ActionResult
    {
        return $this->softDelete($holidayId);
    }

    private function softDelete(int $holidayId): ActionResult
    {
        $query = "
            UPDATE holidays
            SET
                status     = 'Archived'       ,
                deleted_at = CURRENT_TIMESTAMP
            WHERE
                id = :holiday_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":holiday_id", $holidayId, Helper::getPdoParameterType($holidayId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while deleting the holiday. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
