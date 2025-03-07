<?php

class WorkSchedule
{
    public function __construct(
        private readonly   int|string|null $id               ,
        private readonly   int|string      $employeeId       ,
        private readonly   string          $startTime        ,
        private readonly   string          $endTime          ,
        private readonly   bool            $isFlextime       ,
        private readonly ? float           $totalHoursPerWeek,
        private readonly   float           $totalWorkHours   ,
        private readonly   string          $startDate        ,
        private readonly   string          $recurrenceRule
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
}
