<?php

require_once __DIR__ . '/../includes/BaseValidator.php';

class OvertimeRateValidator extends BaseValidator
{
    public function __construct()
    {
    }

    public function validate(array $fieldsToValidate): void
    {
        foreach ($fieldsToValidate as $field) {
            if ( ! array_key_exists($field, $this->data)) {
                $this->errors[$field] = 'The ' . str_replace('_', ' ', $field) . ' field is missing.';
            } else {
                switch ($field) {
                    case 'id'                                  : $this->isValidId                              ($this->data['id'                                  ]); break;
                    case 'overtime_rate_assignment_id'         : $this->isValidOvertimeRateAssignmentId        ($this->data['overtime_rate_assignment_id'         ]); break;
                    case 'day_type'                            : $this->isValidDayType                         ($this->data['day_type'                            ]); break;
                    case 'holiday_type'                        : $this->isValidHolidayType                     ($this->data['holiday_type'                        ]); break;
                    case 'regular_time_rate'                   : $this->isValidRegularTimeRate                 ($this->data['regular_time_rate'                   ]); break;
                    case 'overtime_rate'                       : $this->isValidOvertimeRate                    ($this->data['overtime_rate'                       ]); break;
                    case 'night_differential_rate'             : $this->isValidNightDifferentialRate           ($this->data['night_differential_rate'             ]); break;
                    case 'night_differential_and_overtime_rate': $this->isValidNightDifferentialAndOvertimeRate($this->data['night_differential_and_overtime_rate']); break;
                }
            }
        }
    }

    public function isValidOvertimeRateAssignmentId(mixed $id): bool
    {
        $isEmpty = is_string($id) && trim($id) === '';

        if ($id === null || $isEmpty) {
            $this->errors['overtime_rate_assignment_id'] = 'The overtime rate assignment ID is required.';

            return false;
        }

        if (is_int($id) || filter_var($id, FILTER_VALIDATE_INT) !== false || (is_string($id) && preg_match('/^-?(0|[1-9]\d*)$/', $id))) {
            if ($id < 1) {
                $this->errors['overtime_rate_assignment_id'] = 'The overtime rate assignment ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['overtime_rate_assignment_id'] = 'The overtime rate assignment ID exceeds the maximum allowable integer size.';

                return false;
            }

            return true;
        }

        if ( ! $isEmpty && $this->isValidHash($id)) {
            return true;
        }

        $this->errors['overtime_rate_assignment_id'] = 'Invalid overtime rate assignment ID. Please ensure the overtime rate assignment ID is correct and try again.';

        return false;
    }

    public function isValidDayType(mixed $dayType): bool
    {
        if ($dayType === null) {
            $this->errors['day_type'] = 'The day type cannot be null.';

            return false;
        }

        if ( ! is_string($dayType)) {
            $this->errors['day_type'] = 'The day type must be a string.';

            return false;
        }

        if (trim($dayType) === '') {
            $this->errors['day_type'] = 'The day type cannot be empty.';

            return false;
        }

        $validDayTypes = [
            'regular day',
            'rest day'
        ];

        if ( ! in_array(strtolower($dayType), $validDayTypes)) {
            $this->errors['day_type'] = 'The day type must be one of the following: Regular Day or Rest Day';

            return false;
        }

        return true;
    }

    public function isValidHolidayType(mixed $holidayType): bool
    {
        if ($holidayType === null) {
            $this->errors['holiday_type'] = 'The holiday type cannot be null.';

            return false;
        }

        if ( ! is_string($holidayType)) {
            $this->errors['holiday_type'] = 'The holiday type must be a string.';

            return false;
        }

        if (trim($holidayType) === '') {
            $this->errors['holiday_type'] = 'The holiday type cannot be empty.';

            return false;
        }

        $validHolidayTypes = [
            'non-holiday'           ,
            'special holiday'       ,
            'regular holiday'       ,
            'double special holiday',
            'double holiday'
        ];

        if ( ! in_array(strtolower($holidayType), $validHolidayTypes)) {
            $this->errors['holiday_type'] = 'The holiday type must be one of the following: Non-Holiday, Special Holiday, Regular Holiday, Double Special Holiday, or Double Holiday';

            return false;
        }

        return true;
    }

    public function isValidRegularTimeRate(mixed $regularTimeRate): bool
    {
        if ($regularTimeRate === null) {
            $this->errors['regular_time_rate'] = 'The regular time rate cannot be null.';

            return false;
        }

        $regularTimeRate = filter_var($regularTimeRate, FILTER_VALIDATE_FLOAT);

        if ($regularTimeRate === false) {
            $this->errors['regular_time_rate'] = 'The regular time rate must be a valid number.';

            return false;
        }

        if ($regularTimeRate <= 0.0 || $regularTimeRate > 10.0) {
            $this->errors['regular_time_rate'] = 'The regular time rate must be greater than 0 and less than or equal to 10.';

            return false;
        }

        return true;
    }

    public function isValidOvertimeRate(mixed $overtimeRate): bool
    {
        if ($overtimeRate === null) {
            $this->errors['overtime_rate'] = 'The overtime rate cannot be null.';

            return false;
        }

        $overtimeRate = filter_var($overtimeRate, FILTER_VALIDATE_FLOAT);

        if ($overtimeRate === false) {
            $this->errors['overtime_rate'] = 'The overtime rate must be a valid number.';

            return false;
        }

        if ($overtimeRate <= 0.0 || $overtimeRate > 10.0) {
            $this->errors['overtime_rate'] = 'The overtime rate must be greater than 0 and less than or equal to 10.';

            return false;
        }

        return true;
    }

    public function isValidNightDifferentialRate(mixed $nightDifferentialRate): bool
    {
        if ($nightDifferentialRate === null) {
            $this->errors['night_differential_rate'] = 'The night differential rate cannot be null.';

            return false;
        }

        $nightDifferentialRate = filter_var($nightDifferentialRate, FILTER_VALIDATE_FLOAT);

        if ($nightDifferentialRate === false) {
            $this->errors['night_differential_rate'] = 'The night differential rate must be a valid number.';

            return false;
        }

        if ($nightDifferentialRate <= 0.0 || $nightDifferentialRate > 10.0) {
            $this->errors['night_differential_rate'] = 'The night differential rate must be greater than 0 and less than or equal to 10.';

            return false;
        }

        return true;
    }

    public function isValidNightDifferentialAndOvertimeRate(mixed $nightDifferentialAndOvertimeRate): bool
    {
        if ($nightDifferentialAndOvertimeRate === null) {
            $this->errors['night_differential_and_overtime_rate'] = 'The night differential and overtime rate cannot be null.';

            return false;
        }

        $nightDifferentialAndOvertimeRate = filter_var($nightDifferentialAndOvertimeRate, FILTER_VALIDATE_FLOAT);

        if ($nightDifferentialAndOvertimeRate === false) {
            $this->errors['night_differential_and_overtime_rate'] = 'The night differential and overtime rate must be a valid number.';

            return false;
        }

        if ($nightDifferentialAndOvertimeRate <= 0.0 || $nightDifferentialAndOvertimeRate > 10.0) {
            $this->errors['night_differential_and_overtime_rate'] = 'The night differential and overtime rate must be greater than 0 and less than or equal to 10.';

            return false;
        }

        return true;
    }
}
