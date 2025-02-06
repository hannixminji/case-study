<?php

class LeaveType
{
    public function __construct(
        private readonly   int|string|null $id                 ,
        private readonly   string          $name               ,
        private readonly   int             $maximumNumberOfDays,
        private readonly   bool            $isPaid             ,
        private readonly   bool            $isEncashable       ,
        private readonly ? string          $description        ,
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

    public function getMaximumNumberOfDays(): int
    {
        return $this->maximumNumberOfDays;
    }

    public function isPaid(): bool
    {
        return $this->isPaid;
    }

    public function isEncashable(): bool
    {
        return $this->isEncashable;
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
