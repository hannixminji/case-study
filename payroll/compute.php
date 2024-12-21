<?php

require_once __DIR__ . '/../attendance/AttendanceService.php'                   ;
require_once __DIR__ . '/../database/database.php'                              ;
require_once __DIR__ . '/../breaks/EmployeeBreakService.php'                    ;
require_once __DIR__ . '/../overtime-rates/OvertimeRateRepository.php'          ;
require_once __DIR__ . '/../overtime-rates/OvertimeRateAssignmentRepository.php';
require_once __DIR__ . '/../overtime-rates/OvertimeRateAssignment.php'          ;
require_once __DIR__ . '/../holidays/HolidayRepository.php'                     ;
require_once __DIR__ . '/../allowances/EmployeeAllowanceRepository.php'         ;
require_once __DIR__ . '/../deductions/EmployeeDeductionRepository.php'         ;
require_once __DIR__ . '/PayrollGroup.php'                                      ;
require_once __DIR__ . '/PayslipService.php'                                    ;
require_once __DIR__ . '/PayslipRepository.php'                                 ;
require_once __DIR__ . '/EmployeeHourSummaryRepository.php'                     ;
require_once __DIR__ . '/../leaves/LeaveEntitlementRepository.php'              ;
require_once __DIR__ . '/PayrollGroupRepository.php'                            ;

$attendanceDao             = new AttendanceDao            ($pdo                                   );
$employeeDao               = new EmployeeDao              ($pdo                                   );
$leaveRequestDao           = new LeaveRequestDao          ($pdo                                   );
$workScheduleDao           = new WorkScheduleDao          ($pdo                                   );
$settingDao                = new SettingDao               ($pdo                                   );
$breakScheduleDao          = new BreakScheduleDao         ($pdo                                   );
$employeeBreakDao          = new EmployeeBreakDao         ($pdo                                   );
$overtimeRateDao           = new OvertimeRateDao          ($pdo                                   );
$overtimeRateAssignmentDao = new OvertimeRateAssignmentDao($pdo, $overtimeRateDao);
$holidayDao                = new HolidayDao               ($pdo                                   );
$employeeAllowanceDao      = new EmployeeAllowanceDao     ($pdo                                   );
$employeeDeductionDao      = new EmployeeDeductionDao     ($pdo                                   );
$payslipDao                = new PayslipDao               ($pdo                                   );
$employeeHourSummaryDao    = new EmployeeHourSummaryDao   ($pdo                                   );
$leaveEntitlementDao       = new LeaveEntitlementDao      ($pdo                                   );
$payrollGroupDao           = new PayrollGroupDao          ($pdo                                   );

$attendanceRepository             = new AttendanceRepository            ($attendanceDao                        );
$employeeRepository               = new EmployeeRepository              ($employeeDao                            );
$leaveRequestRepository           = new LeaveRequestRepository          ($leaveRequestDao                    );
$workScheduleRepository           = new WorkScheduleRepository          ($workScheduleDao                    );
$settingRepository                = new SettingRepository               ($settingDao                              );
$breakScheduleRepository          = new BreakScheduleRepository         ($breakScheduleDao                  );
$employeeBreakRepository          = new EmployeeBreakRepository         ($employeeBreakDao                  );
$overtimeRateRepository           = new OvertimeRateRepository          ($overtimeRateDao                    );
$overtimeRateAssignmentRepository = new OvertimeRateAssignmentRepository($overtimeRateAssignmentDao);
$holidayRepository                = new HolidayRepository               ($holidayDao                              );
$employeeAllowanceRepository      = new EmployeeAllowanceRepository     ($employeeAllowanceDao          );
$employeeDeductionRepository      = new EmployeeDeductionRepository     ($employeeDeductionDao          );
$payslipRepository                = new PayslipRepository               ($payslipDao                              );
$employeeHourSummaryRepository    = new EmployeeHourSummaryRepository   ($employeeHourSummaryDao      );
$leaveEntitlementRepository       = new LeaveEntitlementRepository      ($leaveEntitlementDao            );
$payrollGroupRepository           = new PayrollGroupRepository          ($payrollGroupDao                    );

