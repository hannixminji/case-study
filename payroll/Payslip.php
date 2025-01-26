<?php

class Payslip
{
    public function __construct(
        private readonly   int|string|null $id                             = null,
        private readonly   int|string      $employeeId                           ,
        private readonly   int|string      $payrollGroupId                       ,
        private readonly   string          $paydayDate                          ,
        private readonly   string          $cutoffStartDate                      ,
        private readonly   string          $cutoffEndDate                        ,
        private readonly   float           $totalRegularHours                    ,
        private readonly   float           $totalOvertimeHours                   ,
        private readonly   float           $totalNightDifferential               ,
        private readonly   float           $totalNightDifferentialOvertime       ,
        private readonly   float           $totalRegularHolidayHours             ,
        private readonly   float           $totalSpecialHolidayHours             ,
        private readonly   float           $totalDaysWorked                      ,
        private readonly   float           $totalHoursWorked                     ,
        private readonly   float           $grossPay                             ,
        private readonly   float           $netPay                               ,
        private readonly   float           $sssDeduction                         ,
        private readonly   float           $philhealthDeduction                  ,
        private readonly   float           $pagibigFundDeduction                 ,
        private readonly   float           $withholdingTax                       ,
        private readonly ? float           $thirteenMonthPay               = null,
        private readonly ? float           $leaveSalary                    = null
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getEmployeeId(): int|string
    {
        return $this->employeeId;
    }

    public function getPayrollGroupId(): int|string
    {
        return $this->payrollGroupId;
    }

    public function getpaydayDate(): string
    {
        return $this->paydayDate;
    }

    public function getCutoffStartDate(): string
    {
        return $this->cutoffStartDate;
    }

    public function getCutoffEndDate(): string
    {
        return $this->cutoffEndDate;
    }

    public function getTotalRegularHours(): float
    {
        return $this->totalRegularHours;
    }

    public function getTotalOvertimeHours(): float
    {
        return $this->totalOvertimeHours;
    }

    public function getTotalNightDifferential(): float
    {
        return $this->totalNightDifferential;
    }

    public function getTotalNightDifferentialOvertime(): float
    {
        return $this->totalNightDifferentialOvertime;
    }

    public function getTotalRegularHolidayHours(): float
    {
        return $this->totalRegularHolidayHours;
    }

    public function getTotalSpecialHolidayHours(): float
    {
        return $this->totalSpecialHolidayHours;
    }

    public function getTotalDaysWorked(): float
    {
        return $this->totalDaysWorked;
    }

    public function getTotalHoursWorked(): float
    {
        return $this->totalHoursWorked;
    }

    public function getGrossPay(): float
    {
        return $this->grossPay;
    }

    public function getNetPay(): float
    {
        return $this->netPay;
    }

    public function getSssDeduction(): float
    {
        return $this->sssDeduction;
    }

    public function getPhilhealthDeduction(): float
    {
        return $this->philhealthDeduction;
    }

    public function getPagibigFundDeduction(): float
    {
        return $this->pagibigFundDeduction;
    }

    public function getWithholdingTax(): float
    {
        return $this->withholdingTax;
    }

    public function getThirteenMonthPay(): ?float
    {
        return $this->thirteenMonthPay;
    }

    public function getLeaveSalary(): ?float
    {
        return $this->leaveSalary;
    }
}
