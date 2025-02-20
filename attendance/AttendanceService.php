<?php

require_once __DIR__ . '/Attendance.php'                              ;

require_once __DIR__ . '/../work-schedules/WorkSchedule.php'          ;
require_once __DIR__ . '/../breaks/BreakSchedule.php'                 ;
require_once __DIR__ . '/../breaks/EmployeeBreak.php'                 ;

require_once __DIR__ . '/../work-schedules/WorkScheduleSnapshot.php'  ;
require_once __DIR__ . '/../breaks/BreakScheduleSnapshot.php'         ;
require_once __DIR__ . '/../breaks/BreakTypeSnapshot.php'             ;

require_once __DIR__ . '/AttendanceRepository.php'                    ;

require_once __DIR__ . '/../employees/EmployeeRepository.php'         ;
require_once __DIR__ . '/../leaves/LeaveRequestRepository.php'        ;
require_once __DIR__ . '/../work-schedules/WorkScheduleRepository.php';
require_once __DIR__ . '/../settings/SettingRepository.php'           ;
require_once __DIR__ . '/../breaks/BreakScheduleRepository.php'       ;
require_once __DIR__ . '/../breaks/EmployeeBreakRepository.php'       ;
require_once __DIR__ . '/../breaks/BreakTypeRepository.php'           ;

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
    private readonly BreakTypeRepository     $breakTypeRepository    ;

    public function __construct(
        PDO                     $pdo                    ,
        AttendanceRepository    $attendanceRepository   ,
        EmployeeRepository      $employeeRepository     ,
        LeaveRequestRepository  $leaveRequestRepository ,
        WorkScheduleRepository  $workScheduleRepository ,
        SettingRepository       $settingRepository      ,
        BreakScheduleRepository $breakScheduleRepository,
        EmployeeBreakRepository $employeeBreakRepository,
        BreakTypeRepository     $breakTypeRepository
    ) {
        $this->pdo                     = $pdo                    ;
        $this->attendanceRepository    = $attendanceRepository   ;
        $this->employeeRepository      = $employeeRepository     ;
        $this->leaveRequestRepository  = $leaveRequestRepository ;
        $this->workScheduleRepository  = $workScheduleRepository ;
        $this->settingRepository       = $settingRepository      ;
        $this->breakScheduleRepository = $breakScheduleRepository;
        $this->employeeBreakRepository = $employeeBreakRepository;
        $this->breakTypeRepository     = $breakTypeRepository    ;
    }

    public function handleRfidTap(string $rfidUid, string $currentDateTime): array
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

        $employeeId = $this->employeeRepository->fetchAllEmployees(
            columns             : $employeeColumns       ,
            filterCriteria      : $employeeFilterCriteria,
            limit               : 1                      ,
            includeTotalRowCount: false
        );

        if ($employeeId === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $employeeId =
            ! empty($employeeId['result_set'])
                ? $employeeId['result_set'][0]['id']
                : [];

        if (empty($employeeId)) {
            return [
                'status'  => 'warning',
                'message' => 'No employee found. This RFID may be invalid or ' .
                			 'not associated with any employee.'
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
            	'column'      => $formattedCurrentDate     ,
            	'operator'    => 'BETWEEN'                 ,
            	'lower_bound' => 'leave_request.start_date',
            	'upper_bound' => 'leave_request.end_date'
            ],
            [
                'column'     => 'leave_request.status'      ,
                'operator'   => 'IN'                        ,
                'value_list' => ['In Progress', 'Completed']
            ]
        ];

        $isOnLeaveToday = $this->leaveRequestRepository->fetchAllLeaveRequests(
            columns             : $leaveRequestColumns       ,
            filterCriteria      : $leaveRequestFilterCriteria,
            limit               : 1                          ,
            includeTotalRowCount: false
        );

        if ($isOnLeaveToday === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $isOnLeaveToday =
            ! empty($isOnLeaveToday['result_set'])
                ? $isOnLeaveToday['result_set'][0]
                : [];

        if ( ! empty($isOnLeaveToday) && ! $isOnLeaveToday['is_half_day']) {
            return [
                'status'  => 'warning',
                'message' => 'You are currently on leave. You cannot check in or check out.'
            ];
        }

        $attendanceRecordColumns = [
            'id'                                                      ,
            'work_schedule_snapshot_id'                               ,
            'date'                                                    ,
            'check_in_time'                                           ,
            'check_out_time'                                          ,
            'total_break_duration_in_minutes'                         ,
            'total_hours_worked'                                      ,
            'late_check_in'                                           ,
            'early_check_out'                                         ,
            'overtime_hours'                                          ,
            'is_overtime_approved'                                    ,
            'attendance_status'                                       ,
            'remarks'                                                 ,

            'work_schedule_snapshot_work_schedule_id'                 ,
            'work_schedule_snapshot_start_time'                       ,
            'work_schedule_snapshot_end_time'                         ,
            'work_schedule_snapshot_is_flextime'                      ,
            'work_schedule_snapshot_total_hours_per_week'             ,
            'work_schedule_snapshot_total_work_hours'                 ,
            'work_schedule_snapshot_start_date'                       ,
            'work_schedule_snapshot_recurrence_rule'                  ,
            'work_schedule_snapshot_grace_period'                     ,
            'work_schedule_snapshot_minutes_can_check_in_before_shift'
        ];

        $attendanceRecordFilterCriteria = [
            [
                'column'   => 'attendance.deleted_at',
                'operator' => 'IS NULL'
            ],
            [
                'column'   => 'work_schedule_snapshot.employee_id',
                'operator' => '='                                 ,
                'value'    => $employeeId
            ]
        ];

        $attendanceRecordSortCriteria = [
            [
                'column'    => 'attendance.date',
                'direction' => 'DESC'
            ],
            [
                'column'    => 'attendance.check_in_time',
                'direction' => 'DESC'
            ],
            [
                'column'    => 'work_schedule_snapshot.start_time',
                'direction' => 'DESC'
            ]
        ];

        $lastAttendanceRecord = $this->attendanceRepository->fetchAllAttendance(
            columns             : $attendanceRecordColumns       ,
            filterCriteria      : $attendanceRecordFilterCriteria,
            sortCriteria        : $attendanceRecordSortCriteria  ,
            limit               : 1                              ,
            includeTotalRowCount: false
        );

        if ($lastAttendanceRecord === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $lastAttendanceRecord =
            ! empty($lastAttendanceRecord['result_set'])
                ? $lastAttendanceRecord['result_set'][0]
                : [];

        if (empty($lastAttendanceRecord)                      ||

           ($lastAttendanceRecord['check_in_time' ] !== null  &&
            $lastAttendanceRecord['check_out_time'] !== null) ||

           ($lastAttendanceRecord['check_in_time' ] === null  &&
            $lastAttendanceRecord['check_out_time'] !== null) ||

           ($lastAttendanceRecord['check_in_time' ] === null  &&
            $lastAttendanceRecord['check_out_time'] === null)) {

            $isCheckIn = true;

            $isUsingLastAttendanceWorkSchedule = false;

            if ( ! empty($lastAttendanceRecord)) {
                $isUsingLastAttendanceWorkSchedule = true;

                $workScheduleDate      = $lastAttendanceRecord['date'                             ];
                $workScheduleStartTime = $lastAttendanceRecord['work_schedule_snapshot_start_time'];
                $workScheduleEndTime   = $lastAttendanceRecord['work_schedule_snapshot_end_time'  ];

                $workScheduleStartDateTime = new DateTime($workScheduleDate . ' ' . $workScheduleStartTime);
                $workScheduleEndDateTime   = new DateTime($workScheduleDate . ' ' . $workScheduleEndTime  );

                if ($workScheduleEndDateTime <= $workScheduleStartDateTime) {
                    $workScheduleEndDateTime->modify('+1 day');
                }

                $workScheduleId = $lastAttendanceRecord['work_schedule_snapshot_work_schedule_id'];

                $earlyCheckInWindow = $lastAttendanceRecord['work_schedule_snapshot_minutes_can_check_in_before_shift'];

                $adjustedWorkScheduleStartDateTime = (clone $workScheduleStartDateTime)
                    ->modify('-' . $earlyCheckInWindow . ' minutes');
            }

            if (empty($lastAttendanceRecord) ||

                $currentDateTime <  $adjustedWorkScheduleStartDateTime ||
                $currentDateTime >= $workScheduleEndDateTime) {

                $isUsingLastAttendanceWorkSchedule = false;

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

                $workSchedules = $this->workScheduleRepository->fetchAllWorkSchedules(
                    columns             : $workScheduleColumns       ,
                    filterCriteria      : $workScheduleFilterCriteria,
                    sortCriteria        : $workScheduleSortCriteria  ,
                    includeTotalRowCount: false
                );

                if ($workSchedules === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $workSchedules =
                    ! empty($workSchedules['result_set'])
                        ? $workSchedules['result_set']
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

                    if (empty($workScheduleDates)) {
                        return [
                            'status'  => 'information',
                            'message' => 'You don\'t have a work schedule today.'
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
                    if (  isset($currentWorkSchedules[$formattedCurrentDate]) &&
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

                $currentWorkSchedule['employee_id'] = $employeeId;

                $workScheduleStartDateTime = new DateTime($currentWorkSchedule['start_time']);
                $workScheduleEndDateTime   = new DateTime($currentWorkSchedule['end_time'  ]);

                $workScheduleId = $currentWorkSchedule['id'];

                $earlyCheckInWindow = 0;

                $adjustedWorkScheduleStartDateTime = clone $workScheduleStartDateTime;

                if ( ! $currentWorkSchedule['is_flextime']) {
                    $earlyCheckInWindow = (int) $this->settingRepository->fetchSettingValue(
                        settingKey: 'minutes_can_check_in_before_shift',
                        groupName : 'work_schedule'
                    );

                    if ($earlyCheckInWindow === ActionResult::FAILURE) {
                        return [
                            'status' => 'error',
                            'message' => 'An unexpected error occurred. Please try again later.'
                        ];
                    }

                    $adjustedWorkScheduleStartDateTime->modify('-' . $earlyCheckInWindow . ' minutes');

                    $previousWorkSchedule = $this->getPreviousWorkSchedule(
                        assignedWorkSchedules: $currentWorkSchedules,
                        currentWorkSchedule  : $currentWorkSchedule
                    );

                    if ( ! empty($previousWorkSchedule)) {
                        $previousWorkScheduleEndDateTime = new DateTime($previousWorkSchedule['end_time']);

                        if ($previousWorkScheduleEndDateTime > $adjustedWorkScheduleStartDateTime) {
                            $earlyCheckInWindow = max(0,
                                $workScheduleStartDateTime->diff($previousWorkScheduleEndDateTime)->h * 60 +
                                $workScheduleStartDateTime->diff($previousWorkScheduleEndDateTime)->i
                            );

                            $adjustedWorkScheduleStartDateTime = (clone $workScheduleStartDateTime)
                                ->modify('-' . $earlyCheckInWindow . ' minutes');
                        }
                    }

                    if ($currentDateTime < $adjustedWorkScheduleStartDateTime) {
                        return [
                            'status'  => 'warning',
                            'message' => 'You are not allowed to check in early.'
                        ];
                    }
                }
            }

            if ($isUsingLastAttendanceWorkSchedule) {
                $workSchedule = [
                    'id'                   => $lastAttendanceRecord['work_schedule_snapshot_work_schedule_id'    ],
                    'employee_id'          => $employeeId                                                         ,
                    'start_time'           => $lastAttendanceRecord['work_schedule_snapshot_start_time'          ],
                    'end_time'             => $lastAttendanceRecord['work_schedule_snapshot_end_time'            ],
                    'is_flextime'          => $lastAttendanceRecord['work_schedule_snapshot_is_flextime'         ],
                    'total_hours_per_week' => $lastAttendanceRecord['work_schedule_snapshot_total_hours_per_week'],
                    'total_work_hours'     => $lastAttendanceRecord['work_schedule_snapshot_total_work_hours'    ],
                    'start_date'           => $lastAttendanceRecord['work_schedule_snapshot_start_date'          ],
                    'recurrence_rule'      => $lastAttendanceRecord['work_schedule_snapshot_recurrence_rule'     ]
                ];

            } else {
                $workSchedule = $currentWorkSchedule;
            }

            $workScheduleStartDate = new DateTime($workScheduleStartDateTime->format('Y-m-d'));
            $workScheduleEndDate   = new DateTime($workScheduleEndDateTime  ->format('Y-m-d'));

            if ($workScheduleEndDate > $workScheduleStartDate &&
                $workScheduleEndDateTime->format('H:i:s') !== '00:00:00' &&
                $currentDate === $workScheduleEndDate) {

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
                        'column'      => $formattedPreviousDate    ,
                        'operator'    => 'BETWEEN'                 ,
                        'lower_bound' => 'leave_request.start_date',
                        'upper_bound' => 'leave_request.end_date'
                    ],
                    [
                        'column'     => 'leave_request.status'      ,
                        'operator'   => 'IN'                        ,
                        'value_list' => ['In Progress', 'Completed']
                    ]
                ];

                $didLeaveOccurYesterday = $this->leaveRequestRepository->fetchAllLeaveRequests(
                    columns             : $leaveRequestColumns       ,
                    filterCriteria      : $leaveRequestFilterCriteria,
                    limit               : 1                          ,
                    includeTotalRowCount: false
                );

                if ($didLeaveOccurYesterday === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $didLeaveOccurYesterday =
                    ! empty($didLeaveOccurYesterday['result_set'])
                        ? $didLeaveOccurYesterday['result_set'][0]
                        : [];

                if ( ! empty($didLeaveOccurYesterday) && ! $didLeaveOccurYesterday['is_half_day']) {
                    return [
                        'status'  => 'warning',
                        'message' => 'You are on leave. You cannot check in or check out.'
                    ];
                }
            }

            if ($isUsingLastAttendanceWorkSchedule) {
                $employeeBreakColumns = [
                    'break_schedule_snapshot_start_time'         ,
                    'break_schedule_snapshot_end_time'           ,
                    'break_schedule_snapshot_is_flexible'        ,
                    'break_schedule_snapshot_earliest_start_time',
                    'break_schedule_snapshot_latest_end_time'    ,

                    'break_type_snapshot_duration_in_minutes'    ,
                    'break_type_snapshot_is_paid'
                ];

                $employeeBreakFilterCriteria = [
                    [
                        'column'   => 'employee_break.deleted_at',
                        'operator' => 'IS NULL'
                    ],
                    [
                        'column'   => 'break_schedule_snapshot.work_schedule_snapshot_id',
                        'operator' => '='                                                ,
                        'value'    => $lastAttendanceRecord['work_schedule_snapshot_id']
                    ],
                    [
                        'column'      => 'employee_break.created_at'                              ,
                        'operator'    => 'BETWEEN'                                                ,
                        'lower_bound' => $adjustedWorkScheduleStartDateTime->format('Y-m-d H:i:s'),
                        'upper_bound' => $workScheduleEndDateTime          ->format('Y-m-d H:i:s')
                    ]
                ];

                $employeeGroupByColumns = [
                    'employee_break.break_schedule_snapshot_id'
                ];

                $breakSchedules = $this->employeeBreakRepository->fetchAllEmployeeBreaks(
                    columns             : $employeeBreakColumns       ,
                    filterCriteria      : $employeeBreakFilterCriteria,
                    groupByColumns      : $employeeGroupByColumns     ,
                    includeTotalRowCount: false
                );

                if ( ! empty($breakSchedules['result_set'])) {
                    $mapKeys = [
                        "break_schedule_snapshot_start_time"          => "start_time"                    ,
                        "break_schedule_snapshot_end_time"            => "end_time"                      ,
                        "break_schedule_snapshot_is_flexible"         => "is_flexible"                   ,
                        "break_schedule_snapshot_earliest_start_time" => "earliest_start_time"           ,
                        "break_schedule_snapshot_latest_end_time"     => "latest_end_time"               ,

                        "break_type_snapshot_duration_in_minutes"     => "break_type_duration_in_minutes",
                        "break_type_snapshot_is_paid"                 => "break_type_is_paid"
                    ];

                    $breakSchedules['result_set'] = array_map(function ($item) use ($mapKeys) {
                        $newItem = [];
                        foreach ($mapKeys as $oldKey => $newKey) {
                            if (isset($item[$oldKey])) {
                                $newItem[$newKey] = $item[$oldKey];
                            }
                        }
                        return $newItem;
                    }, $breakSchedules['result_set']);
                }

            } else {
                $breakScheduleColumns = [
                    'id'                               ,
                    'break_type_id'                    ,
                    'start_time'                       ,
                    'end_time'                         ,
                    'is_flexible'                      ,
                    'earliest_start_time'              ,
                    'latest_end_time'                  ,

                    'break_type_name'                  ,
                    'break_type_duration_in_minutes'   ,
                    'break_type_is_paid'               ,
                    'is_require_break_in_and_break_out'
                ];

                $breakScheduleFilterCriteria = [
                    [
                        'column'   => 'break_schedule.deleted_at',
                        'operator' => 'IS NULL'
                    ],
                    [
                        'column'   => 'break_schedule.work_schedule_id',
                        'operator' => '='                              ,
                        'value'    => $workScheduleId
                    ]
                ];

                $breakSchedules = $this->breakScheduleRepository->fetchAllBreakSchedules(
                    columns             : $breakScheduleColumns       ,
                    filterCriteria      : $breakScheduleFilterCriteria,
                    includeTotalRowCount: false
                );
            }

            if ($breakSchedules === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $breakSchedules =
                ! empty($breakSchedules['result_set'])
                    ? $breakSchedules['result_set']
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
                    ($workSchedule['total_work_hours'] / 2) * 60;

                if ($halfDayPart === 'first_half') {
                    $halfDayStartDateTime = clone $workScheduleStartDateTime;
                    $halfDayEndDateTime   = clone $workScheduleStartDateTime;

                    $halfDayEndDateTime->modify(
                        '+' . $halfDayDurationInMinutes . ' minutes'
                    );

                } elseif ($halfDayPart === 'second_half') {
                    $halfDayStartDateTime = clone $workScheduleEndDateTime;
                    $halfDayEndDateTime   = clone $workScheduleEndDateTime;

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
                            if ( ! $breakSchedule['break_type_is_paid']) {
                                $breakDurationInMinutes = $breakSchedule['break_type_duration_in_minutes'];

                                if ($breakSchedule['is_flexible']) {
                                    $breakStartTime = $breakSchedule['latest_end_time'];
                                    $breakEndTime   = $breakSchedule['latest_end_time'];

                                    $breakStartTime = (new DateTime($breakStartTime))
                                        ->modify('-' . $breakDurationInMinutes . ' minutes')
                                        ->format('H:i:s');

                                } else {
                                    $breakStartTime = $breakSchedule['start_time'];
                                    $breakEndTime   = $breakSchedule['end_time'  ];
                                }

                                $breakStartDateTime = new DateTime(
                                    $workScheduleStartDate->format('Y-m-d') . ' ' . $breakStartTime
                                );

                                $breakEndDateTime = new DateTime(
                                    $workScheduleStartDate->format('Y-m-d') . ' ' . $breakEndTime
                                );

                                if ($breakStartDateTime < $workScheduleStartDateTime) {
                                    $breakStartDateTime->modify('+1 day');
                                }

                                if ($breakEndDateTime < $workScheduleStartDateTime) {
                                    $breakEndDateTime->modify('+1 day');
                                }

                                if ($breakEndDateTime < $breakStartDateTime) {
                                    $breakEndDateTime->modify('+1 day');
                                }

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

            if ($isUsingLastAttendanceWorkSchedule) {
                $currentAttendanceRecord = new Attendance(
                    id                         : null                                                    ,
                    workScheduleSnapshotId     : $lastAttendanceRecord['work_schedule_snapshot_id'      ],
                    date                       : $lastAttendanceRecord['date'                           ],
                    checkInTime                : $formattedCurrentDateTime                               ,
                    checkOutTime               : null                                                    ,
                    totalBreakDurationInMinutes: $lastAttendanceRecord['total_break_duration_in_minutes'],
                    totalHoursWorked           : $lastAttendanceRecord['total_hours_worked'             ],
                    lateCheckIn                : $lastAttendanceRecord['late_check_in'                  ],
                    earlyCheckOut              : 0                                                       ,
                    overtimeHours              : 0.00                                                    ,
                    isOvertimeApproved         : $lastAttendanceRecord['is_overtime_approved'           ],
                    attendanceStatus           : $lastAttendanceRecord['attendance_status'              ],
                    remarks                    : $lastAttendanceRecord['remarks'                        ]
                );

                $attendanceCheckInResult = $this->attendanceRepository->checkIn($currentAttendanceRecord);

                if ($attendanceCheckInResult === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                return [
                    'status'  => 'success',
                    'message' => 'Checked-in recorded successfully.'
                ];
            }

            $attendanceStatus = 'Present';
            $lateCheckIn      = 0        ;
            $gracePeriod      = 0        ;

            if ( ! $workSchedule['is_flextime']) {
                $gracePeriod = (int) $this->settingRepository->fetchSettingValue(
                    settingKey: 'grace_period' ,
                    groupName : 'work_schedule'
                );

                if ($gracePeriod === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $adjustedStartTime = (clone $workScheduleStartDateTime)->modify('+' . $gracePeriod . ' minutes');

                if ($currentDateTime > $adjustedStartTime) {
                    $lateCheckIn      = max(0, floor(($currentDateTime->getTimestamp() - $adjustedStartTime->getTimestamp()) / 60));
                    $attendanceStatus = 'Late';
                }
            }

            $latestWorkScheduleSnapshot = $this->workScheduleRepository
                ->fetchLatestWorkScheduleSnapshotById($workScheduleId);

            if ($latestWorkScheduleSnapshot === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            if ( ! empty($latestWorkScheduleSnapshot)) {
                $workScheduleSnapshotId = $latestWorkScheduleSnapshot['id'];
            }

            $formattedWorkScheduleStartTime = $workScheduleStartDateTime->format('H:i:s');
            $formattedWorkScheduleEndTime   = $workScheduleEndDateTime  ->format('H:i:s');

            if (empty($latestWorkScheduleSnapshot) ||

                $formattedWorkScheduleStartTime       !== $latestWorkScheduleSnapshot['start_time'                       ] ||
                $formattedWorkScheduleEndTime         !== $latestWorkScheduleSnapshot['end_time'                         ] ||
                $workSchedule['is_flextime'         ] !== $latestWorkScheduleSnapshot['is_flextime'                      ] ||
                $workSchedule['total_hours_per_week'] !== $latestWorkScheduleSnapshot['total_hours_per_week'             ] ||
                $workSchedule['total_work_hours'    ] !== $latestWorkScheduleSnapshot['total_work_hours'                 ] ||
                $workSchedule['start_date'          ] !== $latestWorkScheduleSnapshot['start_date'                       ] ||
                $workSchedule['recurrence_rule'     ] !== $latestWorkScheduleSnapshot['recurrence_rule'                  ] ||
                $gracePeriod                          !== $latestWorkScheduleSnapshot['grace_period'                     ] ||
                $earlyCheckInWindow                   !== $latestWorkScheduleSnapshot['minutes_can_check_in_before_shift']) {

                $newWorkScheduleSnapshot = new WorkScheduleSnapshot(
                    workScheduleId    : $workSchedule['id'                  ],
                    employeeId        : $workSchedule['employee_id'         ],
                    startTime         : $formattedWorkScheduleStartTime      ,
                    endTime           : $formattedWorkScheduleEndTime        ,
                    isFlextime        : $workSchedule['is_flextime'         ],
                    totalHoursPerWeek : $workSchedule['total_hours_per_week'],
                    totalWorkHours    : $workSchedule['total_work_hours'    ],
                    startDate         : $workSchedule['start_date'          ],
                    recurrenceRule    : $workSchedule['recurrence_rule'     ],
                    gracePeriod       : $gracePeriod                         ,
                    earlyCheckInWindow: $earlyCheckInWindow
                );

                $workScheduleSnapshotId = $this->workScheduleRepository
                    ->createWorkScheduleSnapshot($newWorkScheduleSnapshot);

                if ($workScheduleSnapshotId === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }
            }

            try {
                $this->pdo->beginTransaction();

                $currentAttendanceRecord = new Attendance(
                    id                         : null                                   ,
                    workScheduleSnapshotId     : $workScheduleSnapshotId                ,
                    date                       : $workScheduleStartDate->format('Y-m-d'),
                    checkInTime                : $formattedCurrentDateTime              ,
                    checkOutTime               : null                                   ,
                    totalBreakDurationInMinutes: 0.00                                   ,
                    totalHoursWorked           : 0.00                                   ,
                    lateCheckIn                : $lateCheckIn                           ,
                    earlyCheckOut              : 0                                      ,
                    overtimeHours              : 0.00                                   ,
                    isOvertimeApproved         : false                                  ,
                    attendanceStatus           : $attendanceStatus                      ,
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
                        $latestBreakTypeSnapshot = $this->breakTypeRepository
                            ->fetchLatestBreakTypeSnapshotById($breakSchedule['break_type_id']);

                        if ($latestBreakTypeSnapshot === ActionResult::FAILURE) {
                            return [
                                'status'  => 'error',
                                'message' => 'An unexpected error occurred. Please try again later.'
                            ];
                        }

                        if ( ! empty($latestBreakTypeSnapshot)) {
                            $breakTypeSnapshotId = $latestBreakTypeSnapshot['id'];
                        }

                        if (empty($latestBreakTypeSnapshot) ||

                            $breakSchedule['break_type_name'                  ] !== $latestBreakTypeSnapshot['name'                             ] ||
                            $breakSchedule['break_type_duration_in_minutes'   ] !== $latestBreakTypeSnapshot['duration_in_minutes'              ] ||
                            $breakSchedule['break_type_is_paid'               ] !== $latestBreakTypeSnapshot['is_paid'                          ] ||
                            $breakSchedule['is_require_break_in_and_break_out'] !== $latestBreakTypeSnapshot['is_require_break_in_and_break_out']) {

                            $newBreakTypeSnapshot = new BreakTypeSnapshot(
                                breakTypeId              : $breakSchedule['break_type_id'                    ],
                                name                     : $breakSchedule['break_type_name'                  ],
                                durationInMinutes        : $breakSchedule['break_type_duration_in_minutes'   ],
                                isPaid                   : $breakSchedule['break_type_is_paid'               ],
                                requireBreakInAndBreakOut: $breakSchedule['is_require_break_in_and_break_out']
                            );

                            $breakTypeSnapshotId = $this->breakTypeRepository
                                ->createBreakTypeSnapshot($newBreakTypeSnapshot);

                            if ($breakTypeSnapshotId === ActionResult::FAILURE) {
                                return [
                                    'status'  => 'error',
                                    'message' => 'An unexpected error occurred. Please try again later.'
                                ];
                            }
                        }

                        $latestBreakScheduleSnapshot = $this->breakScheduleRepository
                            ->fetchLatestBreakScheduleSnapshotById($breakSchedule['id']);

                        if ($latestBreakScheduleSnapshot === ActionResult::FAILURE) {
                            return [
                                'status'  => 'error',
                                'message' => 'An unexpected error occurred. Please try again later.'
                            ];
                        }

                        if ( ! empty($latestBreakScheduleSnapshot)) {
                            $breakScheduleSnapshotId = $latestBreakScheduleSnapshot['id'];
                        }

                        if (empty($latestBreakScheduleSnapshot) ||

                            $workScheduleSnapshotId               !== $latestBreakScheduleSnapshot['work_schedule_snapshot_id'] ||
                            $breakTypeSnapshotId                  !== $latestBreakScheduleSnapshot['break_type_snapshot_id'   ] ||
                            $breakSchedule['start_time'         ] !== $latestBreakScheduleSnapshot['start_time'               ] ||
                            $breakSchedule['end_time'           ] !== $latestBreakScheduleSnapshot['end_time'                 ] ||
                            $breakSchedule['is_flexible'        ] !== $latestBreakScheduleSnapshot['is_flexible'              ] ||
                            $breakSchedule['earliest_start_time'] !== $latestBreakScheduleSnapshot['earliest_start_time'      ] ||
                            $breakSchedule['latest_end_time'    ] !== $latestBreakScheduleSnapshot['latest_end_time'          ]) {

                            $newBreakScheduleSnapshot = new BreakScheduleSnapshot(
                                breakScheduleId       : $breakSchedule['id'                 ],
                                workScheduleSnapshotId: $workScheduleSnapshotId              ,
                                breakTypeSnapshotId   : $breakTypeSnapshotId                 ,
                                startTime             : $breakSchedule['start_time'         ],
                                endTime               : $breakSchedule['end_time'           ],
                                isFlexible            : $breakSchedule['is_flexible'        ],
                                earliestStartTime     : $breakSchedule['earliest_start_time'],
                                latestEndTime         : $breakSchedule['latest_end_time'    ]
                            );

                            $breakScheduleSnapshotId = $this->breakScheduleRepository
                                ->createBreakScheduleSnapshot($newBreakScheduleSnapshot);

                            if ($breakScheduleSnapshotId === ActionResult::FAILURE) {
                                return [
                                    'status'  => 'error',
                                    'message' => 'An unexpected error occurred. Please try again later.'
                                ];
                            }
                        }

                        $employeeBreakRecord = new EmployeeBreak(
                            id                     : null                     ,
                            breakScheduleSnapshotId: $breakScheduleSnapshotId ,
                            startTime              : null                     ,
                            endTime                : null                     ,
                            breakDurationInMinutes : 0                        ,
                            createdAt              : $formattedCurrentDateTime
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

        } elseif ($lastAttendanceRecord['check_in_time' ] !== null &&
                  $lastAttendanceRecord['check_out_time'] === null) {

            $isCheckIn = false;

            $workScheduleDate      = $lastAttendanceRecord['date'                             ];
            $workScheduleStartTime = $lastAttendanceRecord['work_schedule_snapshot_start_time'];
            $workScheduleEndTime   = $lastAttendanceRecord['work_schedule_snapshot_end_time'  ];

            $workScheduleStartDateTime = new DateTime($workScheduleDate . ' ' . $workScheduleStartTime);
            $workScheduleEndDateTime   = new DateTime($workScheduleDate . ' ' . $workScheduleEndTime  );

            if ($workScheduleEndDateTime <= $workScheduleStartDateTime) {
                $workScheduleEndDateTime->modify('+1 day');
            }

            $earlyCheckInWindow = $lastAttendanceRecord['work_schedule_snapshot_minutes_can_check_in_before_shift'];

            $adjustedWorkScheduleStartDateTime = (clone $workScheduleStartDateTime)
                ->modify('-' . $earlyCheckInWindow . ' minutes');

            $checkInDateTime  = new DateTime($lastAttendanceRecord['check_in_time']);
            $checkOutDateTime = clone $currentDateTime;

            $employeeBreakColumns = [
                'break_schedule_snapshot_id'                 ,
                'start_time'                                 ,
                'end_time'                                   ,

                'break_schedule_snapshot_start_time'         ,
                'break_schedule_snapshot_end_time'           ,
                'break_schedule_snapshot_is_flexible'        ,
                'break_schedule_snapshot_earliest_start_time',
                'break_schedule_snapshot_latest_end_time'    ,

                'break_type_snapshot_duration_in_minutes'    ,
                'break_type_snapshot_is_paid'
            ];

            $employeeBreakFilterCriteria = [
                [
                    'column'   => 'employee_break.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'   => 'break_schedule_snapshot.work_schedule_snapshot_id',
                    'operator' => '='                                                ,
                    'value'    => $lastAttendanceRecord['work_schedule_snapshot_id']
                ],
                [
                    'column'      => 'employee_break.created_at'                              ,
                    'operator'    => 'BETWEEN'                                                ,
                    'lower_bound' => $adjustedWorkScheduleStartDateTime->format('Y-m-d H:i:s'),
                    'upper_bound' => $workScheduleEndDateTime          ->format('Y-m-d H:i:s')
                ]
            ];

            $employeeBreakSortCriteria = [
                [
                    'column'    => 'employee_break.created_at',
                    'direction' => 'ASC'
                ],
                [
                    'column'    => 'employee_break.start_time',
                    'direction' => 'ASC'
                ]
            ];

            $employeeBreakFetchResult = $this->employeeBreakRepository->fetchAllEmployeeBreaks(
                columns             : $employeeBreakColumns       ,
                filterCriteria      : $employeeBreakFilterCriteria,
                sortCriteria        : $employeeBreakSortCriteria  ,
                includeTotalRowCount: false
            );

            if ($employeeBreakFetchResult === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred while checking in. Please try again later.'
                ];
            }

            $breakRecords =
                ! empty($employeeBreakFetchResult['result_set'])
                    ? $employeeBreakFetchResult['result_set']
                    : [];

            $paidBreakInMinutes   = 0;
            $unpaidBreakInMinutes = 0;

            if ( ! empty($breakRecords)) {
                $groupedBreakRecords = [];
                foreach ($breakRecords as $breakRecord) {
                    $groupedBreakRecords[$breakRecord['break_schedule_snapshot_id']][] = $breakRecord;
                }

                $mergedBreakRecords = [];
                foreach ($groupedBreakRecords as $breakRecords) {
                    $firstBreakRecord = $breakRecords[0];

                    if ($firstBreakRecord['start_time'] !== null) {
                        $earliestStartDateTime = new DateTime($firstBreakRecord['start_time']);
                        $latestEndDateTime     = null;

                        foreach ($breakRecords as $breakRecord) {
                            $currentStartDateTime = new DateTime($breakRecord['start_time']);

                            if ($currentStartDateTime < $earliestStartDateTime) {
                                $earliestStartDateTime = $currentStartDateTime;
                            }

                            if ($breakRecord['end_time'] !== null) {
                                $currentEndDateTime = new DateTime($breakRecord['end_time']);

                                if ($latestEndDateTime === null || $currentEndDateTime > $latestEndDateTime) {
                                    $latestEndDateTime = $currentEndDateTime;
                                }
                            }
                        }

                        $mergedBreakRecords[] = array_merge(
                            $firstBreakRecord,
                            [
                                'start_time' => $earliestStartDateTime->format('Y-m-d H:i:s'),
                                'end_time' =>
                                    $latestEndDateTime
                                        ? $latestEndDateTime->format('Y-m-d H:i:s')
                                        : null
                            ]
                        );

                    } else {
                        $mergedBreakRecords[] = $firstBreakRecord;
                    }
                }

                foreach ($mergedBreakRecords as $breakRecord) {
                    $breakRecordStartTime =
                        $breakRecord['start_time']
                            ? (new DateTime($breakRecord['start_time']))->format('H:i:s')
                            : null;

                    $breakScheduleStartTime = $breakRecord['break_schedule_snapshot_start_time'];

                    if ($breakRecord['break_schedule_snapshot_is_flexible']) {
                        $breakScheduleStartTime =
                            $breakRecordStartTime
                                ?? $breakRecord['break_schedule_snapshot_earliest_start_time'];
                    }

                    $breakScheduleEndTime = (new DateTime($breakScheduleStartTime))
                        ->modify('+' . $breakRecord['break_type_snapshot_duration_in_minutes'] . ' minutes')
                        ->format('H:i:s');

                    $breakScheduleStartDateTime = new DateTime($lastAttendanceRecord['date'] . ' ' . $breakScheduleStartTime);
                    $breakScheduleEndDateTime   = new DateTime($lastAttendanceRecord['date'] . ' ' . $breakScheduleEndTime  );

                    if ($breakScheduleStartDateTime < $workScheduleStartDateTime) {
                        $breakScheduleStartDateTime->modify('+1 day');
                    }

                    if ($breakScheduleEndDateTime < $workScheduleStartDateTime) {
                        $breakScheduleEndDateTime->modify('+1 day');
                    }

                    if ($breakScheduleEndDateTime < $breakScheduleStartDateTime) {
                        $breakScheduleEndDateTime->modify('+1 day');
                    }

                    if ($checkInDateTime > $breakScheduleStartDateTime) {
                        $breakScheduleStartDateTime = clone $checkInDateTime;
                    }

                    if ($checkOutDateTime >= $breakScheduleStartDateTime) {
                        $breakRecordEndDateTime =
                            $breakRecord['end_time']
                                ? new DateTime($breakRecord['end_time'])
                                : null;

                        if ($breakRecordEndDateTime !== null &&
                            $breakRecordEndDateTime > $breakScheduleEndDateTime) {

                            $actualBreakDurationInMinutes =
                                $breakScheduleStartDateTime->diff($breakRecordEndDateTime)->h * 60 +
                                $breakScheduleStartDateTime->diff($breakRecordEndDateTime)->i;

                            $breakScheduleDurationInMinutes =
                                $breakScheduleStartDateTime->diff($breakScheduleEndDateTime)->h * 60 +
                                $breakScheduleStartDateTime->diff($breakScheduleEndDateTime)->i;

                            $overtimeBreakDurationInMinutes =
                                $breakRecordEndDateTime->diff($breakScheduleEndDateTime)->h * 60 +
                                $breakRecordEndDateTime->diff($breakScheduleEndDateTime)->i;

                            if ($breakRecord['break_type_snapshot_is_paid']) {
                                $paidBreakInMinutes   += $breakScheduleDurationInMinutes;
                                $unpaidBreakInMinutes += $overtimeBreakDurationInMinutes;

                            } else {
                                $unpaidBreakInMinutes += $actualBreakDurationInMinutes;
                            }

                        } else {
                            $breakScheduleEndDateTime =
                                $checkOutDateTime >= $breakScheduleEndDateTime
                                    ? $breakScheduleEndDateTime
                                    : $checkOutDateTime;

                            $breakDuration = $breakScheduleStartDateTime
                                ->diff($breakScheduleEndDateTime);

                            $breakDurationInMinutes = ($breakDuration->h * 60) + $breakDuration->i;

                            if ($breakRecord['break_type_snapshot_is_paid']) {
                                $paidBreakInMinutes += $breakDurationInMinutes;

                            } else {
                                $unpaidBreakInMinutes += $breakDurationInMinutes;
                            }
                        }
                    }
                }
            }

            $attendanceColumns = [
                'id'                             ,
                'work_schedule_snapshot_id'      ,
                'date'                           ,
                'check_in_time'                  ,
                'check_out_time'                 ,
                'total_break_duration_in_minutes',
                'total_hours_worked'             ,
                'late_check_in'                  ,
                'early_check_out'                ,
                'overtime_hours'                 ,
                'is_overtime_approved'           ,
                'attendance_status'              ,
                'remarks'
            ];

            $attendanceFilterCriteria = [
                [
                    'column'   => 'attendance.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'   => 'attendance.work_schedule_snapshot_id'            ,
                    'operator' => '='                                               ,
                    'value'    => $lastAttendanceRecord['work_schedule_snapshot_id']
                ],
                [
                    'column'   => 'attendance.date'            ,
                    'operator' => '='                          ,
                    'value'    => $lastAttendanceRecord['date']
                ],
                [
                    'column'   => 'attendance.id'            ,
                    'operator' => '!='                       ,
                    'value'    => $lastAttendanceRecord['id']
                ]
            ];

            $attendanceSortCriteria = [
                [
                    'column'   => 'attendance.check_in_time',
                    'operator' => 'ASC'
                ]
            ];

            $attendanceFetchResult = $this->attendanceRepository->fetchAllAttendance(
                columns             : $attendanceColumns       ,
                filterCriteria      : $attendanceFilterCriteria,
                sortCriteria        : $attendanceSortCriteria  ,
                includeTotalRowCount: false
            );

            if ($attendanceFetchResult === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred while checking in. Please try again later.'
                ];
            }

            $currentAttendanceRecords =
                ! empty($attendanceFetchResult['result_set'])
                    ? $attendanceFetchResult['result_set']
                    : [];

            $sumOfAllWorkedHours            = 0;
            $sumOfAllBreakDurationInMinutes = 0;

            if ( ! empty($currentAttendanceRecords)) {
                foreach ($currentAttendanceRecords as $attendanceRecord) {
                    $sumOfAllWorkedHours            += $attendanceRecord['total_hours_worked'             ];
                    $sumOfAllBreakDurationInMinutes += $attendanceRecord['total_break_duration_in_minutes'];
                }
            }

            $currentAttendanceStatus = $lastAttendanceRecord['attendance_status'];

            $totalBreakDurationInMinutes = $unpaidBreakInMinutes + $paidBreakInMinutes + $sumOfAllBreakDurationInMinutes;

            $adjustedCheckInDateTime = clone $checkInDateTime;
            if ($checkInDateTime < $workScheduleStartDateTime && ! $lastAttendanceRecord['work_schedule_snapshot_is_flextime']) {
                $adjustedCheckInDateTime = clone $workScheduleStartDateTime;
            }

            $workDuration = $adjustedCheckInDateTime->diff($checkOutDateTime);
            $totalMinutesWorked = ($workDuration->days * 24 * 60) + ($workDuration->h * 60) + $workDuration->i;
            $totalMinutesWorked -= $unpaidBreakInMinutes;
            $totalHoursWorked = $totalMinutesWorked / 60;

            $totalHoursWorked += $sumOfAllWorkedHours;

            $earlyCheckOutInMinutes = 0;
            $overtimeHours          = 0;

            if ($totalHoursWorked < $lastAttendanceRecord['work_schedule_snapshot_total_work_hours']) {
                $earlyCheckOutInMinutes = ($lastAttendanceRecord['work_schedule_snapshot_total_work_hours'] - $totalHoursWorked) * 60;
                $currentAttendanceStatus = 'Undertime';

            } elseif ($totalHoursWorked > $lastAttendanceRecord['work_schedule_snapshot_total_work_hours']) {
                $overtimeHours = $totalHoursWorked - $lastAttendanceRecord['work_schedule_snapshot_total_work_hours'];
                $currentAttendanceStatus = 'Overtime';

            } elseif ($lastAttendanceRecord['attendance_status'] !== 'Late') {
                $currentAttendanceStatus = 'Present';
            }

            try {
                $this->pdo->beginTransaction();

                $currentAttendanceRecord = new Attendance(
                    id                          : $lastAttendanceRecord['id'                               ],
                    workScheduleSnapshotId      : $lastAttendanceRecord['work_schedule_snapshot_id'        ],
                    date                        : $lastAttendanceRecord['date'                             ],
                    checkInTime                 : $lastAttendanceRecord['check_in_time'                    ],
                    checkOutTime                : $checkOutDateTime->format('Y-m-d H:i:s')                  ,
                    totalBreakDurationInMinutes : $totalBreakDurationInMinutes                              ,
                    totalHoursWorked            : $totalHoursWorked                                         ,
                    lateCheckIn                 : $lastAttendanceRecord['late_check_in'                    ],
                    earlyCheckOut               : $earlyCheckOutInMinutes                                   ,
                    overtimeHours               : $overtimeHours                                            ,
                    isOvertimeApproved          : $lastAttendanceRecord['is_overtime_approved'             ],
                    attendanceStatus            : $currentAttendanceStatus                                  ,
                    remarks                     : $lastAttendanceRecord['remarks'                          ]
                );

                $attendanceCheckOutResult = $this->attendanceRepository->checkOut($currentAttendanceRecord);

                if ($attendanceCheckOutResult === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                if ( ! empty($currentAttendanceRecords)) {
                    $lastRecordIndex = count($currentAttendanceRecords) - 1;
                    $lastRecord = $currentAttendanceRecords[$lastRecordIndex];

                    $currentAttendanceRecord = new Attendance(
                        id                         : $lastRecord['id'                             ],
                        workScheduleSnapshotId     : $lastRecord['work_schedule_snapshot_id'      ],
                        date                       : $lastRecord['date'                           ],
                        checkInTime                : $lastRecord['check_in_time'                  ],
                        checkOutTime               : $lastRecord['check_out_time'                 ],
                        totalBreakDurationInMinutes: $lastRecord['total_break_duration_in_minutes'],
                        totalHoursWorked           : $lastRecord['total_hours_worked'             ],
                        lateCheckIn                : $lastRecord['late_check_in'                  ],
                        earlyCheckOut              : 0                                             ,
                        overtimeHours              : $lastRecord['overtime_hours'                 ],
                        isOvertimeApproved         : $lastRecord['is_overtime_approved'           ],
                        attendanceStatus           : $currentAttendanceStatus                      ,
                        remarks                    : $lastRecord['remarks'                        ]
                    );

                    $attendanceUpdateResult = $this->attendanceRepository->updateAttendance($currentAttendanceRecord);

                    if ($attendanceUpdateResult === ActionResult::FAILURE) {
                        return [
                            'status' => 'error',
                            'message' => 'Failed to update the last attendance record.'
                        ];
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
        }

        if ($isCheckIn) {
            return [
                'status'  => 'success',
                'message' => 'Checked-in recorded successfully.'
            ];

        } else {
            return [
                'status'  => 'success',
                'message' => 'Checked-out recorded successfully.'
            ];
        }
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
