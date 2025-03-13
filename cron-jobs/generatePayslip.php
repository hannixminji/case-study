<?php

require_once __DIR__ . '/../database/database.php'                 ;

require_once __DIR__ . '/../payroll/PayslipService.php'            ;
require_once __DIR__ . '/../holidays/HolidayService.php'           ;
require_once __DIR__ . '/../settings/SettingService.php'           ;
require_once __DIR__ . '/../leaves/LeaveRequestService.php'        ;
require_once __DIR__ . '/../work-schedules/WorkScheduleService.php';
require_once __DIR__ . '/../breaks/BreakScheduleService.php'       ;
require_once __DIR__ . '/../breaks/BreakTypeService.php'           ;
require_once __DIR__ . '/../payroll/PayrollGroupService.php'       ;

$payslipDao                = new PayslipDao               ($pdo);
$employeeDao               = new EmployeeDao              ($pdo);
$holidayDao                = new HolidayDao               ($pdo);
$attendanceDao             = new AttendanceDao            ($pdo);
$leaveRequestDao           = new LeaveRequestDao          ($pdo);

$overtimeRateDao           = new OvertimeRateDao          ($pdo);
$departmentDao             = new DepartmentDao            ($pdo);
$jobTitleDao               = new JobTitleDao              ($pdo);
$employeeDao               = new EmployeeDao              ($pdo);
$overtimeRateAssignmentDao = new OvertimeRateAssignmentDao(
    pdo            : $pdo            ,
    overtimeRateDao: $overtimeRateDao,
    departmentDao  : $departmentDao  ,
    jobTitleDao    : $jobTitleDao    ,
    employeeDao    : $employeeDao
);

$employeeBreakDao          = new EmployeeBreakDao         ($pdo);
$employeeAllowanceDao      = new EmployeeAllowanceDao     ($pdo);
$employeeDeductionDao      = new EmployeeDeductionDao     ($pdo);
$leaveEntitlementDao       = new LeaveEntitlementDao      ($pdo);

$payslipRepository                = new PayslipRepository               ($payslipDao               );
$employeeRepository               = new EmployeeRepository              ($employeeDao              );
$holidayRepository                = new HolidayRepository               ($holidayDao               );
$attendanceRepository             = new AttendanceRepository            ($attendanceDao            );
$leaveRequestRepository           = new LeaveRequestRepository          ($leaveRequestDao          );
$overtimeRateAssignmentRepository = new OvertimeRateAssignmentRepository($overtimeRateAssignmentDao);
$overtimeRateRepository           = new OvertimeRateRepository          ($overtimeRateDao          );
$employeeBreakRepository          = new EmployeeBreakRepository         ($employeeBreakDao         );
$employeeAllowanceRepository      = new EmployeeAllowanceRepository     ($employeeAllowanceDao     );
$employeeDeductionRepository      = new EmployeeDeductionRepository     ($employeeDeductionDao     );
$leaveEntitlementRepository       = new LeaveEntitlementRepository      ($leaveEntitlementDao      );

$payslipService = new PayslipService(
    payslipRepository               : $payslipRepository               ,
    employeeRepository              : $employeeRepository              ,
    holidayRepository               : $holidayRepository               ,
    attendanceRepository            : $attendanceRepository            ,
    leaveRequestRepository          : $leaveRequestRepository          ,
    overtimeRateAssignmentRepository: $overtimeRateAssignmentRepository,
    overtimeRateRepository          : $overtimeRateRepository          ,
    employeeBreakRepository         : $employeeBreakRepository         ,
    employeeAllowanceRepository     : $employeeAllowanceRepository     ,
    employeeDeductionRepository     : $employeeDeductionRepository     ,
    leaveEntitlementRepository      : $leaveEntitlementRepository
);

$holidayService = new HolidayService($holidayRepository);

$settingDao        = new SettingDao       ($pdo              );
$settingRepository = new SettingRepository($settingDao       );
$settingService    = new SettingService   ($settingRepository);

$leaveRequestAttachmentDao        = new LeaveRequestAttachmentDao       ($pdo                      );
$leaveRequestAttachmentRepository = new LeaveRequestAttachmentRepository($leaveRequestAttachmentDao);
$leaveRequestService              = new LeaveRequestService             (
    leaveRequestRepository          : $leaveRequestRepository          ,
    leaveRequestAttachmentRepository: $leaveRequestAttachmentRepository
);