$payslipService = new PayslipService(
    employeeRepository              : $employeeRepository              ,
    workScheduleRepository          : $workScheduleRepository          ,
    attendanceRepository            : $attendanceRepository            ,
    overtimeRateAssignmentRepository: $overtimeRateAssignmentRepository,
    overtimeRateRepository          : $overtimeRateRepository          ,
    holidayRepository               : $holidayRepository               ,
    leaveRequestRepository          : $leaveRequestRepository          ,
    employeeAllowanceRepository     : $employeeAllowanceRepository     ,
    settingRepository               : $settingRepository               ,
    employeeBreakRepository         : $employeeBreakRepository         ,
    breakScheduleRepository         : $breakScheduleRepository         ,
    employeeDeductionRepository     : $employeeDeductionRepository     ,
    payslipRepository               : $payslipRepository               ,
    employeeHourSummaryRepository   : $employeeHourSummaryRepository   ,
    leaveEntitlementRepository      : $leaveEntitlementRepository
);

$payrollGroupTableColumns = [
    'id'                  ,
    'name'                ,
    'pay_frequency'       ,
    'start_date'          ,
    'pay_day_after_cutoff',
    'status'
];

$payrollGroupFilterCriteria = [
    [
        'column'   => 'payroll_group.status',
        'operator' => '='                   ,
        'value'    => 'Active'
    ]
];

$payrollGroups = $payrollGroupRepository->fetchAllPayrollGroups($payrollGroupTableColumns, $payrollGroupFilterCriteria);

if ($payrollGroups === ActionResult::FAILURE) {
    return [
        'status'  => 'error',
        'message' => 'An unexpected error occurred. Please try again later.'
    ];
}

$payrollGroups = $payrollGroups['result_set'];

// $currentDate = (new DateTime())->format('Y-m-d');
$currentDate = '2024-11-01';

