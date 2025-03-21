<?php

class Department
{
    public function __construct(
        private readonly   null|int|string $id              ,
        private readonly   string          $name            ,
        private readonly   null|int|string $departmentHeadId,
        private readonly ? string          $description     ,
        private readonly   string          $status
    ) {
    }

    public function getId(): null|int|string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDepartmentHeadId(): null|int|string
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
