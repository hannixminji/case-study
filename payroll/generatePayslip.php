<?php

echo '<pre>';

require_once __DIR__ . '/../database/database.php'                  ;

require_once __DIR__ . '/../work-schedules/WorkScheduleSnapshot.php';
require_once __DIR__ . '/../breaks/BreakScheduleSnapshot.php'       ;
require_once __DIR__ . '/../breaks/BreakTypeSnapshot.php'           ;
require_once __DIR__ . '/PayrollGroup.php'                          ;

require_once __DIR__ . '/PayslipService.php'                        ;
require_once __DIR__ . '/../holidays/HolidayService.php'            ;
require_once __DIR__ . '/../settings/SettingService.php'            ;
require_once __DIR__ . '/../leaves/LeaveRequestService.php'         ;
require_once __DIR__ . '/../work-schedules/WorkScheduleService.php' ;
require_once __DIR__ . '/../breaks/BreakScheduleService.php'        ;
require_once __DIR__ . '/../breaks/BreakTypeService.php'            ;
require_once __DIR__ . '/PayrollGroupService.php'                   ;

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

$currentDateTime   = new DateTime()                            ;
$currentDateTime   = (clone $currentDateTime)->modify('-1 day');
$currentDate       =        $currentDateTime ->format('Y-m-d' );
$currentDayOfMonth = (int)  $currentDateTime ->format('j'     );
$currentWeekNumber = (int)  $currentDateTime ->format('W'     );
$currentDayOfWeek  = (int)  $currentDateTime ->format('w'     );

try {
    $datesMarkedAsHoliday = $holidayService->getHolidayDatesForPeriod(
        startDate: $currentDate,
        endDate  : $currentDate
    );

    if ($datesMarkedAsHoliday === ActionResult::FAILURE) {
        return [
            'status'  => 'error',
            'message' => 'An unexpected error occurred. Please try again later.'
        ];
    }

    $attendanceStatus = 'Absent';

    if ( ! empty($datesMarkedAsHoliday[$currentDate])) {
        $attendanceStatus = 'On Unpaid Holiday';

        foreach ($datesMarkedAsHoliday[$currentDate] as $holiday) {
            if ($holiday['is_paid']) {
                $attendanceStatus = 'On Paid Holiday';

                break;
            }
        }
    }

    $gracePeriod = (int) $settingRepository->fetchSettingValue(
        settingKey: 'grace_period' ,
        groupName : 'work_schedule'
    );

    if ($gracePeriod === ActionResult::FAILURE) {
        return [
            'status'  => 'error',
            'message' => 'An unexpected error occurred. Please try again later.'
        ];
    }

    $earlyCheckInWindow = (int) $settingRepository->fetchSettingValue(
        settingKey: 'minutes_can_check_in_before_shift',
        groupName : 'work_schedule'
    );

    if ($earlyCheckInWindow === ActionResult::FAILURE) {
        return [
            'status'  => 'error',
            'message' => 'An unexpected error occurred. Please try again later.'
        ];
    }

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
        AND
            NOT EXISTS (
                SELECT
                    1
                FROM
                    attendance AS attendance_record
                JOIN
                    work_schedule_snapshots AS work_schedule_snapshot
                ON
                    attendance_record.work_schedule_snapshot_id = work_schedule_snapshot.id
                WHERE
                    work_schedule_snapshot.work_schedule_id = work_schedule.id
                AND
                    attendance_record.deleted_at IS NULL
                AND
                    attendance_record.date = :current_date
            )
    ';

    $statement = $pdo->prepare($query);

    $statement->bindValue(':current_date', $currentDate);

    $statement->execute();

    $workSchedules = $statement->fetchAll(PDO::FETCH_ASSOC);

    $employeesWorkSchedules = [];

    foreach ($workSchedules as $workSchedule) {
        $employeesWorkSchedules[$workSchedule['employee_id']][] = $workSchedule;
    }

    foreach ($employeesWorkSchedules as $employeeId => $workSchedules) {
        $datesMarkedAsLeave = $leaveRequestService->getLeaveDatesForPeriod(
            employeeId: $employeeId ,
            startDate : $currentDate,
            endDate   : $currentDate
        );

        if ($datesMarkedAsLeave === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        if ($datesMarkedAsLeave[$currentDate]['is_leave']) {
            $attendanceStatus = 'On Unpaid Leave';

            if (   $datesMarkedAsLeave[$currentDate]['is_paid'    ] &&
                 ! $datesMarkedAsLeave[$currentDate]['is_half_day']) {

                $attendanceStatus = 'On Paid Leave';
            }
        }

        $currentWorkSchedules = [];

        foreach ($workSchedules as $workSchedule) {
            $workScheduleDates = $workScheduleService->getRecurrenceDates(
                $workSchedule['recurrence_rule'],
                $currentDate                    ,
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

        foreach ($currentWorkSchedules as $workSchedule) {
            $workScheduleSnapshot = new WorkScheduleSnapshot(
                workScheduleId    : $workSchedule['id'                  ],
                employeeId        : $workSchedule['employee_id'         ],
                startTime         : $workSchedule['start_time'          ],
                endTime           : $workSchedule['end_time'            ],
                isFlextime        : $workSchedule['is_flextime'         ],
                totalHoursPerWeek : $workSchedule['total_hours_per_week'],
                totalWorkHours    : $workSchedule['total_work_hours'    ],
                startDate         : $workSchedule['start_date'          ],
                recurrenceRule    : $workSchedule['recurrence_rule'     ],
                gracePeriod       : $gracePeriod                         ,
                earlyCheckInWindow: $earlyCheckInWindow
            );

            $workScheduleSnapshotId = $workScheduleService
                ->createWorkScheduleSnapshot($workScheduleSnapshot);

            if ($workScheduleSnapshotId === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
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

            /*
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
            */
        }
    }
}

function getCurrentWorkSchedule(
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

function getPreviousWorkSchedule(
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
