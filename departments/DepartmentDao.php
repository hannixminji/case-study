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
        ? array  $columns       = null,
        ? array  $filters       = null,
        ? string $sortBy        = null,
        ? string $sortDirection = null,
        ? int    $limit         = null,
        ? int    $offset        = null
    ): ActionResult|array {
        $tableColumns = [
            "id"                          => "id"                                                        ,
            "name"                        => "name"                                                      ,
            "department_head_id"          => "department_head.id AS department_head_id"                  ,
            "department_head_first_name"  => "department_head.first_name AS department_head_first_name"  ,
            "department_head_middle_name" => "department_head.middle_name AS department_head_middle_name",
            "department_head_last_name"   => "department_head.last_name AS department_head_last_name"    ,
            "description"                 => "description"                                               ,
            "status"                      => "status"                                                    ,
            "created_at"                  => "created_at"                                                ,
            "created_by"                  => "created_by_admin.username AS created_by"                   ,
            "updated_at"                  => "updated_at"                                                ,
            "updated_by"                  => "updated_by_admin.username AS updated_by"                   ,
            "deleted_at"                  => "deleted_at"                                                ,
            "deleted_by"                  => "deleted_by_admin.username AS deleted_by"
        ];

        $selectedColumns =
            empty($columns)
                ? implode(", ", $tableColumns)
                : implode(", ", array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                ));

        $query = "
            SELECT
                {$selectedColumns}
            FROM
                departments AS department
        ";

        if (in_array("department_head_id"         , $columns) ||
            in_array("department_head_first_name" , $columns) ||
            in_array("department_head_middle_name", $columns) ||
            in_array("department_head_last_name"  , $columns)) {
            $query .= "
                LEFT JOIN
                    employees AS department_head
                ON
                    department.department_head_id = department_head.id
            ";
        }

        if (in_array("created_by", $columns)) {
            $query .= "
                LEFT JOIN
                    admins AS created_by_admin
                ON
                    department.created_by = created_by_admin.id
            ";
        }

        if (in_array("updated_by", $columns)) {
            $query .= "
                LEFT JOIN
                    admins AS updated_by_admin
                ON
                    department.updated_by = updated_by_admin.id
            ";
        }

        if (in_array("deleted_by", $columns)) {
            $query .= "
                LEFT JOIN
                    admins AS deleted_by_admin
                ON
                    department.deleted_by = deleted_by_admin.id
            ";
        }

        if (empty($filters)) {
            $query .= " WHERE status <> 'Archived'";
        } else {
            $query .= " WHERE 1";

            foreach ($filters as $filter) {
                $column   = $filter["column"  ];
                $operator = $filter["operator"];

                switch ($operator) {
                    case "="   :
                    case "LIKE":
                        $query .= " AND {$column} {$operator} :value";
                        break;
                    case "BETWEEN":
                        $query .= " AND {$column} {$operator} :start_value AND :end_value";
                        break;
                    default:
                        // Do nothing
                }
            }
        }

        if ( ! empty($sortBy) && ! empty($sortDirection)) {
            $query .= " ORDER BY {$sortBy} {$sortDirection}";
        }

        if ($limit !== null && $offset !== null) {
            $query .= " LIMIT :limit OFFSET :offset";
        }

        try {
            $statement = $this->pdo->prepare($query);

            if ( ! empty($filters)) {
                foreach ($filters as $filter) {
                    $operator = $filter["operator"];

                    switch ($operator) {
                        case "="   :
                        case "LIKE":
                            $statement->bindValue(":value", $filter["value"], Helper::getPdoParameterType($filter["value"]));
                            break;
                        case "BETWEEN":
                            $statement->bindValue(":start_value", $filter["start_value"], Helper::getPdoParameterType($filter["start_value"]));
                            $statement->bindValue(":end_value"  , $filter["end_value"  ], Helper::getPdoParameterType($filter["end_value"  ]));
                            break;
                        default:
                            // Do nothing
                    }
                }
            }

            if ($limit !== null && $offset !== null) {
                $statement->bindValue(":limit" , $limit , Helper::getPdoParameterType($limit ));
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
