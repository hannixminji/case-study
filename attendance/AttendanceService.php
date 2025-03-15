<?php

require_once __DIR__ . '/AttendanceRepository.php'                    ;

require_once __DIR__ . '/../employees/EmployeeRepository.php'         ;
require_once __DIR__ . '/../holidays/HolidayRepository.php'           ;
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
    private readonly HolidayRepository       $holidayRepository      ;
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
        HolidayRepository       $holidayRepository      ,
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
        $this->holidayRepository       = $holidayRepository      ;
        $this->leaveRequestRepository  = $leaveRequestRepository ;
        $this->workScheduleRepository  = $workScheduleRepository ;
        $this->settingRepository       = $settingRepository      ;
        $this->breakScheduleRepository = $breakScheduleRepository;
        $this->employeeBreakRepository = $employeeBreakRepository;
        $this->breakTypeRepository     = $breakTypeRepository    ;
    }

    public function fetchAllAttendance(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->attendanceRepository->fetchAllAttendance(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function approveOvertime(int|string $attendanceId): ActionResult
    {
        return $this->attendanceRepository->approveOvertime($attendanceId);
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

        $holidayColumns = [
            'is_paid'
        ];

        $holidayFilterCriteria = [
            [
                'column'   => 'holiday.deleted_at',
                'operator' => 'IS NULL'
            ],
            [
                'column'   => 'holiday.start_date' ,
                'operator' => '<='                 ,
                'value'    => $formattedCurrentDate
            ],
            [
                'column'   => 'holiday.end_date'   ,
                'operator' => '>='                 ,
                'value'    => $formattedCurrentDate
            ]
        ];

        $holidaySortCriteria = [
            [
                'column'    => 'holiday.start_date',
                'direction' => 'DESC'
            ]
        ];

        $isPaidHolidayToday = $this->holidayRepository->fetchAllHolidays(
            columns             : $holidayColumns       ,
            filterCriteria      : $holidayFilterCriteria,
            sortCriteria        : $holidaySortCriteria  ,
            limit               : 1                     ,
            includeTotalRowCount: false
        );

        if ($isPaidHolidayToday === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $isPaidHolidayToday =
            ! empty($isPaidHolidayToday['result_set'])
                ? $isPaidHolidayToday['result_set'][0]['is_paid']
                : [];

        $isOnLeaveToday = [];

        if ( ! $isPaidHolidayToday) {
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

            if ( ! empty($isOnLeaveToday) &&
                       ! $isOnLeaveToday['is_half_day']) {

                return [
                    'status'  => 'warning',
                    'message' => 'You are currently on leave. You cannot check in or check out.'
                ];
            }
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
            ],
            [
                'column'   => 'attendance.check_in_time',
                'operator' => 'IS NOT NULL'
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
            $lastAttendanceRecord['check_out_time'] !== null)) {

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
                        'message' => 'You don\'t have an assigned work schedule.'
                    ];
                }

                $formattedPreviousTwoDaysDate = (clone $previousDate)
                    ->modify('-1 day')
                    ->format('Y-m-d' );

                $query = '
                    SELECT
                        attendance_record.date                  AS date            ,
                        work_schedule_snapshot.work_schedule_id AS work_schedule_id,
                        work_schedule_snapshot.start_time       AS start_time      ,
                        work_schedule_snapshot.end_time         AS end_time
                    FROM
                        attendance AS attendance_record
                    JOIN
                        work_schedule_snapshots AS work_schedule_snapshot
                    ON
                        attendance_record.work_schedule_snapshot_id = work_schedule_snapshot.id
                    WHERE
                        attendance_record.deleted_at IS NULL
                    AND
                        attendance_record.date BETWEEN :previous_two_days_date AND :current_date
                    AND
                        work_schedule_snapshot.employee_id = :employee_id
                    GROUP BY
                        attendance_record.date                 ,
                        work_schedule_snapshot.work_schedule_id
                    ORDER BY
                        attendance_record.date            ASC,
                        work_schedule_snapshot.start_time ASC
                ';

                try {
                    $statement = $this->pdo->prepare($query);

                    $statement->bindValue(':previous_two_days_date', $formattedPreviousTwoDaysDate, Helper::getPdoParameterType($formattedPreviousTwoDaysDate));
                    $statement->bindValue(':current_date'          , $formattedCurrentDate        , Helper::getPdoParameterType($formattedCurrentDate        ));
                    $statement->bindValue(':employee_id'           , $employeeId                  , Helper::getPdoParameterType($employeeId                  ));

                    $statement->execute();

                    $employeeRecordedWorkSchedules = $statement->fetchAll(PDO::FETCH_ASSOC);

                } catch (PDOException $exception) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $recordedWorkSchedules = [];

                $recordedWorkSchedules[$formattedPreviousTwoDaysDate] = [];
                $recordedWorkSchedules[$formattedPreviousDate       ] = [];
                $recordedWorkSchedules[$formattedCurrentDate        ] = [];

                foreach ($employeeRecordedWorkSchedules as $recordedWorkSchedule) {
                    $date           = $recordedWorkSchedule['date'            ];
                    $workScheduleId = $recordedWorkSchedule['work_schedule_id'];

                    $recordedWorkSchedule['is_recorded'] = true;

                    $recordedWorkSchedules[$date][$workScheduleId] = $recordedWorkSchedule;
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
                            'message' => 'You do not have a work schedule today.'
                        ];
                    }

                    foreach ($workScheduleDates as $workScheduleDate) {
                        $currentWorkSchedules[$workScheduleDate][] = $workSchedule;
                    }
                }

                foreach ($currentWorkSchedules as $date => $workSchedules) {
                    foreach ($workSchedules as $workSchedule) {
                        $workScheduleId = $workSchedule['id'];

                        if ( ! isset($recordedWorkSchedules[$date][$workScheduleId])) {
                            $recordedWorkSchedules[$date][$workScheduleId] = $workSchedule;
                        }
                    }
                }

                foreach ($recordedWorkSchedules as &$workSchedules) {
                    usort($workSchedules, fn($workScheduleA, $workScheduleB) =>
                        $workScheduleA['start_time'] <=> $workScheduleB['start_time']
                    );

                    $workSchedules = array_values($workSchedules);
                }

                $currentWorkSchedules = $recordedWorkSchedules;

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
                            'message' => 'You do not have a work schedule today.'
                        ];
                    }
                }

                $currentWorkSchedule['employee_id'] = $employeeId;

                $workScheduleStartDateTime = new DateTime($currentWorkSchedule['start_time']);
                $workScheduleEndDateTime   = new DateTime($currentWorkSchedule['end_time'  ]);

                $workScheduleDate = $workScheduleStartDateTime->format('Y-m-d');

                $workScheduleId = $currentWorkSchedule['id'];

                $earlyCheckInWindow = 0;

                $adjustedWorkScheduleStartDateTime = clone $workScheduleStartDateTime;

                if ( ! $currentWorkSchedule['is_flextime']) {
                    $earlyCheckInWindow = $this->settingRepository->fetchSettingValue(
                        settingKey: 'minutes_can_check_in_before_shift',
                        groupName : 'work_schedule'
                    );

                    if ($earlyCheckInWindow === ActionResult::FAILURE) {
                        return [
                            'status' => 'error',
                            'message' => 'An unexpected error occurred. Please try again later.'
                        ];
                    }

                    $earlyCheckInWindow = (int) $earlyCheckInWindow;

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
                    'start_time'           => $workScheduleStartDateTime->format('Y-m-d H:i:s')                   ,
                    'end_time'             => $workScheduleEndDateTime  ->format('Y-m-d H:i:s')                   ,
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

            $holidayColumns = [
                'is_paid'
            ];

            $holidayFilterCriteria = [
                [
                    'column'   => 'holiday.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'   => 'holiday.start_date'  ,
                    'operator' => '<='                  ,
                    'value'    => $formattedPreviousDate
                ],
                [
                    'column'   => 'holiday.end_date'    ,
                    'operator' => '>='                  ,
                    'value'    => $formattedPreviousDate
                ]
            ];

            $holidaySortCriteria = [
                [
                    'column'    => 'holiday.start_date',
                    'direction' => 'DESC'
                ]
            ];

            $isPaidHolidayYesterday = $this->holidayRepository->fetchAllHolidays(
                columns             : $holidayColumns       ,
                filterCriteria      : $holidayFilterCriteria,
                sortCriteria        : $holidaySortCriteria  ,
                limit               : 1                     ,
                includeTotalRowCount: false
            );

            if ($isPaidHolidayYesterday === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $isPaidHolidayYesterday =
                ! empty($isPaidHolidayYesterday['result_set'])
                    ? $isPaidHolidayYesterday['result_set'][0]['is_paid']
                    : [];

            if ( ! $isPaidHolidayYesterday &&
                $workScheduleEndDate > $workScheduleStartDate &&
                $workScheduleEndDateTime->format('H:i:s') !== '00:00:00' &&
                $formattedCurrentDate === $workScheduleEndDate->format('Y-m-d')) {

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
                        'value'    => $formattedPreviousDate
                    ],
                    [
                        'column'   => 'leave_request.end_date',
                        'operator' => '>='                    ,
                        'value'    => $formattedPreviousDate
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

            if ($isUsingLastAttendanceWorkSchedule && ( ! $isPaidHolidayToday || ! $isPaidHolidayYesterday)) {
                $employeeBreakColumns = [
                    'break_schedule_snapshot_start_time'     ,
                    'break_schedule_snapshot_end_time'       ,

                    'break_type_snapshot_duration_in_minutes',
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

                if ($breakSchedules === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                if ( ! empty($breakSchedules['result_set'])) {
                    $mapKeys = [
                        "break_schedule_snapshot_start_time"      => "start_time"                    ,
                        "break_schedule_snapshot_end_time"        => "end_time"                      ,

                        "break_type_snapshot_duration_in_minutes" => "break_type_duration_in_minutes",
                        "break_type_snapshot_is_paid"             => "break_type_is_paid"
                    ];

                    $breakSchedules['result_set'] = array_map(function ($item) use ($mapKeys) {
                        $newItem = [];
                        foreach ($mapKeys as $oldKey => $newKey) {
                            if (array_key_exists($oldKey, $item)) {
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

                    'break_type_name'                  ,
                    'break_type_duration_in_minutes'   ,
                    'break_type_is_paid'               ,
                    'is_require_break_in_and_break_out',

                    'is_recorded' => "
                        CASE
                            WHEN EXISTS (
                                SELECT
                                    1
                                FROM
                                    employee_breaks AS employee_break
                                JOIN
                                    break_schedule_snapshots AS break_schedule_snapshot
                                ON
                                    employee_break.break_schedule_snapshot_id = break_schedule_snapshot.id
                                WHERE
                                    break_schedule_snapshot.break_schedule_id = break_schedule.id
                                AND
                                    employee_break.created_at BETWEEN '{$adjustedWorkScheduleStartDateTime->format('Y-m-d H:i:s')}' AND '{$workScheduleEndDateTime->format('Y-m-d H:i:s')}'
                            )
                            THEN 1
                            ELSE 0
                        END AS is_recorded
                    "
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

                if ($breakSchedules === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }
            }

            $breakSchedules =
                ! empty($breakSchedules['result_set'])
                    ? $breakSchedules['result_set']
                    : [];

            if ( ! empty($breakSchedules)) {
                usort($breakSchedules, function ($breakScheduleA, $breakScheduleB) use ($workScheduleDate, $workScheduleStartDateTime) {
                    $breakScheduleStartTimeA = $breakScheduleA['start_time'];
                    $breakScheduleStartTimeB = $breakScheduleB['start_time'];

                    if ($breakScheduleStartTimeA === null && $breakScheduleStartTimeB === null) {
                        return 0;
                    }

                    if ($breakScheduleStartTimeA === null) {
                        return 1;
                    }

                    if ($breakScheduleStartTimeB === null) {
                        return -1;
                    }

                    $breakScheduleStartDateTimeA = new DateTime($workScheduleDate . ' ' . $breakScheduleStartTimeA);
                    $breakScheduleStartDateTimeB = new DateTime($workScheduleDate . ' ' . $breakScheduleStartTimeB);

                    if ($breakScheduleStartDateTimeA < $workScheduleStartDateTime) {
                        $breakScheduleStartDateTimeA->modify('+1 day');
                    }

                    if ($breakScheduleStartDateTimeB < $workScheduleStartDateTime) {
                        $breakScheduleStartDateTimeB->modify('+1 day');
                    }

                    return $breakScheduleStartDateTimeA <=> $breakScheduleStartDateTimeB;
                });
            }

            if ((   isset($didLeaveOccurYesterday) &&
                  ! empty($didLeaveOccurYesterday) &&
                          $didLeaveOccurYesterday['is_half_day']) ||

                ( ! empty($isOnLeaveToday)                &&
                          $isOnLeaveToday['is_half_day'])) {

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
                                $breakStartTime = $breakSchedule['start_time'];
                                $breakEndTime   = $breakSchedule['end_time'  ];

                                $breakStartDateTime = new DateTime($workScheduleDate . ' ' . $breakStartTime);
                                $breakEndDateTime   = new DateTime($workScheduleDate . ' ' . $breakEndTime  );

                                if ($breakStartDateTime < $workScheduleStartDateTime) {
                                    $breakStartDateTime->modify('+1 day');
                                }

                                if ($breakEndDateTime < $workScheduleStartDateTime) {
                                    $breakEndDateTime->modify('+1 day');
                                }

                                if ($breakEndDateTime < $breakStartDateTime) {
                                    $breakEndDateTime->modify('+1 day');
                                }

                                $breakDurationInMinutes = $breakSchedule['break_type_duration_in_minutes'];

                                if ($halfDayPart === 'first_half') {
                                    if ($halfDayEndDateTime > $breakStartDateTime &&
                                        $halfDayEndDateTime < $breakEndDateTime  ) {

                                        $overlapTimeDuration  = $breakStartDateTime->diff($halfDayEndDateTime)        ;
                                        $overlapTimeInMinutes = $overlapTimeDuration->h * 60 + $overlapTimeDuration->i;

                                        $halfDayEndDateTime = (clone $breakEndDateTime)
                                            ->modify('+' . $overlapTimeInMinutes . ' minutes');

                                    } elseif ($breakStartDateTime >= $halfDayStartDateTime &&
                                              $breakEndDateTime   <= $halfDayEndDateTime  ) {

                                        $halfDayEndDateTime->modify('+' . $breakDurationInMinutes . ' minutes');
                                    }

                                } elseif ($halfDayPart === 'second_half') {
                                    if ($halfDayStartDateTime > $breakStartDateTime &&
                                        $halfDayStartDateTime < $breakEndDateTime  ) {

                                        $overlapTimeDuration  = $breakEndDateTime->diff($halfDayStartDateTime)        ;
                                        $overlapTimeInMinutes = $overlapTimeDuration->h * 60 + $overlapTimeDuration->i;

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
                $gracePeriod = $this->settingRepository->fetchSettingValue(
                    settingKey: 'grace_period' ,
                    groupName : 'work_schedule'
                );

                if ($gracePeriod === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $gracePeriod = (int) $gracePeriod;

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
                        if ((  isset($breakSchedule['is_recorded']) &&
                                   ! $breakSchedule['is_recorded']) ||

                             ! isset($breakSchedule['is_recorded'])) {

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
                                $breakSchedule['end_time'           ] !== $latestBreakScheduleSnapshot['end_time'                 ]) {

                                $newBreakScheduleSnapshot = new BreakScheduleSnapshot(
                                    breakScheduleId       : $breakSchedule['id'        ],
                                    workScheduleSnapshotId: $workScheduleSnapshotId     ,
                                    breakTypeSnapshotId   : $breakTypeSnapshotId        ,
                                    startTime             : $breakSchedule['start_time'],
                                    endTime               : $breakSchedule['end_time'  ]
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
                'break_schedule_snapshot_id'             ,
                'start_time'                             ,
                'end_time'                               ,

                'break_schedule_snapshot_start_time'     ,
                'break_schedule_snapshot_end_time'       ,

                'break_type_snapshot_duration_in_minutes',
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

            $employeeBreakFetchResult = $this->employeeBreakRepository->fetchAllEmployeeBreaks(
                columns             : $employeeBreakColumns       ,
                filterCriteria      : $employeeBreakFilterCriteria,
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

                $previousBreakRecordEndDateTime = null;

                foreach ($mergedBreakRecords as $breakRecord) {
                    $isPaid = $breakRecord['break_type_snapshot_is_paid'];

                    $breakScheduleStartTime = $breakRecord['break_schedule_snapshot_start_time'];
                    $breakScheduleEndTime   = $breakRecord['break_schedule_snapshot_end_time'  ];

                    $breakScheduleStartDateTime = new DateTime($workScheduleDate . ' ' . $breakScheduleStartTime);
                    $breakScheduleEndDateTime   = new DateTime($workScheduleDate . ' ' . $breakScheduleEndTime  );

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
                    } elseif ( ! $isPaid && $previousBreakRecordEndDateTime !== null && $previousBreakRecordEndDateTime > $breakScheduleStartDateTime) {
                        $breakScheduleStartDateTime = clone $previousBreakRecordEndDateTime;
                    }

                    if ($checkOutDateTime > $breakScheduleStartDateTime) {
                        $breakRecordEndDateTime =
                            $breakRecord['end_time'] !== null
                                ? new DateTime($breakRecord['end_time'])
                                : null;

                        if ($checkOutDateTime < $breakRecordEndDateTime) {
                            $breakRecordEndDateTime = clone $checkOutDateTime;
                        }

                        if ($breakRecordEndDateTime !== null &&
                            $breakRecordEndDateTime >   $breakScheduleEndDateTime) {

                            if ($breakScheduleStartDateTime > $breakScheduleEndDateTime) {
                                $breakScheduleEndDateTime = clone $breakScheduleStartDateTime;
                            }

                            $breakScheduleDuration          = $breakScheduleStartDateTime->diff($breakScheduleEndDateTime);
                            $breakScheduleDurationInMinutes = $breakScheduleDuration->h * 60 + $breakScheduleDuration->i  ;

                            $breakDuration          = $breakScheduleStartDateTime->diff($breakRecordEndDateTime);
                            $breakDurationInMinutes = $breakDuration->h * 60 + $breakDuration->i                ;

                            $overtimeBreakDuration          = $breakScheduleEndDateTime->diff($breakRecordEndDateTime)  ;
                            $overtimeBreakDurationInMinutes = $overtimeBreakDuration->h * 60 + $overtimeBreakDuration->i;

                            if ($isPaid) {
                                $paidBreakInMinutes   += $breakScheduleDurationInMinutes        ;
                                $unpaidBreakInMinutes += max(0, $overtimeBreakDurationInMinutes);

                            } else {
                                $unpaidBreakInMinutes += max(0, $breakDurationInMinutes);
                            }

                            $breakScheduleEndDateTime       = clone $breakRecordEndDateTime  ;
                            $previousBreakRecordEndDateTime = clone $breakScheduleEndDateTime;

                        } else {
                            $breakScheduleEndDateTime =
                                $checkOutDateTime >= $breakScheduleEndDateTime
                                    ? $breakScheduleEndDateTime
                                    : $checkOutDateTime;

                            if ($breakScheduleStartDateTime > $breakScheduleEndDateTime) {
                                $breakScheduleStartDateTime = clone $breakScheduleEndDateTime;
                            }

                            $breakDuration          = $breakScheduleStartDateTime->diff($breakScheduleEndDateTime);
                            $breakDurationInMinutes = $breakDuration->h * 60 + $breakDuration->i                  ;

                            if ($isPaid) {
                                $paidBreakInMinutes += max(0, $breakDurationInMinutes);
                            } else {
                                $unpaidBreakInMinutes += max(0, $breakDurationInMinutes);
                            }
                        }
                    }

                    if ($previousBreakRecordEndDateTime === null) {
                        $previousBreakRecordEndDateTime = $breakScheduleEndDateTime;
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
                    'column'   => 'attendance.date'            ,
                    'operator' => '='                          ,
                    'value'    => $lastAttendanceRecord['date']
                ],
                [
                    'column'   => 'attendance.work_schedule_snapshot_id'            ,
                    'operator' => '='                                               ,
                    'value'    => $lastAttendanceRecord['work_schedule_snapshot_id']
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

                $sumOfAllWorkedHours = $currentAttendanceRecords[array_key_last($currentAttendanceRecords)]['total_hours_worked'] ?? 0;
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

                $this->pdo->beginTransaction();

                if ( ! empty($currentAttendanceRecords)) {
                    foreach ($currentAttendanceRecords as $attendanceRecord) {
                        $currentAttendanceRecord = new Attendance(
                            id                         : $attendanceRecord['id'                             ],
                            workScheduleSnapshotId     : $attendanceRecord['work_schedule_snapshot_id'      ],
                            date                       : $attendanceRecord['date'                           ],
                            checkInTime                : $attendanceRecord['check_in_time'                  ],
                            checkOutTime               : $attendanceRecord['check_out_time'                 ],
                            totalBreakDurationInMinutes: $attendanceRecord['total_break_duration_in_minutes'],
                            totalHoursWorked           : $attendanceRecord['total_hours_worked'             ],
                            lateCheckIn                : $attendanceRecord['late_check_in'                  ],
                            earlyCheckOut              : 0                                                   ,
                            overtimeHours              : $attendanceRecord['overtime_hours'                 ],
                            isOvertimeApproved         : $attendanceRecord['is_overtime_approved'           ],
                            attendanceStatus           : $currentAttendanceStatus                            ,
                            remarks                    : $attendanceRecord['remarks'                        ]
                        );

                        $attendanceUpdateResult = $this->attendanceRepository->updateAttendance($currentAttendanceRecord);

                        if ($attendanceUpdateResult === ActionResult::FAILURE) {
                            $this->pdo->rollback();

                            return [
                                'status' => 'error',
                                'message' => 'Failed to update one or more attendance records.'
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
        }

        if (isset($isCheckIn)) {
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

        } else {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }
    }

    public function updateAttendance(
        int|string $attendanceId    ,
        string     $checkInDateTime ,
        string     $checkOutDateTime
    ): array {

        $checkInDateTime  = new DateTime($checkInDateTime );
        $checkOutDateTime = new DateTime($checkOutDateTime);

        $formattedCheckInDateTime  = $checkInDateTime ->format('Y-m-d H:i:s');
        $formattedCheckOutDateTime = $checkOutDateTime->format('Y-m-d H:i:s');

        $attendanceRecordColumns = [
            'work_schedule_snapshot_id'                               ,
            'date'                                                    ,
            'is_overtime_approved'                                    ,
            'remarks'                                                 ,

            'work_schedule_snapshot_work_schedule_id'                 ,
            'work_schedule_snapshot_employee_id'                      ,
            'work_schedule_snapshot_start_time'                       ,
            'work_schedule_snapshot_end_time'                         ,
            'work_schedule_snapshot_is_flextime'                      ,
            'work_schedule_snapshot_total_work_hours'                 ,
            'work_schedule_snapshot_grace_period'                     ,
            'work_schedule_snapshot_minutes_can_check_in_before_shift'
        ];

        if (preg_match('/^[1-9]\d*$/', $attendanceId)) {
            $attendanceRecordFilterCriteria = [
                [
                    'column'   => 'attendance.id',
                    'operator' => '='            ,
                    'value'    => $attendanceId
                ]
            ];

        } else {
            $attendanceRecordFilterCriteria = [
                [
                    'column'   => 'SHA2(attendance.id, 256)',
                    'operator' => '='                       ,
                    'value'    => $attendanceId
                ]
            ];
        }

        $attendanceRecord = $this->attendanceRepository->fetchAllAttendance(
            columns             : $attendanceRecordColumns       ,
            filterCriteria      : $attendanceRecordFilterCriteria,
            limit               : 1                              ,
            includeTotalRowCount: false
        );

        if ($attendanceRecord === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $attendanceRecord =
            ! empty($attendanceRecord['result_set'])
                ? $attendanceRecord['result_set'][0]
                : [];

        if (empty($attendanceRecord)) {
            return [
                'status'  => 'error',
                'message' => 'Attendance record not found. The record may have been deleted. ' .
                             'Please verify the attendance ID and try again.'
            ];
        }

        $workScheduleDate = $attendanceRecord['date'];

        $workScheduleStartTime = $attendanceRecord['work_schedule_snapshot_start_time'];
        $workScheduleEndTime   = $attendanceRecord['work_schedule_snapshot_end_time'  ];

        $workScheduleStartDateTime = new DateTime($workScheduleDate . ' ' . $workScheduleStartTime);
        $workScheduleEndDateTime   = new DateTime($workScheduleDate . ' ' . $workScheduleEndTime  );

        if ($workScheduleEndDateTime <= $workScheduleStartDateTime) {
            $workScheduleEndDateTime->modify('+1 day');
        }

        $earlyCheckInWindow = $attendanceRecord['work_schedule_snapshot_minutes_can_check_in_before_shift'];
        $adjustedWorkScheduleStartDateTime = (clone $workScheduleStartDateTime)
            ->modify('-' . $earlyCheckInWindow . ' minutes');

        if (empty($checkInDateTime) || empty($checkOutDateTime)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'Check-in and check-out times must not be empty.'
            ];
        }

        if ($checkOutDateTime < $checkInDateTime) {
            return [
                'status'  => 'invalid_input',
                'message' => 'Check-out time cannot be earlier than check-in time.'
            ];
        }

        if ($checkInDateTime < $adjustedWorkScheduleStartDateTime) {
            return [
                'status'  => 'invalid_input',
                'message' => 'Check-in time is earlier than the allowed start time of the work schedule.'
            ];
        }

        if ($checkInDateTime >= $workScheduleEndDateTime) {
            return [
                'status'  => 'invalid_input',
                'message' => 'Check-in time is later than or equal to the end of the work schedule.'
            ];
        }

        if ($checkOutDateTime > (clone $workScheduleEndDateTime)->modify('+1 day')) {
            return [
                'status'  => 'invalid_input',
                'message' => 'The check-out date cannot be more than one day after the work schedule end date.'
            ];
        }

        $holidayColumns = [
            'is_paid'
        ];

        $holidayFilterCriteria = [
            [
                'column'   => 'holiday.deleted_at',
                'operator' => 'IS NULL'
            ],
            [
                'column'   => 'holiday.start_date',
                'operator' => '<='                ,
                'value'    => $workScheduleDate
            ],
            [
                'column'   => 'holiday.end_date',
                'operator' => '>='              ,
                'value'    => $workScheduleDate
            ]
        ];

        $isPaidHoliday = $this->holidayRepository->fetchAllHolidays(
            columns             : $holidayColumns       ,
            filterCriteria      : $holidayFilterCriteria,
            limit               : 1                     ,
            includeTotalRowCount: false
        );

        if ($isPaidHoliday === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $isPaidHoliday =
            ! empty($isPaidHoliday['result_set'])
                ? $isPaidHoliday['result_set'][0]['is_paid']
                : [];

        $employeeId = $attendanceRecord['work_schedule_snapshot_employee_id'];

        $isOnLeave = [];

        if ( ! $isPaidHoliday) {
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
                    'value'    => $workScheduleDate
                ],
                [
                    'column'   => 'leave_request.end_date',
                    'operator' => '>='                    ,
                    'value'    => $workScheduleDate
                ],
                [
                    'column'     => 'leave_request.status'      ,
                    'operator'   => 'IN'                        ,
                    'value_list' => ['In Progress', 'Completed']
                ]
            ];

            $isOnLeave = $this->leaveRequestRepository->fetchAllLeaveRequests(
                columns             : $leaveRequestColumns       ,
                filterCriteria      : $leaveRequestFilterCriteria,
                limit               : 1                          ,
                includeTotalRowCount: false
            );

            if ($isOnLeave === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $isOnLeave =
                ! empty($isOnLeave['result_set'])
                    ? $isOnLeave['result_set'][0]
                    : [];

            if ( ! empty($isOnLeave) &&
                       ! $isOnLeave['is_half_day']) {

                return [
                    'status'  => 'warning',
                    'message' => 'The attendance record is marked as a full-day leave and cannot be modified.'
                ];
            }
        }

        $attendanceRecordColumns = [
            'id'
        ];

        $attendanceRecordFilterCriteria = [
            [
                'column'   => 'attendance.deleted_at',
                'operator' => 'IS NULL'
            ],
            [
                'column'   => 'attendance.date',
                'operator' => '!='             ,
                'value'    => $workScheduleDate
            ],
            [
                [
                    [
                        'column'   => 'attendance.check_in_time',
                        'operator' => '<'                       ,
                        'value'    => $formattedCheckOutDateTime
                    ],
                    [
                        'column'   => 'attendance.check_out_time',
                        'operator' => '>'                        ,
                        'value'    => $formattedCheckInDateTime  ,
                        'boolean'  => 'OR'
                    ]
                ],
                [
                    [
                        'column'   => 'attendance.check_out_time',
                        'operator' => 'IS NULL'
                    ],
                    [
                        'column'   => 'attendance.check_in_time',
                        'operator' => '>'                       ,
                        'value'    => $formattedCheckInDateTime
                    ],
                    [
                        'column'   => 'attendance.check_in_time',
                        'operator' => '>'                       ,
                        'value'    => $formattedCheckOutDateTime
                    ]
                ]
            ]
        ];

        $isOverlapped = $this->attendanceRepository->fetchAllAttendance(
            columns             : $attendanceRecordColumns       ,
            filterCriteria      : $attendanceRecordFilterCriteria,
            limit               : 1                              ,
            includeTotalRowCount: false
        );
return [];
        if ($isOverlapped === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $isOverlapped = ! empty($isOverlapped['result_set']);

        if ($isOverlapped) {
            return [
                'status'  => 'invalid_input',
                'message' => 'Time overlap detected. Please verify your check-in and check-out times ' .
                             'and ensure they do not conflict with existing records.'
            ];
        }

        $attendanceRecordColumns = [
            'id'            ,
            'check_in_time' ,
            'check_out_time'
        ];

        $attendanceRecordFilterCriteria = [
            [
                'column'   => 'attendance.deleted_at',
                'operator' => 'IS NULL'
            ],
            [
                'column'   => 'attendance.date',
                'operator' => '='              ,
                'value'    => $workScheduleDate
            ],
            [
                'column'   => 'attendance.work_schedule_snapshot_id'        ,
                'operator' => '='                                           ,
                'value'    => $attendanceRecord['work_schedule_snapshot_id']
            ],
            [
                'column'   => 'attendance.id',
                'operator' => '!='           ,
                'value'    => $attendanceId
            ],
        ];

        $attendanceRecordSortCriteria = [
            [
                'column'    => 'attendance.check_in_time',
                'direction' => 'ASC'
            ]
        ];

        $attendanceRecords = $this->attendanceRepository->fetchAllAttendance(
            columns             : $attendanceRecordColumns       ,
            filterCriteria      : $attendanceRecordFilterCriteria,
            sortCriteria        : $attendanceRecordSortCriteria  ,
            includeTotalRowCount: false
        );

        if ($attendanceRecords === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $attendanceRecords =
            ! empty($attendanceRecords['result_set'])
                ? $attendanceRecords['result_set']
                : [];

        $employeeBreakRecordColumns = [
            'id'                                     ,
            'break_schedule_snapshot_id'             ,
            'start_time'                             ,
            'end_time'                               ,

            'break_schedule_snapshot_start_time'     ,
            'break_schedule_snapshot_end_time'       ,

            'break_type_snapshot_duration_in_minutes',
            'break_type_snapshot_is_paid'
        ];

        $employeeBreakRecordFilterCriteria = [
            [
                'column'   => 'employee_break.deleted_at',
                'operator' => 'IS NULL'
            ],
            [
                'column'   => 'break_schedule_snapshot.work_schedule_snapshot_id',
                'operator' => '='                                                ,
                'value'    => $attendanceRecord['work_schedule_snapshot_id']
            ],
            [
                'column'      => 'employee_break.created_at'                              ,
                'operator'    => 'BETWEEN'                                                ,
                'lower_bound' => $adjustedWorkScheduleStartDateTime->format('Y-m-d H:i:s'),
                'upper_bound' => $workScheduleEndDateTime          ->format('Y-m-d H:i:s')
            ]
        ];

        $employeeBreakRecords = $this->employeeBreakRepository->fetchAllEmployeeBreaks(
            columns             : $employeeBreakRecordColumns       ,
            filterCriteria      : $employeeBreakRecordFilterCriteria,
            includeTotalRowCount: false
        );

        if ($employeeBreakRecords === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while checking in. Please try again later.'
            ];
        }

        $employeeBreakRecords =
            ! empty($employeeBreakRecords['result_set'])
                ? $employeeBreakRecords['result_set']
                : [];

        usort($employeeBreakRecords, function ($employeeBreakRecordA, $employeeBreakRecordB) use ($workScheduleDate, $workScheduleStartDateTime) {
            $breakStartTimeA = $employeeBreakRecordA['start_time'] ?? $employeeBreakRecordA['break_snapshot_start_time'];
            $breakStartTimeB = $employeeBreakRecordB['start_time'] ?? $employeeBreakRecordB['break_snapshot_start_time'];

            if ($breakStartTimeA === null && $breakStartTimeB === null) {
                return 0;
            }

            if ($breakStartTimeA === null) {
                return 1;
            }

            if ($breakStartTimeB === null) {
                return -1;
            }

            $breakStartDateTimeA = new DateTime($workScheduleDate . ' ' . $breakStartTimeA);
            $breakStartDateTimeB = new DateTime($workScheduleDate . ' ' . $breakStartTimeB);

            if ($breakStartDateTimeA < $workScheduleStartDateTime) {
                $breakStartDateTimeA->modify('+1 day');
            }

            if ($breakStartDateTimeB < $workScheduleStartDateTime) {
                $breakStartDateTimeB->modify('+1 day');
            }

            return $breakStartDateTimeA <=> $breakStartDateTimeB;
        });

        $breakSchedules = [];

        foreach ($employeeBreakRecords as $breakRecord) {
            $breakScheduleSnapshotId = $breakRecord['break_schedule_snapshot_id'];

            if ( ! isset($breakSchedules[$breakScheduleSnapshotId])) {
                $breakSchedules[$breakScheduleSnapshotId] = [];
            }

            $breakSchedules[$breakScheduleSnapshotId] = [
                'start_time'                     => $breakRecord['break_schedule_snapshot_start_time'     ],
                'end_time'                       => $breakRecord['break_schedule_snapshot_end_time'       ],
                'break_type_duration_in_minutes' => $breakRecord['break_type_snapshot_duration_in_minutes'],
                'break_type_is_paid'             => $breakRecord['break_type_snapshot_is_paid'            ]
            ];
        }

        if ( ! empty($isOnLeave)               &&
                     $isOnLeave['is_half_day']) {

            $halfDayPart = $isOnLeave['half_day_part'];

            $halfDayDurationInMinutes =
                ($attendanceRecord['work_schedule_snapshot_total_work_hours'] / 2) * 60;

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
                            $breakStartTime = $breakSchedule['start_time'];
                            $breakEndTime   = $breakSchedule['end_time'  ];

                            $breakStartDateTime = new DateTime($workScheduleDate . ' ' . $breakStartTime);
                            $breakEndDateTime   = new DateTime($workScheduleDate . ' ' . $breakEndTime  );

                            if ($breakStartDateTime < $workScheduleStartDateTime) {
                                $breakStartDateTime->modify('+1 day');
                            }

                            if ($breakEndDateTime < $workScheduleStartDateTime) {
                                $breakEndDateTime->modify('+1 day');
                            }

                            if ($breakEndDateTime < $breakStartDateTime) {
                                $breakEndDateTime->modify('+1 day');
                            }

                            $breakDurationInMinutes = $breakSchedule['break_type_duration_in_minutes'];

                            if ($halfDayPart === 'first_half') {
                                if ($halfDayEndDateTime > $breakStartDateTime &&
                                    $halfDayEndDateTime < $breakEndDateTime  ) {

                                    $overlapTimeDuration  = $breakStartDateTime->diff($halfDayEndDateTime)        ;
                                    $overlapTimeInMinutes = $overlapTimeDuration->h * 60 + $overlapTimeDuration->i;

                                    $halfDayEndDateTime = (clone $breakEndDateTime)
                                        ->modify('+' . $overlapTimeInMinutes . ' minutes');

                                } elseif ($breakStartDateTime >= $halfDayStartDateTime &&
                                        $breakEndDateTime   <= $halfDayEndDateTime  ) {

                                    $halfDayEndDateTime->modify('+' . $breakDurationInMinutes . ' minutes');
                                }

                            } elseif ($halfDayPart === 'second_half') {
                                if ($halfDayStartDateTime > $breakStartDateTime &&
                                    $halfDayStartDateTime < $breakEndDateTime  ) {

                                    $overlapTimeDuration  = $breakEndDateTime->diff($halfDayStartDateTime)        ;
                                    $overlapTimeInMinutes = $overlapTimeDuration->h * 60 + $overlapTimeDuration->i;

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

                if ($checkInDateTime >= $halfDayStartDateTime &&
                    $checkInDateTime <= $halfDayEndDateTime  ) {

                    $formattedHalfDayStartTime = $halfDayStartDateTime->format('h:i A');
                    $formattedHalfDayEndTime   = $halfDayEndDateTime  ->format('h:i A');

                    return [
                        'status'  => 'error',
                        'message' => 'Unable to update check-in time: The selected time is within your ' .
                                     'half-day leave period. Please pick a time outside of ' .
                                     $formattedHalfDayStartTime . ' to ' . $formattedHalfDayEndTime . '.'
                    ];
                }
            }
        }

        $earliestCheckInDateTime = $checkInDateTime ;
        $latestCheckOutDateTime  = $checkOutDateTime;

        try {
            $this->pdo->beginTransaction();

            foreach ($attendanceRecords as $key => $record) {
                $existingCheckInDateTime  = new DateTime($record['check_in_time' ]);
                $existingCheckOutDateTime = new DateTime($record['check_out_time']);

                $attendanceRecords[$key]['check_in_time' ] = $existingCheckInDateTime ->format('Y-m-d H:i:s');
                $attendanceRecords[$key]['check_out_time'] = $existingCheckOutDateTime->format('Y-m-d H:i:s');

                if ($earliestCheckInDateTime < $existingCheckOutDateTime &&
                    $latestCheckOutDateTime  > $existingCheckInDateTime) {

                    $earliestCheckInDateTime = clone (min($existingCheckInDateTime , $checkInDateTime ));
                    $latestCheckOutDateTime  = clone (max($existingCheckOutDateTime, $checkOutDateTime));

                    $deleteAttendanceRecordResult = $this->attendanceRepository
                        ->deleteAttendance($record['id']);

                    if ($deleteAttendanceRecordResult === ActionResult::FAILURE) {
                        $this->pdo->rollback();

                        return [
                            'status'  => 'error',
                            'message' => 'An unexpected error occurred while checking in. Please try again later.'
                        ];
                    }

                    $attendanceRecords[$key]['is_deleted'] = true;
                }
            }

            foreach ($employeeBreakRecords as $breakRecord) {
                $breakRecordStartDateTime =
                    $breakRecord['start_time'] !== null
                        ? new DateTime($breakRecord['start_time'])
                        : null;

                if ($breakRecordStartDateTime instanceof DateTime) {
                    $formattedBreakRecordStartDateTime = $breakRecordStartDateTime->format('Y-m-d H:i:s');

                    foreach ($attendanceRecords as $record) {
                    }
                }
            }

            $this->pdo->commit();

        } catch (PDOException $exception) {
            $this->pdo->rollback();

            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while checking in. Please try again later.'
            ];
        }

        return [];
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

                if ($currentDateTime >= $workStartDateTime && $currentDateTime < $workEndDateTime && ! isset($workSchedule['is_recorded'])) {
                    return $workSchedule;
                }

                if ($currentDateTime < $workStartDateTime && empty($nextWorkSchedule) && ! isset($workSchedule['is_recorded'])) {
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
