<?php

class OvertimeRateAssignment
{
    public function __construct(
        private readonly int|string|null $id          ,
        private readonly int|string|null $departmentId,
        private readonly int|string|null $jobTitleId  ,
        private readonly int|string|null $employeeId
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getDepartmentId(): int|string|null
    {
        return $this->departmentId;
    }

    public function getJobTitleId(): int|string|null
    {
        return $this->jobTitleId;
    }

    public function getEmployeeId(): int|string|null
    {
        return $this->employeeId;
    }
}
