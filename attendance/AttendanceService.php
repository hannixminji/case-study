<?php

require_once __DIR__ . '/Attendance.php'                              ;
require_once __DIR__ . '/../breaks/EmployeeBreak.php'                 ;

require_once __DIR__ . '/AttendanceRepository.php'                    ;
require_once __DIR__ . '/../employees/EmployeeRepository.php'         ;
require_once __DIR__ . '/../leaves/LeaveRequestRepository.php'        ;
require_once __DIR__ . '/../work-schedules/WorkScheduleRepository.php';
require_once __DIR__ . '/../settings/SettingRepository.php'           ;
require_once __DIR__ . '/../breaks/BreakScheduleRepository.php'       ;
require_once __DIR__ . '/../breaks/EmployeeBreakRepository.php'       ;

class AttendanceService
{
    private readonly AttendanceRepository    $attendanceRepository   ;
    private readonly EmployeeRepository      $employeeRepository     ;
    private readonly LeaveRequestRepository  $leaveRequestRepository ;
    private readonly WorkScheduleRepository  $workScheduleRepository ;
    private readonly SettingRepository       $settingRepository      ;
    private readonly BreakScheduleRepository $breakScheduleRepository;
    private readonly EmployeeBreakRepository $employeeBreakRepository;

    public function __construct(
        AttendanceRepository    $attendanceRepository   ,
        EmployeeRepository      $employeeRepository     ,
        LeaveRequestRepository  $leaveRequestRepository ,
        WorkScheduleRepository  $workScheduleRepository ,
        SettingRepository       $settingRepository      ,
        BreakScheduleRepository $breakScheduleRepository,
        EmployeeBreakRepository $employeeBreakRepository
    ) {
        $this->attendanceRepository    = $attendanceRepository   ;
        $this->employeeRepository      = $employeeRepository     ;
        $this->leaveRequestRepository  = $leaveRequestRepository ;
        $this->workScheduleRepository  = $workScheduleRepository ;
        $this->settingRepository       = $settingRepository      ;
        $this->breakScheduleRepository = $breakScheduleRepository;
        $this->employeeBreakRepository = $employeeBreakRepository;
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

        if ( ! empty($lastAttendanceRecord)) {
            $lastAttendanceRecord = $lastAttendanceRecord[0];
        }

        $currentDateTime = new DateTime($currentDateTime );
        $currentDate     = $currentDateTime->format('Y-m-d');
        $currentTime     = $currentDateTime->format('H:i:s');

        $isCheckIn = false;

        if ( empty($lastAttendanceRecord) ||
            ($lastAttendanceRecord['check_in_time' ] !== null  &&
             $lastAttendanceRecord['check_out_time'] !== null)) {

            $isCheckIn = true;

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

            $minutesCanCheckInBeforeShift = (int) $this->settingRepository->fetchSettingValue('minutes_can_check_in_before_shift', 'work_schedule');

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
                $gracePeriod = (int) $this->settingRepository->fetchSettingValue('grace_period', 'work_schedule');

                if ($gracePeriod === ActionResult::FAILURE) {
                    return [
                        'status' => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $startTime = new DateTime($currentWorkSchedule['start_time']);
                $adjustedStartTime = (clone $startTime)->modify("+{$gracePeriod} minutes");

                if ($currentTime > $adjustedStartTime->format('H:i:s')) {
                    $lateCheckIn = ceil(((new DateTime($currentDateTime->format('H:i:s')))->getTimestamp() - (new DateTime($startTime->format('H:i:s')))->getTimestamp()) / 60);
                    $attendanceStatus = 'Late';
                }
            }

            $attendance = new Attendance(
                id               : null,
                workScheduleId   : $currentWorkSchedule['id'],
                date             : $currentDate,
                checkInTime      : $currentDateTime->format('Y-m-d H:i:s'),
                checkOutTime     : null,
                totalBreakDurationInMinutes: null,
                totalHoursWorked : null,
                lateCheckIn      : $lateCheckIn,
                earlyCheckOut    : null,
                overtimeHours    : null,
                isOvertimeApproved: null,
                attendanceStatus : $attendanceStatus,
                remarks          : null
            );

            $result = $this->attendanceRepository->checkIn($attendance);

            if ($result === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

        } elseif ($lastAttendanceRecord['check_in_time' ] !== null &&
                  $lastAttendanceRecord['check_out_time'] === null) {

            $lastAttendanceDate    = new DateTime($lastAttendanceRecord['date']);
            $workScheduleStartTime = new DateTime($lastAttendanceDate->format('Y:m:d') . ' ' . (new DateTime($lastAttendanceRecord['work_schedule_start_time']))->format('H:i:s'));
            $workScheduleEndTime   = new DateTime($lastAttendanceDate->format('Y:m:d') . ' ' . (new DateTime($lastAttendanceRecord['work_schedule_end_time'  ]))->format('H:i:s'));

            if ($workScheduleEndTime->format('H:i:s') < $workScheduleStartTime->format('H:i:s')) {
                $workScheduleEndTime->modify('+1 day');
            }

            $breakScheduleColumns = [
                'id'                               ,
                'start_time'                       ,
                'break_type_duration_in_minutes'   ,
                'is_require_break_in_and_break_out',
                'is_flextime'                      ,
                'earliest_start_time'              ,
                'latest_end_time'                  ,
                'break_type_is_paid'
            ];

            $filterCriteria = [
                [
                    'column'   => 'break_schedule.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'   => 'break_schedule.work_schedule_id',
                    'operator' => '=',
                    'value'    => $lastAttendanceRecord['work_schedule_id']
                ]
            ];

            $result = $this->breakScheduleRepository->fetchAllBreakSchedules($breakScheduleColumns, $filterCriteria);

            if ($result === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $breakSchedules = $result['result_set'];

            $employeeBreakColumns = [
                'id'                               ,
                'employee_id'                      ,
                'break_schedule_id'                ,
                'break_schedule_start_time'        ,
                'start_time'                       ,
                'end_time'                         ,
                'break_duration_in_minutes'        ,
                'is_require_break_in_and_break_out'
            ];

            $filterCriteria = [
                [
                    'column'   => 'work_schedule.employee_id',
                    'operator' => '='                        ,
                    'value'    => $employeeId
                ],
                [
                    'column'      => 'employee_break.created_at',
                    'operator'    => 'BETWEEN'                  ,
                    'lower_bound' => $workScheduleStartTime->format('Y-m-d H:i:s'),
                    'upper_bound' => $workScheduleEndTime->format('Y-m-d H:i:s')
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

            $result = $this->employeeBreakRepository->fetchAllEmployeeBreaks(
                columns       : $employeeBreakColumns,
                filterCriteria: $filterCriteria      ,
                sortCriteria  : $sortCriteria
            );

            if ($result === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $employeeBreaks = $result['result_set'];

            $completedBreakIds = array_column($employeeBreaks, 'break_schedule_id');

            $unpaidBreakDurationInMinutes = 0;
            $paidBreakDurationInMinutes   = 0;

            foreach ($breakSchedules as $breakSchedule) {
                if ($breakSchedule['is_require_break_in_and_break_out']) {
                    if ( ! in_array($breakSchedule['id'], $completedBreakIds)) {

                        $employeeBreak = new EmployeeBreak(
                            id                    : null                ,
                            breakScheduleId       : $breakSchedule['id'],
                            startTime             : null                ,
                            endTime               : null                ,
                            breakDurationInMinutes: 0
                        );

                        $result = $this->employeeBreakRepository->breakIn($employeeBreak);

                        if ($result === ActionResult::FAILURE) {
                            return [
                                'status'  => 'error',
                                'message' => 'An unexpected error occurred. Please try again later.'
                            ];
                        }

                        $lastBreakRecord = $this->employeeBreakRepository->fetchEmployeeLastBreakRecord($employeeId);

                        if ($lastBreakRecord === ActionResult::FAILURE) {
                            return [
                                'status'  => 'error',
                                'message' => 'An unexpected error occurred. Please try again later.'
                            ];
                        }

                        $lastBreakRecord = $lastBreakRecord[0];

                        $employeeBreak = new EmployeeBreak(
                            id                    : $lastBreakRecord['id'               ],
                            breakScheduleId       : $lastBreakRecord['break_schedule_id'],
                            startTime             : null                                 ,
                            endTime               : null                                 ,
                            breakDurationInMinutes: 0
                        );

                        $result = $this->employeeBreakRepository->breakOut($employeeBreak);

                        if ($result === ActionResult::FAILURE) {
                            return [
                                'status'  => 'error',
                                'message' => 'An unexpected error occurred. Please try again later.'
                            ];
                        }
                    } else {
                        foreach ($employeeBreaks as $employeeBreak) {
                            if ($employeeBreak['break_schedule_id'] === $breakSchedule['id'] && ($employeeBreak['start_time'] !== null && $employeeBreak['end_time'] !== null)) {
                                if ($employeeBreak['break_duration_in_minutes'] > $breakSchedule['break_type_duration_in_minutes']) {
                                    if ( ! $breakSchedule['break_type_is_paid']) {
                                        $unpaidBreakDurationInMinutes += $employeeBreak['break_duration_in_minutes'];
                                    } else {
                                        $paidBreakDurationInMinutes += $breakSchedule['break_type_duration_in_minutes'];
                                        $unpaidBreakDurationInMinutes += ($employeeBreak['break_duration_in_minutes'] - $breakSchedule['break_type_duration_in_minutes']);
                                    }
                                } else {
                                    if ( ! $breakSchedule['break_type_is_paid']) {
                                        $unpaidBreakDurationInMinutes += $breakSchedule['break_type_duration_in_minutes'];
                                    } else {
                                        $paidBreakDurationInMinutes += $breakSchedule['break_type_duration_in_minutes'];
                                    }
                                }
                            }
                        }
                    }
                } else {
                    if ( ! $breakSchedule['break_type_is_paid']) {
                        $unpaidBreakDurationInMinutes += $breakSchedule['break_type_duration_in_minutes'];
                    } else {
                        $paidBreakDurationInMinutes += $breakSchedule['break_type_duration_in_minutes'];
                    }
                }
            }

            $attendanceStatus = $lastAttendanceRecord['attendance_status'];

            $totalBreakDurationInMinutes = $unpaidBreakDurationInMinutes + $paidBreakDurationInMinutes;

            $checkInTime = new DateTime($lastAttendanceRecord['check_in_time']);
            $checkOutTime = new DateTime($currentDateTime->format('Y-m-d H:i:s'));
            $totalWorkDuration = $checkInTime->diff($checkOutTime);

            $totalMinutesWorked = ($totalWorkDuration->days * 24 * 60) + ($totalWorkDuration->h * 60) + $totalWorkDuration->i;
            $totalMinutesWorked -= $unpaidBreakDurationInMinutes;

            $totalHoursWorked = $totalMinutesWorked / 60;
            $totalHoursWorked = round($totalHoursWorked, 2);

            $earlyCheckOutInMinutes = 0;
            $overtimeHours          = 0;

            if ( ! $lastAttendanceRecord['work_schedule_is_flextime']) {
                if ($checkOutTime < $workScheduleEndTime) {
                    $interval = $workScheduleEndTime->diff($checkOutTime);

                    $earlyCheckOutInMinutes = $interval->h * 60 + $interval->i;

                    $attendanceStatus = 'Undertime';

                } elseif ($checkOutTime > $workScheduleEndTime) {
                    $interval = $workScheduleEndTime->diff($checkOutTime);

                    $overtimeHours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
                    $overtimeHours = round($overtimeHours, 2);

                    $attendanceStatus = 'Overtime';
                }
            }

            $attendance = new Attendance(
                id                         : $lastAttendanceRecord['id'],
                workScheduleId             : $lastAttendanceRecord['work_schedule_id'],
                date                       : $lastAttendanceRecord['date'],
                checkInTime                : $lastAttendanceRecord['check_in_time'],
                checkOutTime               : $checkOutTime->format('Y-m-d H:i:s'),
                totalBreakDurationInMinutes: $totalBreakDurationInMinutes,
                totalHoursWorked           : $totalHoursWorked,
                lateCheckIn                : $lastAttendanceRecord['late_check_in'],
                earlyCheckOut              : $earlyCheckOutInMinutes,
                overtimeHours              : $overtimeHours,
                isOvertimeApproved         : false,
                attendanceStatus           : $attendanceStatus,
                remarks                    : null
            );

            $result = $this->attendanceRepository->checkOut($attendance);

            if ($result === ActionResult::FAILURE) {
                return [
                    'status'  => 'error16',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }
        }

        if ($isCheckIn) {
            return [
                'status' => 'success',
                'message' => 'Checked-in recorded successfully.'
            ];
        } else {
            return [
                'status' => 'success',
                'message' => 'Checked-out recorded successfully.'
            ];
        }
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
