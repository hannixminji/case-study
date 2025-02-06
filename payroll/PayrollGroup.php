<?php

class PayrollGroup
{
    public function __construct(
        private readonly   int|string|null $id                     ,
        private readonly   string          $name                   ,
        private readonly   string          $payrollFrequency       ,
        private readonly ? string          $dayOfWeeklyCutoff      ,
        private readonly ? string          $dayOfBiweeklyCutoff    ,
        private readonly ? string          $semiMonthlyFirstCutoff ,
        private readonly ? string          $semiMonthlySecondCutoff,
        private readonly   int             $paydayOffset           ,
        private readonly   string          $paydayAdjustment       ,
        private readonly   string          $status
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPayrollFrequency(): string
    {
        return $this->payrollFrequency;
    }

    public function getDayOfWeeklyCutoff(): ?string
    {
        return $this->dayOfWeeklyCutoff;
    }

    public function getDayOfBiweeklyCutoff(): ?string
    {
        return $this->dayOfBiweeklyCutoff;
    }

    public function getSemiMonthlyFirstCutoff(): ?string
    {
        return $this->semiMonthlyFirstCutoff;
    }

    public function getSemiMonthlySecondCutoff(): ?string
    {
        return $this->semiMonthlySecondCutoff;
    }

    public function getPaydayOffset(): int
    {
        return $this->paydayOffset;
    }

    public function getPaydayAdjustment(): string
    {
        return $this->paydayAdjustment;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
