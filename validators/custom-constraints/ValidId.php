<?php

namespace App\Validator\CustomConstraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidId extends Constraint
{
    public string $message = 'The value "{{ value }}" is not a valid ID.';

    public function validatedBy(): string
    {
        return ValidIdValidator::class;
    }

    public function getTargets(): array|string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