$workScheduleDao        = new WorkScheduleDao       ($pdo                   );
$workScheduleRepository = new WorkScheduleRepository($workScheduleDao       );
$workScheduleService    = new WorkScheduleService   ($workScheduleRepository);

$breakScheduleDao        = new BreakScheduleDao       ($pdo                    );
$breakScheduleRepository = new BreakScheduleRepository($breakScheduleDao       );
$breakScheduleService    = new BreakScheduleService   ($breakScheduleRepository);

$breakTypeDao        = new BreakTypeDao       ($pdo                );
$breakTypeRepository = new BreakTypeRepository($breakTypeDao       );
$breakTypeService    = new BreakTypeService   ($breakTypeRepository);

$payrollGroupDao        = new PayrollGroupDao       ($pdo                   );
$payrollGroupRepository = new PayrollGroupRepository($payrollGroupDao       );
$payrollGroupService    = new PayrollGroupService   ($payrollGroupRepository);

$originalCurrentDateTime = new DateTime();

$currentDateTime   =  clone $originalCurrentDateTime                             ;
$currentDateTime   = (clone $currentDateTime)->modify('-1 day')                  ;
$currentDate       =        $currentDateTime                   ->format('Y-m-d' );
$previousDate      = (clone $currentDateTime)->modify('-1 day')->format('Y-m-d' );
$currentDayOfMonth = (int)  $currentDateTime                   ->format('j'     );
$currentWeekNumber = (int)  $currentDateTime                   ->format('W'     );
$currentDayOfWeek  = (int)  $currentDateTime                   ->format('w'     );

