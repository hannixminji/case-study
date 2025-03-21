<?php

class EmployeeDeduction
{
    public function __construct(
        private readonly null|int|string $id         ,
        private readonly int|string      $employeeId ,
        private readonly int|string      $deductionId,
        private readonly float           $amount
    ) {
    }

    public function getId(): null|int|string
    {
        return $this->id;
    }

    public function getEmployeeId(): int|string
    {
        return $this->employeeId;
    }

    public function getDeductionId(): int|string
    {
        return $this->deductionId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }
}
