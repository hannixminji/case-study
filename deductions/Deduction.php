<?php

class Deduction
{
    public function __construct(
        private readonly ?int    $id           ,
        private readonly string  $name         ,
        private readonly string  $amountType   ,
        private readonly float   $amount       ,
        private readonly bool    $isPreTax     ,
        private readonly string  $frequency    ,
        private readonly ?string $description  ,
        private readonly string  $status
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAmountType(): string
    {
        return $this->amountType;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getIsPreTax(): bool
    {
        return $this->isPreTax;
    }

    public function getFrequency(): string
    {
        return $this->frequency;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
