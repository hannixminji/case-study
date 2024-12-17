<?php

class EmployeeAllowance
{
    public function __construct(
        private readonly int|string|null $id          = null,
        private readonly int|string      $employeeId        ,
        private readonly int|string      $allowanceId       ,
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

    public function getAllowanceId(): int|string
    {
        return $this->allowanceId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }
}
