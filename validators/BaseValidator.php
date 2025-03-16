<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Validator\Validation                  ;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Component\Validator\Constraints\Collection;

abstract class BaseValidator
{
    protected ValidatorInterface $validator;

    protected array $data   = [];
    protected array $errors = [];

    public function __construct()
    {
        $this->validator = Validation::createValidator();

        $this->data = array_merge($_GET, $_POST, $_FILES);
    }

    abstract protected function getConstraints(): Collection;

    public function validate(): void
    {
        $this->errors = [];

        $constraints = $this->getConstraints();

        $violations = $this->validator->validate($this->data, $constraints);

        foreach ($violations as $violation) {
            $this->errors[] = $violation->getPropertyPath() . ' ' . $violation->getMessage();
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function sanitizeData(array $excludeFields = []): void
    {
        foreach ($this->data as $key => $value) {
            if ( ! (isset($_FILES[$key]) || in_array($key, $excludeFields))) {
                $this->data[$key] = htmlspecialchars(
                    strip_tags(trim( (string) $value)),
                    ENT_QUOTES,
                    'UTF-8'
                );
            }
        }
    }
}
