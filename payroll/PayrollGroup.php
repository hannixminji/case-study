<?php

class PayrollGroup
{
    public function __construct(
        private readonly   int|string|null $id                     ,
        private readonly   string          $name                   ,
        private readonly   string          $payrollFrequency       ,
        private readonly ? int             $dayOfWeeklyCutoff      ,
        private readonly ? int             $dayOfBiweeklyCutoff    ,
        private readonly ? int             $semiMonthlyFirstCutoff ,
        private readonly ? int             $semiMonthlySecondCutoff,
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

    public function getDayOfWeeklyCutoff(): ?int
    {
        return $this->dayOfWeeklyCutoff;
    }

    public function getDayOfBiweeklyCutoff(): ?int
    {
        return $this->dayOfBiweeklyCutoff;
    }

    public function getSemiMonthlyFirstCutoff(): ?int
    {
        return $this->semiMonthlyFirstCutoff;
    }

    public function getSemiMonthlySecondCutoff(): ?int
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
