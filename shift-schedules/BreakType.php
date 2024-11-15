<?php

class BreakType
{
    public function __construct(
        private readonly ?int   $id               ,
        private readonly string $name             ,
        private readonly int    $durationInMinutes,
        private readonly bool   $isPaid
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

    public function getDurationInMinutes(): int
    {
        return $this->durationInMinutes;
    }

    public function isPaid(): bool
    {
        return $this->isPaid;
    }
}
