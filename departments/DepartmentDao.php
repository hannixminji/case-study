<?php

require_once __DIR__ . "/Department.php"                    ;

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

class DepartmentDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Department $department): ActionResult
    {
        $query = "
            INSERT INTO departments (
                name              ,
                department_head_id,
                description       ,
                status
            )
            VALUES (
                :name              ,
                :department_head_id,
                :description       ,
                :status
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"              , $department->getName()            , Helper::getPdoParameterType($department->getName()            ));
            $statement->bindValue(":department_head_id", $department->getDepartmentHeadId(), Helper::getPdoParameterType($department->getDepartmentHeadId()));
            $statement->bindValue(":description"       , $department->getDescription()     , Helper::getPdoParameterType($department->getDescription()     ));
            $statement->bindValue(":status"            , $department->getStatus()          , Helper::getPdoParameterType($department->getStatus()          ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the department. " .
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
            "id"                            => "department.id                 AS id"                           ,
            "name"                          => "department.name               AS name"                         ,
            "department_head_id"            => "department.department_head_id AS department_head_id"           ,
            "description"                   => "department.description        AS description"                  ,
            "status"                        => "department.status             AS status"                       ,
            "created_at"                    => "department.created_at         AS created_at"                   ,
            "updated_at"                    => "department.updated_at         AS updated_at"                   ,
            "deleted_at"                    => "department.deleted_at         AS deleted_at"                   ,

            "department_head_full_name"     => "department_head.full_name     AS department_head_full_name"    ,

            "job_title_id"                  => "job_title.id                  AS job_title_id"                 ,
            "job_title"                     => "job_title.title               AS job_title"                    ,
            "job_title_status"              => "job_title.status              AS job_title_status"             ,

            "employee_id"                   => "employee.id                   AS employee_id"                  ,
            "employee_full_name"            => "employee.full_name            AS employee_full_name"           ,
            "employee_code"                 => "employee.employee_code        AS employee_code"                ,
            "employee_deleted_at"           => "employee.deleted_at           AS employee_deleted_at"          ,

            "employee_supervisor_full_name" => "supervisor.full_name          AS employee_supervisor_full_name"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("department_head_full_name", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    employees AS department_head
                ON
                    department.department_head_id = department_head.id
            ";
        }

        if (array_key_exists("job_title_id"    , $selectedColumns) ||
            array_key_exists("job_title"       , $selectedColumns) ||
            array_key_exists("job_title_status", $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    job_titles AS job_title
                ON
                    department.id = job_title.department_id
            ";
        }

        if (array_key_exists("employee_id"                  , $selectedColumns) ||
            array_key_exists("employee_full_name"           , $selectedColumns) ||
            array_key_exists("employee_code"                , $selectedColumns) ||
            array_key_exists("employee_deleted_at"          , $selectedColumns) ||

            array_key_exists("employee_supervisor_full_name", $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    employees AS employee
                ON
                    department.id = employee.department_id
                AND
                    job_title.id = employee.job_title_id
            ";
        }

        if (array_key_exists("employee_supervisor_full_name", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    employees AS supervisor
                ON
                    employee.supervisor_id = supervisor.id
            ";
        }

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "department.deleted_at IS NULL";

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
                departments AS department
            {$joinClauses}
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
                        COUNT(department.id)
                    FROM
                        departments AS department
                    {$joinClauses}
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
            error_log("Database Error: An error occurred while fetching the departments. " .
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

    public function fetchEmployeeCountsPerDepartment(): array|ActionResult
    {
        $query = "
            SELECT
                department.name    AS department_name,
                COUNT(employee.id) AS employee_count
            FROM
                departments AS department
            LEFT JOIN
                employees AS employee
            ON
                department.id = employee.department_id
            WHERE
                department.deleted_at IS NULL
            GROUP BY
                department.id  ,
                department.name
            HAVING
                COUNT(employee.id) > 0
        ";

        try {
            $statement = $this->pdo->prepare($query);

            $statement->execute();

            $resultSet = [];

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $resultSet[] = $row;
            }

            $totalRowCount = count($resultSet);

            return [
                "result_set"      => $resultSet    ,
                "total_row_count" => $totalRowCount
            ];

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching employee counts per department. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(Department $department): ActionResult
    {
        $query = "
            UPDATE departments
            SET
                name               = :name              ,
                department_head_id = :department_head_id,
                description        = :description       ,
                status             = :status
            WHERE
        ";

        if (filter_var($department->getId(), FILTER_VALIDATE_INT) !== false) {
            $query .= "id = :department_id";
        } else {
            $query .= "SHA2(id, 256) = :department_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"              , $department->getName()            , Helper::getPdoParameterType($department->getName()            ));
            $statement->bindValue(":department_head_id", $department->getDepartmentHeadId(), Helper::getPdoParameterType($department->getDepartmentHeadId()));
            $statement->bindValue(":description"       , $department->getDescription()     , Helper::getPdoParameterType($department->getDescription()     ));
            $statement->bindValue(":status"            , $department->getStatus()          , Helper::getPdoParameterType($department->getStatus()          ));

            $statement->bindValue(":department_id"     , $department->getId()              , Helper::getPdoParameterType($department->getId()              ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while updating the department. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function isDepartmentHead(int|string $employeeId): bool|ActionResult
    {
        $query = "
            SELECT
                COUNT(*) AS count
            FROM
                departments
            WHERE
        ";

        if (filter_var($employeeId, FILTER_VALIDATE_INT) !== false) {
            $query .= "department_head_id = :employee_id";
        } else {
            $query .= "SHA2(department_head_id, 256) = :employee_id";
        }

        $query .= "
            AND
                deleted_at IS NULL
        ";

        try {
            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id", $employeeId, PDO::PARAM_INT);

            $statement->execute();

            $result = $statement->fetch(PDO::FETCH_ASSOC);

            return $result["count"] > 0;

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while checking if the employee is a department head. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $departmentId): ActionResult
    {
        return $this->softDelete($departmentId);
    }

    private function softDelete(int|string $departmentId): ActionResult
    {
        $query = "
            UPDATE departments
            SET
                status     = 'Archived'       ,
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if (filter_var($departmentId, FILTER_VALIDATE_INT) !== false) {
            $query .= "id = :department_id";
        } else {
            $query .= "SHA2(id, 256) = :department_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":department_id", $departmentId, Helper::getPdoParameterType($departmentId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while deleting the department. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
