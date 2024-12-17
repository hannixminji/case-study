<?php

class EmployeeDeduction
{
    public function __construct(
        private readonly int|string|null $id          = null,
        private readonly int|string      $employeeId        ,
        private readonly int|string      $deductionId       ,
        private readonly float           $amount
    ) {
    }

    public function getId(): int|string|null
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
