<?php

class BreakScheduleSnapshot
{
    public function __construct(
        private readonly int    $breakScheduleId       ,
        private readonly int    $workScheduleSnapshotId,
        private readonly int    $breakTypeSnapshotId   ,
        private readonly string $startTime             ,
        private readonly string $endTime
    ) {
    }

    public function getBreakScheduleId(): int
    {
        return $this->breakScheduleId;
    }

    public function getWorkScheduleSnapshotId(): int
    {
        return $this->workScheduleSnapshotId;
    }

    public function getBreakTypeSnapshotId(): int
    {
        return $this->breakTypeSnapshotId;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }
}
