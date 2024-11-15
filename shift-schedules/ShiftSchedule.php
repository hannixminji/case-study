<?php

class ShiftSchedule
{
    public function __construct(
        private readonly ?int    $id                ,
        private readonly int     $employeeId        ,
        private readonly string  $shiftTitle        ,
        private readonly string  $startTime         ,
        private readonly string  $endTime           ,
        private readonly bool    $isFlexible        ,
        private readonly ?string $flexibleStartTime ,
        private readonly ?string $flexibleEndTime   ,
        private readonly ?string $coreHoursStartTime,
        private readonly ?string $coreHoursEndTime  ,
        private readonly int     $totalHoursPerWeek ,
        private readonly string  $startDate         ,
        private readonly ?string $recurrencePattern ,
        private readonly ?string $note
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

    public function getShiftTitle(): string
    {
        return $this->shiftTitle;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }

    public function isFlexible(): bool
    {
        return $this->isFlexible;
    }

    public function getFlexibleStartTime(): ?string
    {
        return $this->flexibleStartTime;
    }

    public function getFlexibleEndTime(): ?string
    {
        return $this->flexibleEndTime;
    }

    public function getCoreHoursStartTime(): ?string
    {
        return $this->coreHoursStartTime;
    }

    public function getCoreHoursEndTime(): ?string
    {
        return $this->coreHoursEndTime;
    }

    public function getTotalHoursPerWeek(): int
    {
        return $this->totalHoursPerWeek;
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getRecurrencePattern(): ?string
    {
        return $this->recurrencePattern;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }
}
