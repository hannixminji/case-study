<?php

class LeaveEntitlement
{
    public function __construct(
        private readonly ?int $id                  ,
        private readonly int  $employeeId          ,
        private readonly int  $leaveTypeId         ,
        private readonly int  $numberOfEntitledDays,
        private readonly int  $numberOfDaysTaken   ,
        private readonly int  $remainingDays
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

    public function getLeaveTypeId(): int
    {
        return $this->leaveTypeId;
    }

    public function getNumberOfEntitledDays(): int
    {
        return $this->numberOfEntitledDays;
    }

    public function getNumberOfDaysTaken(): int
    {
        return $this->numberOfDaysTaken;
    }

    public function getRemainingDays(): int
    {
        return $this->remainingDays;
    }
}
