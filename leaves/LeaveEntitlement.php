<?php

class LeaveEntitlement
{
    public function __construct(
        private readonly null|int|string $id                  ,
        private readonly int|string      $employeeId          ,
        private readonly int|string      $leaveTypeId         ,
        private readonly float           $numberOfEntitledDays,
        private readonly float           $numberOfDaysTaken   ,
        private readonly float           $remainingDays
    ) {
    }

    public function getId(): null|int|string
    {
        return $this->id;
    }

    public function getEmployeeId(): int|string
    {
        return $this->employeeId;
    }

    public function getLeaveTypeId(): int|string
    {
        return $this->leaveTypeId;
    }

    public function getNumberOfEntitledDays(): float
    {
        return $this->numberOfEntitledDays;
    }

    public function getNumberOfDaysTaken(): float
    {
        return $this->numberOfDaysTaken;
    }

    public function getRemainingDays(): float
    {
        return $this->remainingDays;
    }
}