if ( ! empty($payrollGroups)) {
    foreach ($payrollGroups as $payrollGroup) {
        $payrollGroupModel = new PayrollGroup(
            id               : $payrollGroup['id'                  ],
            name             : $payrollGroup['name'                ],
            payFrequency     : $payrollGroup['pay_frequency'       ],
            startDate        : $payrollGroup['start_date'          ],
            paydayAfterCutoff: $payrollGroup['pay_day_after_cutoff'],
            status           : $payrollGroup['status'              ]
        );

        switch ($payrollGroup['pay_frequency']) {
            case 'Weekly':
                if ($currentDate >= $payrollGroup['start_date'] && (new DateTime($currentDate))->format('l') === (new DateTime($payrollGroup['start_date']))->format('l')) {
                    $payrollGroupStartDate = new DateTime($payrollGroup['start_date']);

                    $cutoffStartDate = clone $payrollGroupStartDate;
                    $cutoffStartDate->modify('-6 days');

                    $payDate = clone new DateTime($currentDate);
                    $payDate->modify("+{$payrollGroup['pay_day_after_cutoff']} days");

                    $result = $payslipService->calculate($payrollGroup, $cutoffStartDate->format('Y-m-d'), $currentDate, $payDate->format('Y-m-d'));
                }

                break;

            case 'Semi-Monthly':
                $currentDay = (int) (new DateTime($currentDate))->format('d');
                $dayOfStartDate = (int) (new DateTime($payrollGroup['start_date']))->format('d');

                $firstCutoff  = 0;
                $secondCutoff = 0;

                if ($dayOfStartDate <= 15) {
                    $firstCutoff  = $dayOfStartDate;
                    $secondCutoff = $dayOfStartDate + 15;
                } else {
                    $firstCutoff  = $dayOfStartDate - 15;
                    $secondCutoff = $dayOfStartDate;
                }

                $cutoffStartDay = 0;
                $cutoffEndDay   = 0;

                if ($currentDate >= $payrollGroup['start_date']) {
                    if ($dayOfStartDate <= 12 && $currentDay === $firstCutoff) {
                        $cutoffStartDay = $secondCutoff + 1;
                        $cutoffEndDay   = $firstCutoff    ;
                    } elseif ($dayOfStartDate <= 12 && $currentDay === $secondCutoff) {
                        $cutoffStartDay = $firstCutoff + 1;
                        $cutoffEndDay   = $secondCutoff   ;
                    } elseif ($dayOfStartDate === 13 && $currentDay === $firstCutoff) {
                        if ( (int) (new DateTime($currentDate))->format('m') === 3) {
                            $lastDayOfFebruary = clone new DateTime($currentDate);
                            $lastDayOfFebruary->modify('last day of previous month');
                            $lastDayOfFebruary = (int) $lastDayOfFebruary->format('d');

                            if ($lastDayOfFebruary === 28) {
                                $cutoffStartDay = 1           ;
                                $cutoffEndDay   = $firstCutoff;
                            } else {
                                $cutoffStartDay = $secondCutoff + 1;
                                $cutoffEndDay   = $firstCutoff     ;
                            }
                        } else {
                            $cutoffStartDay = $secondCutoff + 1;
                            $cutoffEndDay   = $firstCutoff     ;
                        }
                    } elseif ($dayOfStartDate === 13 && $currentDay === $secondCutoff) {
                        $cutoffStartDay = $firstCutoff + 1;
                        $cutoffEndDay   = $secondCutoff   ;
                    } elseif ($dayOfStartDate === 14 && $currentDay === $firstCutoff) {
                        if ( (int) (new DateTime($currentDate))->format('m') === 3) {
                            $lastDayOfFebruary = clone new DateTime($currentDate);
                            $lastDayOfFebruary->modify('last day of previous month');
                            $lastDayOfFebruary = (int) $lastDayOfFebruary->format('d');

                            if ($lastDayOfFebruary === 28) {
                                $cutoffStartDay = 1           ;
                                $cutoffEndDay   = $firstCutoff;
                            } else {
                                $cutoffStartDay = $secondCutoff + 1;
                                $cutoffEndDay   = $firstCutoff     ;
                            }
                        } else {
                            $cutoffStartDay = $secondCutoff + 1;
                            $cutoffEndDay   = $firstCutoff     ;
                        }
                    } elseif ($dayOfStartDate === 14 && $currentDay === $secondCutoff ||
                            ($dayOfStartDate === 14 && (int) (new DateTime($currentDate))->format('m') === 2)) {
                        if ( (int) (new DateTime($currentDate))->format('m') === 2) {
                            $lastDayOfFebruary = clone new DateTime($currentDate);
                            $lastDayOfFebruary->modify('last day of previous month');
                            $lastDayOfFebruary = (int) $lastDayOfFebruary->format('d');

                            if ($lastDayOfFebruary === 28) {
                                $cutoffStartDay = $firstCutoff + 1;
                                $cutoffEndDay   = 28;
                            } else {
                                $cutoffStartDay = $firstCutoff + 1;
                                $cutoffEndDay   = 29;
                            }
                        } else {
                            $cutoffStartDay = $firstCutoff + 1;
                            $cutoffEndDay   = $secondCutoff   ;
                        }
                    } elseif ($dayOfStartDate === 15 && $currentDay === $firstCutoff) {
                        $cutoffStartDay = 1;
                        $cutoffEndDay   = $firstCutoff;
                    } elseif ($dayOfStartDate === 15) {
                        $lastDayOfCurrentMonth = (int) (new DateTime($currentDate))->modify('last day of this month')->format('d');

                        if ($currentDay === $lastDayOfCurrentMonth) {
                            $cutoffStartDay = $firstCutoff + 1;
                            $cutoffEndDay   =  $lastDayOfCurrentMonth;
                        }
                    } elseif ($dayOfStartDate >= 16) {
                        if ($currentDay === $firstCutoff) {
                            $cutoffStartDay = $secondCutoff + 1;
                            $cutoffEndDay   = $firstCutoff     ;
                        } else {
                            $cutoffStartDay = $firstCutoff + 1;
                            $cutoffEndDay   = $secondCutoff   ;
                        }
                    }
                }

                break;

            case 'Monthly':
                break;

            default:
                // Do nothing
        }

    }
}

