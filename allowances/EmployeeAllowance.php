<?php

class EmployeeAllowance
{
    public function __construct(
        private readonly null|int|string $id         ,
        private readonly int|string      $employeeId ,
        private readonly int|string      $allowanceId,
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

    public function getAllowanceId(): int|string
    {
        return $this->allowanceId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }
}
