<?php

class BreakScheduleSnapshot
{
    public function __construct(
        private readonly   int    $breakScheduleId       ,
        private readonly   int    $workScheduleSnapshotId,
        private readonly   int    $breakTypeSnapshotId   ,
        private readonly ? string $startTime             ,
        private readonly ? string $endTime               ,
        private readonly   bool   $isFlexible            ,
        private readonly ? string $earliestStartTime     ,
        private readonly ? string $latestEndTime
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

    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function isFlexible(): bool
    {
        return $this->isFlexible;
    }

    public function getEarliestStartTime(): ?string
    {
        return $this->earliestStartTime;
    }

    public function getLatestEndTime(): ?string
    {
        return $this->latestEndTime;
    }
}
