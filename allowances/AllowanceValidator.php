<?php

require_once __DIR__ . '/../includes/BaseValidator.php';

use Symfony\Component\Validator\Constraints\Collection        ;
use Symfony\Component\Validator\Constraints\NotBlank          ;
use Symfony\Component\Validator\Constraints\Type              ;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length            ;
use Symfony\Component\Validator\Constraints\Regex             ;
use Symfony\Component\Validator\Constraints\Choice            ;

class AllowanceValidator extends BaseValidator
{
    private array $requiredFields = [];

    private array $fieldConstraints = [
        'id' => [
            new NotBlank(),
            new Type('integer'),
            new GreaterThanOrEqual(1)
        ],

        'name' => [
            new NotBlank(),
            new Length(['min' => 2, 'max' => 100]),
            new Regex([
                'pattern' => '/^[A-Za-z0-9._-]+$/',
                'message' => 'The name can only contain letters, numbers, hyphens, underscores, and dots.',
            ])
        ],

        'amount' => [
            new NotBlank(),
            new Type('numeric'),
            new GreaterThanOrEqual(0)
        ],

        'frequency' => [
            new NotBlank(),
            new Choice(['choices' => ['weekly', 'bi-weekly', 'semi-monthly', 'monthly']])
        ],

        'description' => [
            new NotBlank(),
            new Length(['min' => 5, 'max' => 255])
        ],

        'status' => [
            new NotBlank(),
            new Choice(['choices' => ['Active', 'Inactive', 'Archived']])
        ]
    ];

    public function __construct(array $requiredFields)
    {
        $this->requiredFields = $requiredFields;
    }

    protected function getConstraints(): Collection
    {
        return new Collection([
        ]);
    }
}
