<?php

require_once __DIR__ . '/../includes/BaseValidator.php';

class PayrollGroupValidator extends BaseValidator
{
    private readonly PayrollGroupRepository $payrollGroupRepository;

    public function __construct(PayrollGroupRepository $payrollGroupRepository)
    {
        $this->payrollGroupRepository = $payrollGroupRepository;
    }

    public function validate(array $fieldsToValidate): void
    {
        foreach ($fieldsToValidate as $field) {
            if ( ! array_key_exists($field, $this->data)) {
                $this->errors[$field] = 'The ' . $field . ' field is missing.';
            } else {
                switch ($field) {
                    case 'id'               : $this->isValidId              ($this->data['id'               ]); break;
                    case 'name'             : $this->isValidName            ($this->data['name'             ]); break;
                    case 'payroll_frequency': $this->isValidPayrollFrequency($this->data['payroll_frequency']); break;
                }

                if (array_key_exists('payroll_frequency', $this->data) && ! isset($this->errors['payroll_frequency'])) {
                    switch ($field) {
                        case 'day_of_weekly_cutoff'      : if (strtolower($this->data['payroll_frequency']) === 'weekly'      ) $this->isValidDayOfWeeklyCutoff      ($this->data['day_of_weekly_cutoff'      ]); break;
                        case 'day_of_biweekly_cutoff'    : if (strtolower($this->data['payroll_frequency']) === 'bi-weekly'   ) $this->isValidDayOfBiweeklyCutoff    ($this->data['day_of_biweekly_cutoff'    ]); break;
                        case 'semi_monthly_first_cutoff' : if (strtolower($this->data['payroll_frequency']) === 'semi-monthly') $this->isValidSemiMonthlyFirstCutoff ($this->data['semi_monthly_first_cutoff' ]); break;
                        case 'semi_monthly_second_cutoff': if (strtolower($this->data['payroll_frequency']) === 'semi-monthly') $this->isValidSemiMonthlySecondCutoff($this->data['semi_monthly_second_cutoff']); break;
                    }
                }

                switch ($field) {
                    case 'payday_offset'    : $this->isValidPaydayOffset    ($this->data['payday_offset'    ]); break;
                    case 'payday_adjustment': $this->isValidPaydayAdjustment($this->data['payday_adjustment']); break;
                    case 'status'           : $this->isValidStatus          ($this->data['status'           ]); break;
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
            $this->errors['name'] = 'Unable to verify the uniqueness of the name. The provided payroll group ID may be missing or invalid. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['name'] = 'This name already exists. Please provide a different one.';

            return false;
        }

        return true;
    }

    public function isValidPayrollFrequency(mixed $payrollFrequency): bool
    {
        if ($payrollFrequency === null) {
            $this->errors['payroll_frequency'] = 'The payroll frequency cannot be null.';

            return false;
        }

        if ( ! is_string($payrollFrequency)) {
            $this->errors['payroll_frequency'] = 'The payroll frequency must be a string.';

            return false;
        }

        if (trim($payrollFrequency) === '') {
            $this->errors['payroll_frequency'] = 'The payroll frequency cannot be empty.';

            return false;
        }

        $validPayrollFrequencies = [
            'weekly'      ,
            'bi-weekly'   ,
            'semi-monthly'
        ];

        if ( ! in_array(strtolower($payrollFrequency), $validPayrollFrequencies)) {
            $this->errors['payroll_frequency'] = 'The payroll frequency must be one of the following: Weekly, Bi-weekly, or Semi-monthly';

            return false;
        }

        return true;
    }

    public function isValidDayOfWeeklyCutoff(mixed $dayOfWeeklyCutoff): bool
    {
        if ($dayOfWeeklyCutoff === null) {
            $this->errors['day_of_weekly_cutoff'] = 'The day of weekly cutoff cannot be null.';

            return false;
        }

        $dayOfWeeklyCutoff = filter_var($dayOfWeeklyCutoff, FILTER_VALIDATE_INT);

        if ($dayOfWeeklyCutoff === false) {
            $this->errors['day_of_weekly_cutoff'] = 'The day of weekly cutoff must be a valid integer.';

            return false;
        }

        if ($dayOfWeeklyCutoff < 0 || $dayOfWeeklyCutoff > 6) {
            $this->errors['day_of_weekly_cutoff'] = 'The day of weekly cutoff must be between 0 (Sunday) and 6 (Saturday).';

            return false;
        }

        return true;
    }

    public function isValidDayOfBiweeklyCutoff(mixed $dayOfBiweeklyCutoff): bool
    {
        if ($dayOfBiweeklyCutoff === null) {
            $this->errors['day_of_biweekly_cutoff'] = 'The day of biweekly cutoff cannot be null.';

            return false;
        }

        $dayOfBiweeklyCutoff = filter_var($dayOfBiweeklyCutoff, FILTER_VALIDATE_INT);

        if ($dayOfBiweeklyCutoff === false) {
            $this->errors['day_of_biweekly_cutoff'] = 'The day of biweekly cutoff must be a valid integer.';

            return false;
        }

        if ($dayOfBiweeklyCutoff < 0 || $dayOfBiweeklyCutoff > 6) {
            $this->errors['day_of_biweekly_cutoff'] = 'The day of biweekly cutoff must be between 0 (Sunday) and 6 (Saturday).';

            return false;
        }

        return true;
    }

    public function isValidSemiMonthlyFirstCutoff(mixed $semiMonthlyFirstCutoff): bool
    {
        if ($semiMonthlyFirstCutoff === null) {
            $this->errors['semi_monthly_first_cutoff'] = 'The semi-monthly first cutoff cannot be null.';

            return false;
        }

        $semiMonthlyFirstCutoff = filter_var($semiMonthlyFirstCutoff, FILTER_VALIDATE_INT);

        if ($semiMonthlyFirstCutoff === false) {
            $this->errors['semi_monthly_first_cutoff'] = 'The semi-monthly first cutoff must be a valid integer.';

            return false;
        }

        if ($semiMonthlyFirstCutoff < 1 || $semiMonthlyFirstCutoff > 15) {
            $this->errors['semi_monthly_first_cutoff'] = 'The semi-monthly first cutoff must be between 1 and 15.';

            return false;
        }

        return true;
    }

    public function isValidSemiMonthlySecondCutoff(mixed $semiMonthlySecondCutoff): bool
    {
        if ($semiMonthlySecondCutoff === null) {
            $this->errors['semi_monthly_second_cutoff'] = 'The semi-monthly second cutoff cannot be null.';

            return false;
        }

        $semiMonthlySecondCutoff = filter_var($semiMonthlySecondCutoff, FILTER_VALIDATE_INT);

        if ($semiMonthlySecondCutoff === false) {
            $this->errors['semi_monthly_second_cutoff'] = 'The semi-monthly second cutoff must be a valid integer.';

            return false;
        }

        if ($semiMonthlySecondCutoff < 16 || $semiMonthlySecondCutoff > 30) {
            $this->errors['semi_monthly_second_cutoff'] = 'The semi-monthly second cutoff must be between 16 and 30.';

            return false;
        }

        return true;
    }

    public function isValidPaydayOffset(mixed $paydayOffset): bool
    {
        if ($paydayOffset === null) {
            $this->errors['payday_offset'] = 'The payday offset cannot be null.';

            return false;
        }

        $paydayOffset = filter_var($paydayOffset, FILTER_VALIDATE_INT);

        if ($paydayOffset === false) {
            $this->errors['payday_offset'] = 'The payday offset must be a valid integer.';

            return false;
        }

        if ($paydayOffset < 0 || $paydayOffset > 5) {
            $this->errors['payday_offset'] = 'The payday offset must be between 0 and 5.';

            return false;
        }

        return true;
    }

    public function isValidPaydayAdjustment(mixed $paydayAdjustment): bool
    {
        if ($paydayAdjustment === null) {
            $this->errors['payday_adjustment'] = 'The payday adjustment cannot be null.';

            return false;
        }

        if ( ! is_string($paydayAdjustment)) {
            $this->errors['payday_adjustment'] = 'The payday adjustment must be a string.';

            return false;
        }

        if (trim($paydayAdjustment) === '') {
            $this->errors['payday_adjustment'] = 'The payday adjustment cannot be empty.';

            return false;
        }

        $validPaydayAdjustments = [
            'on the saturday before'        ,
            'payday remains on the same day',
            'on the monday after'
        ];

        if ( ! in_array(strtolower($paydayAdjustment), $validPaydayAdjustments)) {
            $this->errors['payday_adjustment'] = 'The payday adjustment must be one of the following: On the Saturday before, Payday remains on the same day, or On the Monday after.';

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
                    'column'   => 'payroll_group.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'   => 'payroll_group.' . $field,
                    'operator' => '='                      ,
                    'value'    => $value
                ]
            ];

            if (is_int($id) || filter_var($id, FILTER_VALIDATE_INT) !== false) {
                $filterCriteria[] = [
                    'column'   => 'payroll_group.id',
                    'operator' => '!='              ,
                    'value'    => (int) $id
                ];

            } elseif (is_string($id) && trim($id) !== '' && $this->isValidHash($id)) {
                $filterCriteria[] = [
                    'column'   => 'SHA2(payroll_group.id, 256)',
                    'operator' => '!='                         ,
                    'value'    => $id
                ];
            }

            $isUnique = $this->payrollGroupRepository->fetchAllPayrollGroups(
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
