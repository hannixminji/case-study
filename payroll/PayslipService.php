<?php

require_once __DIR__ . '/../employees/EmployeeRepository.php'   ;
require_once __DIR__ . '/../attendance/AttendanceRepository.php';
require_once __DIR__ . '/../holidays/HolidayRepository.php'     ;
require_once __DIR__ . '/../leaves/LeaveRequestRepository.php'  ;
require_once __DIR__ . '/../breaks/EmployeeBreakRepository.php' ;

class PayslipService
{
    private readonly EmployeeRepository      $employeeRepository     ;
    private readonly AttendanceRepository    $attendanceRepository   ;
    private readonly HolidayRepository       $holidayRepository      ;
    private readonly LeaveRequestRepository  $leaveRequestRepository ;
    private readonly EmployeeBreakRepository $employeeBreakRepository;

    public function __construct(
        EmployeeRepository      $employeeRepository     ,
        AttendanceRepository    $attendanceRepository   ,
        HolidayRepository       $holidayRepository      ,
        LeaveRequestRepository  $leaveRequestRepository ,
        EmployeeBreakRepository $employeeBreakRepository
    ) {
        $this->employeeRepository      = $employeeRepository     ;
        $this->attendanceRepository    = $attendanceRepository   ;
        $this->holidayRepository       = $holidayRepository      ;
        $this->leaveRequestRepository  = $leaveRequestRepository ;
        $this->employeeBreakRepository = $employeeBreakRepository;
    }

