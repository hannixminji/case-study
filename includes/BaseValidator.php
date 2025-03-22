<?php

abstract class BaseValidator
{
    protected string $group      ;
    protected array  $data   = [];
    protected array  $errors = [];

    abstract protected function validate(array $fieldsToValidate): void;

    public function isValidId(mixed $id): bool
    {
        $isEmpty = is_string($id) && trim($id) === '';

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

        if ( ! $isEmpty && ! $this->isValidHash($id)) {
            $this->errors['id'] = 'The ID is an invalid type.';

            return false;
        }

        if (($id === null || $isEmpty) && $this->group !== 'create') {
            $this->errors['id'] = 'The ID cannot be empty or null.';

            return false;
        }

        if ($id !== null && ! is_int($id) && ! is_string($id)) {
            $this->errors['id'] = 'The ID is an invalid type.';

            return false;
        }

        return true;
    }

    public function isValidDescription(mixed $description): bool
    {
        if (is_string($description) && trim($description) !== '') {
            if (mb_strlen($description) > 255) {
                $this->errors['description'] = 'The description cannot exceed 255 characters long.';

                return false;
            }

            if ($description !== htmlspecialchars(strip_tags($description), ENT_QUOTES, 'UTF-8')) {
                $this->errors['description'] = 'The description contains invalid characters.';

                return false;
            }
        }

        if ( ! is_string($description) && ($description !== null || $description !== '')) {
            $this->errors['description'] = 'The description must be a valid string.';

            return false;
        }

        return true;
    }

    public function isValidStatus(mixed $status): bool
    {
        if ($status === null) {
            $this->errors['status'] = 'The status cannot be null.';

            return false;
        }

        if ( ! is_string($status)) {
            $this->errors['status'] = 'The status must be a string.';

            return false;
        }

        if (trim($status) === '') {
            $this->errors['status'] = 'The status cannot be empty.';

            return false;
        }

        $validStatuses = [
            'active'  ,
            'inactive',
            'archived'
        ];

        if ( ! in_array(strtolower($status), $validStatuses)) {
            $this->errors['status'] = 'The status must be active, inactive, or archived.';

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

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    protected function isValidHash(string $value): bool
    {
        $patterns = [
            '/^[a-f0-9]{32}$/'                       ,
            '/^[a-f0-9]{40}$/'                       ,
            '/^[a-f0-9]{64}$/'                       ,
            '/^[a-f0-9]{128}$/'                      ,
            '/^\$2[ayb]\$\d{2}\$[.\/A-Za-z0-9]{53}$/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }
}