/*
<?php

$currentDate = '2022-03-31';
$payrollGroup = [
    'start_date' => '2022-01-15'
];

$currentDay = (int) (new DateTime($currentDate))->format('d');
$dayOfStartDate = (int) (new DateTime($payrollGroup['start_date']))->format('d');

$firstCutoff  = 0;
$secondCutoff = 0;

if ($dayOfStartDate <= 15) {
    $firstCutoff  = $dayOfStartDate;
    $secondCutoff = $dayOfStartDate + 15;
} else {
    $firstCutoff  = $dayOfStartDate - 15;
    $secondCutoff = $dayOfStartDate;
}

$cutoffStartDay = 0;
$cutoffEndDay   = 0;

if ($currentDate >= $payrollGroup['start_date']) {
    if ($dayOfStartDate <= 12 && $currentDay === $firstCutoff) {
        $cutoffStartDay = $secondCutoff + 1;
        $cutoffEndDay   = $firstCutoff    ;
    } elseif ($dayOfStartDate <= 12 && $currentDay === $secondCutoff) {
        $cutoffStartDay = $firstCutoff + 1;
        $cutoffEndDay   = $secondCutoff   ;
    } elseif ($dayOfStartDate === 13 && $currentDay === $firstCutoff) {
        if ( (int) (new DateTime($currentDate))->format('m') === 3) {
            $lastDayOfFebruary = clone new DateTime($currentDate);
            $lastDayOfFebruary->modify('last day of previous month');
            $lastDayOfFebruary = (int) $lastDayOfFebruary->format('d');

            if ($lastDayOfFebruary === 28) {
                $cutoffStartDay = 1           ;
                $cutoffEndDay   = $firstCutoff;
            } else {
                $cutoffStartDay = $secondCutoff + 1;
                $cutoffEndDay   = $firstCutoff     ;
            }
        } else {
            $cutoffStartDay = $secondCutoff + 1;
            $cutoffEndDay   = $firstCutoff     ;
        }
    } elseif ($dayOfStartDate === 13 && $currentDay === $secondCutoff) {
        $cutoffStartDay = $firstCutoff + 1;
        $cutoffEndDay   = $secondCutoff   ;
    } elseif ($dayOfStartDate === 14 && $currentDay === $firstCutoff) {
        if ( (int) (new DateTime($currentDate))->format('m') === 3) {
            $lastDayOfFebruary = clone new DateTime($currentDate);
            $lastDayOfFebruary->modify('last day of previous month');
            $lastDayOfFebruary = (int) $lastDayOfFebruary->format('d');

            if ($lastDayOfFebruary === 28) {
                $cutoffStartDay = 1           ;
                $cutoffEndDay   = $firstCutoff;
            } else {
                $cutoffStartDay = $secondCutoff + 1;
                $cutoffEndDay   = $firstCutoff     ;
            }
        } else {
            $cutoffStartDay = $secondCutoff + 1;
            $cutoffEndDay   = $firstCutoff     ;
        }
    } elseif ($dayOfStartDate === 14 && $currentDay === $secondCutoff ||
             ($dayOfStartDate === 14 && (int) (new DateTime($currentDate))->format('m') === 2)) {
        if ( (int) (new DateTime($currentDate))->format('m') === 2) {
            $lastDayOfFebruary = clone new DateTime($currentDate);
            $lastDayOfFebruary = (int) $lastDayOfFebruary->format('d');

            if ($lastDayOfFebruary === 28) {
                $cutoffStartDay = $firstCutoff + 1;
                $cutoffEndDay   = 28;
            } else {
                $cutoffStartDay = $firstCutoff + 1;
                $cutoffEndDay   = 29;
            }
        } else {
            $cutoffStartDay = $firstCutoff + 1;
            $cutoffEndDay   = $secondCutoff   ;
        }
    } elseif ($dayOfStartDate === 15 && $currentDay === $firstCutoff) {
        $cutoffStartDay = 1;
        $cutoffEndDay   = $firstCutoff;
    } elseif ($dayOfStartDate === 15) {
        $lastDayOfCurrentMonth = (int) (new DateTime($currentDate))->modify('last day of this month')->format('d');
        echo $lastDayOfCurrentMonth;
        if ($currentDay === $lastDayOfCurrentMonth) {
            $cutoffStartDay = $firstCutoff + 1;
            $cutoffEndDay   =  $lastDayOfCurrentMonth;
        }
    }
}

echo "First Cutoff: $firstCutoff\n";
echo "Second Cutoff: $secondCutoff\n";
echo "Cutoff Start Day: $cutoffStartDay\n";
echo "Cutoff End Day: $cutoffEndDay\n";

*/

/*
                    if ($dayOfStartDate <= 15 && $secondCutoff === 30) {
                        if ($currentDay === 28 || $currentDay === 29 || $currentDay === 30) {
                            $cutoffStartDay = $firstCutoff - 15 + 31;
                            $cutoffEndDay   = $currentDay           ;
                        }
                    }

if ($currentDate >= $payrollGroup['start_date'] && ($currentDay === $firstCutoff || $currentDay === $secondCutoff)) {

                }

                if ($currentDate >= $payrollGroup['start_date'] &&
                   ($currentDay === $dayOfStartDate || $currentDay === ($dayOfStartDate + 15 > 30 ? $dayOfStartDate + 15 - 30 : $dayOfStartDate + 15))
                ) {

                    if ($currentDay === $dayOfStartDate) {
                        if ($currentDay <= 13) {
                            $cutoffStartDate = $currentDay - 15 + 31;
                            $cutoffEndDate = $currentDay;
                        } elseif ($currentDay <= 31) {
                            if ($dayOfStartDate === 29) {

                            }

                        }
                    }
                }
                */


