<?php

class EmploymentTypeBenefit
{
    public function __construct(
        private readonly int|string|null $id             = null,
        private readonly string          $employmentType       ,
        private readonly int|string|null $leaveTypeId    = null,
        private readonly int|string|null $allowanceId    = null,
        private readonly int|string|null $deductionId    = null
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getEmploymentType(): string
    {
        return $this->employmentType;
    }

    public function getLeaveTypeId(): int|string|null
    {
        return $this->leaveTypeId;
    }

    public function getAllowanceId(): int|string|null
    {
        return $this->allowanceId;
    }

    public function getDeductionId(): int|string|null
    {
        return $this->deductionId;
    }
}
