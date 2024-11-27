<?php

class EmployeeAllowance
{
    public function __construct(
        private readonly ? int   $id          = null,
        private readonly   int   $employeeId        ,
        private readonly   int   $allowanceId       ,
        private readonly   float $amount
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getAllowanceId(): int
    {
        return $this->allowanceId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }
}
