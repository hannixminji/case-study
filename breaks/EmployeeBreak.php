<?php

class EmployeeBreak
{
    public function __construct(
        private readonly   int|string|null $id                     = null,
        private readonly   int|string      $breakScheduleId              ,
        private readonly ? string          $startTime              = null,
        private readonly ? string          $endTime                = null,
        private readonly   int             $breakDurationInMinutes = 0   ,
        private readonly   string          $createdAt
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getBreakScheduleId(): int|string
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

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
}
