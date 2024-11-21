<?php

class WorkSchedule
{
    public function __construct(
        private readonly ?int    $id                  ,
        private readonly int     $employeeId          ,
        private readonly string  $title               ,
        private readonly string  $startTime           ,
        private readonly string  $endTime             ,
        private readonly bool    $isFlexible          ,
        private readonly ?string $arrival_start_time  ,
        private readonly ?string $arrival_end_time    ,
        private readonly ?string $coreHoursStartTime  ,
        private readonly ?string $coreHoursEndTime    ,
        private readonly ?string $departure_start_time,
        private readonly ?string $departure_end_time  ,
        private readonly ?int    $totalHoursPerWeek   ,
        private readonly string  $startDate           ,
        private readonly string  $recurrenceRule      ,
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

    public function isFlexible(): bool
    {
        return $this->isFlexible;
    }

    public function getArrivalStartTime(): ?string
    {
        return $this->arrival_start_time;
    }

    public function getArrivalEndTime(): ?string
    {
        return $this->arrival_end_time;
    }

    public function getCoreHoursStartTime(): ?string
    {
        return $this->coreHoursStartTime;
    }

    public function getCoreHoursEndTime(): ?string
    {
        return $this->coreHoursEndTime;
    }

    public function getDepartureStartTime(): ?string
    {
        return $this->departure_start_time;
    }

    public function getDepartureEndTime(): ?string
    {
        return $this->departure_end_time;
    }

    public function getTotalHoursPerWeek(): ?int
    {
        return $this->totalHoursPerWeek;
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getRecurrenceRule(): string
    {
        return $this->recurrenceRule;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }
}
