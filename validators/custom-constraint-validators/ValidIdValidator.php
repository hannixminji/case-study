<?php

namespace App\Validator\CustomConstraints;

use Symfony\Component\Validator\Constraint         ;
use Symfony\Component\Validator\ConstraintValidator;

use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidIdValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ( ! $constraint instanceof ValidId) {
            throw new UnexpectedTypeException($constraint, ValidId::class);
        }

        if ($value === null) {
            return;
        }

        $violationMessage = $constraint->message;

        if (preg_match('/^[1-9]\d*$/', $value)) {
            if ($value <= 0) {
                $violationMessage = 'The ID must be greater than 0.';
            }

            if ($value > PHP_INT_MAX) {
                $violationMessage = 'The ID exceeds the maximum allowed integer value.';
            }

        } elseif (is_string($value) && $this->isValidHash($value)) {
            return;
        }

        $this->context->buildViolation($violationMessage)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }

    private function isValidHash(string $value): bool
    {
        $patterns = [
            '/^[a-f0-9]{32}$/i'                       ,
            '/^[a-f0-9]{40}$/i'                       ,
            '/^[a-f0-9]{64}$/i'                       ,
            '/^[a-f0-9]{128}$/i'                      ,
            '/^\$2[ayb]\$\d{2}\$[.\/A-Za-z0-9]{53}$/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }
}