try {
    if ($leaveRequestService->updateLeaveRequestStatuses($originalCurrentDateTime->format('Y-m-d')) === ActionResult::FAILURE) {
        return [
            'status'  => 'error',
            'message' => 'An unexpected error occurred. Please try again later.'
        ];
    }

    $datesMarkedAsHoliday = $holidayService->getHolidayDatesForPeriod(
        startDate: $previousDate,
        endDate  : $currentDate
    );

    if ($datesMarkedAsHoliday === ActionResult::FAILURE) {
        return [
            'status'  => 'error',
            'message' => 'An unexpected error occurred. Please try again later.'
        ];
    }

    $gracePeriod = $settingRepository->fetchSettingValue(
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

    $earlyCheckInWindow = $settingRepository->fetchSettingValue(
        settingKey: 'minutes_can_check_in_before_shift',
        groupName : 'work_schedule'
    );

    if ($earlyCheckInWindow === ActionResult::FAILURE) {
        return [
            'status'  => 'error',
            'message' => 'An unexpected error occurred. Please try again later.'
        ];
    }

    $earlyCheckInWindow = (int) $earlyCheckInWindow;

    $query = '
        SELECT
            work_schedule.id                   AS id                  ,
            work_schedule.employee_id          AS employee_id         ,
            work_schedule.start_time           AS start_time          ,
            work_schedule.end_time             AS end_time            ,
            work_schedule.is_flextime          AS is_flextime         ,
            work_schedule.total_hours_per_week AS total_hours_per_week,
            work_schedule.total_work_hours     AS total_work_hours    ,
            work_schedule.start_date           AS start_date          ,
            work_schedule.recurrence_rule      AS recurrence_rule
        FROM
            work_schedules AS work_schedule
        JOIN
            employees AS employee
        ON
            work_schedule.employee_id = employee.id
        WHERE
            work_schedule.deleted_at IS NULL
        AND
            employee.deleted_at IS NULL
        AND
            employee.access_role != "Admin"
        ORDER BY
            work_schedule.start_time ASC
    ';

    $statement = $pdo->prepare($query);

    $statement->execute();

    $workSchedules = $statement->fetchAll(PDO::FETCH_ASSOC);

    $employeesWorkSchedules = [];

    foreach ($workSchedules as $workSchedule) {
        $employeesWorkSchedules[$workSchedule['employee_id']][] = $workSchedule;
    }

    $previousTwoDaysDate = (new DateTime($previousDate))
        ->modify('-1 day')
        ->format('Y-m-d' );

    foreach ($employeesWorkSchedules as $employeeId => $workSchedules) {
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

        $statement = $pdo->prepare($query);

        $statement->bindValue(':previous_two_days_date', $previousTwoDaysDate, Helper::getPdoParameterType($previousTwoDaysDate));
        $statement->bindValue(':current_date'          , $currentDate        , Helper::getPdoParameterType($currentDate        ));
        $statement->bindValue(':employee_id'           , $employeeId         , Helper::getPdoParameterType($employeeId         ));

        $statement->execute();

        $employeeRecordedWorkSchedules = $statement->fetchAll(PDO::FETCH_ASSOC);

        $recordedWorkSchedules = [];

        $recordedWorkSchedules[$previousTwoDaysDate] = [];
        $recordedWorkSchedules[$previousDate       ] = [];
        $recordedWorkSchedules[$currentDate        ] = [];

        foreach ($employeeRecordedWorkSchedules as $recordedWorkSchedule) {
            $date           = $recordedWorkSchedule['date'            ];
            $workScheduleId = $recordedWorkSchedule['work_schedule_id'];

            $recordedWorkSchedule['is_recorded'] = true;

            $recordedWorkSchedules[$date][$workScheduleId] = $recordedWorkSchedule;
        }

        $currentWorkSchedules = [];

        foreach ($workSchedules as $workSchedule) {
            $workScheduleDates = $workScheduleService->getRecurrenceDates(
                $workSchedule['recurrence_rule'],
                $previousTwoDaysDate            ,
                $currentDate
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

        foreach ($currentWorkSchedules as $date => $workSchedules) {
            foreach ($workSchedules as $workSchedule) {
                $workScheduleId = $workSchedule['id'];

                if ( ! isset($recordedWorkSchedules[$date][$workScheduleId])) {
                    $recordedWorkSchedules[$date][$workScheduleId] = $workSchedule;
                }
            }
        }

        foreach ($recordedWorkSchedules as $date => $workSchedules) {
            usort($workSchedules, fn($workScheduleA, $workScheduleB) =>
                $workScheduleA['start_time'] <=> $workScheduleB['start_time']
            );

            $recordedWorkSchedules[$date] = array_values($workSchedules);
        }

        $previousWorkScheduleEndDateTime = null;

        foreach ($recordedWorkSchedules as $date => $workSchedules) {
            foreach ($workSchedules as $index => $workSchedule) {
                $currentWorkScheduleStartTime = $workSchedule['start_time'];
                $currentWorkScheduleEndTime   = $workSchedule['end_time'  ];

                $currentWorkScheduleStartDateTime = new DateTime($date . ' ' . $currentWorkScheduleStartTime);
                $currentWorkScheduleEndDateTime   = new DateTime($date . ' ' . $currentWorkScheduleEndTime  );

                if ($currentWorkScheduleEndDateTime <= $currentWorkScheduleStartDateTime) {
                    $currentWorkScheduleEndDateTime->modify('+1 day');
                }

                $adjustedEarlyCheckInWindow = $earlyCheckInWindow;

                if ($previousWorkScheduleEndDateTime !== null) {
                    $adjustedCurrentWorkScheduleStartDateTime = (clone $currentWorkScheduleStartDateTime)
                        ->modify('-' . $earlyCheckInWindow . ' minutes');

                    if ($previousWorkScheduleEndDateTime > $adjustedCurrentWorkScheduleStartDateTime) {
                        $gapDuration          = $previousWorkScheduleEndDateTime->diff($currentWorkScheduleStartDateTime);
                        $gapDurationInMinutes = $gapDuration->h * 60 + $gapDuration->i;

                        $adjustedEarlyCheckInWindow = max(0, $gapDurationInMinutes);
                    }
                }

                if ( ! isset($workSchedule['is_recorded'])) {
                    $recordedWorkSchedules[$date][$index]['start_time'] = $currentWorkScheduleStartDateTime->format('Y-m-d H:i:s');
                    $recordedWorkSchedules[$date][$index]['end_time'  ] = $currentWorkScheduleEndDateTime  ->format('Y-m-d H:i:s');

                    $recordedWorkSchedules[$date][$index]['early_check_in_window'] = $adjustedEarlyCheckInWindow;
                }

                $previousWorkScheduleEndDateTime = clone $currentWorkScheduleEndDateTime;
            }
        }

        unset($recordedWorkSchedules[$previousTwoDaysDate]);

        $lastWorkSchedule = end($recordedWorkSchedules[$previousDate]);

        if ( ! empty($lastWorkSchedule)) {
            if ($lastWorkSchedule['end_time'] <= $lastWorkSchedule['start_time'] && $lastWorkSchedule['end_time'] !== '00:00:00') {
                $recordedWorkSchedules[$previousDate] = [$lastWorkSchedule];
            } else {
                unset($recordedWorkSchedules[$previousDate]);
            }
        }

        $lastWorkSchedule = end($recordedWorkSchedules[$currentDate]);

        if ( ! empty($lastWorkSchedule)) {
            if ($lastWorkSchedule['end_time'] <= $lastWorkSchedule['start_time'] && $lastWorkSchedule['end_time'] !== '00:00:00') {
                array_pop($recordedWorkSchedules[$currentDate]);
            }
        }

        foreach ($recordedWorkSchedules as $date => $workSchedules) {
            foreach ($workSchedules as $workSchedule) {
                if ( ! isset($workSchedule['is_recorded'])) {
                    $workScheduleSnapshot = new WorkScheduleSnapshot(
                        workScheduleId    : $workSchedule['id'                   ],
                        employeeId        : $workSchedule['employee_id'          ],
                        startTime         : $workSchedule['start_time'           ],
                        endTime           : $workSchedule['end_time'             ],
                        isFlextime        : $workSchedule['is_flextime'          ],
                        totalHoursPerWeek : $workSchedule['total_hours_per_week' ],
                        totalWorkHours    : $workSchedule['total_work_hours'     ],
                        startDate         : $workSchedule['start_date'           ],
                        recurrenceRule    : $workSchedule['recurrence_rule'      ],
                        gracePeriod       : $gracePeriod                          ,
                        earlyCheckInWindow: $workSchedule['early_check_in_window']
                    );

                    $workScheduleSnapshotId = $workScheduleService
                        ->createWorkScheduleSnapshot($workScheduleSnapshot);

                    if ($workScheduleSnapshotId === ActionResult::FAILURE) {
                        return [
                            'status'  => 'error',
                            'message' => 'An unexpected error occurred. Please try again later.'
                        ];
                    }

                    $attendanceStatus = 'Absent';

                    $emptyAttendanceRecord = new Attendance(
                        id                         : null                   ,
                        workScheduleSnapshotId     : $workScheduleSnapshotId,
                        date                       : $date                  ,
                        checkInTime                : null                   ,
                        checkOutTime               : null                   ,
                        totalBreakDurationInMinutes: 0                      ,
                        totalHoursWorked           : 0.00                   ,
                        lateCheckIn                : 0                      ,
                        earlyCheckOut              : 0                      ,
                        overtimeHours              : 0.00                   ,
                        isOvertimeApproved         : false                  ,
                        attendanceStatus           : $attendanceStatus      ,
                        remarks                    : null
                    );

                    $createEmptyAttendanceRecordResult = $attendanceRepository
                        ->createAttendance($emptyAttendanceRecord);

                    if ($createEmptyAttendanceRecordResult === ActionResult::FAILURE) {
                        return [
                            'status'  => 'error',
                            'message' => 'An unexpected error occurred. Please try again later.'
                        ];
                    }

                    $breakScheduleColumns = [
                        'id'                               ,
                        'break_type_id'                    ,
                        'start_time'                       ,
                        'end_time'                         ,

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
                            'value'    => $workSchedule['id']
                        ]
                    ];

                    $breakSchedules = $breakScheduleService->fetchAllBreakSchedules(
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

                    $breakSchedules =
                        ! empty($breakSchedules['result_set'])
                            ? $breakSchedules['result_set']
                            : [];

                    foreach ($breakSchedules as $breakSchedule) {
                        $breakTypeSnapshot = new BreakTypeSnapshot(
                            breakTypeId              : $breakSchedule['break_type_id'                    ],
                            name                     : $breakSchedule['break_type_name'                  ],
                            durationInMinutes        : $breakSchedule['break_type_duration_in_minutes'   ],
                            isPaid                   : $breakSchedule['break_type_is_paid'               ],
                            requireBreakInAndBreakOut: $breakSchedule['is_require_break_in_and_break_out']
                        );

                        $breakTypeSnapshotId = $breakTypeService
                            ->createBreakTypeSnapshot($breakTypeSnapshot);

                        if ($breakTypeSnapshotId === ActionResult::FAILURE) {
                            return [
                                'status'  => 'error',
                                'message' => 'An unexpected error occurred. Please try again later.'
                            ];
                        }

                        $breakScheduleSnapshot = new BreakScheduleSnapshot(
                            breakScheduleId       : $breakSchedule['id'        ],
                            workScheduleSnapshotId: $workScheduleSnapshotId     ,
                            breakTypeSnapshotId   : $breakTypeSnapshotId        ,
                            startTime             : $breakSchedule['start_time'],
                            endTime               : $breakSchedule['end_time'  ]
                        );

                        $breakScheduleSnapshotId = $breakScheduleService
                            ->createBreakScheduleSnapshot($breakScheduleSnapshot);

                        if ($breakScheduleSnapshotId === ActionResult::FAILURE) {
                            return [
                                'status'  => 'error',
                                'message' => 'An unexpected error occurred. Please try again later.'
                            ];
                        }

                        $emptyBreakRecord = new EmployeeBreak(
                            id                     : null                       ,
                            breakScheduleSnapshotId: $breakScheduleSnapshotId   ,
                            startTime              : null                       ,
                            endTime                : null                       ,
                            breakDurationInMinutes : 0                          ,
                            createdAt              : $workSchedule['start_time']
                        );

                        $createEmptyBreakRecordResult = $employeeBreakRepository
                            ->createEmployeeBreak($emptyBreakRecord);

                        if ($createEmptyBreakRecordResult === ActionResult::FAILURE) {
                            return [
                                'status'  => 'error',
                                'message' => 'An unexpected error occurred while checking in. Please try again later.'
                            ];
                        }
                    }
                }
            }
        }
    }

} catch (PDOException $exception) {
    return [
        'status'  => 'error',
        'message' => 'An unexpected error occurred. Please try again later.'
    ];
}

$payrollGroupColumns = [
    'id'                        ,
    'name'                      ,
    'payroll_frequency'         ,
    'day_of_weekly_cutoff'      ,
    'day_of_biweekly_cutoff'    ,
    'semi_monthly_first_cutoff' ,
    'semi_monthly_second_cutoff',
    'payday_offset'             ,
    'payday_adjustment'         ,
    'status'
];

$payrollGroupFilterCriteria = [
    [
        'column'   => 'payroll_group.status',
        'operator' => '='                   ,
        'value'    => 'Active'              ,
        'boolean'  => 'AND'
    ],
    [
        'column'   => 'payroll_group.day_of_weekly_cutoff',
        'operator' => '='                                 ,
        'value'    => $currentDayOfWeek                   ,
        'boolean'  => 'OR'
    ]
];

if ($currentWeekNumber % 2 === 0) {
    $payrollGroupFilterCriteria[] = [
        'column'   => 'payroll_group.day_of_biweekly_cutoff',
        'operator' => '='                                   ,
        'value'    => $currentDayOfWeek                     ,
        'boolean'  => 'OR'
    ];
}

$payrollGroupFilterCriteria[] = [
    'column'   => 'payroll_group.semi_monthly_first_cutoff',
    'operator' => 'IS NOT NULL'                            ,
    'boolean'  => 'OR'
];

$payrollGroupSortCriteria = [
    [
        'column' => 'payroll_group.payroll_frequency',
        'custom_order' => [
            'Weekly'      ,
            'Bi-weekly'   ,
            'Semi-monthly'
        ]
    ]
];

$payrollGroups = $payrollGroupService->fetchAllPayrollGroups(
    columns             : $payrollGroupColumns       ,
    filterCriteria      : $payrollGroupFilterCriteria,
    sortCriteria        : $payrollGroupSortCriteria  ,
    includeTotalRowCount: false
);

if ($payrollGroups === ActionResult::FAILURE) {
    return [
        'status'  => 'error',
        'message' => 'An unexpected error occurred. Please try again later.'
    ];
}

$payrollGroups =
    ! empty($payrollGroups['result_set'])
        ? $payrollGroups['result_set']
        : [];

if ( ! empty($payrollGroups)) {
    foreach ($payrollGroups as $payrollGroup) {
        $newPayrollGroup = new PayrollGroup(
            id                     : $payrollGroup['id'                        ],
            name                   : $payrollGroup['name'                      ],
            payrollFrequency       : $payrollGroup['payroll_frequency'         ],
            dayOfWeeklyCutoff      : $payrollGroup['day_of_weekly_cutoff'      ],
            dayOfBiweeklyCutoff    : $payrollGroup['day_of_biweekly_cutoff'    ],
            semiMonthlyFirstCutoff : $payrollGroup['semi_monthly_first_cutoff' ],
            semiMonthlySecondCutoff: $payrollGroup['semi_monthly_second_cutoff'],
            paydayOffset           : $payrollGroup['payday_offset'             ],
            paydayAdjustment       : $payrollGroup['payday_adjustment'         ],
            status                 : $payrollGroup['status'                    ]
        );

        $cutoffPeriodStartDate = null;
        $cutoffPeriodEndDate   = null;

        switch (strtolower($payrollGroup['payroll_frequency'])) {
            case 'weekly':
                if ($currentDayOfWeek === $payrollGroup['day_of_weekly_cutoff']) {
                    $cutoffPeriodStartDate = new DateTime($currentDate)  ;
                    $cutoffPeriodEndDate   = clone $cutoffPeriodStartDate;

                    $cutoffPeriodStartDate->modify('-6 days');
                }

                break;

            case 'bi-weekly':
                if ($currentDayOfWeek === $payrollGroup['day_of_biweekly_cutoff']) {
                    $cutoffPeriodStartDate = new DateTime($currentDate  );
                    $cutoffPeriodEndDate   = clone $cutoffPeriodStartDate;

                    $cutoffPeriodStartDate->modify('-13 days');
                }

                break;

            case 'semi-monthly':
                $firstCutoff  = $payrollGroup['semi_monthly_first_cutoff' ];
                $secondCutoff = $payrollGroup['semi_monthly_second_cutoff'];

                if ($currentDayOfMonth === $firstCutoff && ($currentDayOfMonth >= 1 && $currentDayOfMonth <= 15)) {
                    $cutoffPeriodStartDate = new DateTime($currentDate  );
                    $cutoffPeriodEndDate   = clone $cutoffPeriodStartDate;

                    if ($firstCutoff !== 15) {
                        $cutoffPeriodStartDate->modify('-1 month');
                    }

                    $numberOfDaysInMonth = (int) $cutoffPeriodStartDate->format('t');

                    if ($firstCutoff === 15 || $numberOfDaysInMonth <= $secondCutoff) {
                        $cutoffPeriodStartDate->modify('first day of this month');
                    } else {
                        $cutoffPeriodStartDate->modify('+16 days');
                    }

                } elseif (($currentDayOfMonth === $secondCutoff        && ($currentDayOfMonth >= 16 && $currentDayOfMonth <= 27)) ||
                         (($secondCutoff >= 28 && $secondCutoff <= 30) && ($currentDayOfMonth >= 28 && $currentDayOfMonth <= 31))) {

                    $cutoffPeriodStartDate = new DateTime($currentDate  );
                    $cutoffPeriodEndDate   = clone $cutoffPeriodStartDate;

                    $numberOfDaysInMonth = (int) $cutoffPeriodStartDate->format('t');

                    if ($firstCutoff === 15 || $numberOfDaysInMonth <= $secondCutoff) {
                        $cutoffPeriodEndDate->modify('last day of this month');
                    }

                    $cutoffPeriodStartDate->modify('first day of this month')
                        ->modify('+' . $firstCutoff . ' days');
                }

                break;
        }

        if ($cutoffPeriodStartDate !== null &&
            $cutoffPeriodEndDate   !== null) {

            $paydayDate = (clone $cutoffPeriodEndDate)
                ->modify('+' . $payrollGroup['payday_offset'] . ' days');

            if ($paydayDate->format('l') === 'Sunday') {
                switch (strtolower($payrollGroup['payday_adjustment'])) {
                    case 'on the saturday before':
                        if ($payrollGroup['payday_offset'] > 0) {
                            $paydayDate->modify('-1 day');
                        }

                        break;

                    case 'on the monday after':
                        $paydayDate->modify('+1 day');

                        break;
                }
            }

            $generatePayslipResult = $payslipService->generatePayslip(
                payrollGroup         : $newPayrollGroup                       ,
                cutoffPeriodStartDate: $cutoffPeriodStartDate->format('Y-m-d'),
                cutoffPeriodEndDate  : $cutoffPeriodEndDate  ->format('Y-m-d'),
                paydayDate           : $paydayDate           ->format('Y-m-d')
            );

            if ($generatePayslipResult === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }
        }
    }
}
