<?php

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
                $this->errors[$field] = 'The ' . str_replace('_', ' ', $field) . ' field is missing.';
            } else {
                switch ($field) {
                    case 'id'              : $this->isValidId               ($this->data['id'               ]); break;
                    case 'employee_id'     : $this->isValidEmployeeId       ($this->data['employee_id'      ]); break;
                    case 'start_time'      : $this->isValidStartTime        ($this->data['start_time'       ]); break;
                    case 'end_time'        : $this->isValidEndTime          ($this->data['end_time'         ]); break;
                    case 'is_flextime'     : $this->isValidIsFlextime       ($this->data['is_flextime'      ]); break;
                    case 'total_work_hours': $this->isValidTotalWorkHours   ($this->data['total_work_hours' ]); break;
                    case 'start_date'      : $this->isValidStartDate        ($this->data['start_date'       ]); break;
                    case 'recurrence_rule' : $this->isValidRecurrenceRule   ($this->data['recurrence_rule'  ]); break;
                }

                if (array_key_exists('is_flextime', $this->data) && ! isset($this->errors['is_flextime']) && filter_var($this->data['is_flextime'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true) {
                    $this->isValidTotalWorkHours($this->data['total_work_hours']);
                }
            }
        }

        if (   array_key_exists('start_time', $this->data) &&
               array_key_exists('end_time'  , $this->data) &&

             ! isset($this->errors['start_time']) &&
             ! isset($this->errors['end_time'  ])) {

            if ($this->data['start_time'] === $this->data['end_time']) {
                $this->errors['end_time'] = 'Please enter a valid end time.';
            }
        }

        if (array_key_exists('employee_id', $this->data) && ! isset($this->errors['employee_id'])) {
            $isUnique = $this->isUnique('employee_id', filter_var($this->data['employee_id'], FILTER_VALIDATE_INT));

            if ($isUnique === null) {
                $this->errors['employee_id'] = 'An unexpected error occurred. Please try again later.';
            } elseif ($isUnique === false) {
                $this->errors['employee_id'] = 'This employee already has an assigned work schedule.';
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
        if ($startTime === null) {
            $this->errors['start_time'] = 'The start time is required.';

            return false;
        }

        if ( ! is_string($startTime)) {
            $this->errors['start_time'] = 'The start time is invalid.';

            return false;
        }

        if (trim($startTime) === '') {
            $this->errors['start_time'] = 'The start time is required.';

            return false;
        }

        $time = DateTime::createFromFormat('H:i:s', $startTime);

        if ($time === false || $time->format('H:i:s') !== $startTime) {
            $this->errors['start_time'] = 'The start time is invalid. Please ensure it follows the format HH:MM:SS (24-hour format).';

            return false;
        }

        return true;
    }

    public function isValidEndTime(mixed $endTime): bool
    {
        if ($endTime === null) {
            $this->errors['end_time'] = 'The end time is required.';

            return false;
        }

        if ( ! is_string($endTime)) {
            $this->errors['end_time'] = 'The end time is invalid.';

            return false;
        }

        if (trim($endTime) === '') {
            $this->errors['end_time'] = 'The end time is required.';

            return false;
        }

        $time = DateTime::createFromFormat('H:i:s', $endTime);

        if ($time === false || $time->format('H:i:s') !== $endTime) {
            $this->errors['end_time'] = 'The end time is invalid. Please ensure it follows the format HH:MM:SS (24-hour format).';

            return false;
        }

        return true;
    }

    public function isValidIsFlextime(mixed $isFlextime): bool
    {
        if ($isFlextime === null || (is_string($isFlextime) && trim($isFlextime) === '')) {
            $this->errors['is_flextime'] = 'The "Is Flextime" field is required.';

            return false;
        }

        if (filter_var($isFlextime, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
            $this->errors['is_flextime'] = 'The "Is Flextime" field is invalid.';

            return false;
        }

        return true;
    }

    public function isValidTotalHoursPerWeek(mixed $totalHoursPerWeek): bool
    {
        if ($totalHoursPerWeek === null || (is_string($totalHoursPerWeek) && trim($totalHoursPerWeek) === '')) {
            $this->errors['total_hours_per_week'] = 'The total hours per day is required.';

            return false;
        }

        $totalHoursPerWeek = filter_var($totalHoursPerWeek,  FILTER_VALIDATE_FLOAT);

        if ($totalHoursPerWeek === false) {
            $this->errors['total_hours_per_week'] = 'The total hours per day is invalid.';

            return false;
        }

        $totalHoursPerWeek /= 6;

        if ($totalHoursPerWeek < 1 || $totalHoursPerWeek > 24) {
            $this->errors['total_hours_per_week'] = 'The total hours per day is invalid. It must be between 1 and 24 hours.';

            return false;
        }

        return true;
    }

    public function isValidTotalWorkHours(mixed $totalWorkHours): bool
    {
        if ($totalWorkHours === null || (is_string($totalWorkHours) && trim($totalWorkHours) === '')) {
            $this->errors['total_work_hours'] = 'The total work hours is required.';

            return false;
        }

        $totalWorkHours = filter_var($totalWorkHours, FILTER_VALIDATE_FLOAT);

        if ($totalWorkHours === false) {
            $this->errors['total_work_hours'] = 'The total work hours is invalid.';

            return false;
        }

        if ($totalWorkHours < 1 || $totalWorkHours > 23) {
            $this->errors['total_work_hours'] = 'The total work hours is invalid. It must be between 1 and 23 hours.';

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

    public function isValidRecurrenceRule(mixed $recurrenceRule): bool
    {
        if ($recurrenceRule === null) {
            $this->errors['recurrent_rule'] = 'The recurrence rule is required.';

            return false;
        }

        if ( ! is_string($recurrenceRule)) {
            $this->errors['recurrent_rule'] = 'The recurrence rule is invalid.';

            return false;
        }

        if (trim($recurrenceRule) === '') {
            $this->errors['recurrent_rule'] = 'The recurrence rule is required.';

            return false;
        }

        if ($this->workScheduleRepository->getRecurrenceDates($recurrenceRule, '2025-01-01', '2025-01-01') === ActionResult::FAILURE) {
            $this->errors['recurrent_rule'] = 'The recurrence rule is invalid.';

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
                    'column'   => 'work_schedule.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'   => 'work_schedule.' . $field,
                    'operator' => '='                      ,
                    'value'    => $value
                ]
            ];

            if (is_int($id) || filter_var($id, FILTER_VALIDATE_INT) !== false) {
                $filterCriteria[] = [
                    'column'   => 'work_schedule.id',
                    'operator' => '!='              ,
                    'value'    => (int) $id
                ];

            } elseif (is_string($id) && trim($id) !== '' && $this->isValidHash($id)) {
                $filterCriteria[] = [
                    'column'   => 'SHA2(work_schedule.id, 256)',
                    'operator' => '!='                         ,
                    'value'    => $id
                ];
            }

            $isUnique = $this->workScheduleRepository->fetchAllWorkSchedules(
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
