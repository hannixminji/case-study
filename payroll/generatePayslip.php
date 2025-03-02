<?php

require_once __DIR__ . '/../database/database.php'  ;

require_once __DIR__ . '/PayrollGroup.php'          ;

require_once __DIR__ . '/PayslipService.php'        ;

require_once __DIR__ . '/PayrollGroupRepository.php';

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

$payrollGroupDao        = new PayrollGroupDao       ($pdo                   );
$payrollGroupRepository = new PayrollGroupRepository($payrollGroupDao       );
$payrollGroupService    = new PayrollGroupService   ($payrollGroupRepository);

$currentDateTime   = (new DateTime())->modify('-1 day'     );
$currentDate       =       $currentDateTime->format('Y-m-d');
$currentDayOfMonth = (int) $currentDateTime->format('j'    );
$currentWeekNumber = (int) $currentDateTime->format('W'    );
$currentDayOfWeek  = (int) $currentDateTime->format('w'    );

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
                        $cutoffPeriodStartDate->modify('first day of next month');
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

            $paydayDate = (new DateTime($currentDate))
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
