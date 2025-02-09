<?php

require_once __DIR__ . '/Attendance.php'                              ;
require_once __DIR__ . '/../work-schedules/WorkSchedule.php'          ;
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
    private readonly PDO                     $pdo                    ;
    private readonly AttendanceRepository    $attendanceRepository   ;
    private readonly EmployeeRepository      $employeeRepository     ;
    private readonly LeaveRequestRepository  $leaveRequestRepository ;
    private readonly WorkScheduleRepository  $workScheduleRepository ;
    private readonly SettingRepository       $settingRepository      ;
    private readonly BreakScheduleRepository $breakScheduleRepository;
    private readonly EmployeeBreakRepository $employeeBreakRepository;

    public function __construct(
        PDO                     $pdo                    ,
        AttendanceRepository    $attendanceRepository   ,
        EmployeeRepository      $employeeRepository     ,
        LeaveRequestRepository  $leaveRequestRepository ,
        WorkScheduleRepository  $workScheduleRepository ,
        SettingRepository       $settingRepository      ,
        BreakScheduleRepository $breakScheduleRepository,
        EmployeeBreakRepository $employeeBreakRepository
    ) {
        $this->pdo                     = $pdo                    ;
        $this->attendanceRepository    = $attendanceRepository   ;
        $this->employeeRepository      = $employeeRepository     ;
        $this->leaveRequestRepository  = $leaveRequestRepository ;
        $this->workScheduleRepository  = $workScheduleRepository ;
        $this->settingRepository       = $settingRepository      ;
        $this->breakScheduleRepository = $breakScheduleRepository;
        $this->employeeBreakRepository = $employeeBreakRepository;
    }

    public function handleRfidTap(string $rfidUid, string $currentDateTime)
    {
        $employeeColumns = [
            'id'
        ];

        $employeeFilterCriteria = [
            [
                'column'   => 'employee.deleted_at',
                'operator' => 'IS NULL'
            ],
            [
                'column'   => 'employee.rfid_uid',
                'operator' => '='                ,
                'value'    => $rfidUid
            ]
        ];

        $employeeFetchResult = $this->employeeRepository->fetchAllEmployees(
            columns             : $employeeColumns       ,
            filterCriteria      : $employeeFilterCriteria,
            limit               : 1                      ,
            includeTotalRowCount: false
        );

        if ($employeeFetchResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $employeeId =
            ! empty($employeeFetchResult['result_set'])
                ? $employeeFetchResult['result_set'][0]['id']
                : [];

        if (empty($employeeId)) {
            return [
                'status'  => 'warning',
                'message' => 'No employee found. This RFID may be invalid or not associated with any employee.'
            ];
        }

        $currentDateTime = new DateTime($currentDateTime                 );
        $currentDate     = new DateTime($currentDateTime->format('Y-m-d'));
        $previousDate    = (clone $currentDate)->modify('-1 day'         );

        $formattedCurrentDateTime = $currentDateTime->format('Y-m-d H:i:s');
        $formattedCurrentDate     = $currentDate    ->format('Y-m-d'      );
        $formattedPreviousDate    = $previousDate   ->format('Y-m-d'      );

        $leaveRequestColumns = [
            'is_half_day'  ,
            'half_day_part'
        ];

        $leaveRequestFilterCriteria = [
            [
                'column'   => 'leave_request.deleted_at',
                'operator' => 'IS NULL'
            ],
            [
                'column'   => 'leave_request.employee_id',
                'operator' => '='                        ,
                'value'    => $employeeId
            ],
            [
                'column'   => 'leave_request.start_date',
                'operator' => '<='                      ,
                'value'    => $formattedCurrentDate
            ],
            [
                'column'   => 'leave_request.end_date',
                'operator' => '>='                    ,
                'value'    => $formattedCurrentDate
            ],
            [
                'column'   => 'leave_request.status',
                'operator' => '='                   ,
                'value'    => 'In Progress'
            ]
        ];

        $leaveRequestFetchResult = $this->leaveRequestRepository->fetchAllLeaveRequests(
            columns             : $leaveRequestColumns       ,
            filterCriteria      : $leaveRequestFilterCriteria,
            limit               : 1                          ,
            includeTotalRowCount: false
        );

        if ($leaveRequestFetchResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $isOnLeaveToday =
            ! empty($leaveRequestFetchResult['result_set'])
                ? $leaveRequestFetchResult['result_set'][0]
                : [];

        if ( ! empty($isOnLeaveToday) && ! $isOnLeaveToday['is_half_day']) {
            return [
                'status'  => 'warning',
                'message' => 'You are currently on leave. You cannot check in or check out.'
            ];
        }

        $attendanceColumns = [
            'id'                                    ,
            'work_schedule_history_id'              ,
            'date'                                  ,
            'check_in_time'                         ,
            'check_out_time'                        ,
            'total_break_duration_in_minutes'       ,
            'total_hours_worked'                    ,
            'late_check_in'                         ,
            'early_check_out'                       ,
            'overtime_hours'                        ,
            'is_overtime_approved'                  ,
            'attendance_status'                     ,
            'remarks'                               ,

            'work_schedule_history_work_schedule_id'
        ];

        $attendanceFilterCriteria = [
            [
                'column'   => 'attendance.deleted_at',
                'operator' => 'IS NULL'
            ],
            [
                'column'   => 'work_schedule_history.employee_id',
                'operator' => '='                                ,
                'value'    => $employeeId
            ]
        ];

        $attendanceSortCriteria = [
            [
                'column'    => 'attendance.date',
                'direction' => 'DESC'
            ],
            [
                'column'    => 'attendance.check_in_time',
                'direction' => 'DESC'
            ]
        ];

        $attendanceFetchResult = $this->attendanceRepository->fetchAllAttendance(
            columns             : $attendanceColumns       ,
            filterCriteria      : $attendanceFilterCriteria,
            sortCriteria        : $attendanceSortCriteria  ,
            limit               : 1                        ,
            includeTotalRowCount: false
        );

        if ($attendanceFetchResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $lastAttendanceRecord =
            ! empty($attendanceFetchResult['result_set'])
                ? $attendanceFetchResult['result_set'][0]
                : [];

        if (empty($lastAttendanceRecord) ||

           ($lastAttendanceRecord['check_in_time' ] !== null  &&
            $lastAttendanceRecord['check_out_time'] !== null) ||

           ($lastAttendanceRecord['check_in_time' ] === null  &&
            $lastAttendanceRecord['check_out_time'] !== null) ||

           ($lastAttendanceRecord['check_in_time' ] === null  &&
            $lastAttendanceRecord['check_out_time'] === null)) {

            $isCheckIn = true;

            $workScheduleColumns = [
                'id'                  ,
                'start_time'          ,
                'end_time'            ,
                'is_flextime'         ,
                'total_hours_per_week',
                'total_work_hours'    ,
                'start_date'          ,
                'recurrence_rule'
            ];

            $workScheduleFilterCriteria = [
                [
                    'column'   => 'work_schedule.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'   => 'work_schedule.employee_id',
                    'operator' => '='                        ,
                    'value'    => $employeeId
                ]
            ];

            $workScheduleSortCriteria = [
                [
                    'column'    => 'work_schedule.start_time',
                    'direction' => 'ASC'
                ]
            ];

            $workScheduleFetchResult = $this->workScheduleRepository->fetchAllWorkSchedules(
                columns             : $workScheduleColumns       ,
                filterCriteria      : $workScheduleFilterCriteria,
                sortCriteria        : $workScheduleSortCriteria  ,
                includeTotalRowCount: false
            );

            if ($workScheduleFetchResult === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $workSchedules =
                ! empty($workScheduleFetchResult['result_set'])
                    ? $workScheduleFetchResult['result_set']
                    : [];

            if (empty($workSchedules)) {
                return [
                    'status'  => 'warning',
                    'message' => 'You don\'t have an assigned work schedule, or your schedule starts on a later date.'
                ];
            }

            $currentWorkSchedules = [];

            foreach ($workSchedules as $workSchedule) {
                $workScheduleDates = $this->workScheduleRepository->getRecurrenceDates(
                    $workSchedule['recurrence_rule'],
                    $formattedPreviousDate          ,
                    $formattedCurrentDate
                );

                if ($workScheduleDates === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                foreach ($workScheduleDates as $workScheduleDate) {
                    $currentWorkSchedules[$workScheduleDate][] = $workSchedule;
                }
            }

            $currentWorkSchedule = $this->getCurrentWorkSchedule(
                $currentWorkSchedules    ,
                $formattedCurrentDateTime
            );

            if (empty($currentWorkSchedule)) {
                if (   isset($currentWorkSchedules[$formattedCurrentDate]) &&
                     ! empty($currentWorkSchedules[$formattedCurrentDate])) {

                    return [
                        'status'  => 'information',
                        'message' => 'Your work schedule for today has ended.'
                    ];

                } else {
                    return [
                        'status'  => 'information',
                        'message' => 'You don\'t have a work schedule today.'
                    ];
                }
            }

            $currentWorkScheduleStartDateTime = new DateTime($currentWorkSchedule['start_time']);
            $currentWorkScheduleEndDateTime   = new DateTime($currentWorkSchedule['end_time'  ]);

            $currentWorkScheduleStartDate = new DateTime(
                $currentWorkScheduleStartDateTime->format('Y-m-d')
            );

            $currentWorkScheduleEndDate = new DateTime(
                $currentWorkScheduleEndDateTime->format('Y-m-d')
            );

            if ($currentWorkScheduleEndDate > $currentWorkScheduleStartDate     &&
                $currentWorkScheduleEndDateTime->format('H:i:s') !== '00:00:00') {

                $leaveRequestColumns = [
                    'is_half_day'  ,
                    'half_day_part'
                ];

                $leaveRequestFilterCriteria = [
                    [
                        'column'   => 'leave_request.deleted_at',
                        'operator' => 'IS NULL'
                    ],
                    [
                        'column'   => 'leave_request.employee_id',
                        'operator' => '='                        ,
                        'value'    => $employeeId
                    ],
                    [
                        'column'   => 'leave_request.start_date'                    ,
                        'operator' => '<='                                          ,
                        'value'    => $currentWorkScheduleStartDate->format('Y-m-d')
                    ],
                    [
                        'column'   => 'leave_request.end_date'                      ,
                        'operator' => '>='                                          ,
                        'value'    => $currentWorkScheduleStartDate->format('Y-m-d')
                    ],
                    [
                        'column'     => 'leave_request.status'      ,
                        'operator'   => 'IN'                        ,
                        'value_list' => ['Completed', 'In Progress']
                    ]
                ];

                $leaveRequestFetchResult = $this->leaveRequestRepository->fetchAllLeaveRequests(
                    columns             : $leaveRequestColumns       ,
                    filterCriteria      : $leaveRequestFilterCriteria,
                    limit               : 1                          ,
                    includeTotalRowCount: false
                );

                if ($leaveRequestFetchResult === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $didLeaveOccurYesterday =
                    ! empty($leaveRequestFetchResult['result_set'])
                        ? $leaveRequestFetchResult['result_set'][0]
                        : [];

                if ( ! empty($didLeaveOccurYesterday) && ! $didLeaveOccurYesterday['is_half_day']) {
                    return [
                        'status'  => 'warning',
                        'message' => 'You are currently on leave. You cannot check in or check out.'
                    ];
                }
            }

            $breakScheduleColumns = [
                'id'                            ,
                'start_time'                    ,
                'end_time'                      ,
                'is_flexible'                   ,
                'earliest_start_time'           ,
                'latest_end_time'               ,

                'break_type_duration_in_minutes',
                'break_type_is_paid'
            ];

            $breakScheduleFilterCriteria = [
                [
                    'column'   => 'break_schedule.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'   => 'break_schedule.work_schedule_id',
                    'operator' => '='                              ,
                    'value'    => $currentWorkSchedule['id']
                ]
            ];

            $breakScheduleFetchResult = $this->breakScheduleRepository->fetchAllBreakSchedules(
                columns             : $breakScheduleColumns       ,
                filterCriteria      : $breakScheduleFilterCriteria,
                includeTotalRowCount: false
            );

            if ($breakScheduleFetchResult === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $breakSchedules =
                ! empty($breakScheduleFetchResult['result_set'])
                    ? $breakScheduleFetchResult['result_set']
                    : [];

            if ( ! empty($breakSchedules)) {
                usort($breakSchedules, function($breakScheduleA, $breakScheduleB) {
                    $startTimeA = $breakScheduleA['start_time'] ?? $breakScheduleA['earliest_start_time'];
                    $startTimeB = $breakScheduleB['start_time'] ?? $breakScheduleB['earliest_start_time'];

                    if ($startTimeA === null && $startTimeB === null) {
                        return 0;
                    }

                    if ($startTimeA === null) {
                        return 1;
                    }
                    if ($startTimeB === null) {
                        return -1;
                    }

                    return $startTimeA <=> $startTimeB;
                });
            }

            if (( ! empty($isOnLeaveToday['is_half_day']) &&
                          $isOnLeaveToday['is_half_day']) ||

                (   isset($didLeaveOccurYesterday) &&
                  ! empty($didLeaveOccurYesterday) &&
                          $didLeaveOccurYesterday['is_half_day'])) {

                $halfDayPart =
                    isset($didLeaveOccurYesterday)
                        ? $didLeaveOccurYesterday['half_day_part']
                        : $isOnLeaveToday        ['half_day_part'];

                $halfDayDurationInMinutes =
                    ($currentWorkSchedule['total_work_hours'] / 2) * 60;

                if ($halfDayPart === 'first_half') {
                    $halfDayStartDateTime = clone $currentWorkScheduleStartDateTime;
                    $halfDayEndDateTime   = clone $currentWorkScheduleStartDateTime;

                    $halfDayEndDateTime->modify(
                        '+' . $halfDayDurationInMinutes . ' minutes'
                    );

                } elseif ($halfDayPart === 'second_half') {
                    $halfDayStartDateTime = clone $currentWorkScheduleEndDateTime;
                    $halfDayEndDateTime   = clone $currentWorkScheduleEndDateTime;

                    $halfDayStartDateTime->modify(
                        '-' . $halfDayDurationInMinutes . ' minutes'
                    );
                }

                if (isset($halfDayStartDateTime, $halfDayEndDateTime)) {
                    if ( ! empty($breakSchedules)) {
                        $assignedBreakSchedules = $breakSchedules;

                        if ($halfDayPart === 'second_half') {
                            $assignedBreakSchedules = array_reverse($assignedBreakSchedules);
                        }

                        foreach ($assignedBreakSchedules as $breakSchedule) {
                            if ( ! $breakSchedule['break_type_is_paid'] && $breakSchedule['is_flexible']) {
                                $breakStartTime = $breakSchedule['start_time'];
                                $breakEndTime   = $breakSchedule['end_time'  ];

                                $breakStartDateTime = new DateTime(
                                    $currentWorkScheduleStartDate->format('Y-m-d') . ' ' . $breakStartTime
                                );

                                $breakEndDateTime = new DateTime(
                                    $currentWorkScheduleStartDate->format('Y-m-d') . ' ' . $breakEndTime
                                );

                                if ($breakStartDateTime < $currentWorkScheduleStartDateTime) {
                                    $breakStartDateTime->modify('+1 day');
                                }

                                if ($breakEndDateTime < $currentWorkScheduleStartDateTime) {
                                    $breakEndDateTime->modify('+1 day');
                                }

                                if ($breakEndDateTime < $breakStartDateTime) {
                                    $breakEndDateTime->modify('+1 day');
                                }

                                $breakDurationInMinutes = $breakSchedule['break_type_duration_in_minutes'];

                                if ($halfDayPart === 'first_half') {

                                    if ($halfDayEndDateTime > $breakStartDateTime &&
                                        $halfDayEndDateTime < $breakEndDateTime  ) {

                                        $overlapTimeInMinutes =
                                            ($breakStartDateTime->diff($halfDayEndDateTime))->h * 60 +
                                            ($breakStartDateTime->diff($halfDayEndDateTime))->i;

                                        $halfDayEndDateTime = (clone $breakEndDateTime)
                                            ->modify('+' . $overlapTimeInMinutes . ' minutes');

                                    } elseif ($breakStartDateTime >= $halfDayStartDateTime &&
                                              $breakEndDateTime   <= $halfDayEndDateTime  ) {

                                        $halfDayEndDateTime->modify('+' . $breakDurationInMinutes . ' minutes');
                                    }

                                } elseif ($halfDayPart === 'second_half') {
                                    if ($halfDayStartDateTime > $breakStartDateTime &&
                                        $halfDayStartDateTime < $breakEndDateTime  ) {

                                        $overlapTimeInMinutes =
                                            ($breakEndDateTime->diff($halfDayStartDateTime))->h * 60 +
                                            ($breakEndDateTime->diff($halfDayStartDateTime))->i;

                                        $halfDayStartDateTime = (clone $breakStartDateTime)
                                            ->modify('-' . $overlapTimeInMinutes . ' minutes');

                                    } elseif ($breakStartDateTime >= $halfDayStartDateTime &&
                                              $breakEndDateTime   <= $halfDayEndDateTime  ) {

                                        $halfDayStartDateTime->modify('-' . $breakDurationInMinutes . ' minutes');
                                    }
                                }
                            }
                        }
                    }

                    if ($currentDateTime >= $halfDayStartDateTime &&
                        $currentDateTime <= $halfDayEndDateTime  ) {

                        $formattedHalfDayStartTime = $halfDayStartDateTime->format('h:i A');
                        $formattedHalfDayEndTime   = $halfDayEndDateTime  ->format('h:i A');

                        return [
                            'status'  => 'warning',
                            'message' => 'You are currently on a half-day leave from ' .
                                         $formattedHalfDayStartTime . ' to ' . $formattedHalfDayEndTime
                        ];
                    }
                }
            }

            $minutesAllowedForEarlyCheckIn = 0;
            $adjustedCurrentWorkScheduleStartDateTime = clone $currentWorkScheduleStartDateTime;

            if ( ! $currentWorkSchedule['is_flextime']) {
                $minutesAllowedForEarlyCheckIn = (int) $this->settingRepository->fetchSettingValue(
                    settingKey: 'minutes_can_check_in_before_shift',
                    groupName : 'work_schedule'
                );

                if ($minutesAllowedForEarlyCheckIn === ActionResult::FAILURE) {
                    return [
                        'status' => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $adjustedCurrentWorkScheduleStartDateTime->modify('-' . $minutesAllowedForEarlyCheckIn . ' minutes');

                $previousWorkSchedule = $this->getPreviousWorkSchedule(
                    assignedWorkSchedules: $currentWorkSchedules,
                    currentWorkSchedule  : $currentWorkSchedule
                );

                if ( ! empty($previousWorkSchedule)) {
                    $previousWorkScheduleEndDateTime = new DateTime($previousWorkSchedule['end_time']);

                    if ($previousWorkScheduleEndDateTime > $adjustedCurrentWorkScheduleStartDateTime) {
                        $interval = $previousWorkScheduleEndDateTime->diff($currentWorkScheduleStartDateTime);
                        $minutesAllowedForEarlyCheckIn = max(0, ($interval->h * 60) + $interval->i);

                        $adjustedCurrentWorkScheduleStartDateTime = clone $currentWorkScheduleStartDateTime;
                        $adjustedCurrentWorkScheduleStartDateTime->modify('-' . $minutesAllowedForEarlyCheckIn . ' minutes');
                    }
                }
            }

            if ( ! $currentWorkSchedule['is_flextime'] && $currentDateTime < $adjustedCurrentWorkScheduleStartDateTime) {
                return [
                    'status' => 'warning',
                    'message' => 'You are not allowed to check in early.'
                ];
            }

            if ( ! empty($lastAttendanceRecord) &&
                $lastAttendanceRecord['work_schedule_history_work_schedule_id'] === $currentWorkSchedule['id'] &&
                $lastAttendanceRecord['check_in_time' ] >= $adjustedCurrentWorkScheduleStartDateTime->format('Y-m-d H:i:s') &&
                $lastAttendanceRecord['check_out_time'] <= $currentWorkSchedule['end_time']) {

                $currentAttendanceRecord = new Attendance(
                    id                         : null                                                           ,
                    workScheduleHistoryId      : $lastAttendanceRecord['work_schedule_history_work_schedule_id'],
                    date                       : $lastAttendanceRecord['date'                                  ],
                    checkInTime                : $formattedCurrentDateTime                                      ,
                    checkOutTime               : null                                                           ,
                    totalBreakDurationInMinutes: $lastAttendanceRecord['total_break_duration_in_minutes'       ],
                    totalHoursWorked           : $lastAttendanceRecord['total_hours_worked'                    ],
                    lateCheckIn                : $lastAttendanceRecord['late_check_in'                         ],
                    earlyCheckOut              : 0                                                              ,
                    overtimeHours              : 0.00                                                           ,
                    isOvertimeApproved         : $lastAttendanceRecord['is_overtime_approved'                  ],
                    attendanceStatus           : $lastAttendanceRecord['attendance_status'                     ],
                    remarks                    : $lastAttendanceRecord['remarks'                               ]
                );

                $attendanceCheckInResult = $this->attendanceRepository->checkIn($currentAttendanceRecord);

                if ($attendanceCheckInResult === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                return [
                    'status' => 'success',
                    'message' => 'Checked-in recorded successfully.'
                ];
            }

            $attendanceStatus = 'Present';
            $lateCheckIn      = 0        ;
            $gracePeriod      = 0        ;

            if ( ! $currentWorkSchedule['is_flextime']) {
                $gracePeriod = $this->settingRepository->fetchSettingValue('grace_period', 'work_schedule');

                if ($gracePeriod === ActionResult::FAILURE) {
                    return [
                        'status' => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $adjustedStartTime = (clone $currentWorkScheduleStartDateTime)->modify('+' . $gracePeriod . ' minutes');

                if ($currentDateTime > $adjustedStartTime) {
                    $lateCheckIn = max(0, floor(($currentDateTime->getTimestamp() - $adjustedStartTime->getTimestamp()) / 60));
                    $attendanceStatus = 'Late';
                }
            }

            $workScheduleHistory = $this->workScheduleRepository
                ->fetchLatestWorkScheduleHistory($currentWorkSchedule['id']);

            if ($workScheduleHistory === ActionResult::FAILURE) {
                return [
                    'status' => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            if ($gracePeriod                   !== $workScheduleHistory['grace_period'                     ] ||
                $minutesAllowedForEarlyCheckIn !== $workScheduleHistory['minutes_can_check_in_before_shift']) {

                $workSchedule = new WorkSchedule(
                    id               : $currentWorkSchedule['id'                  ],
                    employeeId       : $employeeId                                 ,
                    startTime        : $currentWorkSchedule['start_time'          ],
                    endTime          : $currentWorkSchedule['end_time'            ],
                    isFlextime       : $currentWorkSchedule['is_flextime'         ],
                    totalHoursPerWeek: $currentWorkSchedule['total_hours_per_week'],
                    totalWorkHours   : $currentWorkSchedule['total_work_hours'    ],
                    startDate        : $currentWorkSchedule['start_date'          ],
                    recurrenceRule   : $currentWorkSchedule['recurrence_rule'     ]
                );

                $workScheduleHistoryCreateResult = $this->workScheduleRepository
                    ->createWorkScheduleHistory($workSchedule);

                if ($workScheduleHistoryCreateResult === ActionResult::FAILURE) {
                    return [
                        'status' => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }
            }

            $workScheduleHistoryId = $this->workScheduleRepository
                ->fetchLatestWorkScheduleHistoryId($currentWorkSchedule['id']);

            if ($workScheduleHistoryId === ActionResult::FAILURE) {
                return [
                    'status' => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            try {
                $this->pdo->beginTransaction();

                $currentAttendanceRecord = new Attendance(
                    id                         : null                                          ,
                    workScheduleHistoryId      : $workScheduleHistoryId                        ,
                    date                       : $currentWorkScheduleStartDate->format('Y-m-d'),
                    checkInTime                : $formattedCurrentDateTime                     ,
                    checkOutTime               : null                                          ,
                    totalBreakDurationInMinutes: 0.00                                          ,
                    totalHoursWorked           : 0.00                                          ,
                    lateCheckIn                : $lateCheckIn                                  ,
                    earlyCheckOut              : 0                                             ,
                    overtimeHours              : 0.00                                          ,
                    isOvertimeApproved         : false                                         ,
                    attendanceStatus           : $attendanceStatus                             ,
                    remarks                    : null
                );

                $attendanceCheckInResult = $this->attendanceRepository->checkIn($currentAttendanceRecord);

                if ($attendanceCheckInResult === ActionResult::FAILURE) {
                    $this->pdo->rollback();

                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred while checking in. Please try again later.'
                    ];
                }

                if ( ! empty($breakSchedules)) {
                    foreach ($breakSchedules as $breakSchedule) {
                        $breakScheduleHistoryId = $this->breakScheduleRepository
                            ->fetchLatestBreakScheduleHistoryId($breakSchedule['id']);

                        if ($breakScheduleHistoryId === ActionResult::FAILURE) {
                            $this->pdo->rollback();

                            return [
                                'status'  => 'error',
                                'message' => 'An unexpected error occurred while checking in. Please try again later.'
                            ];
                        }

                        $employeeBreakRecord = new EmployeeBreak(
                            id                    : null                     ,
                            attendanceId          : null                     ,
                            breakScheduleHistoryId: $breakScheduleHistoryId  ,
                            startTime             : null                     ,
                            endTime               : null                     ,
                            breakDurationInMinutes: 0                        ,
                            createdAt             : $formattedCurrentDateTime
                        );

                        $employeeBreakCreateResult = $this->employeeBreakRepository->createEmployeeBreak($employeeBreakRecord);

                        if ($employeeBreakCreateResult === ActionResult::FAILURE) {
                            $this->pdo->rollback();

                            return [
                                'status'  => 'error',
                                'message' => 'An unexpected error occurred while checking in. Please try again later.'
                            ];
                        }
                    }
                }

                $this->pdo->commit();

            } catch (PDOException $exception) {
                $this->pdo->rollback();

                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];

            } catch (Exception $exception) {
                $this->pdo->rollback();

                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }
            // if check in
        }

        //outside
    }

    private function getCurrentWorkSchedule(
        array  $assignedWorkSchedules,
        string $currentDateTime
    ): array {

        $currentDateTime = new DateTime($currentDateTime);

        $nextWorkSchedule = [];

        foreach ($assignedWorkSchedules as $workDate => $workSchedules) {
            foreach ($workSchedules as $workSchedule) {
                $workStartTime = $workSchedule['start_time'];
                $workEndTime   = $workSchedule['end_time'  ];

                $workStartDateTime = new DateTime($workDate . ' ' . $workStartTime);
                $workEndDateTime   = new DateTime($workDate . ' ' . $workEndTime  );

                if ($workEndDateTime <= $workStartDateTime) {
                    $workEndDateTime->modify('+1 day');
                }

                $workSchedule['start_time'] = $workStartDateTime->format('Y-m-d H:i:s');
                $workSchedule['end_time'  ] = $workEndDateTime  ->format('Y-m-d H:i:s');

                if ($currentDateTime >= $workStartDateTime && $currentDateTime < $workEndDateTime) {
                    return $workSchedule;
                }

                if ($currentDateTime < $workStartDateTime && empty($nextWorkSchedule)) {
                    $nextWorkSchedule = $workSchedule;
                }
            }
        }

        return $nextWorkSchedule;
    }

    private function getPreviousWorkSchedule(
        array $assignedWorkSchedules,
        array $currentWorkSchedule
    ): array {

        $currentWorkStartDateTime = new DateTime($currentWorkSchedule['start_time']);

        $previousWorkSchedule = [];

        foreach ($assignedWorkSchedules as $workDate => $workSchedules) {
            foreach ($workSchedules as $workSchedule) {
                $workStartTime = $workSchedule['start_time'];
                $workEndTime   = $workSchedule['end_time'  ];

                $workStartDateTime = new DateTime($workDate . ' ' . $workStartTime);
                $workEndDateTime   = new DateTime($workDate . ' ' . $workEndTime  );

                if ($workEndDateTime <= $workStartDateTime) {
                    $workEndDateTime->modify('+1 day');
                }

                $workSchedule['start_time'] = $workStartDateTime->format('Y-m-d H:i:s');
                $workSchedule['end_time'  ] = $workEndDateTime  ->format('Y-m-d H:i:s');

                if ($currentWorkStartDateTime <= $workStartDateTime) {
                    return $previousWorkSchedule;
                }

                $previousWorkSchedule = $workSchedule;
            }
        }

        return $previousWorkSchedule;
    }
}
