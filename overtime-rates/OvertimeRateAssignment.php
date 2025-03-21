<?php

class OvertimeRateAssignment
{
    public function __construct(
        private readonly null|int|string $id          ,
        private readonly null|int|string $departmentId,
        private readonly null|int|string $jobTitleId  ,
        private readonly null|int|string $employeeId
    ) {
    }

    public function getId(): null|int|string
    {
        return $this->id;
    }

    public function getDepartmentId(): null|int|string
    {
        return $this->departmentId;
    }

    public function getJobTitleId(): null|int|string
    {
        return $this->jobTitleId;
    }

    public function getEmployeeId(): null|int|string
    {
        return $this->employeeId;
    }
}
