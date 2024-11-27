<?php

class EmployeeBreak
{
    public function __construct(
        private readonly ? int    $id                     = null,
        private readonly   int    $breakScheduleId              ,
        private readonly ? string $startTime              = null,
        private readonly ? string $endTime                = null,
        private readonly   int    $breakDurationInMinutes = 0
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBreakScheduleId(): int
    {
        return $this->breakScheduleId;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function getBreakDurationInMinutes(): int
    {
        return $this->breakDurationInMinutes;
    }
}
