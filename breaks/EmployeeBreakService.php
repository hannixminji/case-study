<?php

require_once __DIR__ . '/EmployeeBreak.php'                     ;

require_once __DIR__ . '/EmployeeBreakRepository.php'           ;
require_once __DIR__ . '/../employees/EmployeeRepository.php'   ;
require_once __DIR__ . '/../attendance/AttendanceRepository.php';
require_once __DIR__ . '/BreakScheduleRepository.php'           ;

class EmployeeBreakService
{
    private readonly EmployeeBreakRepository $employeeBreakRepository;
    private readonly EmployeeRepository      $employeeRepository     ;
    private readonly AttendanceRepository    $attendanceRepository   ;
    private readonly BreakScheduleRepository $breakScheduleRepository;

    public function __construct(
        EmployeeBreakRepository $employeeBreakRepository,
        EmployeeRepository      $employeeRepository     ,
        AttendanceRepository    $attendanceRepository   ,
        BreakScheduleRepository $breakScheduleRepository
    ) {
        $this->employeeBreakRepository = $employeeBreakRepository;
        $this->employeeRepository      = $employeeRepository     ;
        $this->attendanceRepository    = $attendanceRepository   ;
        $this->breakScheduleRepository = $breakScheduleRepository;
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

        $employeeFetchResult = $this->employeeRepository->fetchAllEmployees(
            columns       : $employeeColumns       ,
            filterCriteria: $employeeFilterCriteria,
            limit         : 1
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

        $attendanceColumns = [
			'id'                                    ,
			'date'                                  ,
			'check_in_time'                         ,
			'check_out_time'                        ,

			'work_schedule_history_work_schedule_id',
			'work_schedule_history_start_time'      ,
			'work_schedule_history_end_time'        ,
			'work_schedule_history_is_flextime'
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
            columns       : $attendanceColumns       ,
            filterCriteria: $attendanceFilterCriteria,
            sortCriteria  : $attendanceSortCriteria  ,
            limit         : 1
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

        if ( ! empty($lastAttendanceRecord) && $lastAttendanceRecord['work_schedule_history_is_flextime']) {
            return [
		        'status'  => 'information',
		        'message' => 'You are on a flexible schedule. Breaks may be taken at your convenience.'
		    ];
        }

        if (empty($lastAttendanceRecord) ||

           ($lastAttendanceRecord['check_in_time' ] !== null  &&
            $lastAttendanceRecord['check_out_time'] !== null)) {

            return [
                'status'  => 'warning',
                'message' => 'You must check in before taking a break.'
            ];
        }

        if ($lastAttendanceRecord['check_in_time' ] !== null &&
            $lastAttendanceRecord['check_out_time'] === null) {

            $workScheduleId = $lastAttendanceRecord['work_schedule_history_work_schedule_id'];

			$employeeBreakColumns = [
                'id'                                    ,
                'attendance_id'                         ,
                'break_schedule_history_id'             ,
                'start_time'                            ,
                'end_time'                              ,
                'created_at'                            ,

                'work_schedule_history_work_schedule_id'
			];

			$employeeBreakFilterCriteria = [
                [
                    'column'   => 'employee_break.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'   => 'work_schedule_history.work_schedule_id',
                    'operator' => '='                                     ,
                    'value'    => $workScheduleId
                ]
			];

			$employeeBreakSortCriteria = [
                [
                    'column'    => 'employee_break.created_at',
                    'direction' => 'DESC'
                ],
                [
                    'column'    => 'employee_break.start_time',
                    'direction' => 'DESC'
                ]
			];

			$employeeBreakFetchResult = $this->employeeBreakRepository->fetchAllEmployeeBreaks(
				columns       : $employeeBreakColumns       ,
				filterCriteria: $employeeBreakFilterCriteria,
				sortCriteria  : $employeeBreakSortCriteria  ,
				limit         : 1
			);

			if ($employeeBreakFetchResult === ActionResult::FAILURE) {
	            return [
			        'status'  => 'error',
			        'message' => 'An unexpected error occurred. Please try again later.'
			    ];
	        }

			$lastBreakRecord =
				! empty($employeeBreakFetchResult['result_set'])
					? $employeeBreakFetchResult['result_set'][0]
					: [];

            if (empty($lastBreakRecord) ||

               ($lastBreakRecord['start_time'] !== null  &&
                $lastBreakRecord['end_time'  ] !== null) ||

               ($lastBreakRecord['start_time'] === null  &&
                $lastBreakRecord['end_time'  ] === null)) {

                $isBreakIn = true;

                $currentDateTime = new DateTime($currentDateTime);

                $workScheduleStartTime = $lastAttendanceRecord['work_schedule_history_start_time'];
                $workScheduleEndTime   = $lastAttendanceRecord['work_schedule_history_end_time'  ];

                $workScheduleStartDateTime = new DateTime($lastAttendanceRecord['date'] . ' ' . $workScheduleStartTime);
                $workScheduleEndDateTime   = new DateTime($lastAttendanceRecord['date'] . ' ' . $workScheduleEndTime  );

                if ($workScheduleEndDateTime <= $workScheduleStartDateTime) {
                    $workScheduleEndDateTime->modify('+1 day');
                }

                if ($currentDateTime >= $workScheduleEndDateTime) {
                    return [
                        'status'  => 'warning',
                        'message' => 'You may have forgotten to check out from your previous shift. Please' .
                                     'complete your attendance first before taking your break.'
                    ];
                }

                $breakScheduleColumns = [
                    'id'                            ,
                    'start_time'                    ,
                    'end_time'                      ,
                    'is_flexible'                   ,
                    'earliest_start_time'           ,
                    'latest_end_time'               ,

                    'break_type_duration_in_minutes'
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

                $breakScheduleFetchResult = $this->breakScheduleRepository->fetchAllBreakSchedules(
                    columns       : $breakScheduleColumns       ,
                    filterCriteria: $breakScheduleFilterCriteria
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

                if (empty($breakSchedules)) {
                    return [
                        'status'  => 'information',
                        'message' => 'There is no break schedule assigned for this shift.'
                    ];
                }

                $currentBreakSchedule = $this->getCurrentBreakSchedule(
                    breakSchedules           : $breakSchedules                                  ,
                    currentDateTime          : $currentDateTime          ->format('Y-m-d H:i:s'),
                    workScheduleStartDateTime: $workScheduleStartDateTime->format('Y-m-d H:i:s'),
                    workScheduleEndDateTime  : $workScheduleEndDateTime  ->format('Y-m-d H:i:s')
                );

                if (empty($currentBreakSchedule)) {
                    return [
                        'status'  => 'information',
                        'message' => 'The break time has already ended.'
                    ];
                }

                if ($currentBreakSchedule['is_flexible']) {
                    $breakScheduleStartDateTime = new DateTime($currentBreakSchedule['earliest_start_time']);
                    $breakScheduleEndDateTime   = new DateTime($currentBreakSchedule['latest_end_time'    ]);
                } else {
                    $breakScheduleStartDateTime = new DateTime($currentBreakSchedule['start_time']);
                    $breakScheduleEndDateTime   = new DateTime($currentBreakSchedule['end_time'  ]);
                }

                if ($currentDateTime < $breakScheduleStartDateTime) {
                    $formattedBreakStartTime = $breakScheduleStartDateTime->format('g:i A');
                    $formattedBreakEndTime   = $breakScheduleEndDateTime  ->format('g:i A');

                    return [
                        'status'  => 'information',
                        'message' => "Your next break is scheduled to start at $formattedBreakStartTime and end at $formattedBreakEndTime."
                    ];
                }

                $breakScheduleHistoryId = $this->breakScheduleRepository
                    ->fetchLatestBreakScheduleHistoryId($currentBreakSchedule['id']);

                if ($breakScheduleHistoryId === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $formattedBreakStartDateTime = $currentDateTime->format('Y-m-d H:i:s');

                $employeeBreak = new EmployeeBreak(
                    id                    : null                        ,
                    attendanceId          : $lastAttendanceRecord['id'] ,
                    breakScheduleHistoryId: $breakScheduleHistoryId     ,
                    startTime             : $formattedBreakStartDateTime,
                    endTime               : null                        ,
                    breakDurationInMinutes: 0                           ,
                    createdAt             : $formattedBreakStartDateTime
                );

                $employeeBreakInResult = $this->employeeBreakRepository->breakIn($employeeBreak);

                if ($employeeBreakInResult === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

            } elseif ($lastBreakRecord['start_time'] !== null &&
                      $lastBreakRecord['end_time'  ] === null) {

                $isBreakIn = false;

                $breakStartDateTime = new DateTime($lastBreakRecord['start_time']);
                $breakEndDateTime   = new DateTime($currentDateTime              );

                $breakDuration          = $breakStartDateTime->diff($breakEndDateTime);
                $breakDurationInMinutes = ($breakDuration->h * 60) + $breakDuration->i;

                $formattedBreakEndDateTime = $breakEndDateTime->format('Y-m-d H:i:s');

                $employeeBreak = new EmployeeBreak(
                    id                    : $lastBreakRecord['id'                       ],
                    attendanceId          : $lastBreakRecord['attendance_id'            ],
                    breakScheduleHistoryId: $lastBreakRecord['break_schedule_history_id'],
                    startTime             : $lastBreakRecord['start_time'               ],
                    endTime               : $formattedBreakEndDateTime                   ,
                    breakDurationInMinutes: $breakDurationInMinutes                      ,
                    createdAt             : $lastBreakRecord['created_at'               ]
                );

                $employeeBreakOutResult = $this->employeeBreakRepository->breakOut($employeeBreak);

                if ($employeeBreakOutResult === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }
            }
        }

        if ($isBreakIn) {
            return [
                'status'  => 'success',
                'message' => 'Break-in recorded successfully.'
            ];
        } else {
            return [
                'status'  => 'success',
                'message' => 'Break-out recorded successfully.'
            ];
        }
    }

    private function getCurrentBreakSchedule(
        array  $breakSchedules           ,
        string $currentDateTime          ,
        string $workScheduleStartDateTime,
        string $workScheduleEndDateTime
    ) {
        $currentDateTime = new DateTime($currentDateTime);

        $workScheduleStartDateTime = new DateTime($workScheduleStartDateTime   );
        $workScheduleEndDateTime   = new DateTime($workScheduleEndDateTime     );
        $workScheduleStartDate     = $workScheduleStartDateTime->format('Y-m-d');

        $nextBreakSchedule = [];

        foreach ($breakSchedules as $breakSchedule) {
            if ($breakSchedule['is_flexible']) {
                $breakStartTime = $breakSchedule['earliest_start_time'];
                $breakEndTime   = $breakSchedule['latest_end_time'    ];
            } else {
                $breakStartTime = $breakSchedule['start_time'];
                $breakEndTime   = $breakSchedule['end_time'  ];
            }

            $breakStartDateTime = new DateTime($workScheduleStartDate . ' ' . $breakStartTime);
            $breakEndDateTime   = new DateTime($workScheduleStartDate . ' ' . $breakEndTime  );

            if ($breakStartDateTime < $workScheduleStartDateTime) {
                $breakStartDateTime->modify('+1 day');
            }

            if ($breakEndDateTime < $workScheduleStartDateTime) {
                $breakEndDateTime->modify('+1 day');
            }

            if ($breakEndDateTime <= $breakStartDateTime) {
                $breakEndDateTime->modify('+1 day');
            }

            if ($breakSchedule['is_flexible']) {
                $breakSchedule['earliest_start_time'] = $breakStartDateTime->format('Y-m-d H:i:s');
                $breakSchedule['latest_end_time'    ] = $breakEndDateTime  ->format('Y-m-d H:i:s');
            } else {
                $breakSchedule['start_time'] = $breakStartDateTime->format('Y-m-d H:i:s');
                $breakSchedule['end_time'  ] = $breakEndDateTime  ->format('Y-m-d H:i:s');
            }

            if ($currentDateTime >= $breakStartDateTime && $currentDateTime < $breakEndDateTime) {
                return $breakSchedule;
            }

            if (empty($nextBreakSchedule) && $currentDateTime < $breakStartDateTime) {
                $nextBreakSchedule = $breakSchedule;
            }
        }

        return $nextBreakSchedule;
    }
}
