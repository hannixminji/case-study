<?php

class Payslip
{
    public function __construct(
        private readonly   int|string|null $id                  ,
        private readonly   int|string      $employeeId          ,
        private readonly   int|string      $payrollGroupId      ,
        private readonly   string          $payDate             ,
        private readonly   string          $payPeriodStartDate  ,
        private readonly   string          $payPeriodEndDate    ,
        private readonly   float           $basicSalary         ,
        private readonly   float           $basicPay            ,
        private readonly   float           $grossPay            ,
        private readonly   float           $netPay              ,
        private readonly   float           $sssDeduction        ,
        private readonly   float           $philhealthDeduction ,
        private readonly   float           $pagibigFundDeduction,
        private readonly   float           $withholdingTax      ,
        private readonly ? float           $thirteenMonthPay    ,
        private readonly ? float           $leaveSalary         ,
        private readonly   string          $workHours           ,
        private readonly   string          $overtimeRates
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

    public function getPayDate(): string
    {
        return $this->payDate;
    }

    public function getPayPeriodStartDate(): string
    {
        return $this->payPeriodStartDate;
    }

    public function getPayPeriodEndDate(): string
    {
        return $this->payPeriodEndDate;
    }

    public function getBasicSalary(): float
    {
        return $this->basicSalary;
    }

    public function getBasicPay(): float
    {
        return $this->basicPay;
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

    public function getWorkHours(): string
    {
        return $this->workHours;
    }

    public function getOvertimeRates(): string
    {
        return $this->overtimeRates;
    }
}
