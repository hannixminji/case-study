<?php

class PayrollGroup
{
    public function __construct(
        private readonly ? int $id = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
