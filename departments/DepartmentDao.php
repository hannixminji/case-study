<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

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

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"              , $department->getName()            , Helper::getPdoParameterType($department->getName()            ));
            $statement->bindValue(":department_head_id", $department->getDepartmentHeadId(), Helper::getPdoParameterType($department->getDepartmentHeadId()));
            $statement->bindValue(":description"       , $department->getDescription()     , Helper::getPdoParameterType($department->getDescription()     ));
            $statement->bindValue(":status"            , $department->getStatus()          , Helper::getPdoParameterType($department->getStatus()          ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the department. " .
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
            "id"                        => "department.id                 AS id"                       ,
            "name"                      => "department.name               AS name"                     ,

            "department_head_id"        => "department.department_head_id AS department_head_id"       ,
            "department_head_full_name" => "department_head.full_name     AS department_head_full_name",

            "description"               => "department.description        AS description"              ,
            "status"                    => "department.status             AS status"                   ,
            "created_at"                => "department.created_at         AS created_at"               ,
            "updated_at"                => "department.updated_at         AS updated_at"               ,
            "deleted_at"                => "department.deleted_at         AS deleted_at"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("department_head_full_name" , $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    employees AS department_head
                ON
                    department.department_head_id = department_head.id
            ";
        }

        $queryParameters = [];

        $whereClauses = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "department.deleted_at IS NULL";
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
            error_log("Database Error: An error occurred while fetching the departments. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function fetchEmployeeCountsPerDepartment(): ActionResult|array
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
        ";

        try {
            $statement = $this->pdo->prepare($query);

            $statement->execute();

            $resultSet = [];
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $resultSet[] = $row;
            }

            return $resultSet;

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching employee counts per department. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(Department $department, bool $isHashedId = false): ActionResult
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

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :department_id";
        } else {
            $query .= " id = :department_id";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"              , $department->getName()            , Helper::getPdoParameterType($department->getName()            ));
            $statement->bindValue(":department_head_id", $department->getDepartmentHeadId(), Helper::getPdoParameterType($department->getDepartmentHeadId()));
            $statement->bindValue(":description"       , $department->getDescription()     , Helper::getPdoParameterType($department->getDescription()     ));
            $statement->bindValue(":status"            , $department->getStatus()          , Helper::getPdoParameterType($department->getStatus()          ));
            $statement->bindValue(":department_id"     , $department->getId()              , Helper::getPdoParameterType($department->getId()              ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while updating the department. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function isDepartmentHead(int|string $employeeId, bool $isHashedId = false): ActionResult|bool
    {
        $query = "
            SELECT
                COUNT(*) AS count
            FROM
                departments
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(department_head_id, 256) = :employee_id";
        } else {
            $query .= " department_head_id = :employee_id";
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

    public function delete(int|string $departmentId, bool $isHashedId = false): ActionResult
    {
        return $this->softDelete($departmentId, $isHashedId);
    }

    private function softDelete(int|string $departmentId, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE departments
            SET
                status     = 'Archived'       ,
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :department_id";
        } else {
            $query .= " id = :department_id";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":department_id", $departmentId, Helper::getPdoParameterType($departmentId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while deleting the department. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
