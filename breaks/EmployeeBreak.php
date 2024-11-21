<?php

class EmployeeBreak
{
    public function __construct(
        private readonly ?int    $id                     = null,
        private readonly int     $scheduleBreakId              ,
        private readonly string  $startTime                    ,
        private readonly ?string $endTime                = null,
        private readonly ?int    $breakDurationInMinutes = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScheduleBreakId(): int
    {
        return $this->scheduleBreakId;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function getBreakDurationInMinutes(): ?int
    {
        return $this->breakDurationInMinutes;
    }
}
