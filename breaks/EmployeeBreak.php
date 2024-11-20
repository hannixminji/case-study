<?php

class EmployeeBreak
{
    public function __construct(
        private readonly ?int    $id                     = null,
        private readonly int     $employeeId                   ,
        private readonly int     $breakTypeId                  ,
        private readonly ?string $startTime              = null,
        private readonly ?string $endTime                = null,
        private readonly ?int    $breakDurationInMinutes = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getBreakTypeId(): int
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

    public function getBreakDurationInMinutes(): int
    {
        return $this->breakDurationInMinutes;
    }
}
