<?php

require_once __DIR__ . '/../includes/Helper.php'            ;
require_once __DIR__ . '/../includes/enums/ActionResult.php';
require_once __DIR__ . '/../includes/enums/ErrorCode.php'   ;

class JobTitleDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(JobTitle $jobTitle, int $userId): ActionResult
    {
        $query = '
            INSERT INTO job_titles (
                title        ,
                department_id,
                description  ,
                status       ,
                created_by   ,
                updated_by
            )
            VALUES (
                :title        ,
                :department_id,
                :description  ,
                :status       ,
                :created_by   ,
                :updated_by
            )
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':title'        , $jobTitle->getTitle()       , Helper::getPdoParameterType($jobTitle->getTitle()       ));
            $statement->bindValue(':department_id', $jobTitle->getDepartmentId(), Helper::getPdoParameterType($jobTitle->getDepartmentId()));
            $statement->bindValue(':description'  , $jobTitle->getDescription() , Helper::getPdoParameterType($jobTitle->getDescription() ));
            $statement->bindValue(':status'       , $jobTitle->getStatus()      , Helper::getPdoParameterType($jobTitle->getStatus()      ));
            $statement->bindValue(':created_by'   , $userId                     , Helper::getPdoParameterType($userId                     ));
            $statement->bindValue(':updated_by'   , $userId                     , Helper::getPdoParameterType($userId                     ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while creating the job title. ' .
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
            "id"              => "job_title.id              AS id"             ,
            "title"           => "job_title.title           AS title"          ,
            "department_id"   => "department.id             AS department_id"  ,
            "department_name" => "department.name           AS department_name",
            "description"     => "job_title.description     AS description"    ,
            "status"          => "job_title.status          AS status"         ,
            "created_at"      => "job_title.created_at      AS created_at"     ,
            "created_by"      => "created_by_admin.username AS created_by"     ,
            "updated_at"      => "job_title.updated_at      AS updated_at"     ,
            "updated_by"      => "updated_by_admin.username AS updated_by"     ,
            "deleted_at"      => "job_title.deleted_at      AS deleted_at"     ,
            "deleted_by"      => "deleted_by_admin.username AS deleted_by"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("department_id", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    departments AS department
                ON
                    job_title.department_id = department.id
            ";
        }

        if (array_key_exists("created_by", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    admins AS created_by_admin
                ON
                    job_title.created_by = created_by_admin.id
            ";
        }

        if (array_key_exists("updated_by", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    admins AS updated_by_admin
                ON
                    job_title.updated_by = updated_by_admin.id
            ";
        }

        if (array_key_exists("deleted_by", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    admins AS deleted_by_admin
                ON
                    job_title.deleted_by = deleted_by_admin.id
            ";
        }

        $queryParameters = [];
        $whereClauses = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "job_title.status <> 'Archived'";
        } else {
            foreach ($filterCriteria as $filterCriterion) {
                $column   = $filterCriterion["column"];
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
                job_titles AS job_title
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
            error_log("Database Error: An error occurred while fetching the job titles. " .
                      "Exception: {$exception->getMessage()}");
            return ActionResult::FAILURE;
        }
    }

    public function update(JobTitle $jobTitle, int $userId): ActionResult
    {
        $query = '
            UPDATE job_titles
            SET
                title         = :title        ,
                department_id = :department_id,
                description   = :description  ,
                status        = :status       ,
                updated_by    = :updated_by
            WHERE
                id = :job_title_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':title'        , $jobTitle->getTitle()       , Helper::getPdoParameterType($jobTitle->getTitle()       ));
            $statement->bindValue(':department_id', $jobTitle->getDepartmentId(), Helper::getPdoParameterType($jobTitle->getDepartmentId()));
            $statement->bindValue(':description'  , $jobTitle->getDescription() , Helper::getPdoParameterType($jobTitle->getDescription() ));
            $statement->bindValue(':status'       , $jobTitle->getStatus()      , Helper::getPdoParameterType($jobTitle->getStatus()      ));
            $statement->bindValue(':updated_by'   , $userId                     , Helper::getPdoParameterType($userId                     ));
            $statement->bindValue(':job_title_id' , $jobTitle->getId()          , Helper::getPdoParameterType($jobTitle->getId()          ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while updating the job title. ' .
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

    private function softDelete(int $jobTitleId, int $userId): ActionResult
    {
        $query = '
            UPDATE job_titles
            SET
                status     = "Archived"       ,
                deleted_at = CURRENT_TIMESTAMP,
                deleted_by = :deleted_by
            WHERE
                id = :job_title_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':deleted_by'  , $userId    , Helper::getPdoParameterType($userId    ));
            $statement->bindValue(':job_title_id', $jobTitleId, Helper::getPdoParameterType($jobTitleId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while deleting the job title. ' .
                      'Exception: ' . $exception->getMessage());

            return ActionResult::FAILURE;
        }
    }

}
