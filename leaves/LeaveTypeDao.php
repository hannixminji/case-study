<?php

require_once __DIR__ . '/../includes/Helper.php'            ;
require_once __DIR__ . '/../includes/enums/ActionResult.php';
require_once __DIR__ . '/../includes/enums/ErrorCode.php'   ;

class LeaveTypeDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(LeaveType $leaveType, int $userId): ActionResult
    {
        $query = '
            INSERT INTO leave_types (
                name                  ,
                maximum_number_of_days,
                is_paid               ,
                description           ,
                status                ,
                created_by            ,
                updated_by
            )
            VALUES (
                :name                  ,
                :maximum_number_of_days,
                :is_paid               ,
                :description           ,
                :status                ,
                :created_by            ,
                :updated_by
            )
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':name'                  , $leaveType->getName()               , Helper::getPdoParameterType($leaveType->getName()               ));
            $statement->bindValue(':maximum_number_of_days', $leaveType->getMaximumNumberOfDays(), Helper::getPdoParameterType($leaveType->getMaximumNumberOfDays()));
            $statement->bindValue(':is_paid'               , $leaveType->isPaid()                , Helper::getPdoParameterType($leaveType->isPaid()                ));
            $statement->bindValue(':description'           , $leaveType->getDescription()        , Helper::getPdoParameterType($leaveType->getDescription()        ));
            $statement->bindValue(':status'                , $leaveType->getStatus()             , Helper::getPdoParameterType($leaveType->getStatus()             ));
            $statement->bindValue(':created_by'            , $userId                             , Helper::getPdoParameterType($userId                             ));
            $statement->bindValue(':updated_by'            , $userId                             , Helper::getPdoParameterType($userId                             ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while creating the leave type. ' .
                      'Exception: ' . $exception->getMessage());

            if ( (int) $exception->getCode() === ErrorCode::DUPLICATE_ENTRY->value) {
                return ActionResult::DUPLICATE_ENTRY_ERROR;
            }

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
            "id"                     => "leave_type.id                     AS id"                    ,
            "name"                   => "leave_type.name                   AS name"                  ,
            "maximum_number_of_days" => "leave_type.maximum_number_of_days AS maximum_number_of_days",
            "is_paid"                => "leave_type.is_paid                AS is_paid"               ,
            "description"            => "leave_type.description            AS description"           ,
            "status"                 => "leave_type.status                 AS status"                ,
            "created_at"             => "leave_type.created_at             AS created_at"            ,
            "created_by"             => "created_by_admin.username         AS created_by"            ,
            "updated_at"             => "leave_type.updated_at             AS updated_at"            ,
            "updated_by"             => "updated_by_admin.username         AS updated_by"            ,
            "deleted_at"             => "leave_type.deleted_at             AS deleted_at"            ,
            "deleted_by"             => "deleted_by_admin.username         AS deleted_by"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("created_by", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    admins AS created_by_admin
                ON
                    leave_type.created_by = created_by_admin.id
            ";
        }

        if (array_key_exists("updated_by", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    admins AS updated_by_admin
                ON
                    leave_type.updated_by = updated_by_admin.id
            ";
        }

        if (array_key_exists("deleted_by", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    admins AS deleted_by_admin
                ON
                    leave_type.deleted_by = deleted_by_admin.id
            ";
        }

        $queryParameters = [];
        $whereClauses = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "leave_type.status <> 'Archived'";
        } else {
            foreach ($filterCriteria as $filterCriterion) {
                $column   = $filterCriterion["column"];
                $operator = $filterCriterion["operator"];

                switch ($operator) {
                    case "=":
                    case "LIKE":
                        $whereClauses   [] = "{$column} {$operator} ?";
                        $queryParameters[] = $filterCriterion["value"];
                        break;

                    case "BETWEEN":
                        $whereClauses   [] = "{$column} {$operator} ? AND ?";
                        $queryParameters[] = $filterCriterion["lower_bound"];
                        $queryParameters[] = $filterCriterion["upper_bound"];
                        break;

                    default:
                        // Do nothing
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

        $limitClause  = ($limit  !== null) ? " LIMIT :limit"   : "";
        $offsetClause = ($offset !== null) ? " OFFSET :offset" : "";

        $query = "
            SELECT
                " . implode(", ", $selectedColumns) . "
            FROM
                leave_types AS leave_type
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

            if ($limit !== null) {
                $statement->bindValue(":limit", $limit, Helper::getPdoParameterType($limit));
            }

            if ($offset !== null) {
                $statement->bindValue(":offset", $offset, Helper::getPdoParameterType($offset));
            }

            $statement->execute();

            $resultSet = [];
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $resultSet[] = $row;
            }

            return $resultSet;

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching the leave types. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(LeaveType $leaveType, int $userId): ActionResult
    {
        $query = '
            UPDATE leave_types
            SET
                name                   = :name                  ,
                maximum_number_of_days = :maximum_number_of_days,
                is_paid                = :is_paid               ,
                description            = :description           ,
                status                 = :status                ,
                updated_by             = :updated_by
            WHERE
                id = :leave_type_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':name'                  , $leaveType->getName()               , Helper::getPdoParameterType($leaveType->getName()               ));
            $statement->bindValue(':maximum_number_of_days', $leaveType->getMaximumNumberOfDays(), Helper::getPdoParameterType($leaveType->getMaximumNumberOfDays()));
            $statement->bindValue(':is_paid'               , $leaveType->isPaid()                , Helper::getPdoParameterType($leaveType->isPaid()                ));
            $statement->bindValue(':description'           , $leaveType->getDescription()        , Helper::getPdoParameterType($leaveType->getDescription()        ));
            $statement->bindValue(':status'                , $leaveType->getStatus()             , Helper::getPdoParameterType($leaveType->getStatus()             ));
            $statement->bindValue(':updated_by'            , $userId                             , Helper::getPdoParameterType($userId                             ));
            $statement->bindValue(':leave_type_id'         , $leaveType->getId()                 , Helper::getPdoParameterType($leaveType->getId()                 ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while updating the leave type. ' .
                    'Exception: ' . $exception->getMessage());

            if ( (int) $exception->getCode() === ErrorCode::DUPLICATE_ENTRY->value) {
                return ActionResult::DUPLICATE_ENTRY_ERROR;
            }

            return ActionResult::FAILURE;
        }
    }

    public function delete(int $leaveTypeId, int $userId): ActionResult
    {
        return $this->softDelete($leaveTypeId, $userId);
    }

    private function softDelete(int $leaveTypeId, int $userId): ActionResult
    {
        $query = '
            UPDATE leave_types
            SET
                status     = "Archived"       ,
                deleted_at = CURRENT_TIMESTAMP,
                deleted_by = :deleted_by
            WHERE
                id = :leave_type_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':deleted_by'   , $userId     , Helper::getPdoParameterType($userId     ));
            $statement->bindValue(':leave_type_id', $leaveTypeId, Helper::getPdoParameterType($leaveTypeId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while deleting the leave type. ' .
                      'Exception: ' . $exception->getMessage());

            return ActionResult::FAILURE;
        }
    }
}
