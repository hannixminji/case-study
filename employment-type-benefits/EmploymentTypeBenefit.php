<?php

class EmploymentTypeBenefit
{
    public function __construct(
        private readonly null|int|string $id            ,
        private readonly string          $employmentType,
        private readonly null|int|string $leaveTypeId   ,
        private readonly null|int|string $allowanceId   ,
        private readonly null|int|string $deductionId
    ) {
    }

    public function getId(): null|int|string
    {
        return $this->id;
    }

    public function getEmploymentType(): string
    {
        return $this->employmentType;
    }

    public function getLeaveTypeId(): null|int|string
    {
        return $this->leaveTypeId;
    }

    public function getAllowanceId(): null|int|string
    {
        return $this->allowanceId;
    }

    public function getDeductionId(): null|int|string
    {
        return $this->deductionId;
    }
}
