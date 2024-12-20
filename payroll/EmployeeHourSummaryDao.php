<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

class EmployeeHourSummaryDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(EmployeeHourSummary $employeeHourSummary): ActionResult
    {
        $query = "
            INSERT INTO employee_hour_summaries (
                payslip_id                     ,
                overtime_rate_assignment_id    ,
                day_type                       ,
                holiday_type                   ,
                regular_hours                  ,
                overtime_hours                 ,
                night_differential             ,
                night_differential_and_overtime
            )
            VALUES (
                :payslip_id                     ,
                :overtime_rate_assignment_id    ,
                :day_type                       ,
                :holiday_type                   ,
                :regular_hours                  ,
                :overtime_hours                 ,
                :night_differential             ,
                :night_differential_and_overtime
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":payslip_id"                     , $employeeHourSummary->getPayslipId()                   , Helper::getPdoParameterType($employeeHourSummary->getPayslipId()                   ));
            $statement->bindValue(":overtime_rate_assignment_id"    , $employeeHourSummary->getOvertimeRateAssignmentId()    , Helper::getPdoParameterType($employeeHourSummary->getOvertimeRateAssignmentId()    ));
            $statement->bindValue(":day_type"                       , $employeeHourSummary->getDayType()                     , Helper::getPdoParameterType($employeeHourSummary->getDayType()                     ));
            $statement->bindValue(":holiday_type"                   , $employeeHourSummary->getHolidayType()                 , Helper::getPdoParameterType($employeeHourSummary->getHolidayType()                 ));
            $statement->bindValue(":regular_hours"                  , $employeeHourSummary->getRegularHours()                , Helper::getPdoParameterType($employeeHourSummary->getRegularHours()                ));
            $statement->bindValue(":overtime_hours"                 , $employeeHourSummary->getOvertimeHours()               , Helper::getPdoParameterType($employeeHourSummary->getOvertimeHours()               ));
            $statement->bindValue(":night_differential"             , $employeeHourSummary->getNightDifferential()           , Helper::getPdoParameterType($employeeHourSummary->getNightDifferential()           ));
            $statement->bindValue(":night_differential_and_overtime", $employeeHourSummary->getNightDifferentialAndOvertime(), Helper::getPdoParameterType($employeeHourSummary->getNightDifferentialAndOvertime()));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the employee hour summary. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function fetchEmployeeHourSummaries(int $payslipId, bool $isHashedId = false): ActionResult|array
    {
        $query = "
            SELECT
                id                             ,
                payslip_id                     ,
                overtime_rate_assignment_id    ,
                day_type                       ,
                holiday_type                   ,
                regular_hours                  ,
                overtime_hours                 ,
                night_differential             ,
                night_differential_and_overtime
            FROM
                employee_hour_summaries
            WHERE
        ";

        if ($isHashedId) {
            $query .= " SHA2(payslip_id, 256) = :payslip_id";
        } else {
            $query .= " payslip_id = :payslip_id";
        }

        try {
            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":payslip_id", $payslipId, Helper::getPdoParameterType($payslipId));

            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching employee hour summaries. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(EmployeeHourSummary $employeeHourSummary, bool $isHashedId = false): ActionResult
    {
        $query = "
            UPDATE employee_hour_summaries
            SET
                regular_hours                   = :regular_hours                  ,
                overtime_hours                  = :overtime_hours                 ,
                night_differential              = :night_differential             ,
                night_differential_and_overtime = :night_differential_and_overtime
            WHERE
        ";

        if ($isHashedId) {
            $query .= "
                SHA2(payslip_id, 256) = :payslip_id
            AND
                SHA2(id, 256) = :employee_hour_summary_id
            ";
        } else {
            $query .= "
                payslip_id = :payslip_id
            AND
                id = :employee_hour_summary_id
            ";
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":regular_hours"                  , $employeeHourSummary->getRegularHours()                , Helper::getPdoParameterType($employeeHourSummary->getRegularHours()                ));
            $statement->bindValue(":overtime_hours"                 , $employeeHourSummary->getOvertimeHours()               , Helper::getPdoParameterType($employeeHourSummary->getOvertimeHours()               ));
            $statement->bindValue(":night_differential"             , $employeeHourSummary->getNightDifferential()           , Helper::getPdoParameterType($employeeHourSummary->getNightDifferential()           ));
            $statement->bindValue(":night_differential_and_overtime", $employeeHourSummary->getNightDifferentialAndOvertime(), Helper::getPdoParameterType($employeeHourSummary->getNightDifferentialAndOvertime()));
            $statement->bindValue(":payslip_id"                     , $employeeHourSummary->getPayslipId()                   , Helper::getPdoParameterType($employeeHourSummary->getPayslipId()                   ));
            $statement->bindValue(":employee_hour_summary_id"       , $employeeHourSummary->getId()                          , Helper::getPdoParameterType($employeeHourSummary->getId()                          ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while updating the employee hour summary. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
