<?php

require_once __DIR__ . '/AttendanceRepository.php'                    ;
require_once __DIR__ . '/../employees/EmployeeRepository.php'         ;
require_once __DIR__ . '/../leaves/LeaveRequestRepository.php'        ;
require_once __DIR__ . '/../work-schedules/WorkScheduleRepository.php';
require_once __DIR__ . '/../settings/SettingRepository.php'           ;

class AttendanceService
{
    private readonly AttendanceRepository   $attendanceRepository  ;
    private readonly EmployeeRepository     $employeeRepository    ;
    private readonly LeaveRequestRepository $leaveRequestRepository;
    private readonly WorkScheduleRepository $workScheduleRepository;
    private readonly SettingRepository      $settingRepository     ;

    public function __construct(
        AttendanceRepository   $attendanceRepository  ,
        EmployeeRepository     $employeeRepository    ,
        LeaveRequestRepository $leaveRequestRepository,
        WorkScheduleRepository $workScheduleRepository,
        SettingRepository      $settingRepository
    ) {
        $this->attendanceRepository   = $attendanceRepository  ;
        $this->employeeRepository     = $employeeRepository    ;
        $this->leaveRequestRepository = $leaveRequestRepository;
        $this->workScheduleRepository = $workScheduleRepository;
        $this->settingRepository      = $settingRepository     ;
    }

    public function handleRfidTap(string $rfidUid, string $currentDateTime)
    {
        $employeeId = $this->employeeRepository->getEmployeeIdBy('employee.rfid_uid', $rfidUid);

        if ($employeeId === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $isOnLeave = $this->leaveRequestRepository->isEmployeeOnLeave($employeeId);

        if ($isOnLeave === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.',
            ];
        }

        if ($isOnLeave) {
            return [
                'status'  => 'error',
                'message' => 'You are currently on leave. You cannot check in or check out.'
            ];
        }

        $previousDate = (new DateTime($currentDateTime))
            ->modify('-1 day')
            ->format('Y-m-d'   );

        $currentDate  = (new DateTime($currentDateTime))
            ->format('Y-m-d');

        $workSchedules = $this->workScheduleRepository->getEmployeeWorkSchedules($employeeId, $previousDate, $currentDate);

        if ($workSchedules === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        if ($workSchedules === ActionResult::NO_WORK_SCHEDULE_FOUND) {
            return [
                'status'  => 'error',
                'message' => 'You do not have a work schedule for today.'
            ];
        }

        $currentTime = (new DateTime($currentDateTime))->format('H:i:s');

        $columns = [
            'check_in_time' ,
            'check_out_time'
        ];

        $filterCriteria = [
            [
                'column'   => 'attendance.employee_id',
                'operator' => '=',
                'value'    => $employeeId
            ],
            [
                'column'   => 'attendance.date',
                'operator' => '>='             ,
                'value'    => $previousDate
            ],
            [
                'column'   => 'attendance.date',
                'operator' => '<='             ,
                'value'    => $currentDate
            ]
        ];

        $sortCriteria = [
            [
                'column'    => 'attendance.date',
                'direction' => 'DESC'
            ]
        ];

        $attendanceLogPreviousAndCurrentDay = $this->attendanceRepository->fetchAllAttendance(
            columns       : $columns       ,
            filterCriteria: $filterCriteria,
            sortCriteria  : $sortCriteria
        );

        if ($attendanceLogPreviousAndCurrentDay === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        if (empty($attendanceLogPreviousAndCurrentDay)) {
            $attendanceLogPreviousAndCurrentDay = [
                [
                    'check_in_time'  => null,
                    'check_out_time' => null
                ]
            ];
        }
    }
}
