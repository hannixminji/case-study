<?php

require_once __DIR__ . '/../database/database.php'  ;
require_once __DIR__ . '/PayslipService.php'        ;
require_once __DIR__ . '/PayrollGroupRepository.php';

$payslipDao                = new PayslipDao               ($pdo                                   );
$employeeDao               = new EmployeeDao              ($pdo                                   );
$workScheduleDao           = new WorkScheduleDao          ($pdo                                   );
$attendanceDao             = new AttendanceDao            ($pdo                                   );
$holidayDao                = new HolidayDao               ($pdo                                   );
$leaveRequestDao           = new LeaveRequestDao          ($pdo                                   );
$breakScheduleDao          = new BreakScheduleDao         ($pdo                                   );
$employeeBreakDao          = new EmployeeBreakDao         ($pdo                                   );
$settingDao                = new SettingDao               ($pdo                                   );
$employeeAllowanceDao      = new EmployeeAllowanceDao     ($pdo                                   );
$employeeDeductionDao      = new EmployeeDeductionDao     ($pdo                                   );
$overtimeRateDao           = new OvertimeRateDao          ($pdo                                   );
$overtimeRateAssignmentDao = new OvertimeRateAssignmentDao($pdo, $overtimeRateDao);
$leaveEntitlementDao       = new LeaveEntitlementDao      ($pdo                                   );
$payrollGroupDao           = new PayrollGroupDao          ($pdo                                   );

$payslipRepository                = new PayslipRepository               ($payslipDao                              );
$employeeRepository               = new EmployeeRepository              ($employeeDao                            );
$workScheduleRepository           = new WorkScheduleRepository          ($workScheduleDao                    );
$attendanceRepository             = new AttendanceRepository            ($attendanceDao                        );
$holidayRepository                = new HolidayRepository               ($holidayDao                              );
$leaveRequestRepository           = new LeaveRequestRepository          ($leaveRequestDao                    );
$breakScheduleRepository          = new BreakScheduleRepository         ($breakScheduleDao                  );
$employeeBreakRepository          = new EmployeeBreakRepository         ($employeeBreakDao                  );
$settingRepository                = new SettingRepository               ($settingDao                              );
$employeeAllowanceRepository      = new EmployeeAllowanceRepository     ($employeeAllowanceDao          );
$employeeDeductionRepository      = new EmployeeDeductionRepository     ($employeeDeductionDao          );
$overtimeRateRepository           = new OvertimeRateRepository          ($overtimeRateDao                    );
$overtimeRateAssignmentRepository = new OvertimeRateAssignmentRepository($overtimeRateAssignmentDao);
$leaveEntitlementRepository       = new LeaveEntitlementRepository      ($leaveEntitlementDao            );
$payrollGroupRepository           = new PayrollGroupRepository          ($payrollGroupDao                    );

$payslipService = new PayslipService(
    payslipRepository               : $payslipRepository               ,
    employeeRepository              : $employeeRepository              ,
    workScheduleRepository          : $workScheduleRepository          ,
    attendanceRepository            : $attendanceRepository            ,
    holidayRepository               : $holidayRepository               ,
    leaveRequestRepository          : $leaveRequestRepository          ,
    breakScheduleRepository         : $breakScheduleRepository         ,
    employeeBreakRepository         : $employeeBreakRepository         ,
    settingRepository               : $settingRepository               ,
    employeeAllowanceRepository     : $employeeAllowanceRepository     ,
    employeeDeductionRepository     : $employeeDeductionRepository     ,
    overtimeRateRepository          : $overtimeRateRepository          ,
    overtimeRateAssignmentRepository: $overtimeRateAssignmentRepository,
    leaveEntitlementRepository      : $leaveEntitlementRepository
);

$currentDateTime         = new DateTime();
$currentDate             =       $currentDateTime->format('Y-m-d');
$currentWeekNumber       = (int) $currentDateTime->format('W'    );
$currentDayOfMonth       = (int) $currentDateTime->format('j'    );
$currentDayOfWeekNumeric = (int) $currentDateTime->format('w'    );

$payrollGroupColumns = [
    'id'                        ,
    'payroll_frequency'         ,
    'day_of_weekly_cutoff'      ,
    'day_of_biweekly_cutoff'    ,
    'semi_monthly_first_cutoff' ,
    'semi_monthly_second_cutoff',
    'pay_day_after_cutoff'
];

$payrollGroupFilterCriteria = [
    [
        'column'   => 'payroll_group.status',
        'operator' => '='                   ,
        'value'    => 'Active'              ,
        'boolean'  => 'AND'
    ]
];

$payrollGroupFilterCriteria[] = [
    'column'   => 'payroll_group.day_of_weekly_cutoff',
    'operator' => '='                                 ,
    'value'    => $currentDayOfWeekNumeric            ,
    'boolean'  => 'OR'
];

