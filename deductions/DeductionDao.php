<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

class DeductionDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Deduction $deduction): ActionResult
    {
        $query = "
            INSERT INTO deductions (
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

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"       , $deduction->getName()       , Helper::getPdoParameterType($deduction->getName()       ));
            $statement->bindValue(":amount"     , $deduction->getAmount()     , Helper::getPdoParameterType($deduction->getAmount()     ));
            $statement->bindValue(":frequency"  , $deduction->getFrequency()  , Helper::getPdoParameterType($deduction->getFrequency()  ));
            $statement->bindValue(":description", $deduction->getDescription(), Helper::getPdoParameterType($deduction->getDescription()));
            $statement->bindValue(":status"     , $deduction->getStatus()     , Helper::getPdoParameterType($deduction->getStatus()     ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the deduction. " .
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
            "id"             => "deduction.id             AS id"            ,
            "name"           => "deduction.name           AS name"          ,
            "amount"         => "deduction.amount         AS amount"        ,
            "frequency"      => "deduction.frequency      AS frequency"     ,
            "description"    => "deduction.description    AS description"   ,
            "status"         => "deduction.status         AS status"        ,
            "created_at"     => "deduction.created_at     AS created_at"    ,
            "updated_at"     => "deduction.updated_at     AS updated_at"    ,
            "deleted_at"     => "deduction.deleted_at     AS deleted_at"
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
            $whereClauses[] = "deduction.deleted_at IS NULL";

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

                    case "IS NULL":
                        $whereClauses[] = "{$column} {$operator}";

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
                deductions AS deduction
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
                        COUNT(deduction.id)
                    FROM
                        deductions AS deduction
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
            error_log("Database Error: An error occurred while fetching the deductions. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(Deduction $deduction): ActionResult
    {
        $query = "
            UPDATE deductions
            SET
                name        = :name       ,
                amount      = :amount     ,
                frequency   = :frequency  ,
                description = :description,
                status      = :status
            WHERE
        ";

        if ( ! ctype_digit( (string) $deduction->getId())) {
            $query .= " SHA2(id, 256) = :deduction_id";
        } else {
            $query .= " id = :deduction_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"        , $deduction->getName()       , Helper::getPdoParameterType($deduction->getName()       ));
            $statement->bindValue(":amount"      , $deduction->getAmount()     , Helper::getPdoParameterType($deduction->getAmount()     ));
            $statement->bindValue(":frequency"   , $deduction->getFrequency()  , Helper::getPdoParameterType($deduction->getFrequency()  ));
            $statement->bindValue(":description" , $deduction->getDescription(), Helper::getPdoParameterType($deduction->getDescription()));
            $statement->bindValue(":status"      , $deduction->getStatus()     , Helper::getPdoParameterType($deduction->getStatus()     ));

            $statement->bindValue(":deduction_id", $deduction->getId()         , Helper::getPdoParameterType($deduction->getId()         ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while updating the deduction. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $deductionId): ActionResult
    {
        return $this->softDelete($deductionId);
    }

    private function softDelete(int|string $deductionId): ActionResult
    {
        $query = "
            UPDATE deductions
            SET
                status     = 'Archived'       ,
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if ( ! ctype_digit( (string) $deductionId)) {
            $query .= " SHA2(id, 256) = :deduction_id";
        } else {
            $query .= " id = :deduction_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":deduction_id", $deductionId, Helper::getPdoParameterType($deductionId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while deleting the deduction. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
