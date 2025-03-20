<?php

require_once __DIR__ . '/../includes/BaseValidator.php';

class HolidayValidator extends BaseValidator
{
    private readonly HolidayRepository $holidayRepository;

    public function __construct(HolidayRepository $holidayRepository)
    {
        $this->holidayRepository = $holidayRepository;
    }

    public function validate(array $fieldsToValidate): void
    {
        $this->errors = [];

        foreach ($fieldsToValidate as $field) {
            if ( ! array_key_exists($field, $this->data)) {
                $this->errors[$field] = 'The ' . $field . ' field is missing.';
            } else {
                switch ($field) {
                    case 'id'                : $this->isValidId              ($this->data['id'                ]                           ); break;
                    case 'name'              : $this->isValidName            ($this->data['name'              ], $this->data['id'] ?? null); break;
                    case 'description'       : $this->isValidDescription     ($this->data['description'       ]                           ); break;
                    case 'status'            : $this->isValidStatus          ($this->data['status'            ]                           ); break;
                }
            }
        }
    }

    public function isValidName(mixed $name, mixed $id): bool
    {
        if ( ! is_string($name)) {
            $this->errors['name'] = 'The name must be a string.';

            return false;
        }

        $name = trim($name);

        if ($name === '') {
            $this->errors['name'] = 'The name cannot be empty.';

            return false;
        }

        if (mb_strlen($name) < 3 || mb_strlen($name) > 50) {
            $this->errors['name'] = 'The name must be between 3 and 50 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Za-z0-9._\- ]+$/', $name)) {
            $this->errors['name'] = 'The name can only contain letters, numbers, periods, hyphens, underscores, and spaces.';

            return false;
        }

        if ($name !== htmlspecialchars(strip_tags($name), ENT_QUOTES, 'UTF-8')) {
            $this->errors['name'] = 'The name contains invalid characters.';

            return false;
        }

        $isUnique = $this->isUnique('name', $name, $id);

        if ($isUnique === null) {
            $this->errors['name'] = 'An unexpected error occurred while checking for uniqueness.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['name'] = 'The name must be unique, another entry already exists with this name.';

            return false;
        }

        return true;
    }

    public function isValidDate(mixed $date): bool
    {
        return true;
    }

    private function isUnique(string $field, mixed $value, mixed $id): ?bool
    {
        if ( ! isset($this->errors['id'])) {
            $columns = [
                'id'
            ];

            $filterCriteria = [
                [
                    'column'   => 'holiday.status',
                    'operator' => '='             ,
                    'value'    => 'Active'
                ],
                [
                    'column'   => 'holiday.' . $field,
                    'operator' => '='                ,
                    'value'    => $value
                ]
            ];

            if (is_int($id) || (is_string($id) && preg_match('/^[1-9]\d*$/', $id))) {
                $filterCriteria[] = [
                    'column'   => 'holiday.id',
                    'operator' => '!='        ,
                    'value'    => $id
                ];

            } elseif (is_string($id) && ! $this->isValidHash($id)) {
                $filterCriteria[] = [
                    'column'   => 'SHA2(holiday.id, 256)',
                    'operator' => '!='                   ,
                    'value'    => $id
                ];
            }

            $isUnique = $this->holidayRepository->fetchAllHolidays(
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
