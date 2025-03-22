<?php

require_once __DIR__ . '/../includes/BaseValidator.php';

class EmployeeDeductionValidator extends BaseValidator
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
                    case 'deduction_id': $this->isValidDeductionId($this->data['deduction_id']); break;
                    case 'amount'      : $this->isValidAmount     ($this->data['amount'      ]); break;
                }
            }
        }
    }

    public function isValidEmployeeId(mixed $id): bool
    {
        $isEmpty = is_string($id) && trim($id) === '';

        if ($id === null || $isEmpty) {
            $this->errors['employee_id'] = 'The employee ID is required.';

            return false;
        }

        if (is_int($id) || filter_var($id, FILTER_VALIDATE_INT) !== false) {
            if ($id < 1) {
                $this->errors['employee_id'] = 'The employee ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['employee_id'] = 'The employee ID exceeds the maximum allowable integer size.';

                return false;
            }

            $id = filter_var($id, FILTER_VALIDATE_INT);
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

    public function isValidDeductionId(mixed $id): bool
    {
        $isEmpty = is_string($id) && trim($id) === '';

        if ($id === null || $isEmpty) {
            $this->errors['deduction_id'] = 'The deduction ID is required.';

            return false;
        }

        if (is_int($id) || filter_var($id, FILTER_VALIDATE_INT) !== false) {
            if ($id < 1) {
                $this->errors['deduction_id'] = 'The deduction ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['deduction_id'] = 'The deduction ID exceeds the maximum allowable integer size.';

                return false;
            }

            $id = filter_var($id, FILTER_VALIDATE_INT);
        }

        if (is_string($id) && ! $this->isValidHash($id)) {
            $this->errors['deduction_id'] = 'The deduction ID is an invalid type.';

            return false;
        }

        if ( ! is_int($id) && ! is_string($id)) {
            $this->errors['deduction_id'] = 'The deduction ID is an invalid type.';

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
