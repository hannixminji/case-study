<?php

require_once __DIR__ . '/../../includes/BaseValidator.php';

class LeaveTypeValidator extends BaseValidator
{
    private readonly LeaveTypeRepository $leaveTypeRepository;

    public function __construct(LeaveTypeRepository $leaveTypeRepository)
    {
        $this->leaveTypeRepository = $leaveTypeRepository;
    }

    public function validate(array $fieldsToValidate): void
    {
        foreach ($fieldsToValidate as $field) {
            if ( ! array_key_exists($field, $this->data)) {
                $this->errors[$field] = 'The ' . $field . ' field is missing.';
            } else {
                switch ($field) {
                    case 'id'                    : $this->isValidId                 ($this->data['id'                    ]); break;
                    case 'name'                  : $this->isValidName               ($this->data['name'                  ]); break;
                    case 'maximum_number_of_days': $this->isValidMaximumNumberOfDays($this->data['maximum_number_of_days']); break;
                    case 'is_paid'               : $this->isValidIsPaid             ($this->data['is_paid'               ]); break;
                    case 'is_encashable'         : $this->isValidIsEncashable       ($this->data['is_encashable'         ]); break;
                    case 'description'           : $this->isValidDescription        ($this->data['description'           ]); break;
                    case 'status'                : $this->isValidStatus             ($this->data['status'                ]); break;
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
            $this->errors['name'] = 'Unable to verify the uniqueness of the name. The provided leave type ID may be missing or invalid. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['name'] = 'This name already exists. Please provide a different one.';

            return false;
        }

        return true;
    }

    public function isValidMaximumNumberOfDays(mixed $maximumNumberOfDays): bool
    {
        if ($maximumNumberOfDays === null) {
            $this->errors['maximum_number_of_days'] = 'The maximum number of days cannot be null.';

            return false;
        }

        $maximumNumberOfDays = filter_var($maximumNumberOfDays, FILTER_VALIDATE_INT);

        if ($maximumNumberOfDays === false) {
            $this->errors['maximum_number_of_days'] = 'The maximum number of days must be a valid integer.';

            return false;
        }

        if ($maximumNumberOfDays < 1 || $maximumNumberOfDays > 365) {
            $this->errors['maximum_number_of_days'] = 'The maximum number of days must be between 1 and 365.';

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

    public function isValidIsEncashable(mixed $isEncashable): bool
    {
        if ($isEncashable === null) {
            $this->errors['is_encashable'] = 'The "Is Encashable" field cannot be null.';

            return false;
        }

        if (filter_var($isEncashable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
            $this->errors['is_encashable'] = 'The "Is Encashable" field must be a valid boolean.';

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
                    'column'   => 'leave_type.status',
                    'operator' => '='                ,
                    'value'    => 'Active'
                ],
                [
                    'column'   => 'leave_type.' . $field,
                    'operator' => '='                   ,
                    'value'    => $value
                ]
            ];

            if (is_int($id) || filter_var($id, FILTER_VALIDATE_INT) !== false) {
                $filterCriteria[] = [
                    'column'   => 'leave_type.id',
                    'operator' => '!='           ,
                    'value'    => (int) $id
                ];

            } elseif (is_string($id) && trim($id) !== '' && $this->isValidHash($id)) {
                $filterCriteria[] = [
                    'column'   => 'SHA2(leave_type.id, 256)',
                    'operator' => '!='                      ,
                    'value'    => $id
                ];
            }

            $isUnique = $this->leaveTypeRepository->fetchAllLeaveTypes(
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
