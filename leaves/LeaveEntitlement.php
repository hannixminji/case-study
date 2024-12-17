<?php

class LeaveEntitlement
{
    public function __construct(
        private readonly int|string|null $id                   = null,
        private readonly int|string      $employeeId                 ,
        private readonly int|string      $leaveTypeId                ,
        private readonly int             $numberOfEntitledDays       ,
        private readonly int             $numberOfDaysTaken          ,
        private readonly int             $remainingDays
    ) {
    }

    public function getId(): int|string|null
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
