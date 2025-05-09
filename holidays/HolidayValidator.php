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
                $this->errors[$field] = 'The ' . str_replace('_', ' ', $field) . ' field is missing.';
            } else {
                switch ($field) {
                    case 'id'                   : $this->isValidId                 ($this->data['id'                   ]); break;
                    case 'name'                 : $this->isValidName               ($this->data['name'                 ]); break;
                    case 'start_date'           : $this->isValidStartDate          ($this->data['start_date'           ]); break;
                    case 'end_date'             : $this->isValidEndDate            ($this->data['end_date'             ]); break;
                    case 'is_paid'              : $this->isValidIsPaid             ($this->data['is_paid'              ]); break;
                    case 'is_recurring_annually': $this->isValidIsRecurringAnnually($this->data['is_recurring_annually']); break;
                    case 'description'          : $this->isValidDescription        ($this->data['description'          ]); break;
                    case 'status'               : $this->isValidStatus             ($this->data['status'               ]); break;
                }
            }
        }

        if (   array_key_exists('start_date', $this->data) &&
               array_key_exists('end_date'  , $this->data) &&

             ! isset($this->errors['start_date']) &&
             ! isset($this->errors['end_date'  ])) {

            $startDate = new DateTime($this->data['start_date']);
            $endDate   = new DateTime($this->data['end_date'  ]);

            if ($endDate < $startDate) {
                $this->errors['end_date'] = 'End date cannot be earlier than start date.';
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
            $this->errors['name'] = 'Unable to verify the uniqueness of the name. The provided holiday ID may be missing or invalid. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['name'] = 'This name already exists. Please provide a different one.';

            return false;
        }

        return true;
    }

    public function isValidStartDate(mixed $startDate): bool
    {
        if ($startDate === null) {
            $this->errors['start_date'] = 'The start date cannot be null.';

            return false;
        }

        if ( ! is_string($startDate)) {
            $this->errors['start_date'] = 'The start date must be a string.';

            return false;
        }

        if (trim($startDate) === '') {
            $this->errors['start_date'] = 'The start date cannot be empty.';

            return false;
        }

        $date = DateTime::createFromFormat('Y-m-d', $startDate);

        if ($date === false || $date->format('Y-m-d') !== $startDate) {
            $this->errors['start_date'] = 'The start date must be in the Y-m-d format and be a valid date, e.g., 2025-01-01.';

            return false;
        }

        return true;
    }

    public function isValidEndDate(mixed $endDate): bool
    {
        if ($endDate === null) {
            $this->errors['end_date'] = 'The end date cannot be null.';

            return false;
        }

        if ( ! is_string($endDate)) {
            $this->errors['end_date'] = 'The end date must be a string.';

            return false;
        }

        if (trim($endDate) === '') {
            $this->errors['end_date'] = 'The end date cannot be empty.';

            return false;
        }

        $date = DateTime::createFromFormat('Y-m-d', $endDate);

        if ($date === false || $date->format('Y-m-d') !== $endDate) {
            $this->errors['end_date'] = 'The end date must be in the Y-m-d format and be a valid date, e.g., 2025-01-01.';

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

    public function isValidIsRecurringAnnually(mixed $isRecurringAnnually): bool
    {
        if ($isRecurringAnnually === null) {
            $this->errors['is_recurring_annually'] = 'The "Is Recurring Annually" field cannot be null.';

            return false;
        }

        if (filter_var($isRecurringAnnually, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
            $this->errors['is_recurring_annually'] = 'The "Is Recurring Annually" field must be a valid boolean.';

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
                    'column'   => 'holiday.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'   => 'holiday.' . $field,
                    'operator' => '='                ,
                    'value'    => $value
                ]
            ];

            if (is_int($id) || filter_var($id, FILTER_VALIDATE_INT) !== false) {
                $filterCriteria[] = [
                    'column'   => 'holiday.id',
                    'operator' => '!='        ,
                    'value'    => (int) $id
                ];

            } elseif (is_string($id) && trim($id) !== '' && $this->isValidHash($id)) {
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
