<?php

require_once __DIR__ . "/../vendor/autoload.php"       ;

require_once __DIR__ . '/../includes/BaseValidator.php';

class WorkScheduleValidator extends BaseValidator
{
    private readonly WorkScheduleRepository $workScheduleRepository;

    public function __construct(WorkScheduleRepository $workScheduleRepository)
    {
        $this->workScheduleRepository = $workScheduleRepository;
    }

    public function validate(array $fieldsToValidate): void
    {
        foreach ($fieldsToValidate as $field) {
            if ( ! array_key_exists($field, $this->data)) {
                $this->errors[$field] = 'The ' . $field . ' field is missing.';
            } else {
                switch ($field) {
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

        if (is_int($id) || filter_var($id, FILTER_VALIDATE_INT) !== false || (is_string($id) && preg_match('/^-?(0|[1-9]\d*)$/', $id))) {
            if ($id < 1) {
                $this->errors['employee_id'] = 'The employee ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['employee_id'] = 'The employee ID exceeds the maximum allowable integer size.';

                return false;
            }

            return true;
        }

        if ( ! $isEmpty && $this->isValidHash($id)) {
            return true;
        }

        $this->errors['employee_id'] = 'Invalid employee ID. Please ensure the employee ID is correct and try again.';

        return false;
    }

    public function isValidStartTime(mixed $startTime): bool
    {
        return true;
    }

    public function isValidEndTime(mixed $endTime): bool
    {
        return true;
    }

    public function isValidIsFlextime(mixed $isFlextime): bool
    {
        return true;
    }

    public function isValidTotalHoursPerWeek(mixed $totalHoursPerWeek): bool
    {
        return true;
    }

    public function isValidTotalWorkHours(mixed $totalWorkHours): bool
    {
        return true;
    }

    public function isValidStartDate(mixed $startDate): bool
    {
        return true;
    }

    public function isValidRecurrenceRule(mixed $recurrenceRule): bool
    {
        return true;
    }
}
