<?php

class BreakSchedule
{
    public function __construct(
        private readonly ? int    $id                = null ,
        private readonly   int    $workScheduleId           ,
        private readonly   int    $breakTypeId              ,
        private readonly ? string $startTime         = null ,
        private readonly   bool   $isFlexible        = false,
        private readonly ? string $earliestStartTime = null ,
        private readonly ? string $latestEndTime     = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorkScheduleId(): int
    {
        return $this->workScheduleId;
    }

    public function getBreakTypeId(): int
    {
        return $this->breakTypeId;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime;
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
