<?php

class LeaveRequest
{
    public function __construct(
        private readonly null|int|string $id         ,
        private readonly int|string      $employeeId ,
        private readonly int|string      $leaveTypeId,
        private readonly string          $startDate  ,
        private readonly string          $endDate    ,
        private readonly string          $reason     ,
        private readonly bool            $isHalfDay  ,
        private readonly string          $halfDayPart,
        private readonly string          $status
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

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getEndDate(): string
    {
        return $this->endDate;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function isHalfDay(): bool
    {
        return $this->isHalfDay;
    }

    public function getHalfDayPart(): string
    {
        return $this->halfDayPart;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
