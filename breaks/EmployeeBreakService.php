<?php

require_once __DIR__ . '/EmployeeBreakRepository.php'           ;

require_once __DIR__ . '/../employees/EmployeeRepository.php'   ;
require_once __DIR__ . '/../attendance/AttendanceRepository.php';

class EmployeeBreakService
{
    private readonly PDO                     $pdo                    ;

    private readonly EmployeeBreakRepository $employeeBreakRepository;
    private readonly EmployeeRepository      $employeeRepository     ;
    private readonly AttendanceRepository    $attendanceRepository   ;

    public function __construct(
        PDO                     $pdo                    ,

        EmployeeBreakRepository $employeeBreakRepository,
        EmployeeRepository      $employeeRepository     ,
        AttendanceRepository    $attendanceRepository
    ) {
        $this->pdo                     = $pdo                    ;

        $this->employeeBreakRepository = $employeeBreakRepository;
        $this->employeeRepository      = $employeeRepository     ;
        $this->attendanceRepository    = $attendanceRepository   ;
    }

    public function fetchAllEmployeeBreaks(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? array $groupByColumns       = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->employeeBreakRepository->fetchAllEmployeeBreaks(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            groupByColumns      : $groupByColumns      ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
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

        $attendanceRecordColumns = [
            'work_schedule_snapshot_id'                               ,
            'date'                                                    ,
            'check_in_time'                                           ,
            'check_out_time'                                          ,

            'work_schedule_snapshot_start_time'                       ,
            'work_schedule_snapshot_end_time'                         ,
            'work_schedule_snapshot_is_flextime'                      ,
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

        if ( ! empty($lastAttendanceRecord) &&
                     $lastAttendanceRecord['work_schedule_snapshot_is_flextime']) {

            return [
                'status'  => 'info',
                'message' => 'You are on a flexible schedule. Breaks may ' .
                             'be taken at your convenience.'
            ];
        }

        if (empty($lastAttendanceRecord)                      ||

           ($lastAttendanceRecord['check_in_time' ] !== null  &&
            $lastAttendanceRecord['check_out_time'] !== null) ||

           ($lastAttendanceRecord['check_in_time' ] === null  &&
            $lastAttendanceRecord['check_out_time'] !== null) ||

           ($lastAttendanceRecord['check_in_time' ] === null  &&
            $lastAttendanceRecord['check_out_time'] === null)) {

            return [
                'status'  => 'warning',
                'message' => 'You must check in before taking a break.'
            ];
        }

        if ($lastAttendanceRecord['check_in_time' ] !== null &&
            $lastAttendanceRecord['check_out_time'] === null) {

            $currentDateTime = new DateTime($currentDateTime);

            $workScheduleSnapshotId = $lastAttendanceRecord['work_schedule_snapshot_id'];

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

            $formattedWorkScheduleStartDateTime = $workScheduleStartDateTime->format('Y-m-d H:i:s');
            $formattedWorkScheduleEndDateTime   = $workScheduleEndDateTime  ->format('Y-m-d H:i:s');

            $formattedAdjustedWorkScheduleStartDateTime = $adjustedWorkScheduleStartDateTime->format('Y-m-d H:i:s');

            $employeeBreakColumns = [
                'id',
                'break_schedule_snapshot_id'                       ,
                'start_time'                                       ,
                'end_time'                                         ,
                'created_at'                                       ,

                'break_schedule_snapshot_work_schedule_snapshot_id'
            ];

            $employeeBreakFilterCriteria = [
                [
                    'column'   => 'employee_break.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'   => 'break_schedule_snapshot.work_schedule_snapshot_id',
                    'operator' => '='                                                ,
                    'value'    => $workScheduleSnapshotId
                ],
                [
                    'column'      => 'employee_break.created_at'                ,
                    'operator'    => 'BETWEEN'                                  ,
                    'lower_bound' => $formattedAdjustedWorkScheduleStartDateTime,
                    'upper_bound' => $formattedWorkScheduleEndDateTime
                ],
                [
                    'column'   => 'employee_break.start_time',
                    'operator' => 'IS NOT NULL'
                ]
            ];

            $employeeBreakSortCriteria = [
                [
                    'column'    => 'employee_break.created_at',
                    'direction' => 'DESC'
                ]
            ];

            $lastBreakRecord = $this->employeeBreakRepository->fetchAllEmployeeBreaks(
                columns             : $employeeBreakColumns       ,
                filterCriteria      : $employeeBreakFilterCriteria,
                sortCriteria        : $employeeBreakSortCriteria  ,
                limit               : 1                           ,
                includeTotalRowCount: false
            );

            if ($lastBreakRecord === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $lastBreakRecord =
                ! empty($lastBreakRecord['result_set'])
                    ? $lastBreakRecord['result_set'][0]
                    : [];

            if (empty($lastBreakRecord)                  ||

               ($lastBreakRecord['start_time'] !== null  &&
                $lastBreakRecord['end_time'  ] !== null)) {

                if ($currentDateTime >= $workScheduleEndDateTime) {
                    return [
                        'status'  => 'warning',
                        'message' => 'You may have forgotten to check out from your previous shift. Please' .
                                     'complete your attendance first before taking your break.'
                    ];
                }

                $employeeBreakColumns = [
                    'id'                                     ,
                    'break_schedule_snapshot_id'             ,
                    'start_time'                             ,
                    'end_time'                               ,
                    'created_at'                             ,

                    'break_schedule_snapshot_start_time'     ,
                    'break_schedule_snapshot_end_time'       ,

                    'break_type_snapshot_duration_in_minutes'
                ];

                $employeeBreakFilterCriteria = [
                    [
                        'column'   => 'employee_break.deleted_at',
                        'operator' => 'IS NULL'
                    ],
                    [
                        'column'   => 'break_schedule_snapshot.work_schedule_snapshot_id',
                        'operator' => '='                                                ,
                        'value'    => $workScheduleSnapshotId
                    ],
                    [
                        'column'      => 'employee_break.created_at'                ,
                        'operator'    => 'BETWEEN'                                  ,
                        'lower_bound' => $formattedAdjustedWorkScheduleStartDateTime,
                        'upper_bound' => $formattedWorkScheduleEndDateTime
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

                $breakSchedules =
                    ! empty($breakSchedules['result_set'])
                        ? $breakSchedules['result_set']
                        : [];

                if (empty($breakSchedules)) {
                    return [
                        'status'  => 'info',
                        'message' => 'There is no break schedule assigned for this shift.'
                    ];
                }

                $mapKeys = [
                    'id'                                      => 'employee_break_id'             ,
                    'break_schedule_snapshot_id'              => 'break_schedule_snapshot_id'    ,
                    'start_time'                              => 'employee_break_start_time'     ,
                    'end_time'                                => 'employee_break_end_time'       ,
                    'created_at'                              => 'employee_break_created_at'     ,

                    'break_schedule_snapshot_start_time'      => 'start_time'                    ,
                    'break_schedule_snapshot_end_time'        => 'end_time'                      ,

                    'break_type_snapshot_duration_in_minutes' => 'break_type_duration_in_minutes'
                ];

                $breakSchedules = array_map(function ($item) use ($mapKeys) {
                    $newItem = [];

                    foreach ($mapKeys as $oldKey => $newKey) {
                        if (array_key_exists($oldKey, $item)) {
                            $newItem[$newKey] = $item[$oldKey];
                        }
                    }

                    return $newItem;

                }, $breakSchedules);

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

                $currentBreakSchedule = $this->getCurrentBreakSchedule(
                    breakSchedules           : $breakSchedules                                  ,
                    currentDateTime          : $currentDateTime          ->format('Y-m-d H:i:s'),
                    workScheduleStartDateTime: $workScheduleStartDateTime->format('Y-m-d H:i:s')
                );

                if (empty($currentBreakSchedule)) {
                    return [
                        'status'  => 'info',
                        'message' => 'The break time has already ended.'
                    ];
                }

                $breakScheduleStartDateTime = new DateTime($currentBreakSchedule['start_time']);
                $breakScheduleEndDateTime   = new DateTime($currentBreakSchedule['end_time'  ]);

                if ($currentDateTime < $breakScheduleStartDateTime) {
                    $formattedBreakStartTime = $breakScheduleStartDateTime->format('g:i A');
                    $formattedBreakEndTime   = $breakScheduleEndDateTime  ->format('g:i A');

                    return [
                        'status'  => 'info',
                        'message' => "Your next break is scheduled to start at $formattedBreakStartTime and end at $formattedBreakEndTime."
                    ];
                }

                $formattedBreakStartDateTime = $currentDateTime->format('Y-m-d H:i:s');

                if ($currentBreakSchedule['employee_break_start_time'] === null &&
                    $currentBreakSchedule['employee_break_end_time'  ] === null) {

                    $employeeBreak = new EmployeeBreak(
                        id                     : $currentBreakSchedule['employee_break_id'         ],
                        breakScheduleSnapshotId: $currentBreakSchedule['break_schedule_snapshot_id'],
                        startTime              : $formattedBreakStartDateTime                       ,
                        endTime                : null                                               ,
                        breakDurationInMinutes : 0                                                  ,
                        createdAt              : $currentBreakSchedule['employee_break_created_at' ]
                    );

                    $employeeBreakUpdateResult = $this->employeeBreakRepository->updateEmployeeBreak($employeeBreak);

                    if ($employeeBreakUpdateResult === ActionResult::FAILURE) {
                        return [
                            'status'  => 'error',
                            'message' => 'An unexpected error occurred. Please try again later.'
                        ];
                    }

                    return [
                        'status'            => 'success'                        ,
                        'message'           => 'Break-in recorded successfully.',
                        'employee_break_id' => $employeeBreak->getId()
                    ];
                }

                $employeeBreak = new EmployeeBreak(
                    id                     : null                                               ,
                    breakScheduleSnapshotId: $currentBreakSchedule['break_schedule_snapshot_id'],
                    startTime              : $formattedBreakStartDateTime                       ,
                    endTime                : null                                               ,
                    breakDurationInMinutes : 0                                                  ,
                    createdAt              : $formattedBreakStartDateTime
                );

                $employeeBreakInResult = $this->employeeBreakRepository->breakIn($employeeBreak);

                if ($employeeBreakInResult === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                return [
                    'status'            => 'success'                        ,
                    'message'           => 'Break-in recorded successfully.',
                    'employee_break_id' => $this->pdo->lastInsertId()
                ];

            } elseif ($lastBreakRecord['start_time'] !== null &&
                      $lastBreakRecord['end_time'  ] === null) {

                $breakStartDateTime = new DateTime($lastBreakRecord['start_time']);
                $breakEndDateTime   = clone $currentDateTime                      ;

                $breakDuration          = $breakStartDateTime->diff($breakEndDateTime);
                $breakDurationInMinutes = ($breakDuration->h * 60) + $breakDuration->i;

                $formattedBreakEndDateTime = $breakEndDateTime->format('Y-m-d H:i:s');

                $employeeBreak = new EmployeeBreak(
                    id                     : $lastBreakRecord['id'                        ],
                    breakScheduleSnapshotId: $lastBreakRecord['break_schedule_snapshot_id'],
                    startTime              : $lastBreakRecord['start_time'                ],
                    endTime                : $formattedBreakEndDateTime                    ,
                    breakDurationInMinutes : $breakDurationInMinutes                       ,
                    createdAt              : $lastBreakRecord['created_at'                 ]
                );

                $employeeBreakOutResult = $this->employeeBreakRepository->breakOut($employeeBreak);

                if ($employeeBreakOutResult === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                return [
                    'status'            => 'success'                         ,
                    'message'           => 'Break-out recorded successfully.',
                    'employee_break_id' => $employeeBreak->getId()
                ];
            }
        }

        return [
            'status'  => 'error',
            'message' => 'An unexpected error occurred. Please try again later.'
        ];
    }

    private function getCurrentBreakSchedule(
        array  $breakSchedules           ,
        string $currentDateTime          ,
        string $workScheduleStartDateTime
    ): array {

        $currentDateTime = new DateTime($currentDateTime);

        $workScheduleStartDateTime = new DateTime($workScheduleStartDateTime   );
        $workScheduleStartDate     = $workScheduleStartDateTime->format('Y-m-d');

        $nextBreakSchedule = [];

        foreach ($breakSchedules as $breakSchedule) {
            $breakStartTime = $breakSchedule['start_time'];
            $breakEndTime   = $breakSchedule['end_time'  ];

            $breakStartDateTime = new DateTime($workScheduleStartDate . ' ' . $breakStartTime);
            $breakEndDateTime   = new DateTime($workScheduleStartDate . ' ' . $breakEndTime  );

            if ($breakStartDateTime < $workScheduleStartDateTime) {
                $breakStartDateTime->modify('+1 day');
            }

            if ($breakEndDateTime < $workScheduleStartDateTime) {
                $breakEndDateTime->modify('+1 day');
            }

            if ($breakEndDateTime < $breakStartDateTime) {
                $breakEndDateTime->modify('+1 day');
            }

            $breakSchedule['start_time'] = $breakStartDateTime->format('Y-m-d H:i:s');
            $breakSchedule['end_time'  ] = $breakEndDateTime  ->format('Y-m-d H:i:s');

            if ($currentDateTime >= $breakStartDateTime && $currentDateTime < $breakEndDateTime) {
                return $breakSchedule;
            }

            if ($currentDateTime < $breakStartDateTime && empty($nextBreakSchedule)) {
                $nextBreakSchedule = $breakSchedule;
            }
        }

        return $nextBreakSchedule;
    }
}