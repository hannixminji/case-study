<?php

require_once __DIR__ . '/EmployeeBreakDao.php';

class EmployeeBreakRepository
{
    private readonly EmployeeBreakDao $employeeBreakDao;

    public function __construct(EmployeeBreakDao $employeeBreakDao)
    {
        $this->employeeBreakDao = $employeeBreakDao;
    }

    public function breakIn(EmployeeBreak $employeeBreak): ActionResult
    {
        return $this->employeeBreakDao->breakIn($employeeBreak);
    }

    public function breakOut(EmployeeBreak $employeeBreak): ActionResult
    {
        return $this->employeeBreakDao->breakOut($employeeBreak);
    }

    public function fetchAllEmployeeBreaks(
        ?array $columns        = null,
        ?array $filterCriteria = null,
        ?array $sortCriteria   = null,
        ?int   $limit          = null,
        ?int   $offset         = null
    ): ActionResult|array {
        return $this->employeeBreakDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function fetchLastEmployeeBreakRecord(int $employeeId): ActionResult|array
    {
        $columns = [
            'id'                       ,
            'break_schedule_id'        ,
            'start_time'               ,
            'end_time'                 ,
            'break_duration_in_minutes'
        ];

        $filterCriteria = [
            [
                'column'   => 'employee_break.employee_id',
                'operator' => '='                          ,
                'value'    => $employeeId
            ]
        ];

        $sortCriteria = [
            [
                'column'    => 'employee_break.created_at',
                'direction' => 'DESC'
            ],
            [
                'column'    => 'employee_break.start_time',
                'direction' => 'DESC'
            ]
        ];

        $result = $this->employeeBreakDao->fetchAll(
            columns       : $columns       ,
            filterCriteria: $filterCriteria,
            sortCriteria  : $sortCriteria  ,
            limit         : 1
        );

        if ($result === ActionResult::FAILURE) {
            return ActionResult::FAILURE;
        }

        return $result['result_set'][0];
    }

    public function fetchTotalBreakDuration(int $workScheduleId, string $startTime, string $endTime): ActionResult|int
    {
        $columns = [
            'work_schedule_id'               ,
            'total_break_duration_in_minutes'
        ];

        $filterCriteria = [
            [
                'column'   => 'break_schedule.work_schedule_id',
                'operator' => '='                              ,
                'value'    => $workScheduleId
            ],
            [
                'column'      => 'employee_break.created_at',
                'operator'    => 'BETWEEN'                  ,
                'lower_bound' => $startTime                 ,
                'upper_bound' => $endTime
            ]
        ];

        $result = $this->employeeBreakDao->fetchAll(
            columns       : $columns       ,
            filterCriteria: $filterCriteria
        );

        if ($result === ActionResult::FAILURE) {
            return ActionResult::FAILURE;
        }

        return (int) $result["result_set"][0]["total_break_duration_in_minutes"];
    }

    public function updateEmployeeBreak(EmployeeBreak $employeeBreak): ActionResult
    {
        return $this->employeeBreakDao->update($employeeBreak);
    }
}
