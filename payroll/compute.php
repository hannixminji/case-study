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
        switch ($payrollGroup['pay_frequency']) {
            case 'Weekly':
                if ($currentDate >= $payrollGroup['start_date'] && (new DateTime($currentDate))->format('l') === (new DateTime($payrollGroup['start_date']))->format('l')) {
                    $payrollGroupModel = new PayrollGroup(
                        id               : $payrollGroup['id'                  ],
                        name             : $payrollGroup['name'                ],
                        payFrequency     : $payrollGroup['pay_frequency'       ],
                        startDate        : $payrollGroup['start_date'          ],
                        paydayAfterCutoff: $payrollGroup['pay_day_after_cutoff'],
                        status           : $payrollGroup['status'              ]
                    );

                    $cutoffStartDate = $payrollGroup['start_date'];
                    $payDate = '';

                    $result = $payslipService->calculate($payrollGroup, $cutoffStartDate, $currentDate, $payDate);
                }
                break;

            case 'Semi-Monthly':
                break;

            case 'Monthly':
                break;

            default:
                // Do nothing
        }

    }
}

/*
$cutoffStartDate = '2024-11-01';
$cutoffEndDate   = '2024-12-07';
$payDate = '2024-12-10';

$payrollGroup = new PayrollGroup(1, 'sds', 'Monthly', '2024-11-01', 2, 'Active');

$payslipService->calculate($payrollGroup, $cutoffStartDate, $cutoffEndDate, $payDate);


$cutoffDate = new DateTime('2024-11-01');
$weekday = $cutoffDate->format('l');
$currentDate = '2024-11-01';

$weekdays = [
    'Monday'    => 'MO',
    'Tuesday'   => 'TU',
    'Wednesday' => 'WE',
    'Thursday'  => 'TH',
    'Friday'    => 'FR',
    'Saturday'  => 'SA',
    'Sunday'    => 'SU'
];

$cutoffDates = new RRule([
    'FREQ' => 'WEEKLY',
    'DTSTART' => $currentDate,
    'COUNT' => 10,
    'BYDAY' => $weekdays[$weekday],
]);

$currentDateObj = new DateTime($currentDate);
$isCurrentDateInCutoffs = false;

foreach ($cutoffDates as $date) {
    if ($date->format('Y-m-d') == $currentDateObj->format('Y-m-d')) {
        $isCurrentDateInCutoffs = true;
        break;
    }
}

echo "RRule DTSTART (current date): " . $currentDate . "<br>";
echo $isCurrentDateInCutoffs ? 'Current date is in the cutoff dates.' : 'Current date is not in the cutoff dates.';
if ($currentDate >= $payrollGroup->getStartDate() && (new DateTime($currentDate))->format('l') === (new DateTime($payrollGroup->getStartDate()))->format('l')) {
    echo 'trddddue';
}
*/
