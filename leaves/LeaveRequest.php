<?php

class LeaveRequest
{
    public function __construct(
        private readonly mixed  $id          = null,
        private readonly mixed  $employeeId        ,
        private readonly mixed  $leaveTypeId       ,
        private readonly string $startDate         ,
        private readonly string $endDate           ,
        private readonly string $reason            ,
        private readonly string $status
    ) {
    }

    public function getId(): mixed
    {
        return $this->id;
    }

    public function getEmployeeId(): mixed
    {
        return $this->employeeId;
    }

    public function getLeaveTypeId(): mixed
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

    public function getStatus(): string
    {
        return $this->status;
    }
}
