<?php

require_once __DIR__ . '/Attendance.php'                              ;

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

    public function handleRfidTap(string $rfidUid, string $currentDateTime): array
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

        $lastAttendanceRecord = $this->attendanceRepository->getLastAttendanceRecord($employeeId);

        if ($lastAttendanceRecord === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.',
            ];
        }

        $currentDateTime = new DateTime($currentDateTime );
        $currentDate     = $currentDateTime->format('Y-m-d');
        $currentTime     = $currentDateTime->format('H:i:s');

        if ( empty($lastAttendanceRecord) ||
            ($lastAttendanceRecord['check_in_time' ] !== null  &&
             $lastAttendanceRecord['check_out_time'] !== null)) {

            $workSchedules = $this->workScheduleRepository->getEmployeeWorkSchedules($employeeId, $currentDate, $currentDate);

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

            $currentWorkSchedule = $this->getCurrentWorkSchedule($workSchedules, $currentTime);

            if (empty($currentWorkSchedule)) {
                return [
                    'status'  => 'error',
                    'message' => 'Your scheduled work has already ended.'
                ];
            }

            $minutesCanCheckInBeforeShift = $this->settingRepository->fetchSettingValue('minutes_can_check_in_before_shift', 'work_schedule');

            if ($minutesCanCheckInBeforeShift === ActionResult::FAILURE) {
                return [
                    'status' => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $earliestCheckInTime = (new DateTime($currentWorkSchedule['start_time']))
                ->modify("-{$minutesCanCheckInBeforeShift} minutes")
                ->format('H:i:s');

            if ($currentTime < $earliestCheckInTime) {
                return [
                    'status' => 'error',
                    'message' => 'You are not allowed to check in early.'
                ];
            }

            $attendanceStatus = 'Present';
            $lateCheckIn = 0;

            if ( ! $currentWorkSchedule['is_flextime']) {
                $gracePeriod = $this->settingRepository->fetchSettingValue('grace_period', 'work_schedule');

                if ($gracePeriod === ActionResult::FAILURE) {
                    return [
                        'status' => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $adjustedStartTime = (new DateTime($currentWorkSchedule['start_time']))
                    ->modify("+{$gracePeriod} minutes")
                    ->format('H:i:s');

                if ($currentTime > $adjustedStartTime) {
                    $lateCheckIn = ceil((strtotime($currentTime) - strtotime($adjustedStartTime)) / 60);
                    $attendanceStatus = 'Late';
                }
            }

            $attendance = new Attendance(
                workScheduleId  : $currentWorkSchedule['id'],
                date            : $currentDate              ,
                checkInTime     : $currentTime              ,
                lateCheckIn     : $lateCheckIn              ,
                attendanceStatus: $attendanceStatus
            );

            $this->attendanceRepository->checkIn($attendance);

        } elseif ($lastAttendanceRecord['check_in_time' ] !== null &&
                  $lastAttendanceRecord['check_out_time'] === null) {

            /*
            $attendance = new Attendance(
                    id: $lastAttendanceRecord['id'],
            );
            */
        }

        return [];
    }

    private function getCurrentWorkSchedule(array $workSchedules, string $currentTime): array
    {
        $currentWorkSchedule = [];
        $nextWorkSchedule    = [];

        $currentTime = (new DateTime($currentTime))->format('H:i:s');

        foreach ($workSchedules as $schedules) {
            foreach ($schedules as $schedule) {
                $startTime = (new DateTime($schedule['start_time']))->format('H:i:s');
                $endTime   = (new DateTime($schedule['end_time'  ]))->format('H:i:s');

                if ($endTime < $startTime) {
                    if ($currentTime >= $startTime || $currentTime <= $endTime) {
                        $currentWorkSchedule = $schedule;
                        break 2;
                    }
                } else {
                    if ($currentTime >= $startTime && $currentTime <= $endTime) {
                        $currentWorkSchedule = $schedule;
                        break 2;
                    }
                }

                if (empty($nextWorkSchedule) && $currentTime < $startTime) {
                    $nextWorkSchedule = $schedule;
                }
            }
        }

        if (empty($currentWorkSchedule) && ! empty($nextWorkSchedule)) {
            $currentWorkSchedule = $nextWorkSchedule;
        }

        return $currentWorkSchedule;
    }
}
