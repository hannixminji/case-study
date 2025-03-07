<?php

class WorkScheduleSnapshot
{
    public function __construct(
        private readonly   int    $workScheduleId    ,
        private readonly   int    $employeeId        ,
        private readonly   string $startTime         ,
        private readonly   string $endTime           ,
        private readonly   bool   $isFlextime        ,
        private readonly ? float  $totalHoursPerWeek ,
        private readonly   float  $totalWorkHours    ,
        private readonly   string $startDate         ,
        private readonly   string $recurrenceRule    ,
        private readonly   int    $gracePeriod       ,
        private readonly   int    $earlyCheckInWindow
    ) {
    }

    public function getWorkScheduleId(): int
    {
        return $this->workScheduleId;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }

    public function isFlextime(): bool
    {
        return $this->isFlextime;
    }

    public function getTotalHoursPerWeek(): ?float
    {
        return $this->totalHoursPerWeek;
    }

    public function getTotalWorkHours(): float
    {
        return $this->totalWorkHours;
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getRecurrenceRule(): string
    {
        return $this->recurrenceRule;
    }

    public function getGracePeriod(): int
    {
        return $this->gracePeriod;
    }

    public function getEarlyCheckInWindow(): int
    {
        return $this->earlyCheckInWindow;
    }
}
