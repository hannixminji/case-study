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

        if (($id === null || $isEmpty) && $this->group === 'create') {
            return true;
        }

        if ($id === null || $isEmpty) {
            $this->errors['id'] = 'The ID is required.';

            return false;
        }

        if (is_int($id) || filter_var($id, FILTER_VALIDATE_INT) !== false || (is_string($id) && preg_match('/^-?(0|[1-9]\d*)$/', $id))) {
            if ($id < 1) {
                $this->errors['id'] = 'The ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['id'] = 'The ID exceeds the maximum allowable integer size.';

                return false;
            }

            return true;
        }

        if ( ! $isEmpty && $this->isValidHash($id)) {
            return true;
        }

        $this->errors['id'] = 'Invalid ID. Please ensure the ID is correct and try again.';

        return false;
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

    protected function isUniques(
        object $repository,
        string $tableName ,
        string $columnName,
        mixed  $value
    ): ?bool {

        $fetchAllMethod = 'fetchAll' . ucfirst($tableName) . 's';

        if ( ! isset($this->errors['id']) && method_exists($repository, $fetchAllMethod)) {
            $id = null;

            if (array_key_exists('id', $this->data)) {
                $id = filter_var($this->data['id'], FILTER_VALIDATE_INT);

                if ($id === false) {
                    $id = $this->data['id'];
                }
            }

            $columns = [
                'id'
            ];

            $filterCriteria = [
                [
                    'column'   => $tableName . '.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'   => $tableName . '.' . $columnName,
                    'operator' => '='                           ,
                    'value'    => $value
                ]
            ];

            if (is_int($id)) {
                $filterCriteria[] = [
                    'column'   => $tableName . '.id',
                    'operator' => '!='              ,
                    'value'    => $id
                ];

            } else {
                $filterCriteria[] = [
                    'column'   => 'SHA2(' . $tableName . '.id, 256)',
                    'operator' => '!='                              ,
                    'value'    => $id
                ];
            }

            $isUnique = $repository->{$fetchAllMethod}(
                columns             : $columns       ,
                filterCriteria      : $filterCriteria,
                limit               : 1              ,
                includeTotalRowCount: false
            );

            if ($isUnique === ActionResult::FAILURE) {
                return null;
            }

            return empty($isUnique['result_set']);
        }

        return null;
    }
}
