<?php

class EmployeeBreak
{
    public function __construct(
        private readonly   int|string|null $id                     = null,
        private readonly   int|string|null $attendanceId           = null,
        private readonly   int|string      $breakScheduleHistoryId       ,
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

    public function getAttendanceId(): int|string|null
    {
        return $this->attendanceId;
    }

    public function getBreakScheduleHistoryId(): int|string
    {
        return $this->breakScheduleHistoryId;
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
