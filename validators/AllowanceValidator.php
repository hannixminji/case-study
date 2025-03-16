<?php

require_once __DIR__ . '/BaseValidator.php';

use App\Validator\CustomConstraints\ValidId;

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

    public function __construct(array $requiredFields)
    {
        $this->requiredFields = $requiredFields;

        if (isset($this->data['frequency'])) {
            $this->data['frequency'] = strtolower($this->data['frequency']);
        }

        if (isset($this->data['status'])) {
            $this->data['status'] = strtolower($this->data['status']);
        }
    }

    protected function getConstraints(): Collection
    {
        return new Collection([
            'id' => [
                new ValidId()
            ],

            'name' => [
                new NotBlank(),
                new Length(['min' => 3, 'max' => 50]),
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
                new Length(['min' => 0, 'max' => 255])
            ],

            'status' => [
                new NotBlank(),
                new Choice(['choices' => ['active', 'inactive', 'archived']])
            ]
        ]);
    }
}
