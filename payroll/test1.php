<?php

echo '<pre>';

require_once __DIR__ . '/../database/database.php'  ;

require_once __DIR__ . '/PayrollGroup.php'          ;

require_once __DIR__ . '/PayslipServices.php'       ;
require_once __DIR__ . '/PayrollGroupRepository.php';

$payslipDao                = new PayslipDao               ($pdo                                   );
$employeeDao               = new EmployeeDao              ($pdo                                   );
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

$payslipService = new PayslipServices(
    payslipRepository               : $payslipRepository               ,
    employeeRepository              : $employeeRepository              ,
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

$result = $payslipService->generatePayslip(
    payrollGroupId       : 1           ,
    payrollFrequency     : 'Weekly'    ,
    cutoffPeriodStartDate: '2025-01-01',
    cutoffPeriodEndDate  : '2025-01-31',
    paydayDate           : '2025-01-31'
);

print_r($result);
