<?php

require_once __DIR__ . '/../includes/BaseValidator.php';

class AllowanceValidator extends BaseValidator
{
    private readonly AllowanceRepository $allowanceRepository;

    public function __construct(AllowanceRepository $allowanceRepository)
    {
        $this->allowanceRepository = $allowanceRepository;
    }

    public function validate(array $fieldsToValidate): void
    {
        $this->errors = [];

        foreach ($fieldsToValidate as $field) {
            if ( ! array_key_exists($field, $this->data)) {
                $this->errors[$field] = 'The ' . $field . ' field is missing.';
            } else {
                switch ($field) {
                    case 'id'         : $this->isValidId         ($this->data['id'         ]); break;
                    case 'name'       : $this->isValidName       ($this->data['name'       ]); break;
                    case 'amount'     : $this->isValidAmount     ($this->data['amount'     ]); break;
                    case 'frequency'  : $this->isValidFrequency  ($this->data['frequency'  ]); break;
                    case 'description': $this->isValidDescription($this->data['description']); break;
                    case 'status'     : $this->isValidStatus     ($this->data['status'     ]); break;
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
            $this->errors['name'] = 'Unable to verify the uniqueness of the name. The provided employee ID may be missing or invalid. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['name'] = 'This name already exists. Please provide a different one.';

            return false;
        }

        return true;
    }

    public function isValidAmount(mixed $amount): bool
    {
        if ($amount === null) {
            $this->errors['amount'] = 'The amount cannot be null.';

            return false;
        }

        if ( ! is_numeric($amount)) {
            $this->errors['amount'] = 'The amount must be a number.';

            return false;
        }

        if ($amount < 0) {
            $this->errors['amount'] = 'The amount cannot be less than 0.';

            return false;
        }

        if ($amount > 50_000) {
            $this->errors['amount'] = 'The amount cannot exceed ₱50,000.';

            return false;
        }

        return true;
    }

    public function isValidFrequency(mixed $frequency): bool
    {
        if ($frequency === null) {
            $this->errors['frequency'] = 'The frequency cannot be null.';

            return false;
        }

        if ( ! is_string($frequency)) {
            $this->errors['frequency'] = 'The frequency must be a string.';

            return false;
        }

        if (trim($frequency) === '') {
            $this->errors['frequency'] = 'The frequency cannot be empty.';

            return false;
        }

        $validFrequencies = [
            'weekly'      ,
            'bi-weekly'   ,
            'semi-monthly',
            'monthly'
        ];

        if ( ! in_array(strtolower($frequency), $validFrequencies)) {
            $this->errors['frequency'] = 'The frequency must be one of the following: Weekly, Bi-weekly, Semi-monthly, or Monthly.';

            return false;
        }

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
                    'column'   => 'allowance.status',
                    'operator' => '='               ,
                    'value'    => 'Active'
                ],
                [
                    'column'   => 'allowance.' . $field,
                    'operator' => '='                  ,
                    'value'    => $value
                ]
            ];

            if (is_int($id) || (is_string($id) && preg_match('/^[1-9]\d*$/', $id))) {
                $filterCriteria[] = [
                    'column'   => 'allowance.id',
                    'operator' => '!='          ,
                    'value'    => $id
                ];

            } elseif (is_string($id) && $this->isValidHash($id)) {
                $filterCriteria[] = [
                    'column'   => 'SHA2(allowance.id, 256)',
                    'operator' => '!='                     ,
                    'value'    => $id
                ];
            }

            $isUnique = $this->allowanceRepository->fetchAllAllowances(
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
