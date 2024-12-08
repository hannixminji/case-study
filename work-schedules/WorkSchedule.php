<?php

class WorkSchedule
{
    public function __construct(
        private readonly ? int    $id                 = null,
        private readonly   int    $employeeId               ,
        private readonly   string $title                    ,
        private readonly   string $startTime                ,
        private readonly   string $endTime                  ,
        private readonly   bool   $isFlextime               ,
        private readonly ? string $coreHoursStartTime = null,
        private readonly ? string $coreHoursEndTime   = null,
        private readonly ? int    $totalHoursPerWeek  = null,
        private readonly   int    $totalWorkHours           ,
        private readonly   string $startDate                ,
        private readonly   string $recurrenceRule           ,
        private readonly ? string $note               = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getTitle(): string
    {
        return $this->title;
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

    public function getCoreHoursStartTime(): ?string
    {
        return $this->coreHoursStartTime;
    }

    public function getCoreHoursEndTime(): ?string
    {
        return $this->coreHoursEndTime;
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

    public function getNote(): ?string
    {
        return $this->note;
    }
}
