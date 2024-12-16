<?php

class LeaveRequest
{
    public function __construct(
        private readonly   int|string    $id         ,
        private readonly   int|string    $employeeId ,
        private readonly   int|string    $leaveTypeId,
        private readonly   string $startDate         ,
        private readonly   string $endDate           ,
        private readonly   string $reason            ,
        private readonly   string $status
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
