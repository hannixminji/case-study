<?php

class ScheduleBreak
{
    public function __construct(
        private readonly ?int    $id             = null,
        private readonly int     $workScheduleId       ,
        private readonly int     $breakTypeId          ,
        private readonly ?string $startTime      = null
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
}
