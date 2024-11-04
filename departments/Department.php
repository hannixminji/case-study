<?php

class Department
{
    public function __construct(
        private readonly ?int    $id              ,
        private readonly string  $name            ,
        private readonly ?int    $departmentHeadId,
        private readonly ?string $description     ,
        private readonly string  $status
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

    public function getDepartmentHeadId(): ?int
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
