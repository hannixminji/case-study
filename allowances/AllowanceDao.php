<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

class AllowanceDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Allowance $allowance): ActionResult
    {
        $query = "
            INSERT INTO allowances (
                name       ,
                amount     ,
                frequency  ,
                description,
                status
            )
            VALUES (
                :name       ,
                :amount     ,
                :frequency  ,
                :description,
                :status
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"       , $allowance->getName()       , Helper::getPdoParameterType($allowance->getName()       ));
            $statement->bindValue(":amount"     , $allowance->getAmount()     , Helper::getPdoParameterType($allowance->getAmount()     ));
            $statement->bindValue(":frequency"  , $allowance->getFrequency()  , Helper::getPdoParameterType($allowance->getFrequency()  ));
            $statement->bindValue(":description", $allowance->getDescription(), Helper::getPdoParameterType($allowance->getDescription()));
            $statement->bindValue(":status"     , $allowance->getStatus()     , Helper::getPdoParameterType($allowance->getStatus()     ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the allowance. " .
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
            "id"          => "allowance.id          AS id"         ,
            "name"        => "allowance.name        AS name"       ,
            "amount"      => "allowance.amount      AS amount"     ,
            "frequency"   => "allowance.frequency   AS frequency"  ,
            "description" => "allowance.description AS description",
            "status"      => "allowance.status      AS status"     ,
            "created_at"  => "allowance.created_at  AS created_at" ,
            "updated_at"  => "allowance.updated_at  AS updated_at" ,
            "deleted_at"  => "allowance.deleted_at  AS deleted_at"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $whereClauses    = [];
        $queryParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "allowance.deleted_at IS NULL";
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
                allowances AS allowance
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

            $countStatement = $this->pdo->query("SELECT FOUND_ROWS()");
            $totalRowCount = $countStatement->fetchColumn();

            return [
                "result_set"      => $resultSet    ,
                "total_row_count" => $totalRowCount
            ];

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching the allowances. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(Allowance $allowance, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE allowances
            SET
                name        = :name       ,
                amount      = :amount     ,
                frequency   = :frequency  ,
                description = :description,
                status      = :status
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :allowance_id";
        } else {
            $query .= " id = :allowance_id";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"        , $allowance->getName()       , Helper::getPdoParameterType($allowance->getName()       ));
            $statement->bindValue(":amount"      , $allowance->getAmount()     , Helper::getPdoParameterType($allowance->getAmount()     ));
            $statement->bindValue(":frequency"   , $allowance->getFrequency()  , Helper::getPdoParameterType($allowance->getFrequency()  ));
            $statement->bindValue(":description" , $allowance->getDescription(), Helper::getPdoParameterType($allowance->getDescription()));
            $statement->bindValue(":status"      , $allowance->getStatus()     , Helper::getPdoParameterType($allowance->getStatus()     ));

            $statement->bindValue(":allowance_id", $allowance->getId()         , Helper::getPdoParameterType($allowance->getId()         ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while updating the allowance. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $allowanceId, bool $isHashedId = false): ActionResult
    {
        return $this->softDelete($allowanceId, $isHashedId);
    }

    private function softDelete(int|string $allowanceId, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE allowances
            SET
                status     = 'Archived'       ,
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(id, 256) = :allowance_id";
        } else {
            $query .= " id = :allowance_id";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":allowance_id", $allowanceId, Helper::getPdoParameterType($allowanceId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while deleting the allowance. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
