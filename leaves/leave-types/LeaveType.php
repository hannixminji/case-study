<?php

class LeaveType
{
    public function __construct(
        private readonly ? int    $id                  = null,
        private readonly   string $name                      ,
        private readonly   int    $maximumNumberOfDays       ,
        private readonly   bool   $isPaid                    ,
        private readonly ? string $description         = null,
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

    public function getMaximumNumberOfDays(): int
    {
        return $this->maximumNumberOfDays;
    }

    public function isPaid(): bool
    {
        return $this->isPaid;
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
