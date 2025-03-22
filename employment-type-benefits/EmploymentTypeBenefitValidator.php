<?php

require_once __DIR__ . '/../includes/BaseValidator.php';

class EmploymentTypeBenefitValidator extends BaseValidator
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
                    case 'id'             : $this->isValidId            ($this->data['id'             ]); break;
                    case 'employment_type': $this->isValidEmploymentType($this->data['employment_type']); break;
                    case 'leave_type_id'  : $this->isValidLeaveTypeId   ($this->data['leave_type_id'  ]); break;
                    case 'allowance_id'   : $this->isValidAllowanceId   ($this->data['allowance_id'   ]); break;
                    case 'deduction_id'   : $this->isValidDeductionId   ($this->data['deduction_id'   ]); break;
                }
            }
        }
    }

    public function isValidEmploymentType(mixed $employmentType): bool
    {
        if ($employmentType === null) {
            $this->errors['employment_type'] = 'The employment type cannot be null.';

            return false;
        }

        if ( ! is_string($employmentType)) {
            $this->errors['employment_type'] = 'The employment type must be a string.';

            return false;
        }

        if (trim($employmentType) === '') {
            $this->errors['employment_type'] = 'The employment type cannot be empty.';

            return false;
        }

        $validEmploymentTypes = [
            'regular'            ,
            'regular permanent'  ,
            'casual'             ,
            'contractual'        ,
            'project-based'      ,
            'seasonal'           ,
            'fixed-term'         ,
            'probationary'       ,
            'part-time'          ,
            'regular part-time'  ,
            'part-time permanent',
            'self-employment'    ,
            'freelance'          ,
            'internship'         ,
            'consultancy'        ,
            'apprenticeship'     ,
            'traineeship'        ,
            'gig'
        ];

        if ( ! in_array(strtolower($employmentType), $validEmploymentTypes)) {
            $this->errors['employment_type'] = 'Please select a valid employment type.';

            return false;
        }

        return true;
    }

    public function isValidLeaveTypeId(mixed $id): bool
    {
        $isEmpty = is_string($id) && trim($id) === '';

        if (is_int($id) || filter_var($id, FILTER_VALIDATE_INT) !== false) {
            if ($id < 1) {
                $this->errors['leave_type_id'] = 'The Leave Type ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['leave_type_id'] = 'The Leave Type ID exceeds the maximum allowable integer size.';

                return false;
            }

            $id = (int) $id;
        }

        if ( ! $isEmpty && ! $this->isValidHash($id)) {
            $this->errors['leave_type_id'] = 'The Leave Type ID is an invalid type.';

            return false;
        }

        if ($id !== null && ! is_int($id) && ! is_string($id)) {
            $this->errors['leave_type_id'] = 'The Leave Type ID is an invalid type.';

            return false;
        }

        return true;
    }

    public function isValidAllowanceId(mixed $id): bool
    {
        $isEmpty = is_string($id) && trim($id) === '';

        if (is_int($id) || filter_var($id, FILTER_VALIDATE_INT) !== false) {
            if ($id < 1) {
                $this->errors['allowance_id'] = 'The Allowance ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['allowance_id'] = 'The Allowance ID exceeds the maximum allowable integer size.';

                return false;
            }

            $id = (int) $id;
        }

        if ( ! $isEmpty && ! $this->isValidHash($id)) {
            $this->errors['allowance_id'] = 'The Allowance ID is an invalid type.';

            return false;
        }

        if ($id !== null && ! is_int($id) && ! is_string($id)) {
            $this->errors['allowance_id'] = 'The Allowance ID is an invalid type.';

            return false;
        }

        return true;
    }

    public function isValidDeductionId(mixed $id): bool
    {
        $isEmpty = is_string($id) && trim($id) === '';

        if (is_int($id) || filter_var($id, FILTER_VALIDATE_INT) !== false) {
            if ($id < 1) {
                $this->errors['deduction_id'] = 'The Deduction ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['deduction_id'] = 'The Deduction ID exceeds the maximum allowable integer size.';

                return false;
            }

            $id = (int) $id;
        }

        if ( ! $isEmpty && ! $this->isValidHash($id)) {
            $this->errors['deduction_id'] = 'The Deduction ID is an invalid type.';

            return false;
        }

        if ($id !== null && ! is_int($id) && ! is_string($id)) {
            $this->errors['deduction_id'] = 'The Deduction ID is an invalid type.';

            return false;
        }

        return true;
    }
}
