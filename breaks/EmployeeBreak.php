<?php

class EmployeeBreak
{
    public function __construct(
        private readonly   int|string|null $id                     ,
        private readonly   int|string|null $attendanceId           ,
        private readonly   int|string      $breakScheduleSnapshotId,
        private readonly ? string          $startTime              ,
        private readonly ? string          $endTime                ,
        private readonly   int             $breakDurationInMinutes ,
        private readonly   string          $createdAt
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getAttendanceId(): int|string
    {
        return $this->attendanceId;
    }

    public function getBreakScheduleSnapshotId(): int|string
    {
        return $this->breakScheduleSnapshotId;
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
