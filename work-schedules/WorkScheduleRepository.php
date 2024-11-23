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
        ?array $columns        = null,
        ?array $filterCriteria = null,
        ?array $sortCriteria   = null,
        ?int   $limit          = null,
        ?int   $offset         = null
    ): ActionResult|array {
        return $this->workScheduleDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateWorkSchedule(WorkSchedule $workSchedule): ActionResult
    {
        return $this->workScheduleDao->update($workSchedule);
    }

    public function getRecurrenceDates(string $recurrenceRule, string $startDate, string $endDate): array
    {
        return $this->workScheduleDao->getRecurrenceDates($recurrenceRule, $startDate, $endDate);
    }

    public function getLastInsertedWorkScheduleId(): ActionResult|int
    {
        return $this->workScheduleDao->getLastInsertId();
    }

    public function getEmployeeWorkSchedules(
        int    $employeeId,
        string $startDate ,
        string $endDate
    ): ActionResult|array {
        $columns = [
            'employee_id'         ,
            'start_time'          ,
            'end_time'            ,
            'is_flexible'         ,
            'arrival_start_time'  ,
            'arrival_end_time'    ,
            'departure_start_time',
            'departure_end_time'  ,
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
                'operator' => '>=',
                'value'    => $startDate
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
            ],
            [
                'column'    => 'work_schedule.arrival_start_time',
                'direction' => 'ASC'
            ]
        ];

        $result = $this->workScheduleDao->fetchAll($columns, $filterCriteria, $sortCriteria);

        if ($result === ActionResult::FAILURE) {
            return ActionResult::FAILURE;
        }

        $workSchedules = $result['result_set'];

        if (empty($workSchedules)) {
            return ActionResult::NO_WORK_SCHEDULE_FOUND;
        }

        foreach ($workSchedules as $workSchedule) {
            $recurrenceRule = $workSchedule['recurrence_rule'];

            $dates = $this->getRecurrenceDates($recurrenceRule, $startDate, $endDate);

            $workSchedule['dates'] = $dates;
        }

        return $workSchedules;
    }

    public function deleteWorkSchedule(int $workScheduleId): ActionResult
    {
        return $this->workScheduleDao->delete($workScheduleId);
    }
}
