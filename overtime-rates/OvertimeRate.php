<?php

class OvertimeRate
{
    public function __construct(
        private readonly   int|string|null $id                               = null,
        private            int|string|null $overtimeRateAssignmentId         = null,
        private readonly   string          $dayType                                ,
        private readonly   string          $holidayType                            ,
        private readonly   float           $regularTimeRate                        ,
        private readonly   float           $overtimeRate                           ,
        private readonly   float           $nightDifferentialRate                  ,
        private readonly   float           $nightDifferentialAndOvertimeRate
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getOvertimeRateAssignmentId(): int|string|null
    {
        return $this->overtimeRateAssignmentId;
    }

    public function setOvertimeRateAssignmentId(int $overtimeRateAssignmentId): void
    {
        $this->overtimeRateAssignmentId = $overtimeRateAssignmentId;
    }

    public function getDayType(): string
    {
        return $this->dayType;
    }

    public function getHolidayType(): string
    {
        return $this->holidayType;
    }

    public function getRegularTimeRate(): float
    {
        return $this->regularTimeRate;
    }

    public function getOvertimeRate(): float
    {
        return $this->overtimeRate;
    }

    public function getNightDifferentialRate(): float
    {
        return $this->nightDifferentialRate;
    }

    public function getNightDifferentialAndOvertimeRate(): float
    {
        return $this->nightDifferentialAndOvertimeRate;
    }
}
