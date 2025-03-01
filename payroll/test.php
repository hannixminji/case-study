<?php

echo '<pre>';

require_once __DIR__ . '/../database/database.php';

require_once __DIR__ . '/PayrollGroup.php'        ;

require_once __DIR__ . '/PayslipService.php'      ;

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

$payrollGroup = new PayrollGroup(
    id                     : 1                    ,
    name                   : 'Weekly Payroll'     ,
    payrollFrequency       : 'Weekly'             ,
    dayOfWeeklyCutoff      : 6                    ,
    dayOfBiweeklyCutoff    : null                 ,
    semiMonthlyFirstCutoff : null                 ,
    semiMonthlySecondCutoff: null                 ,
    paydayOffset           : 0                    ,
    paydayAdjustment       : 'On the Monday after',
    status                 : 'active'
);

$generatePayslipResult = $payslipService->generatePayslip(
    payrollGroup         : $payrollGroup,
    cutoffPeriodStartDate: '2024-12-29' ,
    cutoffPeriodEndDate  : '2025-01-02' ,
    paydayDate           : '2025-01-04'
);

print_r($generatePayslipResult);
