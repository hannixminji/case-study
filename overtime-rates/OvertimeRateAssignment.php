<?php

class OvertimeRateAssignment
{
    public function __construct(
        private readonly ?int $id               ,
        private readonly ?int $overtimeRateSetId,
        private readonly int  $departmentId     ,
        private readonly int  $jobTitleId       ,
        private readonly int  $employeeId       ,
        private readonly int  $assignmentLevel
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOvertimeRateSetId(): ?int
    {
        return $this->overtimeRateSetId;
    }

    public function getDepartmentId(): int
    {
        return $this->departmentId;
    }

    public function getJobTitleId(): int
    {
        return $this->jobTitleId;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getAssignmentLevel(): int
    {
        return $this->assignmentLevel;
    }
}
