<?php

require_once __DIR__ . '/EmployeeBreak.php'                           ;

require_once __DIR__ . '/EmployeeBreakRepository.php'                 ;
require_once __DIR__ . '/../employees/EmployeeRepository.php'         ;
require_once __DIR__ . '/../attendance/AttendanceRepository.php'      ;
require_once __DIR__ . '/../work-schedules/WorkScheduleRepository.php';
require_once __DIR__ . '/BreakScheduleRepository.php'                 ;

class EmployeeBreakService
{
    private readonly EmployeeBreakRepository $employeeBreakRepository;
    private readonly EmployeeRepository      $employeeRepository     ;
    private readonly AttendanceRepository    $attendanceRepository   ;
    private readonly BreakScheduleRepository $breakScheduleRepository;
    private readonly WorkScheduleRepository  $workScheduleRepository ;

    public function __construct(
        EmployeeBreakRepository $employeeBreakRepository,
        EmployeeRepository      $employeeRepository     ,
        AttendanceRepository    $AttendanceRepository   ,
        BreakScheduleRepository $breakScheduleRepository,
        WorkScheduleRepository  $workScheduleRepository
    ) {
        $this->employeeBreakRepository = $employeeBreakRepository;
        $this->employeeRepository      = $employeeRepository     ;
        $this->attendanceRepository    = $AttendanceRepository   ;
        $this->breakScheduleRepository = $breakScheduleRepository;
        $this->workScheduleRepository  = $workScheduleRepository ;
    }

    public function handleRfidTap(string $rfidUid, string $currentDateTime)
    {
        $currentDateTime = new DateTime($currentDateTime );
        $currentDate     = $currentDateTime->format('Y-m-d');
        $currentTime     = $currentDateTime->format('H:i:s');

        $employeeId = $this->employeeRepository->getEmployeeIdBy('employee.rfid_uid', $rfidUid);

        if ($employeeId === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $lastAttendanceRecord = $this->attendanceRepository->getLastAttendanceRecord($employeeId);

        if ($lastAttendanceRecord === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.',
            ];
        }

        if (empty($lastAttendanceRecord) || $lastAttendanceRecord['check_in_time'] === null) {
            return [
                'status'  => 'error',
                'message' => 'You cannot take a break without checking in first.',
            ];
        }

        $workScheduleId = $lastAttendanceRecord['work_schedule_id'];

        $columns = [
            'start_time'                    ,
            'break_type_duration_in_minutes'
        ];

        $filterCriteria = [
            [
                'column'   => 'break_schedule.work_schedule_id',
                'operator' => '=',
                'value'    => $workScheduleId
            ]
        ];

        $breakSchedules = $this->breakScheduleRepository->fetchAllBreakSchedules($columns, $filterCriteria);

        if ($breakSchedules === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.',
            ];
        }

        if (empty($breakSchedules)) {
            return [
                'status'  => 'error',
                'message' => 'No breaks have been scheduled for this schedule.',
            ];
        }

        $currentBreakSchedule = $this->getCurrentBreakSchedule($breakSchedules, $currentTime);

        $startTime = (new DateTime($currentBreakSchedule['start_time']))->format('H:i:s');
        $endTime   = (new DateTime($currentBreakSchedule['end_time'  ]))->format('H:i:s');
    }

    private function getCurrentBreakSchedule(array $breakSchedules, string $currentTime): array
    {
        $currentBreakSchedule = [];
        $nextBreakSchedule = [];

        $currentTime = (new DateTime($currentTime))->format('H:i:s');

        foreach ($breakSchedules as $breakSchedule) {
            $startTime = (new DateTime($breakSchedule['start_time']))->format('H:i:s');
            $endTime = (new DateTime($breakSchedule['start_time']))
                ->modify('+' . $breakSchedule['break_type_duration_in_minutes'] . ' minutes')
                ->format('H:i:s');

            if ($endTime < $startTime) {
                if ($currentTime >= $startTime || $currentTime <= $endTime) {
                    $currentBreakSchedule = $breakSchedule;
                    break;
                }
            } else {
                if ($currentTime >= $startTime && $currentTime <= $endTime) {
                    $currentBreakSchedule = $breakSchedule;
                    break;
                }
            }

            if (empty($nextBreakSchedule) && $currentTime < $startTime) {
                $nextBreakSchedule = $breakSchedule;
            }
        }

        if (empty($currentBreakSchedule) && !empty($nextBreakSchedule)) {
            $currentBreakSchedule = $nextBreakSchedule;

            $currentBreakSchedule['end_time'] = (new DateTime($nextBreakSchedule['start_time']))
                ->modify('+' . $nextBreakSchedule['break_type_duration_in_minutes'] . ' minutes')
                ->format('H:i:s');
        }

        return $currentBreakSchedule;
    }
}
