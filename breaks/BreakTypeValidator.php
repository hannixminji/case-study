<?php

require_once __DIR__ . '/../includes/BaseValidator.php';

class BreakTypeValidator extends BaseValidator
{
    private readonly BreakTypeRepository $breakTypeRepository;

    public function __construct(BreakTypeRepository $breakTypeRepository)
    {
        $this->breakTypeRepository = $breakTypeRepository;
    }

    public function validate(array $fieldsToValidate): void
    {
        $this->errors = [];

        foreach ($fieldsToValidate as $field) {
            if ( ! array_key_exists($field, $this->data)) {
                $this->errors[$field] = 'The ' . $field . ' field is missing.';
            } else {
                switch ($field) {
                }
            }
        }
    }

    public function isValidName(mixed $name): bool
    {
        if ($name === null) {
            $this->errors['name'] = 'The name cannot be null.';

            return false;
        }

        if ( ! is_string($name)) {
            $this->errors['name'] = 'The name must be a string.';

            return false;
        }

        if (trim($name) === '') {
            $this->errors['name'] = 'The name cannot be empty.';

            return false;
        }

        if (mb_strlen($name) < 3 || mb_strlen($name) > 50) {
            $this->errors['name'] = 'The name must be between 3 and 50 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Za-z0-9._\- ]+$/', $name)) {
            $this->errors['name'] = 'The name contains invalid characters. Only letters, numbers, spaces, and the following characters are allowed: - . _';

            return false;
        }

        if ($name !== htmlspecialchars(strip_tags($name), ENT_QUOTES, 'UTF-8')) {
            $this->errors['name'] = 'The name contains HTML tags or special characters that are not allowed.';

            return false;
        }

        $isUnique = $this->isUnique('name', $name);

        if ($isUnique === null) {
            $this->errors['name'] = 'Unable to verify the uniqueness of the name. The provided break type ID may be missing or invalid. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['name'] = 'This name already exists. Please provide a different one.';

            return false;
        }

        return true;
    }

    public function isValidDurationInMinutes(mixed $durationInMinutes): bool
    {
        if ($durationInMinutes === null) {
            $this->errors['duration_in_minutes'] = 'The duration in minutes cannot be null.';

            return false;
        }

        $durationInMinutes = filter_var($durationInMinutes, FILTER_VALIDATE_INT);

        if ($durationInMinutes === false) {
            $this->errors['duration_in_minutes'] = '';

            return false;
        }

        if ($durationInMinutes < 10 || $durationInMinutes > 60) {
            $this->errors['duration_in_minutes'] = '';

            return false;
        }

        return true;
    }

    public function isValidIsPaid(mixed $isPaid): bool
    {
        return true;
    }

    public function isValidisRequireBreakInAndBreakOut(mixed $isRequireBreakInAndBreakOut): bool
    {
        return true;
    }

    private function isUnique(string $field, mixed $value): ?bool
    {
        if ( ! isset($this->errors['id'])) {
            $id = $this->data['id'] ?? null;

            $columns = [
                'id'
            ];

            $filterCriteria = [
                [
                    'column'   => 'break_type.status',
                    'operator' => '='                ,
                    'value'    => 'Active'
                ],
                [
                    'column'   => 'break_type.' . $field,
                    'operator' => '='                   ,
                    'value'    => $value
                ]
            ];

            if (is_int($id) || filter_var($id, FILTER_VALIDATE_INT) !== false) {
                $filterCriteria[] = [
                    'column'   => 'break_type.id',
                    'operator' => '!='          ,
                    'value'    => $id
                ];

            } elseif (is_string($id) && trim($id) !== '' && $this->isValidHash($id)) {
                $filterCriteria[] = [
                    'column'   => 'SHA2(break_type.id, 256)',
                    'operator' => '!='                      ,
                    'value'    => $id
                ];
            }

            $isUnique = $this->breakTypeRepository->fetchAllBreakTypes(
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
