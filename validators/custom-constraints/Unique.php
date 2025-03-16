<?php

namespace App\Validator\CustomConstraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Unique extends Constraint
{
    public string $message = 'The value "{{ value }}" must be unique.';

    public string $repositoryClass;
    public string $field          ;

    public function validatedBy(): string
    {
        return ValidIdValidator::class;
    }

    public function getTargets(): array|string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
