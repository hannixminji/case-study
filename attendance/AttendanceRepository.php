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

    public function checkOut(Attendance $attendance, bool $isHashedId = false): ActionResult
    {
        return $this->attendanceDao->checkOut($attendance, $isHashedId);
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
            'id'                             ,

            'work_schedule_id'               ,
            'work_schedule_start_time'       ,
            'work_schedule_end_time'         ,
            'work_schedule_is_flextime'      ,
            'work_schedule_total_work_hours' ,

            'employee_id'                    ,
            "employee_deleted_at"            ,

            'date'                           ,
            'check_in_time'                  ,
            'check_out_time'                 ,
            'total_break_duration_in_minutes',
            'total_hours_worked'             ,
            'late_check_in'                  ,
            'is_overtime_approved'           ,
            'attendance_status'              ,
            'remarks'
        ];

        $filterCriteria = [
            [
                'column'   => 'attendance.deleted_at',
                'operator' => 'IS NULL'
            ],
            [
                'column'   => 'employee.deleted_at',
                'operator' => 'IS NULL'
            ],
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

        return ! empty($result['result_set'])
            ? $result['result_set'][0]
            : [];
    }

    public function updateAttendance(Attendance $attendance, bool $isHashedId = false): ActionResult
    {
        return $this->attendanceDao->update($attendance, $isHashedId);
    }

    public function approveOvertime(int|string $attendanceId, bool $isHashedId = false): ActionResult
    {
        return $this->attendanceDao->approveOvertime($attendanceId, $isHashedId);
    }
}
