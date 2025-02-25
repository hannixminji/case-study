<?php

class BreakSchedule
{
    public function __construct(
        private readonly int|string|null $id            ,
        private readonly int|string      $workScheduleId,
        private readonly int|string      $breakTypeId   ,
        private readonly string          $startTime     ,
        private readonly string          $endTime
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

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }
}
