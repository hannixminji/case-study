<?php

namespace App\Validator\CustomConstraints;

use Symfony\Component\Validator\Constraint         ;
use Symfony\Component\Validator\ConstraintValidator;

use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueValidator extends ConstraintValidator
{
    private object $repository;

    public function __construct(object $repository)
    {
        $this->repository = $repository;
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ( ! $constraint instanceof Unique) {
            throw new UnexpectedTypeException($constraint, Unique::class);
        }

        if (htmlspecialchars(strip_tags(trim( (string) $value)), ENT_QUOTES, 'UTF-8')) {
        }
    }
}
