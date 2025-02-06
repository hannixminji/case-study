<?php

class EmploymentTypeBenefit
{
    public function __construct(
        private readonly int|string|null $id            ,
        private readonly string          $employmentType,
        private readonly int|string|null $leaveTypeId   ,
        private readonly int|string|null $allowanceId   ,
        private readonly int|string|null $deductionId
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
