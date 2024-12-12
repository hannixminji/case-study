<?php

class PayrollGroup
{
    public function __construct(
        private readonly ? int $id = null,
        private readonly string $name
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
