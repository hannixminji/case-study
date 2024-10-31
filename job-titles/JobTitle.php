<?php

class JobTitle
{
    public function __construct(
        private readonly ? int    $id          ,
        private readonly   string $title       ,
        private readonly   int    $departmentId,
        private readonly ? string $description ,
        private readonly   string $status
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDepartmentId(): int
    {
        return $this->departmentId;
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
