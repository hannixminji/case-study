<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

class PayslipDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Payslip $payslip): ActionResult
    {
        $query = "
            INSERT INTO payslips (
                employee_id                      ,
                payroll_group_id                 ,
                payment_date                     ,
                cutoff_start_date                ,
                cutoff_end_date                  ,
                total_regular_hours              ,
                total_overtime_hours             ,
                total_night_differential         ,
                total_night_differential_overtime,
                total_regular_holiday_hours      ,
                total_special_holiday_hours      ,
                total_days_worked                ,
                total_hours_worked               ,
                gross_pay                        ,
                net_pay                          ,
                sss_deduction                    ,
                philhealth_deduction             ,
                pagibig_fund_deduction           ,
                withholding_tax                  ,
                thirteen_month_pay               ,
                leave_salary
            )
            VALUES (
                :employee_id                      ,
                :payroll_group_id                 ,
                :payment_date                     ,
                :cutoff_start_date                ,
                :cutoff_end_date                  ,
                :total_regular_hours              ,
                :total_overtime_hours             ,
                :total_night_differential         ,
                :total_night_differential_overtime,
                :total_regular_holiday_hours      ,
                :total_special_holiday_hours      ,
                :total_days_worked                ,
                :total_hours_worked               ,
                :gross_pay                        ,
                :net_pay                          ,
                :sss_deduction                    ,
                :philhealth_deduction             ,
                :pagibig_fund_deduction           ,
                :withholding_tax                  ,
                :thirteen_month_pay               ,
                :leave_salary
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"                      , $payslip->getEmployeeId()                    , Helper::getPdoParameterType($payslip->getEmployeeId()                    ));
            $statement->bindValue(":payroll_group_id"                 , $payslip->getPayrollGroupId()                , Helper::getPdoParameterType($payslip->getPayrollGroupId()                ));
            $statement->bindValue(":payment_date"                     , $payslip->getPaymentDate()                   , Helper::getPdoParameterType($payslip->getPaymentDate()                   ));
            $statement->bindValue(":cutoff_start_date"                , $payslip->getCutoffStartDate()               , Helper::getPdoParameterType($payslip->getCutoffStartDate()               ));
            $statement->bindValue(":cutoff_end_date"                  , $payslip->getCutoffEndDate()                 , Helper::getPdoParameterType($payslip->getCutoffEndDate()                 ));
            $statement->bindValue(":total_regular_hours"              , $payslip->getTotalRegularHours()             , Helper::getPdoParameterType($payslip->getTotalRegularHours()             ));
            $statement->bindValue(":total_overtime_hours"             , $payslip->getTotalOvertimeHours()            , Helper::getPdoParameterType($payslip->getTotalOvertimeHours()            ));
            $statement->bindValue(":total_night_differential"         , $payslip->getTotalNightDifferential()        , Helper::getPdoParameterType($payslip->getTotalNightDifferential()        ));
            $statement->bindValue(":total_night_differential_overtime", $payslip->getTotalNightDifferentialOvertime(), Helper::getPdoParameterType($payslip->getTotalNightDifferentialOvertime()));
            $statement->bindValue(":total_regular_holiday_hours"      , $payslip->getTotalRegularHolidayHours()      , Helper::getPdoParameterType($payslip->getTotalRegularHolidayHours()      ));
            $statement->bindValue(":total_special_holiday_hours"      , $payslip->getTotalSpecialHolidayHours()      , Helper::getPdoParameterType($payslip->getTotalSpecialHolidayHours()      ));
            $statement->bindValue(":total_days_worked"                , $payslip->getTotalDaysWorked()               , Helper::getPdoParameterType($payslip->getTotalDaysWorked()               ));
            $statement->bindValue(":total_hours_worked"               , $payslip->getTotalHoursWorked()              , Helper::getPdoParameterType($payslip->getTotalHoursWorked()              ));
            $statement->bindValue(":gross_pay"                        , $payslip->getGrossPay()                      , Helper::getPdoParameterType($payslip->getGrossPay()                      ));
            $statement->bindValue(":net_pay"                          , $payslip->getNetPay()                        , Helper::getPdoParameterType($payslip->getNetPay()                        ));
            $statement->bindValue(":sss_deduction"                    , $payslip->getSssDeduction()                  , Helper::getPdoParameterType($payslip->getSssDeduction()                  ));
            $statement->bindValue(":philhealth_deduction"             , $payslip->getPhilhealthDeduction()           , Helper::getPdoParameterType($payslip->getPhilhealthDeduction()           ));
            $statement->bindValue(":pagibig_fund_deduction"           , $payslip->getPagibigFundDeduction()          , Helper::getPdoParameterType($payslip->getPagibigFundDeduction()          ));
            $statement->bindValue(":withholding_tax"                  , $payslip->getWithholdingTax()                , Helper::getPdoParameterType($payslip->getWithholdingTax()                ));
            $statement->bindValue(":thirteen_month_pay"               , $payslip->getThirteenMonthPay()              , Helper::getPdoParameterType($payslip->getThirteenMonthPay()              ));
            $statement->bindValue(":leave_salary"                     , $payslip->getLeaveSalary()                   , Helper::getPdoParameterType($payslip->getLeaveSalary()                   ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the payslip. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function getLastPayslipId(): ActionResult|int|null
    {
        try {
            $lastInsertedId = $this->pdo->lastInsertId();

            return $lastInsertedId !== false
                ? (int) $lastInsertedId
                : null;

        } catch (PDOException $exception) {
            error_log("Database Error: Unable to retrieve the last inserted ID. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
