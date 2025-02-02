<?php

require_once __DIR__ . '/../database/database.php'  ;

require_once __DIR__ . '/PayrollGroup.php'          ;

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
$overtimeRateAssignmentDao = new OvertimeRateAssignmentDao($pdo, $overtimeRateDao                 );
$leaveEntitlementDao       = new LeaveEntitlementDao      ($pdo                                   );
$payrollGroupDao           = new PayrollGroupDao          ($pdo                                   );

$payslipRepository                = new PayslipRepository               ($payslipDao               );
$employeeRepository               = new EmployeeRepository              ($employeeDao              );
$workScheduleRepository           = new WorkScheduleRepository          ($workScheduleDao          );
$attendanceRepository             = new AttendanceRepository            ($attendanceDao            );
$holidayRepository                = new HolidayRepository               ($holidayDao               );
$leaveRequestRepository           = new LeaveRequestRepository          ($leaveRequestDao          );
$breakScheduleRepository          = new BreakScheduleRepository         ($breakScheduleDao         );
$employeeBreakRepository          = new EmployeeBreakRepository         ($employeeBreakDao         );
$settingRepository                = new SettingRepository               ($settingDao               );
$employeeAllowanceRepository      = new EmployeeAllowanceRepository     ($employeeAllowanceDao     );
$employeeDeductionRepository      = new EmployeeDeductionRepository     ($employeeDeductionDao     );
$overtimeRateRepository           = new OvertimeRateRepository          ($overtimeRateDao          );
$overtimeRateAssignmentRepository = new OvertimeRateAssignmentRepository($overtimeRateAssignmentDao);
$leaveEntitlementRepository       = new LeaveEntitlementRepository      ($leaveEntitlementDao      );
$payrollGroupRepository           = new PayrollGroupRepository          ($payrollGroupDao          );

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

$payrollGroup = new PayrollGroup(
    id: 1,
    name: 'Monthly Payroll',
    payrollFrequency: 'semi-monthly',
    dayOfWeeklyCutoff: null,
    dayOfBiweeklyCutoff: null,
    semiMonthlyFirstCutoff: '10',
    semiMonthlySecondCutoff: '25',
    paydayOffset: 5,
    paydayAdjustment: 'payday remains on the weekend',
    status: 'active'
);

echo '<pre>';
$result = $payslipService->generatePayslip(
    payrollGroup         : $payrollGroup,
    cutoffPeriodStartDate: '2025-01-01',
    cutoffPeriodEndDate  : '2025-01-31',
    paydayDate           : '2025-01-31'
);

print_r($result);
