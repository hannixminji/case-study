<?php

class BreakSchedule
{
    public function __construct(
        private readonly   int|string|null $id                = null ,
        private readonly   int|string      $workScheduleId           ,
        private readonly   int|string      $breakTypeId              ,
        private readonly ? string          $startTime         = null ,
        private readonly   bool            $isFlexible        = false,
        private readonly ? string          $earliestStartTime = null ,
        private readonly ? string          $latestEndTime     = null
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getWorkScheduleId(): int|string
    {
        return $this->workScheduleId;
    }

    public function getBreakTypeId(): int|string
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
