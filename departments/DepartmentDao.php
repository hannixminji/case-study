<?php

require_once __DIR__ . '/../includes/Helper.php'            ;
require_once __DIR__ . '/../includes/enums/ActionResult.php';
require_once __DIR__ . '/../includes/enums/ErrorCode.php'   ;

class DepartmentDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Department $department, int $userId): ActionResult
    {
        $query = '
            INSERT INTO departments (
                name              ,
                department_head_id,
                description       ,
                status            ,
                created_by        ,
                updated_by
            )
            VALUES (
                :name              ,
                :department_head_id,
                :description       ,
                :status            ,
                :created_by        ,
                :updated_by
            )
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':name'              , $department->getName()            , Helper::getPdoParameterType($department->getName()            ));
            $statement->bindValue(':department_head_id', $department->getDepartmentHeadId(), Helper::getPdoParameterType($department->getDepartmentHeadId()));
            $statement->bindValue(':description'       , $department->getDescription()     , Helper::getPdoParameterType($department->getDescription()     ));
            $statement->bindValue(':status'            , $department->getStatus()          , Helper::getPdoParameterType($department->getStatus()          ));
            $statement->bindValue(':created_by'        , $userId                           , Helper::getPdoParameterType($userId                           ));
            $statement->bindValue(':updated_by'        , $userId                           , Helper::getPdoParameterType($userId                           ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while creating the department. ' .
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
            "id"                          => "department.id               AS id"                         ,
            "name"                        => "department.name             AS name"                       ,
            "department_head_id"          => "department_head.id          AS department_head_id"         ,
            "department_head_first_name"  => "department_head.first_name  AS department_head_first_name" ,
            "department_head_middle_name" => "department_head.middle_name AS department_head_middle_name",
            "department_head_last_name"   => "department_head.last_name   AS department_head_last_name"  ,
            "description"                 => "department.description      AS description"                ,
            "status"                      => "department.status           AS status"                     ,
            "created_at"                  => "department.created_at       AS created_at"                 ,
            "created_by"                  => "created_by_admin.username   AS created_by"                 ,
            "updated_at"                  => "department.updated_at       AS updated_at"                 ,
            "updated_by"                  => "updated_by_admin.username   AS updated_by"                 ,
            "deleted_at"                  => "department.deleted_at       AS deleted_at"                 ,
            "deleted_by"                  => "deleted_by_admin.username   AS deleted_by"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("department_head_id"         , $selectedColumns) ||
            array_key_exists("department_head_first_name" , $selectedColumns) ||
            array_key_exists("department_head_middle_name", $selectedColumns) ||
            array_key_exists("department_head_last_name"  , $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    employees AS department_head
                ON
                    department.department_head_id = department_head.id
            ";
        }

        if (array_key_exists("created_by", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    admins AS created_by_admin
                ON
                    department.created_by = created_by_admin.id
            ";
        }

        if (array_key_exists("updated_by", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    admins AS updated_by_admin
                ON
                    department.updated_by = updated_by_admin.id
            ";
        }

        if (array_key_exists("deleted_by", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    admins AS deleted_by_admin
                ON
                    department.deleted_by = deleted_by_admin.id
            ";
        }

        $queryParameters = [];

        $whereClauses = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "department.status <> 'Archived'";
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

                    default:
                        // Do nothing
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

        $limitClause  = ($limit  !== null) ? " LIMIT :limit"   : "";
        $offsetClause = ($offset !== null) ? " OFFSET :offset" : "";

        $query = "
            SELECT
                " . implode(", ", $selectedColumns) . "
            FROM
                departments AS department
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
            error_log("Database Error: An error occurred while fetching the departments. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(Department $department, int $userId): ActionResult
    {
        $query = '
            UPDATE departments
            SET
                name               = :name              ,
                department_head_id = :department_head_id,
                description        = :description       ,
                status             = :status            ,
                updated_by         = :updated_by
            WHERE
                id = :department_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':name'              , $department->getName()            , Helper::getPdoParameterType($department->getName()            ));
            $statement->bindValue(':department_head_id', $department->getDepartmentHeadId(), Helper::getPdoParameterType($department->getDepartmentHeadId()));
            $statement->bindValue(':description'       , $department->getDescription()     , Helper::getPdoParameterType($department->getDescription()     ));
            $statement->bindValue(':status'            , $department->getStatus()          , Helper::getPdoParameterType($department->getStatus()          ));
            $statement->bindValue(':updated_by'        , $userId                           , Helper::getPdoParameterType($userId                           ));
            $statement->bindValue(':department_id'     , $department->getId()              , Helper::getPdoParameterType($department->getId()              ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while updating the department. ' .
                      'Exception: ' . $exception->getMessage());

            if ( (int) $exception->getCode() === ErrorCode::DUPLICATE_ENTRY->value) {
                return ActionResult::DUPLICATE_ENTRY_ERROR;
            }

            return ActionResult::FAILURE;
        }
    }

    public function delete(int $departmentId, int $userId): ActionResult
    {
        return $this->softDelete($departmentId, $userId);
    }

    private function softDelete(int $departmentId, int $userId): ActionResult
    {
        $query = '
            UPDATE departments
            SET
                status     = "Archived"       ,
                deleted_at = CURRENT_TIMESTAMP,
                deleted_by = :deleted_by
            WHERE
                id = :department_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':deleted_by'   , $userId      , Helper::getPdoParameterType($userId      ));
            $statement->bindValue(':department_id', $departmentId, Helper::getPdoParameterType($departmentId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while deleting the department. ' .
                      'Exception: ' . $exception->getMessage());

            return ActionResult::FAILURE;
        }
    }
}