    public function generatePayslip(
        PayrollGroup $payrollGroup         ,
        string       $cutoffPeriodStartDate,
        string       $cutoffPeriodEndDate  ,
        string       $paydayDate
    ) {

        $employeeColumns = [
            'id'           ,
            'job_title_id' ,
            'department_id',
            'basic_salary'
        ];

        $employeeFilterCriteria = [
            [
                'column'   => 'employee.deleted_at',
                'operator' => 'IS NULL'
            ],
            [
                'column'   => 'employee.payroll_group_id',
                'operator' => '='                        ,
                'value'    => $payrollGroup->getId()
            ],
            [
                'column'   => 'employee.access_role',
                'operator' => '!='                  ,
                'value'    => 'Admin'
            ]
        ];

        $employees = $this->employeeRepository->fetchAllEmployees(
            columns             : $employeeColumns       ,
            filterCriteria      : $employeeFilterCriteria,
            includeTotalRowCount: false
        );

        if ($employees === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $employees =
            ! empty($employees['result_set'])
                ? $employees['result_set']
                : [];

        if (empty($employees)) {
            return [
                'status'  => 'information',
                'message' => ''
            ];
        }

        foreach ($employees as $employee) {
            $employeeId   = $employee['id'           ];
            $jobTitleId   = $employee['job_title_id' ];
            $departmentId = $employee['department_id'];
            $basicSalary  = $employee['basic_salary' ];

            $adjustedCutoffPeriodStartDate =
                (new DateTime($cutoffPeriodStartDate))
                    ->modify('-1 day')
                    ->format('Y-m-d' );

            $attendanceRecordColumns = [
                'work_schedule_snapshot_id'                               ,
                'date'                                                    ,
                'check_in_time'                                           ,
                'check_out_time'                                          ,
                'is_overtime_approved'                                    ,
                'attendance_status'                                       ,
                'is_processed_for_next_payroll'                           ,

                'work_schedule_snapshot_start_time'                       ,
                'work_schedule_snapshot_end_time'                         ,
                'work_schedule_snapshot_is_flextime'                      ,
                'work_schedule_snapshot_total_work_hours'                 ,
                'work_schedule_snapshot_grace_period'                     ,
                'work_schedule_snapshot_minutes_can_check_in_before_shift'
            ];

            $attendanceRecordFilterCriteria = [
                [
                    'column'   => 'attendance.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'      => 'attendance.date'             ,
                    'operator'    => 'BETWEEN'                     ,
                    'lower_bound' => $adjustedCutoffPeriodStartDate,
                    'upper_bound' => $cutoffPeriodEndDate
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
                    'direction' => 'ASC'
                ],
                [
                    'column'    => 'work_schedule_snapshot.start_time',
                    'direction' => 'ASC'
                ],
                [
                    'column'    => 'attendance.check_in_time',
                    'direction' => 'ASC'
                ]
            ];

            $employeeAttendanceRecords = $this->attendanceRepository->fetchAllAttendance(
                columns             : $attendanceRecordColumns       ,
                filterCriteria      : $attendanceRecordFilterCriteria,
                sortCriteria        : $attendanceRecordSortCriteria  ,
                includeTotalRowCount: false
            );

            if ($employeeAttendanceRecords === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $employeeAttendanceRecords =
                ! empty($employeeAttendanceRecords['result_set'])
                    ? $employeeAttendanceRecords['result_set']
                    : [];

            if ( ! empty($employeeAttendanceRecords)) {
                $attendanceRecords = [];

                foreach ($employeeAttendanceRecords as $attendanceRecord) {
                    $date                   = $attendanceRecord['date'                     ];
                    $workScheduleSnapshotId = $attendanceRecord['work_schedule_snapshot_id'];

                    if ( ! isset($attendanceRecords[$date][$workScheduleSnapshotId])) {
                        $attendanceRecords[$date][$workScheduleSnapshotId] = [
                            'work_schedule' => [
                                'snapshot_id'                       => $attendanceRecord['work_schedule_snapshot_id'                               ],
                                'start_time'                        => $attendanceRecord['work_schedule_snapshot_start_time'                       ],
                                'end_time'                          => $attendanceRecord['work_schedule_snapshot_end_time'                         ],
                                'is_flextime'                       => $attendanceRecord['work_schedule_snapshot_is_flextime'                      ],
                                'total_work_hours'                  => $attendanceRecord['work_schedule_snapshot_total_work_hours'                 ],
                                'grace_period'                      => $attendanceRecord['work_schedule_snapshot_grace_period'                     ],
                                'minutes_can_check_in_before_shift' => $attendanceRecord['work_schedule_snapshot_minutes_can_check_in_before_shift']
                            ],

                            'attendance_records' => []
                        ];
                    }

                    $attendanceRecords[$date][$workScheduleSnapshotId]['attendance_records'][] = [
                        'date'                          => $attendanceRecord['date'                         ] ,
                        'check_in_time'                 => $attendanceRecord['check_in_time'                ] ,
                        'check_out_time'                => $attendanceRecord['check_out_time'               ] ,
                        'is_overtime_approved'          => $attendanceRecord['is_overtime_approved'         ] ,
                        'attendance_status'             => strtolower($attendanceRecord['attendance_status' ]),
                        'is_processed_for_next_payroll' => $attendanceRecord['is_processed_for_next_payroll']
                    ];
                }

                $firstDate = array_key_first($attendanceRecords);

                if ($firstDate === $adjustedCutoffPeriodStartDate) {
                    $lastWorkSchedule = end($attendanceRecords[$firstDate]);

                    $attendanceRecords[$firstDate] = $lastWorkSchedule;

                    $workScheduleStartTime = $lastWorkSchedule['work_schedule']['start_time'];
                    $workScheduleEndTime   = $lastWorkSchedule['work_schedule']['end_time'  ];

                    $workScheduleStartDateTime = new DateTime($firstDate . ' ' . $workScheduleStartTime);
                    $workScheduleEndDateTime   = new DateTime($firstDate . ' ' . $workScheduleEndTime  );

                    if ($workScheduleEndDateTime <= $workScheduleStartDateTime) {
                        $workScheduleEndDateTime->modify('+1 day');
                    }

                    if ($workScheduleEndDateTime <= (new DateTime($cutoffPeriodEndDate))->modify('+1 day')) {
                        unset($attendanceRecords[$firstDate]);
                    }
                }

                if ( ! empty($attendanceRecords)) {
                    $lastDate = array_key_last($attendanceRecords);

                    $lastWorkSchedule = end($attendanceRecords[$lastDate]);

                    $workScheduleStartTime = $lastWorkSchedule['work_schedule']['start_time'];
                    $workScheduleEndTime   = $lastWorkSchedule['work_schedule']['end_time'  ];

                    $workScheduleStartDateTime = new DateTime($firstDate . ' ' . $workScheduleStartTime);
                    $workScheduleEndDateTime   = new DateTime($firstDate . ' ' . $workScheduleEndTime  );

                    if ($workScheduleEndDateTime <= $workScheduleStartDateTime) {
                        $workScheduleEndDateTime->modify('+1 day');
                    }

                    if ($workScheduleEndDateTime > (new DateTime($cutoffPeriodEndDate))->modify('+1 day')) {
                        array_pop($attendanceRecords[$lastDate]);
                    }

                    if (empty($attendanceRecords[$lastDate])) {
                        unset($attendanceRecords[$lastDate]);
                    }
                }

                $startDate = array_key_first($attendanceRecords);
                $endDate   = array_key_last ($attendanceRecords);

                $datesMarkedAsHolidays = $this->holidayRepository->getHolidayDatesForPeriod(
                    startDate: $startDate,
                    endDate  : $endDate
                );

                if ($datesMarkedAsHolidays === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $datesMarkedAsLeaves = $this->leaveRequestRepository->getLeaveDatesForPeriod(
                    employeeId: $employeeId,
                    startDate : $startDate ,
                    endDate   : $endDate
                );

                if ($datesMarkedAsLeaves === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $workHours = [
                    'regular_day' => [
                        'non_holiday' => [
                            'regular_hours'               => 0.0,
                            'overtime_hours'              => 0.0,
                            'night_differential'          => 0.0,
                            'night_differential_overtime' => 0.0
                        ],

                        'special_holiday' => [
                            'regular_hours'               => 0.0,
                            'overtime_hours'              => 0.0,
                            'night_differential'          => 0.0,
                            'night_differential_overtime' => 0.0
                        ],

                        'regular_holiday' => [
                            'regular_hours'               => 0.0,
                            'overtime_hours'              => 0.0,
                            'night_differential'          => 0.0,
                            'night_differential_overtime' => 0.0
                        ],

                        'double_holiday' => [
                            'regular_hours'               => 0.0,
                            'overtime_hours'              => 0.0,
                            'night_differential'          => 0.0,
                            'night_differential_overtime' => 0.0
                        ]
                    ],

                    'rest_day' => [
                        'non_holiday' => [
                            'regular_hours'               => 0.0,
                            'overtime_hours'              => 0.0,
                            'night_differential'          => 0.0,
                            'night_differential_overtime' => 0.0
                        ],

                        'special_holiday' => [
                            'regular_hours'               => 0.0,
                            'overtime_hours'              => 0.0,
                            'night_differential'          => 0.0,
                            'night_differential_overtime' => 0.0
                        ],

                        'regular_holiday' => [
                            'regular_hours'               => 0.0,
                            'overtime_hours'              => 0.0,
                            'night_differential'          => 0.0,
                            'night_differential_overtime' => 0.0
                        ],

                        'double_holiday' => [
                            'regular_hours'               => 0.0,
                            'overtime_hours'              => 0.0,
                            'night_differential'          => 0.0,
                            'night_differential_overtime' => 0.0
                        ]
                    ],

                    'non_worked_paid_hours' => [
                        'leave'           => 0.0,
                        'regular_holiday' => 0.0,
                        'double_holiday'  => 0.0
                    ]
                ];

                foreach ($attendanceRecords as $date => $workSchedules) {
                    foreach ($workSchedules as $workSchedule) {
                        $workScheduleSnapshotId = $workSchedule['work_schedule']['snapshot_id'];
                        $isFlextime             = $workSchedule['work_schedule']['is_flextime'];

                        $workScheduleStartTime = $workSchedule['work_schedule']['start_time'];
                        $workScheduleEndTime   = $workSchedule['work_schedule']['end_time'  ];

                        $workScheduleStartDateTime = new DateTime($date . ' ' . $workScheduleStartTime);
                        $workScheduleEndDateTime   = new DateTime($date . ' ' . $workScheduleEndTime  );

                        if ($workScheduleEndDateTime <= $workScheduleStartDateTime) {
                            $workScheduleEndDateTime->modify('+1 day');
                        }

                        $earlyCheckInWindow = $workSchedule['work_schedule']['minutes_can_check_in_before_shift'];
                        $adjustedWorkScheduleStartDateTime = (clone $workScheduleStartDateTime)
                            ->modify('-' . $earlyCheckInWindow . ' minutes');

                        $formattedWorkScheduleStartDateTime         = $workScheduleStartDateTime        ->format('Y-m-d H:i:s');
                        $formattedWorkScheduleEndDateTime           = $workScheduleEndDateTime          ->format('Y-m-d H:i:s');
                        $formattedAdjustedWorkScheduleStartDateTime = $adjustedWorkScheduleStartDateTime->format('Y-m-d H:i:s');

                        if ( ! empty($workSchedule['attendance_records']) &&
                                     $workSchedule['attendance_records'][0]['check_in_time'] !== null) {

                            if ( ! $isFlextime) {
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
                                        'value'    => $workScheduleSnapshotId
                                    ],
                                    [
                                        'column'      => 'employee_break.created_at'                ,
                                        'operator'    => 'BETWEEN'                                  ,
                                        'lower_bound' => $formattedAdjustedWorkScheduleStartDateTime,
                                        'upper_bound' => $formattedWorkScheduleEndDateTime
                                    ]
                                ];

                                $employeeBreakRecords = $this->employeeBreakRepository->fetchAllEmployeeBreaks(
                                    columns             : $employeeBreakColumns       ,
                                    filterCriteria      : $employeeBreakFilterCriteria,
                                    includeTotalRowCount: false
                                );

                                if ($employeeBreakRecords === ActionResult::FAILURE) {
                                    return [
                                        'status'  => 'error',
                                        'message' => 'An unexpected error occurred. Please try again later.'
                                    ];
                                }

                                $employeeBreakRecords =
                                    ! empty($employeeBreakRecords['result_set'])
                                        ? $employeeBreakRecords['result_set']
                                        : [];

                                if ( ! empty($employeeBreakRecords)) {
                                    $groupedBreakRecords = [];
                                    foreach ($employeeBreakRecords as $breakRecord) {
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

                                    $employeeBreakRecords = $mergedBreakRecords;
                                }
                            }

                            $isFirstAttendanceRecord = true;

                            foreach ($workSchedule['attendance_records'] as $attendanceRecord) {
                                $checkInDateTime = new DateTime($attendanceRecord['check_in_time']);

                                $checkOutDateTime =
                                    $attendanceRecord['check_out_time'] !== null
                                        ? new DateTime($attendanceRecord['check_out_time'])
                                        : $workScheduleEndDateTime;

                                if ( ! $isFlextime && $isFirstAttendanceRecord) {
                                    if ($checkInDateTime < $workScheduleStartDateTime) {
                                        $checkInDateTime = $workScheduleStartDateTime;
                                    }

                                    $gracePeriod = $workSchedule['work_schedule']['grace_period'];

                                    $gracePeriodStartDateTime = (clone $workScheduleStartDateTime)->modify('+' . $gracePeriod . ' minutes');

                                    if ($checkInDateTime <= $gracePeriodStartDateTime) {
                                        $checkInDateTime = clone $workScheduleStartDateTime;
                                    }

                                    $isFirstAttendanceRecord = false;
                                }

                                if ( ! $isFlextime && ! empty($mergedBreakRecords)) {
                                    $formattedCheckOutDateTime = $checkOutDateTime->format('Y-m-d H:i:s');

                                    $breakRecords = [];

                                    foreach ($employeeBreakRecords as $breakRecord) {
                                        $breakRecordStartTime =
                                            $breakRecord['start_time'] !== null
                                                ? (new DateTime($breakRecord['start_time']))->format('H:i:s')
                                                : null;

                                        $breakScheduleStartTime = $breakRecord['break_schedule_snapshot_start_time'];

                                        $breakScheduleEndTime = (new DateTime($breakScheduleStartTime))
                                            ->modify('+' . $breakRecord['break_type_snapshot_duration_in_minutes'] . ' minutes')
                                            ->format('H:i:s');

                                        $breakScheduleStartDateTime = new DateTime($date . ' ' . $breakScheduleStartTime);
                                        $breakScheduleEndDateTime   = new DateTime($date . ' ' . $breakScheduleEndTime  );

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

                                        $breakRecord['start_time'] = $breakScheduleStartDateTime->format('Y-m-d H:i:s');

                                        $breakRecordEndDateTime =
                                            $breakRecord['end_time'] !== null
                                                ? new DateTime($breakRecord['end_time'])
                                                : clone $breakScheduleEndDateTime;

                                        if ($breakRecordEndDateTime <= $breakScheduleEndDateTime &&
                                            $checkOutDateTime       >  $breakScheduleEndDateTime) {

                                            $breakRecord['end_time'] = $breakScheduleEndDateTime->format('Y-m-d H:i:s');

                                        } elseif (($breakRecordEndDateTime <= $breakScheduleEndDateTime  &&
                                                $checkOutDateTime       <  $breakScheduleEndDateTime) ||

                                                $breakRecordEndDateTime >  $checkOutDateTime) {

                                            $breakRecord['end_time'] = $checkOutDateTime->format('Y-m-d H:i:s');
                                        }

                                        if ($formattedCheckOutDateTime >= $breakRecord['start_time']) {
                                            $breakRecords[] = [
                                                'start_time' => $breakRecord['start_time'                 ],
                                                'end_time'   => $breakRecord['end_time'                   ],
                                                'is_paid'    => $breakRecord['break_type_snapshot_is_paid']
                                            ];
                                        }
                                    }
                                }

                                // inside looping of attendance records
                            }
                        }
                    }
                }

                //
            }
        }
    }
}
