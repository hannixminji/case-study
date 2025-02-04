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
                'column'   => 'leave_request.status',
                'operator' => '='                   ,
                'value'    => 'In Progress'
            ]
        ];

        $leaveRequestFetchResult = $this->leaveRequestRepository->fetchAllLeaveRequests(
            columns       : $leaveRequestColumns       ,
            filterCriteria: $leaveRequestFilterCriteria,
            limit         : 1
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

            'work_schedule_history_work_schedule_id',
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

        if (empty($lastAttendanceRecord) ||

           ($lastAttendanceRecord['check_in_time' ] !== null  &&
            $lastAttendanceRecord['check_out_time'] !== null)) {

            $isCheckIn = true;

            $currentDateTime = new ($currentDateTime                         );
            $currentDate     = new DateTime($currentDateTime->format('Y-m-d'));
            $previousDate    = (clone $currentDate)->modify('-1 day'         );

            $formattedCurrentDateTime = $currentDateTime->format('Y-m-d H:i:s');
            $formattedCurrentDate     = $currentDate    ->format('Y-m-d'      );
            $formattedPreviousDate    = $previousDate   ->format('Y-m-d'      );

            $workScheduleColumns = [
                'id'              ,
                'start_time'      ,
                'end_time'        ,
                'is_flextime'     ,
                'total_work_hours',
                'recurrent_rule'
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
                ],
                [
                    'column'   => 'work_schedule.start_date',
                    'operator' => '<='                      ,
                    'value'    => $formattedCurrentDate
                ]
            ];

            $workScheduleSortCriteria = [
                [
                    'column'    => 'work_schedule.start_date',
                    'direction' => 'ASC'
                ],
                [
                    'column'    => 'work_schedule.start_time',
                    'direction' => 'ASC'
                ]
            ];

            $workScheduleFetchResult = $this->workScheduleRepository->fetchAllWorkSchedules(
                columns       : $workScheduleColumns       ,
                filterCriteria: $workScheduleFilterCriteria,
                sortCriteria  : $workScheduleSortCriteria
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
                $workSchedules           ,
                $formattedCurrentDateTime
            );

            if (empty($currentWorkSchedule)) {
                return [
                    'status'  => 'information',
                    'message' => 'Your work schedule for today has ended.'
                ];
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
                    columns       : $leaveRequestColumns       ,
                    filterCriteria: $leaveRequestFilterCriteria,
                    limit         : 1
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
                    'value'    => $currentWorkSchedule['id']
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

            if (  $isOnLeaveToday['is_half_day'] ||

               (  isset($didLeaveOccurYesterday) &&
                ! empty($didLeaveOccurYesterday) &&
                        $didLeaveOccurYesterday['is_half_day'])) {

                $halfDayPart =
                    isset($didLeaveOccurYesterday)
                        ? strtolower($didLeaveOccurYesterday['half_day_part'])
                        : strtolower($isOnLeaveToday        ['half_day_part']);

                $totalWorkHours = $currentWorkSchedule['total_work_hours'];

                if ($halfDayPart === 'first half') {
                    $halfDayStartTime = clone $currentWorkScheduleEndDateTime;
                    $halfDayEndTime   = clone $halfDayStartTime;
                } elseif ($halfDayPart === 'second half') {
                    $halfDayStartTime = clone $currentWorkScheduleEndDateTime;
                    $halfDayEndTime   = clone $halfDayStartTime;
                }

                if ( ! empty($breakSchedules)) {
                    foreach ($breakSchedules as $breakSchedule) {
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

                        if ($breakEndDateTime < $currentWorkScheduleEndDateTime) {
                            $breakEndDateTime->modify('+1 day');
                        }

                        if ($breakEndDateTime < $breakStartDateTime) {
                            $breakEndDateTime->modify('+1 day');
                        }



                    }
                }
            }

            if ( ! empty($lastAttendanceRecord) &&

                $lastAttendanceRecord['work_schedule_history_work_schedule_id'] === $currentWorkSchedule['work_schedule_id'] &&
                $lastAttendanceRecord['check_in_time' ] >= $currentWorkSchedule['start_time'] &&
                $lastAttendanceRecord['check_out_time'] <= $currentWorkSchedule['end_time'  ]) {
            }

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
}
