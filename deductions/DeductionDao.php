<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

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
                name          ,
                amount_type   ,
                amount        ,
                is_pre_tax    ,
                frequency     ,
                description   ,
                status        ,
                effective_date,
                end_date
            )
            VALUES (
                :name          ,
                :amount_type   ,
                :amount        ,
                :is_pre_tax    ,
                :frequency     ,
                :description   ,
                :status        ,
                :effective_date,
                :end_date
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"          , $deduction->getName()         , Helper::getPdoParameterType($deduction->getName()         ));
            $statement->bindValue(":amount_type"   , $deduction->getAmountType()   , Helper::getPdoParameterType($deduction->getAmountType()   ));
            $statement->bindValue(":amount"        , $deduction->getAmount()       , Helper::getPdoParameterType($deduction->getAmount()       ));
            $statement->bindValue(":is_pre_tax"    , $deduction->getIsPreTax()     , Helper::getPdoParameterType($deduction->getIsPreTax()     ));
            $statement->bindValue(":frequency"     , $deduction->getFrequency()    , Helper::getPdoParameterType($deduction->getFrequency()    ));
            $statement->bindValue(":description"   , $deduction->getDescription()  , Helper::getPdoParameterType($deduction->getDescription()  ));
            $statement->bindValue(":status"        , $deduction->getStatus()       , Helper::getPdoParameterType($deduction->getStatus()       ));
            $statement->bindValue(":effective_date", $deduction->getEffectiveDate(), Helper::getPdoParameterType($deduction->getEffectiveDate()));
            $statement->bindValue(":end_date"      , $deduction->getEndDate()      , Helper::getPdoParameterType($deduction->getEndDate()      ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the deduction. " .
                      "Exception: {$exception->getMessage()}");

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
            "id"             => "deduction.id             AS id"            ,
            "name"           => "deduction.name           AS name"          ,
            "amount_type"    => "deduction.amount_type    AS amount_type"   ,
            "amount"         => "deduction.amount         AS amount"        ,
            "is_pre_tax"     => "deduction.is_pre_tax     AS is_pre_tax"    ,
            "frequency"      => "deduction.frequency      AS frequency"     ,
            "description"    => "deduction.description    AS description"   ,
            "status"         => "deduction.status         AS status"        ,
            "effective_date" => "deduction.effective_date AS effective_date",
            "end_date"       => "deduction.end_date       AS end_date"      ,
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

        $queryParameters = [];

        $whereClauses = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "deduction.status <> 'Archived'";
        } else {
            foreach ($filterCriteria as $filterCriterion) {
                $column   = $filterCriterion["column"  ];
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
                deductions AS deduction
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
                name           = :name          ,
                amount_type    = :amount_type   ,
                amount         = :amount        ,
                is_pre_tax     = :is_pre_tax    ,
                frequency      = :frequency     ,
                description    = :description   ,
                status         = :status        ,
                effective_date = :effective_date,
                end_date       = :end_date
            WHERE
                id = :deduction_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":name"          , $deduction->getName()         , Helper::getPdoParameterType($deduction->getName()         ));
            $statement->bindValue(":amount_type"   , $deduction->getAmountType()   , Helper::getPdoParameterType($deduction->getAmountType()   ));
            $statement->bindValue(":amount"        , $deduction->getAmount()       , Helper::getPdoParameterType($deduction->getAmount()       ));
            $statement->bindValue(":is_pre_tax"    , $deduction->getIsPreTax()     , Helper::getPdoParameterType($deduction->getIsPreTax()     ));
            $statement->bindValue(":frequency"     , $deduction->getFrequency()    , Helper::getPdoParameterType($deduction->getFrequency()    ));
            $statement->bindValue(":description"   , $deduction->getDescription()  , Helper::getPdoParameterType($deduction->getDescription()  ));
            $statement->bindValue(":status"        , $deduction->getStatus()       , Helper::getPdoParameterType($deduction->getStatus()       ));
            $statement->bindValue(":effective_date", $deduction->getEffectiveDate(), Helper::getPdoParameterType($deduction->getEffectiveDate()));
            $statement->bindValue(":end_date"      , $deduction->getEndDate()      , Helper::getPdoParameterType($deduction->getEndDate()      ));
            $statement->bindValue(":deduction_id"  , $deduction->getId()           , Helper::getPdoParameterType($deduction->getId()           ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while updating the deduction. " .
                      "Exception: {$exception->getMessage()}");

            if ( (int) $exception->getCode() === ErrorCode::DUPLICATE_ENTRY->value) {
                return ActionResult::DUPLICATE_ENTRY_ERROR;
            }

            return ActionResult::FAILURE;
        }
    }

    public function delete(int $deductionId): ActionResult
    {
        return $this->softDelete($deductionId);
    }

    private function softDelete(int $deductionId): ActionResult
    {
        $query = "
            UPDATE deductions
            SET
                status     = 'Archived'       ,
                deleted_at = CURRENT_TIMESTAMP
            WHERE
                id = :deduction_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":deduction_id", $deductionId, Helper::getPdoParameterType($deductionId));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while deleting the deduction. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
