<?php

class BreakType
{
    public function __construct(
        private readonly int|string|null $id                        = null,
        private readonly string          $name                            ,
        private readonly int             $durationInMinutes               ,
        private readonly bool            $isPaid                          ,
        private readonly bool            $requireBreakInAndBreakOut
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
