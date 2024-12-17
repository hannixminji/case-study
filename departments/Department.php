<?php

class Department
{
    public function __construct(
        private readonly   int|string|null $id               = null,
        private readonly   string          $name                   ,
        private readonly   int|string|null $departmentHeadId = null,
        private readonly ? string          $description            ,
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

    public function getDepartmentHeadId(): int|string|null
    {
        return $this->departmentHeadId;
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
