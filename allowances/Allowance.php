<?php

class Allowance
{
    public function __construct(
        private readonly   null|int|string $id         ,
        private readonly   string          $name       ,
        private readonly   float           $amount     ,
        private readonly   string          $frequency  ,
        private readonly ? string          $description,
        private readonly   string          $status
    ) {
    }

    public function getId(): null|int|string
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
