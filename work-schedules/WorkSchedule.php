<?php

class WorkSchedule
{
    public function __construct(
        private            int|string|null $id                = null,
        private readonly   int|string      $employeeId              ,
        private readonly   string          $startTime               ,
        private readonly   string          $endTime                 ,
        private readonly   bool            $isFlextime              ,
        private readonly ? int             $totalHoursPerWeek = null,
        private readonly   int             $totalWorkHours          ,
        private readonly   string          $startDate               ,
        private readonly   string          $recurrenceRule
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function setId(int|string|null $id): void
    {
        $this->id = $id;
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

    public function getTotalHoursPerWeek(): ?int
    {
        return $this->totalHoursPerWeek;
    }

    public function getTotalWorkHours(): int
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

    public function setRecurrenceRule(string $recurrenceRule): void
    {
        $this->recurrenceRule = $recurrenceRule;
    }
}
