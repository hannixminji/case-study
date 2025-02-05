<?php

class Holiday
{
    public function __construct(
        private readonly   int|string|null $id                 ,
        private readonly   string          $name               ,
        private readonly   string          $startDate          ,
        private readonly   string          $endDate            ,
        private readonly   bool            $isPaid             ,
        private readonly   bool            $isRecurringAnnually,
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

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getEndDate(): string
    {
        return $this->endDate;
    }

    public function getIsPaid(): bool
    {
        return $this->isPaid;
    }

    public function getIsRecurringAnnually(): bool
    {
        return $this->isRecurringAnnually;
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
