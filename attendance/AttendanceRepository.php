<?php

require_once __DIR__ . '/AttendanceDao.php';

class AttendanceRepository
{
    private readonly AttendanceDao $attendanceDao;

    public function __construct(AttendanceDao $attendanceDao)
    {
        $this->attendanceDao = $attendanceDao;
    }

    public function checkIn(Attendance $attendance): ActionResult
    {
        return $this->attendanceDao->checkIn($attendance);
    }

    public function checkOut(Attendance $attendance): ActionResult
    {
        return $this->attendanceDao->checkOut($attendance);
    }

    public function fetchAllAttendance(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->attendanceDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function getLastAttendanceRecord(int $employeeId): ActionResult|array
    {
        $columns = [
            'id'                               ,
            'work_schedule_id'                 ,
            'work_schedule_start_time'         ,
            'work_schedule_end_time'           ,
            'work_schedule_is_flextime'        ,
            'employee_id'                      ,
            'date'                             ,
            'check_in_time'                    ,
            'check_out_time'                   ,
            'late_check_in'                    ,
            'attendance_status'
        ];

        $filterCriteria = [
            [
                'column'   => 'work_schedule.employee_id',
                'operator' => '=',
                'value'    => $employeeId
            ]
        ];

        $sortCriteria = [
            [
                'column'    => 'attendance.id',
                'direction' => 'DESC'
            ]
        ];

        $result = $this->attendanceDao->fetchAll(
            columns       : $columns       ,
            filterCriteria: $filterCriteria,
            sortCriteria  : $sortCriteria  ,
            limit         : 1
        );

        if ($result === ActionResult::FAILURE) {
            return ActionResult::FAILURE;
        }

        return $result['result_set'];
    }

    public function updateAttendance(Attendance $attendance): ActionResult
    {
        return $this->attendanceDao->update($attendance);
    }

    public function approveOvertime(int $attendanceId): ActionResult
    {
        return $this->attendanceDao->approveOvertime($attendanceId);
    }
}
