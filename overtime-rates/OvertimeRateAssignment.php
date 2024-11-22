<?php

class OvertimeRateAssignment
{
    public function __construct(
        private readonly ?int $id           = null,
        private readonly ?int $departmentId = null,
        private readonly ?int $jobTitleId   = null,
        private readonly ?int $employeeId   = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepartmentId(): ?int
    {
        return $this->departmentId;
    }

    public function getJobTitleId(): ?int
    {
        return $this->jobTitleId;
    }

    public function getEmployeeId(): ?int
    {
        return $this->employeeId;
    }
}
