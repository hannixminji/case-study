<?php

class BreakTypeSnapshot
{
    public function __construct(
        private readonly int    $breakTypeId              ,
        private readonly string $name                     ,
        private readonly int    $durationInMinutes        ,
        private readonly bool   $isPaid                   ,
        private readonly bool   $requireBreakInAndBreakOut
    ) {
    }

    public function getBreakTypeId(): int
    {
        return $this->breakTypeId;
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

    public function isRequireBreakInAndBreakOut(): bool
    {
        return $this->requireBreakInAndBreakOut;
    }
}