if ($currentWeekNumber % 2 === 0) {
    $payrollGroupFilterCriteria[] = [
        'column'   => 'payroll_group.day_of_biweekly_cutoff',
        'operator' => '='                                   ,
        'value'    => $currentDayOfWeekNumeric              ,
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

$payrollGroups = $payrollGroupRepository->fetchAllPayrollGroups(
    columns       : $payrollGroupColumns       ,
    filterCriteria: $payrollGroupFilterCriteria
);

if ($payrollGroups === ActionResult::FAILURE) {
    return [
        'status'  => 'error',
        'message' => 'An unexpected error occurred. Please try again later.'
    ];
}

$payrollGroups = $payrollGroups['result_set'];

if ( ! empty($payrollGroups) && (new DateTime)->format('H:i:s') === '23:59:00') {
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

        $cutoffStartDate = new DateTime($currentDate);
        $cutoffEndDate   = clone $cutoffStartDate              ;
        $paydayDate      = clone $cutoffStartDate              ;

        switch (strtolower($payrollGroup['payroll_frequency'])) {
            case 'weekly':
                $cutoffStartDate->modify('-6 days');
                $paydayDate->modify('+' . $payrollGroup['payday_offset'] . ' days');

                break;

            case 'bi-weekly':
                $cutoffStartDate->modify('-14 days');
                $paydayDate->modify('+' . $payrollGroup['payday_offset'] . ' days');

                break;

            case 'semi-monthly':
                $firstCutoff  = $payrollGroup['semi_monthly_first_cutoff' ];
                $secondCutoff = $payrollGroup['semi_monthly_second_cutoff'];

                break;

            default:
                return [
                    'status'  => 'error',
                    'message' => 'The payroll frequency "' . $payrollGroup['payroll_frequency'] . '" is not supported. ' .
                                 'Please use a valid option: weekly, bi-weekly, or semi-monthly.'
                ];
        }

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

        return $payslipService->generatePayslip(
            payrollGroup   : $newPayrollGroup,
            cutoffStartDate: $cutoffStartDate->format('Y-m-d'),
            cutoffEndDate  : $cutoffEndDate  ->format('Y-m-d'),
            paydayDate    : $paydayDate     ->format('Y-m-d')
        );
    }
}

                /*
                $firstCutoff  = $payrollGroup['semi_monthly_first_cutoff' ];
                $secondCutoff = $payrollGroup['semi_monthly_second_cutoff'];

                if ($currentDayOfMonth === $firstCutoff) {
                    if ($currentDayOfMonth >= 1 && $currentDayOfMonth <= 12) {
                    } elseif ($currentDayOfMonth >= 13 && $currentDayOfMonth <= 15) {
                    }

                } elseif ($currentDayOfMonth === $secondCutoff) {
                }


                $payrollGroup['semi_monthly_first_cutoff'] - 15 + 31

                15 and 30
                1st Cutoff:
                    1 to 15
                2nd Cutoff:
                    16 to 28
                    16 to 29
                    16 to 30
                    16 to 31

                14 and 29
                31 days
                    1st Cutoff:
                        30 to 14
                    2nd Cutoff:
                        15 to 29

                30 days
                    1st Cutoff:
                        30 to 14
                    2nd Cutoff:
                        15 to 29

                29 days
                    1st Cutoff:
                        1 to 14
                    2nd Cutoff:
                        15 to 29

                28 days
                    1st Cutoff:
                        1 to 14
                    2nd Cutoff:
                        15 to 28

                13 and 28
                31 days
                    1st Cutoff:
                        29 to 13
                    2nd Cutoff:
                        14 to 28

                30 days
                    1st Cutoff:
                        29 to 13
                    2nd Cutoff:
                        14 to 28

                29 days
                    1st Cutoff:
                        29 to 13
                    2nd Cutoff:
                        14 to 28

                28 days
                    1st Cutoff:
                        1 to 13
                    2nd Cutoff:
                        14 to 28

                12 and 27
                1st Cutoff:
                    28 to 12
                2nd Cutoff:
                    13 to 27

                11 and 26
                1st Cutoff:
                    27 to 11
                2nd Cutoff:
                    12 to 26

                10 and 25
                1st Cutoff:
                    26 to 10
                2nd Cutoff:
                    11 to 25

                9 and 24
                1st Cutoff:
                    25 to 9
                2nd Cutoff:
                    10 to 24

                8 and 23
                1st Cutoff:
                    24 to 8
                2nd Cutoff:
                    9 to 23

                7 and 22
                1st Cutoff:
                    23 to 7
                2nd Cutoff:
                    8 to 22

                6 and 21
                1st Cutoff:
                    22 to 6
                2nd Cutoff:
                    7 to 21

                5 and 20
                1st Cutoff:
                    21 to 5
                2nd Cutoff:
                    6 to 20

                4 and 19
                1st Cutoff:
                    20 to 4
                2nd Cutoff:
                    5 to 19

                3 and 18
                1st Cutoff:
                    19 to 3
                2nd Cutoff:
                    4 to 18

                2 and 17
                1st Cutoff:
                    18 to 2
                2nd Cutoff:
                    3 to 17

                1 and 16
                1st Cutoff:
                    17 to 1
                2nd Cutoff:
                    2 to 16
                */
