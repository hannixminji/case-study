<?php

class EmployeeHourSummary
{
    public function __construct(
        private readonly   int|string|null $id                           = null,
        private            int|string|null $payslipId                          ,
        private            int|string|null $overtimeRateAssignmentId     = null,
        private readonly   string          $dayType                            ,
        private readonly   string          $holidayType                        ,
        private readonly   float           $regularHours                       ,
        private readonly   float           $overtimeHours                      ,
        private readonly   float           $nightDifferential                  ,
        private readonly   float           $nightDifferentialAndOvertime
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getPayslipId(): int|string|null
    {
        return $this->payslipId;
    }

    public function getOvertimeRateAssignmentId(): int|string|null
    {
        return $this->overtimeRateAssignmentId;
    }

    public function getDayType(): string
    {
        return $this->dayType;
    }

    public function getHolidayType(): string
    {
        return $this->holidayType;
    }

    public function getRegularHours(): float
    {
        return $this->regularHours;
    }

    public function getOvertimeHours(): float
    {
        return $this->overtimeHours;
    }

    public function getNightDifferential(): float
    {
        return $this->nightDifferential;
    }

    public function getNightDifferentialAndOvertime(): float
    {
        return $this->nightDifferentialAndOvertime;
    }
}
