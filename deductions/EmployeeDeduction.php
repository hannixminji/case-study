<?php

class EmployeeDeduction
{
    public function __construct(
        private readonly ? int    $id         ,
        private readonly   int    $employeeId ,
        private readonly   int    $deductionId,
        private readonly   string $amountType ,
        private readonly   float  $amount
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

    public function getDeductionId(): int
    {
        return $this->deductionId;
    }

    public function getAmountType(): string
    {
        return $this->amountType;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }
}
