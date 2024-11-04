<?php

class LeaveRequest
{
    public function __construct(
        private readonly ?int      $id                ,
        private readonly int       $employeeId        ,
        private readonly int       $leaveTypeId       ,
        private readonly DateTime  $startDate         ,
        private readonly DateTime  $endDate           ,
        private readonly string    $reason            ,
        private readonly string    $status            ,
        private readonly ?DateTime $approvedAt        ,
        private readonly ?int      $approvedByAdmin   ,
        private readonly ?int      $approvedByEmployee
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

    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTime
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

    public function getApprovedAt(): ?DateTime
    {
        return $this->approvedAt;
    }

    public function getApprovedByAdmin(): ?int
    {
        return $this->approvedByAdmin;
    }

    public function getApprovedByEmployee(): ?int
    {
        return $this->approvedByEmployee;
    }
}
