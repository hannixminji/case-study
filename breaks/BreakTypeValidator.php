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
                    case 'id'                               : $this->isValidId                         ($this->data['id'                               ]); break;
                    case 'name'                             : $this->isValidName                       ($this->data['name'                             ]); break;
                    case 'duration_in_minutes'              : $this->isValidDurationInMinutes          ($this->data['duration_in_minutes'              ]); break;
                    case 'is_paid'                          : $this->isValidIsPaid                     ($this->data['is_paid'                          ]); break;
                    case 'is_require_break_in_and_break_out': $this->isValidisRequireBreakInAndBreakOut($this->data['is_require_break_in_and_break_out']); break;
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
            $this->errors['duration_in_minutes'] = 'The duration in minutes must be a valid integer.';

            return false;
        }

        if ($durationInMinutes < 10 || $durationInMinutes > 60) {
            $this->errors['duration_in_minutes'] = 'The duration in minutes must be between 10 and 60.';

            return false;
        }

        return true;
    }

    public function isValidIsPaid(mixed $isPaid): bool
    {
        if ($isPaid === null) {
            $this->errors['is_paid'] = 'The "Is Paid" field cannot be null.';

            return false;
        }

        if (filter_var($isPaid, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
            $this->errors['is_paid'] = 'The "Is Paid" field must be a valid boolean.';

            return false;
        }

        return true;
    }

    public function isValidisRequireBreakInAndBreakOut(mixed $isRequireBreakInAndBreakOut): bool
    {
        if ($isRequireBreakInAndBreakOut === null) {
            $this->errors['is_require_break_in_and_break_out'] = 'The "Require Break In and Break Out" field cannot be null.';

            return false;
        }

        $isRequireBreakInAndBreakOut = filter_var($isRequireBreakInAndBreakOut, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($isRequireBreakInAndBreakOut === null) {
            $this->errors['is_require_break_in_and_break_out'] = 'The "Require Break In and Break Out" field must be a valid boolean.';

            return false;
        }

        return true;
    }

    private function isUnique(string $field, mixed $value): ?bool
    {
        if ( ! isset($this->errors['id'])) {
            $id = array_key_exists('id', $this->data)
                ? $this->data['id']
                : null;

            $columns = [
                'id'
            ];

            $filterCriteria = [
                [
                    'column'   => 'break_type.deleted_at',
                    'operator' => 'IS NULL'
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
                    'operator' => '!='           ,
                    'value'    => (int) $id
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
