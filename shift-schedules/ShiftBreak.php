<?php

class ShiftBreak
{
    public function __construct(
        private readonly ?int    $id             ,
        private readonly int     $shiftScheduleId,
        private readonly int     $breakTypeId    ,
        private readonly ?string $startTime
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShiftScheduleId(): int
    {
        return $this->shiftScheduleId;
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
