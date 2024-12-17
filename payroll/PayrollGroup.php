<?php

class PayrollGroup
{
    public function __construct(
        private readonly int|string|null $id           = null,
        private readonly string          $name               ,
        private readonly string          $payFrequency       ,
        private readonly string          $status
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

    public function getPayFrequency(): string
    {
        return $this->payFrequency;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
