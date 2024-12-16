<?php

class Allowance
{
    public function __construct(
        private readonly ? int    $id            = null,
        private readonly   string $name                ,
        private readonly   float  $amount              ,
        private readonly   string $frequency           ,
        private readonly ? string $description   = null,
        private readonly   string $status
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

    public function getAmount(): float
    {
        return $this->amount;
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
