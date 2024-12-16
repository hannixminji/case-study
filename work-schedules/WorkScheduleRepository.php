<?php

require_once __DIR__ . '/WorkScheduleDao.php';

class WorkScheduleRepository
{
    private readonly WorkScheduleDao $workScheduleDao;

    public function __construct(WorkScheduleDao $workScheduleDao)
    {
        $this->workScheduleDao = $workScheduleDao;
    }

    public function createWorkSchedule(WorkSchedule $workSchedule): ActionResult
    {
        return $this->workScheduleDao->create($workSchedule);
    }

    public function fetchAllWorkSchedules(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->workScheduleDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateWorkSchedule(WorkSchedule $workSchedule, bool $isHashedId = false): ActionResult
    {
        return $this->workScheduleDao->update($workSchedule, $isHashedId);
    }

    public function getRecurrenceDates(string $recurrenceRule, string $startDate, string $endDate): array
    {
        return $this->workScheduleDao->getRecurrenceDates($recurrenceRule, $startDate, $endDate);
    }

    public function getEmployeeWorkSchedules(
        int    $employeeId,
        string $startDate ,
        string $endDate
    ): ActionResult|array {
        $startDate = (new DateTime($startDate))->format('Y-m-d');
        $endDate   = (new DateTime($endDate  ))->format('Y-m-d');

        $columns = [
            'id'                  ,
            'start_time'          ,
            'end_time'            ,
            'is_flextime'         ,
            'total_hours_per_week',
            'total_work_hours'    ,
            'recurrence_rule'
        ];

        $filterCriteria = [
            [
                'column'   => 'work_schedule.deleted_at',
                'operator' => 'IS NULL'
            ],
            [
                'column'   => 'work_schedule.employee_id',
                'operator' => '=',
                'value'    => $employeeId
            ],
            [
                'column'   => 'work_schedule.start_date',
                'operator' => '<=',
                'value'    => $endDate
            ]
        ];

        $sortCriteria = [
            [
                'column'    => 'work_schedule.start_date',
                'direction' => 'ASC'
            ],
            [
                'column'    => 'work_schedule.start_time',
                'direction' => 'ASC'
            ]
        ];

        $result = $this->workScheduleDao->fetchAll(
            columns       : $columns       ,
            filterCriteria: $filterCriteria,
            sortCriteria  : $sortCriteria
        );

        if ($result === ActionResult::FAILURE) {
            return ActionResult::FAILURE;
        }

        $employeeWorkSchedules = $result['result_set'];

        if (empty($employeeWorkSchedules)) {
            return ActionResult::NO_WORK_SCHEDULE_FOUND;
        }

        $workSchedules = [];

        $start = new DateTime($startDate);
        $end = (new DateTime($endDate))
            ->modify('+1 day');

        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($start, $interval, $end);

        foreach ($dateRange as $date) {
            $workSchedules[$date->format('Y-m-d')] = [];
        }

        foreach ($employeeWorkSchedules as $workSchedule) {
            $recurrenceRule = $workSchedule['recurrence_rule'];

            $recurrenceDates = $this->getRecurrenceDates($recurrenceRule, $startDate, $endDate);

            if ($recurrenceDates === ActionResult::FAILURE) {
                return ActionResult::FAILURE;
            }

            foreach ($recurrenceDates as $recurrenceDate) {
                if (isset($workSchedules[$recurrenceDate])) {
                    $workSchedules[$recurrenceDate][] = $workSchedule;
                }
            }
        }

        return $workSchedules;
    }

    public function deleteWorkSchedule(int $workScheduleId, bool $isHashedId = false): ActionResult
    {
        return $this->workScheduleDao->delete($workScheduleId, $isHashedId);
    }
}
