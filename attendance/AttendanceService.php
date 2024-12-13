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
                'status'  => 'warning',
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

        $currentDateTime = new DateTime($currentDateTime   );
        $currentDate     = $currentDateTime->format('Y-m-d');

        $isCheckIn = false;

        if ( empty($lastAttendanceRecord) ||
            ($lastAttendanceRecord['check_in_time' ] !== null  &&
             $lastAttendanceRecord['check_out_time'] !== null)) {

            $isCheckIn = true;

            $previousDate = clone new DateTime($currentDate);
            $previousDate->modify('-1 day');

            $workSchedules = $this->workScheduleRepository->getEmployeeWorkSchedules(
                $employeeId,
                $previousDate->format('Y-m-d'),
                $currentDate);

            if ($workSchedules === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            if (empty($workSchedules) || $workSchedules === ActionResult::NO_WORK_SCHEDULE_FOUND) {
                return [
                    'status'  => 'warning',
                    'message' => 'You do not have a work schedule for today.'
                ];
            }

            $currentWorkSchedule = $this->getCurrentWorkSchedule($workSchedules, $currentDateTime->format('Y-m-d H:i:s'));
            $currentWorkScheduleDate = (new DateTime($currentWorkSchedule['start_time']))->format('Y-m-d');

            if (empty($currentWorkSchedule)) {
                return [
                    'status'  => 'warning',
                    'message' => 'Your scheduled work has already ended.'
                ];
            }

            if ( ! empty($lastAttendanceRecord)                                                  &&
                $lastAttendanceRecord['work_schedule_id'] === $currentWorkSchedule['id'        ] &&
                $lastAttendanceRecord['check_in_time'   ] >=  $currentWorkSchedule['start_time'] &&
                $lastAttendanceRecord['check_out_time'  ] <=  $currentWorkSchedule['end_time'  ]) {

                $currentAttendance = new Attendance(
                    id                         : null                                                    ,
                    workScheduleId             : $lastAttendanceRecord['work_schedule_id'               ],
                    date                       : $lastAttendanceRecord['date'                           ],
                    checkInTime                : $currentDateTime->format('Y-m-d H:i:s')                 ,
                    checkOutTime               : null                                                    ,
                    totalBreakDurationInMinutes: $lastAttendanceRecord['total_break_duration_in_minutes'],
                    totalHoursWorked           : $lastAttendanceRecord['total_hours_worked'             ],
                    lateCheckIn                : $lastAttendanceRecord['late_check_in'                  ],
                    earlyCheckOut              : null                                                    ,
                    overtimeHours              : null                                                    ,
                    isOvertimeApproved         : $lastAttendanceRecord['is_overtime_approved'           ],
                    attendanceStatus           : $lastAttendanceRecord['attendance_status'              ],
                    remarks                    : $lastAttendanceRecord['remarks'                        ]
                );

                $result = $this->attendanceRepository->checkIn($currentAttendance);

                if ($result === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $previousAttendance = new Attendance(
                    id                         : $lastAttendanceRecord['id'                             ],
                    workScheduleId             : $lastAttendanceRecord['work_schedule_id'               ],
                    date                       : $lastAttendanceRecord['date'                           ],
                    checkInTime                : $lastAttendanceRecord['check_in_time'                  ],
                    checkOutTime               : $lastAttendanceRecord['check_out_time'                 ],
                    totalBreakDurationInMinutes: $lastAttendanceRecord['total_break_duration_in_minutes'],
                    totalHoursWorked           : $lastAttendanceRecord['total_hours_worked'             ],
                    lateCheckIn                : $lastAttendanceRecord['late_check_in'                  ],
                    earlyCheckOut              : 0                                                       ,
                    overtimeHours              : 0.00                                                    ,
                    isOvertimeApproved         : $lastAttendanceRecord['is_overtime_approved'           ],
                    attendanceStatus           : $lastAttendanceRecord['attendance_status'              ],
                    remarks                    : $lastAttendanceRecord['remarks'                        ]
                );

                $result = $this->attendanceRepository->updateAttendance($previousAttendance);

                if ($result === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

            } else {
                $minutesCanCheckInBeforeShift = (int) $this->settingRepository->fetchSettingValue('minutes_can_check_in_before_shift', 'work_schedule');

                if ($minutesCanCheckInBeforeShift === ActionResult::FAILURE) {
                    return [
                        'status' => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $earliestCheckInTime = (new DateTime($currentWorkSchedule['start_time']))
                    ->modify("-{$minutesCanCheckInBeforeShift} minutes");

                if ($currentDateTime < $earliestCheckInTime) {
                    return [
                        'status' => 'warning',
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

                    if ($currentDateTime->format('Y-m-d H:i:s') > $adjustedStartTime->format('Y-m-d H:i:s')) {
                        $lateCheckIn = ceil(((new DateTime($currentDateTime->format('Y-m-d H:i:s')))->getTimestamp() - (new DateTime($adjustedStartTime->format('Y-m-d H:i:s')))->getTimestamp()) / 60);
                        $attendanceStatus = 'Late';
                    }
                }

                $attendance = new Attendance(
                    id                         : null,
                    workScheduleId             : $currentWorkSchedule['id'],
                    date                       : $currentWorkScheduleDate,
                    checkInTime                : $currentDateTime->format('Y-m-d H:i:s'),
                    checkOutTime               : null,
                    totalBreakDurationInMinutes: 0.00,
                    totalHoursWorked           : 0.00,
                    lateCheckIn                : $lateCheckIn,
                    earlyCheckOut              : 0,
                    overtimeHours              : 0.00,
                    isOvertimeApproved         : false,
                    attendanceStatus           : $attendanceStatus,
                    remarks                    : null
                );

                $result = $this->attendanceRepository->checkIn($attendance);

                if ($result === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }
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
                'is_flexible'                      ,
                'earliest_start_time'              ,
                'latest_end_time'                  ,
                'break_type_is_paid'               ,
                'created_at'
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
                'id'                                ,
                'break_schedule_id'                 ,
                'start_time'                        ,
                'end_time'                          ,
                'break_duration_in_minutes'         ,
                'created_at'                        ,

                'break_schedule_start_time'         ,
                'break_schedule_is_flexible'        ,
                'break_schedule_earliest_start_time',

                'employee_id'                       ,

                'break_type_duration_in_minutes'    ,
                'break_type_is_paid'                ,
                'is_require_break_in_and_break_out'
            ];

            $filterCriteria = [
                [
                    'column'   => 'break_schedule.work_schedule_id',
                    'operator' => '=',
                    'value'    => $lastAttendanceRecord['work_schedule_id']
                ],
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

            $checkInTime = new DateTime($lastAttendanceRecord['check_in_time']);
            $checkOutTime = clone $currentDateTime;

            foreach ($breakSchedules as $breakSchedule) {
                if ($breakSchedule['is_require_break_in_and_break_out']) {
                    if ( ! in_array($breakSchedule['id'], $completedBreakIds)) {
                        $breakScheduleStartTime = null;
                        if ($breakSchedule['is_flexible']) {
                            $breakScheduleStartTime = $breakSchedule['earliest_start_time'];
                        } else {
                            $breakScheduleStartTime = $breakSchedule['start_time'];
                        }

                        $breakScheduleStartTime = new DateTime($breakScheduleStartTime);
                        $breakScheduleEndTime = clone $breakScheduleStartTime;
                        $breakScheduleEndTime->modify("+{$breakSchedule['break_type_duration_in_minutes']} minutes");

                        $breakScheduleStartTime = new DateTime($lastAttendanceDate->format('Y-m-d') . ' ' . $breakScheduleStartTime->format('H:i:s'));
                        $breakScheduleEndTime   = new DateTime($lastAttendanceDate->format('Y-m-d') . ' ' . $breakScheduleEndTime  ->format('H:i:s'));

                        if ($breakScheduleStartTime < $workScheduleStartTime) {
                            $breakScheduleStartTime->modify('+1 day');
                        }

                        if ($breakScheduleEndTime < $workScheduleStartTime) {
                            $breakScheduleEndTime->modify('+1 day');
                        }

                        if ($breakScheduleEndTime->format('H:i:s') < $breakScheduleStartTime->format('H:i:s')) {
                            $breakScheduleEndTime->modify('+1 day');
                        }

                        if ($checkInTime >= $breakScheduleStartTime) {
                            $breakScheduleStartTime = clone $checkInTime;
                        }

                        $actualBreakDurationInMinutes = 0;
                        if ($checkOutTime->format('Y-m-d H:i:s') >= $breakScheduleStartTime->format('Y-m-d H:i:s')) {
                            $actualEndTime = ($checkOutTime < $breakScheduleEndTime) ? $checkOutTime : $breakScheduleEndTime;
                            $actualBreakDurationInMinutes = $breakScheduleStartTime->diff($actualEndTime);
                            $actualBreakDurationInMinutes = $actualBreakDurationInMinutes->h * 60 + $actualBreakDurationInMinutes->i;
                        }

                        if ($breakSchedule['break_type_is_paid']) {
                            $paidBreakDurationInMinutes += $actualBreakDurationInMinutes;
                        } elseif ( ! $breakSchedule['break_type_is_paid']) {
                            $unpaidBreakDurationInMinutes += $actualBreakDurationInMinutes;
                        }

                        $employeeBreak = new EmployeeBreak(
                            id                    : null                ,
                            breakScheduleId       : $breakSchedule['id'],
                            startTime             : null                ,
                            endTime               : null                ,
                            breakDurationInMinutes: 0,
                            createdAt             : $currentDateTime->format('Y-m-d H:i:s')
                        );

                        $result = $this->employeeBreakRepository->breakIn($employeeBreak);

                        if ($result === ActionResult::FAILURE) {
                            return [
                                'status'  => 'error',
                                'message' => 'An unexpected error occurred. Please try again later.'
                            ];
                        }

                        $lastBreakRecord = $this->employeeBreakRepository->fetchEmployeeLastBreakRecord($lastAttendanceRecord['work_schedule_id'], $employeeId);

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
                            breakDurationInMinutes: 0                                    ,
                            createdAt             : $lastBreakRecord['created_at']
                        );

                        $result = $this->employeeBreakRepository->breakOut($employeeBreak);

                        if ($result === ActionResult::FAILURE) {
                            return [
                                'status'  => 'error',
                                'message' => 'An unexpected error occurred. Please try again later.'
                            ];
                        }

                    } else {
                        $groupedBreaks = [];
                        foreach ($employeeBreaks as $break) {
                            $breakScheduleId = $break['break_schedule_id'];
                            if ( ! isset($groupedBreaks[$breakScheduleId])) {
                                $groupedBreaks[$breakScheduleId] = [];
                            }
                            $groupedBreaks[$breakScheduleId][] = $break;
                        }

                        $mergedBreaks = [];
                        foreach ($groupedBreaks as $breakScheduleId => $breaks) {
                            $firstBreak = $breaks[0];

                            if ($firstBreak['start_time'] !== null) {
                                $mergedBreak = $firstBreak;

                                $startTime = new DateTime($firstBreak['start_time']);
                                foreach ($breaks as $break) {
                                    $currentStartTime = new DateTime($break['start_time']);
                                    if ($currentStartTime < $startTime) {
                                        $startTime = $currentStartTime;
                                    }
                                }

                                $endTime = null;
                                foreach ($breaks as $break) {
                                    if ($break['end_time'] !== null) {
                                        $currentEndTime = new DateTime($break['end_time']);
                                        if ($endTime === null || $currentEndTime > $endTime) {
                                            $endTime = $currentEndTime;
                                        }
                                    }
                                }

                                $mergedBreak['start_time'] = $startTime->format('Y-m-d H:i:s');
                                if ($endTime !== null) {
                                    $mergedBreak['end_time'] = $endTime->format('Y-m-d H:i:s');
                                } else {
                                    $mergedBreak['end_time'] = null;
                                }

                                $mergedBreaks[] = $mergedBreak;
                            } else {
                                $mergedBreaks[] = $firstBreak;
                            }
                        }

                        foreach ($mergedBreaks as $employeeBreak) {
                            if ($employeeBreak['break_schedule_id'] === $breakSchedule['id'] && ($employeeBreak['start_time'] !== null && $employeeBreak['end_time'] !== null)) {
                                $breakStartTime = new DateTime($employeeBreak['start_time']);
                                $breakEndTime   = new DateTime($employeeBreak['end_time'  ]);

                                $breakScheduleStartTime = null;
                                if ($breakSchedule['is_flexible']) {
                                    $breakScheduleStartTime = $employeeBreak['start_time'];
                                } else {
                                    $breakScheduleStartTime = $breakSchedule['start_time'];
                                }

                                $breakScheduleStartTime = new DateTime($breakScheduleStartTime);
                                $breakScheduleEndTime   = clone $breakScheduleStartTime;
                                $breakScheduleEndTime->modify("+{$breakSchedule['break_type_duration_in_minutes']} minutes");

                                $breakScheduleStartTime = new DateTime($lastAttendanceDate->format('Y-m-d') . ' ' . $breakScheduleStartTime->format('H:i:s'));
                                $breakScheduleEndTime   = new DateTime($lastAttendanceDate->format('Y-m-d') . ' ' . $breakScheduleEndTime  ->format('H:i:s'));

                                if ($breakScheduleStartTime < $workScheduleStartTime) {
                                    $breakScheduleStartTime->modify('+1 day');
                                }

                                if ($breakScheduleEndTime < $workScheduleStartTime) {
                                    $breakScheduleEndTime->modify('+1 day');
                                }

                                if ($breakScheduleEndTime->format('H:i:s') < $breakScheduleStartTime->format('H:i:s')) {
                                    $breakScheduleEndTime->modify('+1 day');
                                }

                                if ($checkInTime >= $breakScheduleStartTime) {
                                    $breakScheduleStartTime = clone $checkInTime;
                                }

                                if ($breakEndTime->format('Y-m-d H:i:s') > $breakScheduleEndTime->format('Y-m-d H:i:s')) {
                                    $interval1 = $breakScheduleStartTime->diff($breakEndTime);
                                    $interval1 = $interval1->h * 60 + $interval1->i;

                                    $interval2 = $breakScheduleStartTime->diff($breakScheduleEndTime);
                                    $interval2 = $interval2->h * 60 + $interval2->i;

                                    $interval3 = $breakEndTime->diff($breakScheduleEndTime);
                                    $interval3 = $interval3->h * 60 + $interval3->i;

                                    if ( ! $breakSchedule['break_type_is_paid']) {
                                        $unpaidBreakDurationInMinutes += $interval1;
                                    } else {
                                        $paidBreakDurationInMinutes   += $interval2;
                                        $unpaidBreakDurationInMinutes += $interval3;
                                    }

                                } else {
                                    $actualEndTime = ($checkOutTime < $breakScheduleEndTime) ? $checkOutTime : $breakScheduleEndTime;
                                    $actualBreakDurationInMinutes = $breakScheduleStartTime->diff($actualEndTime);
                                    $actualBreakDurationInMinutes = $actualBreakDurationInMinutes->h * 60 + $actualBreakDurationInMinutes->i;

                                    if ( ! $breakSchedule['break_type_is_paid']) {
                                        $unpaidBreakDurationInMinutes += $actualBreakDurationInMinutes;
                                    } else {
                                        $paidBreakDurationInMinutes += $actualBreakDurationInMinutes;
                                    }
                                }

                            } elseif ($employeeBreak['break_schedule_id'] === $breakSchedule['id'] && ($employeeBreak['start_time'] !== null && $employeeBreak['end_time'] === null)) {
                                $breakScheduleStartTime = null;
                                if ($breakSchedule['is_flexible']) {
                                    $breakScheduleStartTime = $employeeBreak['start_time'];
                                } else {
                                    $breakScheduleStartTime = $breakSchedule['start_time'];
                                }

                                $breakScheduleStartTime = new DateTime($breakScheduleStartTime);
                                $breakScheduleEndTime   = clone $breakScheduleStartTime;
                                $breakScheduleEndTime->modify("+{$breakSchedule['break_type_duration_in_minutes']} minutes");

                                $breakScheduleStartTime = new DateTime($lastAttendanceDate->format('Y-m-d') . ' ' . $breakScheduleStartTime->format('H:i:s'));
                                $breakScheduleEndTime   = new DateTime($lastAttendanceDate->format('Y-m-d') . ' ' . $breakScheduleEndTime  ->format('H:i:s'));

                                if ($breakScheduleStartTime < $workScheduleStartTime) {
                                    $breakScheduleStartTime->modify('+1 day');
                                }

                                if ($breakScheduleEndTime < $workScheduleStartTime) {
                                    $breakScheduleEndTime->modify('+1 day');
                                }

                                if ($breakScheduleEndTime->format('H:i:s') < $breakScheduleStartTime->format('H:i:s')) {
                                    $breakScheduleEndTime->modify('+1 day');
                                }

                                if ($checkInTime >= $breakScheduleStartTime) {
                                    $breakScheduleStartTime = clone $checkInTime;
                                }

                                $actualEndTime = ($checkOutTime < $breakScheduleEndTime) ? $checkOutTime : $breakScheduleEndTime;
                                $actualBreakDurationInMinutes = $breakScheduleStartTime->diff($actualEndTime);
                                $actualBreakDurationInMinutes = $actualBreakDurationInMinutes->h * 60 + $actualBreakDurationInMinutes->i;

                                if ($breakSchedule['break_type_is_paid']) {
                                    $paidBreakDurationInMinutes += $actualBreakDurationInMinutes;
                                } else {
                                    $unpaidBreakDurationInMinutes += $actualBreakDurationInMinutes;
                                }

                            } elseif ($employeeBreak['break_schedule_id'] === $breakSchedule['id'] && ($employeeBreak['start_time'] === null && $employeeBreak['end_time'] === null)) {
                                $breakScheduleStartTime = null;
                                if ($breakSchedule['is_flexible']) {
                                    $breakScheduleStartTime = $breakSchedule['earliest_start_time'];
                                } else {
                                    $breakScheduleStartTime = $breakSchedule['start_time'];
                                }

                                $breakScheduleStartTime = new DateTime($breakScheduleStartTime);
                                $breakScheduleEndTime = clone $breakScheduleStartTime;
                                $breakScheduleEndTime->modify("+{$breakSchedule['break_type_duration_in_minutes']} minutes");

                                $breakScheduleStartTime = new DateTime($lastAttendanceDate->format('Y-m-d') . ' ' . $breakScheduleStartTime->format('H:i:s'));
                                $breakScheduleEndTime   = new DateTime($lastAttendanceDate->format('Y-m-d') . ' ' . $breakScheduleEndTime  ->format('H:i:s'));

                                if ($breakScheduleStartTime < $workScheduleStartTime) {
                                    $breakScheduleStartTime->modify('+1 day');
                                }

                                if ($breakScheduleEndTime < $workScheduleStartTime) {
                                    $breakScheduleEndTime->modify('+1 day');
                                }

                                if ($breakScheduleEndTime->format('H:i:s') < $breakScheduleStartTime->format('H:i:s')) {
                                    $breakScheduleEndTime->modify('+1 day');
                                }

                                if ($checkInTime >= $breakScheduleStartTime) {
                                    $breakScheduleStartTime = clone $checkInTime;
                                }

                                $actualBreakDurationInMinutes = 0;
                                if ($checkOutTime->format('Y-m-d H:i:s') >= $breakScheduleStartTime->format('Y-m-d H:i:s')) {
                                    $actualEndTime = ($checkOutTime < $breakScheduleEndTime) ? $checkOutTime : $breakScheduleEndTime;
                                    $actualBreakDurationInMinutes = $breakScheduleStartTime->diff($actualEndTime);
                                    $actualBreakDurationInMinutes = $actualBreakDurationInMinutes->h * 60 + $actualBreakDurationInMinutes->i;
                                }

                                if ($breakSchedule['break_type_is_paid']) {
                                    $paidBreakDurationInMinutes += $actualBreakDurationInMinutes;
                                } elseif ( ! $breakSchedule['break_type_is_paid']) {
                                    $unpaidBreakDurationInMinutes += $actualBreakDurationInMinutes;
                                }
                            }
                        }
                    }
                } else {
                    $breakScheduleStartTime = null;
                    if ($breakSchedule['is_flexible']) {
                        $breakScheduleStartTime = $breakSchedule['earliest_start_time'];
                    } else {
                        $breakScheduleStartTime = $breakSchedule['start_time'];
                    }

                    $breakScheduleStartTime = new DateTime($breakScheduleStartTime);
                    $breakScheduleEndTime = clone $breakScheduleStartTime;
                    $breakScheduleEndTime->modify("+{$breakSchedule['break_type_duration_in_minutes']} minutes");

                    $breakScheduleStartTime = new DateTime($lastAttendanceDate->format('Y-m-d') . ' ' . $breakScheduleStartTime->format('H:i:s'));
                    $breakScheduleEndTime   = new DateTime($lastAttendanceDate->format('Y-m-d') . ' ' . $breakScheduleEndTime  ->format('H:i:s'));

                    if ($breakScheduleStartTime < $workScheduleStartTime) {
                        $breakScheduleStartTime->modify('+1 day');
                    }

                    if ($breakScheduleEndTime < $workScheduleStartTime) {
                        $breakScheduleEndTime->modify('+1 day');
                    }

                    if ($breakScheduleEndTime->format('H:i:s') < $breakScheduleStartTime->format('H:i:s')) {
                        $breakScheduleEndTime->modify('+1 day');
                    }

                    if ($checkInTime >= $breakScheduleStartTime) {
                        $breakScheduleStartTime = clone $checkInTime;
                    }

                    $actualBreakDurationInMinutes = 0;
                    if ($checkOutTime->format('Y-m-d H:i:s') >= $breakScheduleStartTime->format('Y-m-d H:i:s')) {
                        $actualEndTime = ($checkOutTime < $breakScheduleEndTime) ? $checkOutTime : $breakScheduleEndTime;
                        $actualBreakDurationInMinutes = $breakScheduleStartTime->diff($actualEndTime);
                        $actualBreakDurationInMinutes = $actualBreakDurationInMinutes->h * 60 + $actualBreakDurationInMinutes->i;
                    }

                    if ($breakSchedule['break_type_is_paid']) {
                        $paidBreakDurationInMinutes += $actualBreakDurationInMinutes;
                    } elseif ( ! $breakSchedule['break_type_is_paid']) {
                        $unpaidBreakDurationInMinutes += $actualBreakDurationInMinutes;
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
                if (new DateTime($lastAttendanceRecord['check_in_time']) < $workScheduleStartTime) {
                    $interval = $workScheduleStartTime->diff(new DateTime($lastAttendanceRecord['check_in_time']));
                    $interval = $interval->h * 60 + $interval->i;

                    $totalHoursWorked -= $interval / 60;
                }

                if ($totalHoursWorked < $lastAttendanceRecord['work_schedule_total_work_hours']) {
                    $gracePeriod = (int) $this->settingRepository->fetchSettingValue('grace_period', 'work_schedule');

                    if ($gracePeriod === ActionResult::FAILURE) {
                        return [
                            'status' => 'error',
                            'message' => 'An unexpected error occurred. Please try again later.'
                        ];
                    }

                    $earlyCheckOutInMinutes = ($lastAttendanceRecord['work_schedule_total_work_hours'] - $totalHoursWorked) * 60;
                    $earlyCheckOutInMinutes -= $gracePeriod;

                    $attendanceStatus = 'Undertime';

                } elseif ($totalHoursWorked > $lastAttendanceRecord['work_schedule_total_work_hours']) {
                    $overtimeHours = $totalHoursWorked - $lastAttendanceRecord['work_schedule_total_work_hours'];
                    $overtimeHours = round($overtimeHours, 2);

                    $attendanceStatus = 'Overtime';
                }
            }

            $attendance = new Attendance(
                id                         : $lastAttendanceRecord['id']                  ,
                workScheduleId             : $lastAttendanceRecord['work_schedule_id']    ,
                date                       : $lastAttendanceRecord['date']                ,
                checkInTime                : $lastAttendanceRecord['check_in_time']       ,
                checkOutTime               : $checkOutTime->format('Y-m-d H:i:s')         ,
                totalBreakDurationInMinutes: $totalBreakDurationInMinutes                 ,
                totalHoursWorked           : $totalHoursWorked                            ,
                lateCheckIn                : $lastAttendanceRecord['late_check_in']       ,
                earlyCheckOut              : $earlyCheckOutInMinutes                      ,
                overtimeHours              : $overtimeHours                               ,
                isOvertimeApproved         : $lastAttendanceRecord['is_overtime_approved'],
                attendanceStatus           : $attendanceStatus,
                remarks                    : $lastAttendanceRecord['remarks']
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

        $currentTime = new DateTime($currentTime);

        foreach ($workSchedules as $date => $schedules) {
            foreach ($schedules as $schedule) {
                $startTime = (new DateTime($schedule['start_time']))->format('H:i:s');
                $endTime   = (new DateTime($schedule['end_time'  ]))->format('H:i:s');

                $startTime = new DateTime($date . ' ' . $startTime);
                $endTime   = new DateTime($date . ' ' . $endTime  );

                if ($endTime->format('H:i:s') < $startTime->format('H:i:s')) {
                    $endTime->modify('+1 day');
                }

                $schedule['start_time'] = $startTime->format('Y-m-d H:i:s');
                $schedule['end_time'  ] = $endTime  ->format('Y-m-d H:i:s');

                if ($currentTime >= $startTime && $currentTime < $endTime) {
                    $currentWorkSchedule = $schedule;
                    break 2;
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
