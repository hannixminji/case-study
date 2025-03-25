<?php

require_once __DIR__ . "/OvertimeRate.php"                  ;

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

class OvertimeRateDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(OvertimeRate $overtimeRate): ActionResult
    {
        $query = "
            INSERT INTO overtime_rates (
                overtime_rate_assignment_id         ,
                day_type                            ,
                holiday_type                        ,
                regular_time_rate                   ,
                overtime_rate                       ,
                night_differential_rate             ,
                night_differential_and_overtime_rate
            )
            VALUES (
                :overtime_rate_assignment_id         ,
                :day_type                            ,
                :holiday_type                        ,
                :regular_time_rate                   ,
                :overtime_rate                       ,
                :night_differential_rate             ,
                :night_differential_and_overtime_rate
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":overtime_rate_assignment_id"         , $overtimeRate->getOvertimeRateAssignmentId()        , Helper::getPdoParameterType($overtimeRate->getOvertimeRateAssignmentId()        ));
            $statement->bindValue(":day_type"                            , $overtimeRate->getDayType()                         , Helper::getPdoParameterType($overtimeRate->getDayType()                         ));
            $statement->bindValue(":holiday_type"                        , $overtimeRate->getHolidayType()                     , Helper::getPdoParameterType($overtimeRate->getHolidayType()                     ));
            $statement->bindValue(":regular_time_rate"                   , $overtimeRate->getRegularTimeRate()                 , Helper::getPdoParameterType($overtimeRate->getRegularTimeRate()                 ));
            $statement->bindValue(":overtime_rate"                       , $overtimeRate->getOvertimeRate()                    , Helper::getPdoParameterType($overtimeRate->getOvertimeRate()                    ));
            $statement->bindValue(":night_differential_rate"             , $overtimeRate->getNightDifferentialRate()           , Helper::getPdoParameterType($overtimeRate->getNightDifferentialRate()           ));
            $statement->bindValue(":night_differential_and_overtime_rate", $overtimeRate->getNightDifferentialAndOvertimeRate(), Helper::getPdoParameterType($overtimeRate->getNightDifferentialAndOvertimeRate()));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the overtime rate. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function fetchOvertimeRates(int|string $overtimeRateAssignmentId): array|ActionResult
    {
        $query = "
            SELECT
                id                                  ,
                overtime_rate_assignment_id         ,
                day_type                            ,
                holiday_type                        ,
                regular_time_rate                   ,
                overtime_rate                       ,
                night_differential_rate             ,
                night_differential_and_overtime_rate
            FROM
                overtime_rates
            WHERE
        ";

        if (filter_var($overtimeRateAssignmentId, FILTER_VALIDATE_INT) !== false) {
            $query .= "overtime_rate_assignment_id = :overtime_rate_assignment_id";
        } else {
            $query .= "SHA2(overtime_rate_assignment_id, 256) = :overtime_rate_assignment_id";
        }

        $query .= "
            ORDER BY
                CASE
                    WHEN day_type = 'Regular Day' THEN 1
                    WHEN day_type = 'Rest Day'    THEN 2
                                                  ELSE 3
                END,

                CASE
                    WHEN holiday_type = 'Non-holiday'            THEN 1
                    WHEN holiday_type = 'Special Holiday'        THEN 2
                    WHEN holiday_type = 'Regular Holiday'        THEN 3
                    WHEN holiday_type = 'Double Special Holiday' THEN 4
                    WHEN holiday_type = 'Double Holiday'         THEN 5
                                                                 ELSE 6
                END
        ";

        try {
            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":overtime_rate_assignment_id", $overtimeRateAssignmentId, Helper::getPdoParameterType($overtimeRateAssignmentId));

            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching overtime rates. ".
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(OvertimeRate $overtimeRate): ActionResult
    {
        $query = "
            UPDATE overtime_rates
            SET
                regular_time_rate                    = :regular_time_rate                   ,
                overtime_rate                        = :overtime_rate                       ,
                night_differential_rate              = :night_differential_rate             ,
                night_differential_and_overtime_rate = :night_differential_and_overtime_rate
            WHERE
        ";

        if (filter_var($overtimeRate->getOvertimeRateAssignmentId(), FILTER_VALIDATE_INT) !== false) {
            $query .= "overtime_rate_assignment_id = :overtime_rate_assignment_id ";
        } else {
            $query .= "SHA2(overtime_rate_assignment_id, 256) = :overtime_rate_assignment_id ";
        }

        if (filter_var($overtimeRate->getId(), FILTER_VALIDATE_INT) !== false) {
            $query .= "AND id = :overtime_rate_id";
        } else {
            $query .= "AND SHA2(id, 256) = :overtime_rate_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":regular_time_rate"                   , $overtimeRate->getRegularTimeRate()                 , Helper::getPdoParameterType($overtimeRate->getRegularTimeRate()                 ));
            $statement->bindValue(":overtime_rate"                       , $overtimeRate->getOvertimeRate()                    , Helper::getPdoParameterType($overtimeRate->getOvertimeRate()                    ));
            $statement->bindValue(":night_differential_rate"             , $overtimeRate->getNightDifferentialRate()           , Helper::getPdoParameterType($overtimeRate->getNightDifferentialRate()           ));
            $statement->bindValue(":night_differential_and_overtime_rate", $overtimeRate->getNightDifferentialAndOvertimeRate(), Helper::getPdoParameterType($overtimeRate->getNightDifferentialAndOvertimeRate()));

            $statement->bindValue(":overtime_rate_assignment_id"         , $overtimeRate->getOvertimeRateAssignmentId()        , Helper::getPdoParameterType($overtimeRate->getOvertimeRateAssignmentId()        ));
            $statement->bindValue(":overtime_rate_id"                    , $overtimeRate->getId()                              , Helper::getPdoParameterType($overtimeRate->getId()                              ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while updating the overtime rate. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
