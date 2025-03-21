<?php

require_once __DIR__ . '/../includes/BaseValidator.php';

class EmployeeAllowanceValidator extends BaseValidator
{
    public function __construct()
    {
    }

    public function validate(array $fieldsToValidate): void
    {
        $this->errors = [];

        foreach ($fieldsToValidate as $field) {
            if ( ! array_key_exists($field, $this->data)) {
                $this->errors[$field] = 'The ' . $field . ' field is missing.';
            } else {
                switch ($field) {
                    case 'id'          : $this->isValidId         ($this->data['id'          ]); break;
                    case 'employee_id' : $this->isValidEmployeeId ($this->data['employee_id' ]); break;
                    case 'allowance_id': $this->isValidAllowanceId($this->data['allowance_id']); break;
                    case 'amount'      : $this->isValidAmount     ($this->data['amount'      ]); break;
                }
            }
        }
    }

    public function isValidEmployeeId(mixed $id): bool
    {
        if ($id === null) {
            $this->errors['employee_id'] = 'The employee ID cannot be null.';

            return false;
        }

        if (is_int($id) || (is_string($id) && preg_match('/^[1-9]\d*$/', $id))) {
            if ($id < 1) {
                $this->errors['employee_id'] = 'The employee ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['employee_id'] = 'The employee ID exceeds the maximum allowable integer size.';

                return false;
            }

            $id = (int) $id;
        }

        if (is_string($id) && ! $this->isValidHash($id)) {
            $this->errors['employee_id'] = 'The employee ID is an invalid type.';

            return false;
        }

        if ( ! is_int($id) && ! is_string($id)) {
            $this->errors['employee_id'] = 'The employee ID is an invalid type.';

            return false;
        }

        return true;
    }

    public function isValidAllowanceId(mixed $id): bool
    {
        if ($id === null) {
            $this->errors['allowance_id'] = 'The allowance ID cannot be null.';

            return false;
        }

        if (is_int($id) || (is_string($id) && preg_match('/^[1-9]\d*$/', $id))) {
            if ($id < 1) {
                $this->errors['allowance_id'] = 'The allowance ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['allowance_id'] = 'The allowance ID exceeds the maximum allowable integer size.';

                return false;
            }

            $id = (int) $id;
        }

        if (is_string($id) && ! $this->isValidHash($id)) {
            $this->errors['allowance_id'] = 'The allowance ID is an invalid type.';

            return false;
        }

        if ( ! is_int($id) && ! is_string($id)) {
            $this->errors['allowance_id'] = 'The allowance ID is an invalid type.';

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
}
