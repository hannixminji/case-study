<?php

abstract class BaseValidator
{
    protected array  $errors = [];
    protected string $group      ;

    public function __construct()
    {
    }

    abstract protected function validate(array $fieldsToValidate): void;

    public function isValidId(mixed $id): bool
    {
        if (is_int($id) || (is_string($id) && preg_match('/^[1-9]\d*$/', $id))) {
            if ($id < 1) {
                $this->errors['id'] = 'The ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['id'] = 'The ID exceeds the maximum allowable integer size.';

                return false;
            }

            $id = (int) $id;
        }

        if (is_string($id) && ! $this->isValidHash($id)) {
            $this->errors['id'] = 'The ID is an invalid type.';

            return false;
        }

        if ($id === null && $this->group !== 'create') {
            $this->errors['id'] = 'The ID cannot be null.';

            return false;
        }

        if ($id !== null && ! is_int($id) && ! is_string($id)) {
            $this->errors['id'] = 'The ID is an invalid type.';

            return false;
        }

        return true;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setGroup(string $group): void
    {
        $this->group = $group;
    }

    protected function isValidHash(string $value): bool
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
