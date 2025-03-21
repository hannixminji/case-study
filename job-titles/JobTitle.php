<?php

class JobTitle
{
    public function __construct(
        private readonly   null|int|string $id          ,
        private readonly   string          $title       ,
        private readonly   int|string      $departmentId,
        private readonly ? string          $description ,
        private readonly   string          $status
    ) {
    }

    public function getId(): null|int|string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDepartmentId(): int|string
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
