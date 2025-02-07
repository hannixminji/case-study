<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

class JobTitleDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(JobTitle $jobTitle): ActionResult
    {
        $query = "
            INSERT INTO job_titles (
                title        ,
                department_id,
                description  ,
                status
            )
            VALUES (
                :title        ,
                :department_id,
                :description  ,
                :status
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":title"        , $jobTitle->getTitle()       , Helper::getPdoParameterType($jobTitle->getTitle()       ));
            $statement->bindValue(":department_id", $jobTitle->getDepartmentId(), Helper::getPdoParameterType($jobTitle->getDepartmentId()));
            $statement->bindValue(":description"  , $jobTitle->getDescription() , Helper::getPdoParameterType($jobTitle->getDescription() ));
            $statement->bindValue(":status"       , $jobTitle->getStatus()      , Helper::getPdoParameterType($jobTitle->getStatus()      ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the job title. " .
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
    ): ActionResult|array {

        $tableColumns = [
            "id"              => "job_title.id            AS id"             ,
            "title"           => "job_title.title         AS title"          ,
            "department_id"   => "job_title.department_id AS department_id"  ,
            "description"     => "job_title.description   AS description"    ,
            "status"          => "job_title.status        AS status"         ,
            "created_at"      => "job_title.created_at    AS created_at"     ,
            "updated_at"      => "job_title.updated_at    AS updated_at"     ,
            "deleted_at"      => "job_title.deleted_at    AS deleted_at"     ,

            "department_name" => "department.name         AS department_name"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("department_name", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    departments AS department
                ON
                    job_title.department_id = department.id
            ";
        }

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "job_title.deleted_at IS NULL";
        } else {
            foreach ($filterCriteria as $filterCriterion) {
                $column   = $filterCriterion["column"  ];
                $operator = $filterCriterion["operator"];

                switch ($operator) {
                    case "="   :
                    case "LIKE":
                        $whereClauses    [] = "{$column} {$operator} ?";
                        $queryParameters [] = $filterCriterion["value"];

                        $filterParameters[] = $filterCriterion["value"];

                        break;

                    case "BETWEEN":
                        $whereClauses    [] = "{$column} {$operator} ? AND ?";
                        $queryParameters [] = $filterCriterion["lower_bound"];
                        $queryParameters [] = $filterCriterion["upper_bound"];

                        $filterParameters[] = $filterCriterion["lower_bound"];
                        $filterParameters[] = $filterCriterion["upper_bound"];

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
            SELECT
                " . implode(", ", $selectedColumns) . "
            FROM
                job_titles AS job_title
            {$joinClauses}
            WHERE
            " . implode(" AND ", $whereClauses) . "
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
                        COUNT(job_title.id)
                    FROM
                        job_titles AS job_title
                    {$joinClauses}
                    WHERE
                        " . implode(" AND ", $whereClauses) . "
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
            error_log("Database Error: An error occurred while fetching the job titles. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(JobTitle $jobTitle, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE job_titles
            SET
                title         = :title        ,
                department_id = :department_id,
                description   = :description  ,
                status        = :status
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :job_title_id";
        } else {
            $query .= " id = :job_title_id";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":title"        , $jobTitle->getTitle()       , Helper::getPdoParameterType($jobTitle->getTitle()       ));
            $statement->bindValue(":department_id", $jobTitle->getDepartmentId(), Helper::getPdoParameterType($jobTitle->getDepartmentId()));
            $statement->bindValue(":description"  , $jobTitle->getDescription() , Helper::getPdoParameterType($jobTitle->getDescription() ));
            $statement->bindValue(":status"       , $jobTitle->getStatus()      , Helper::getPdoParameterType($jobTitle->getStatus()      ));

            $statement->bindValue(":job_title_id" , $jobTitle->getId()          , Helper::getPdoParameterType($jobTitle->getId()          ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while updating the job title. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $jobTitleId, bool $isHashedId = false): ActionResult
    {
        return $this->softDelete($jobTitleId, $isHashedId);
    }

    private function softDelete(int|string $jobTitleId, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE job_titles
            SET
                status     = 'Archived'       ,
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :job_title_id";
        } else {
            $query .= " id = :job_title_id";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":job_title_id", $jobTitleId, Helper::getPdoParameterType($jobTitleId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while deleting the job title. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
