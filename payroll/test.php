<?php

echo '<pre>';

require_once __DIR__ . '/../database/database.php';

require_once __DIR__ . '/PayrollGroup.php'        ;

require_once __DIR__ . '/PayslipService.php'      ;

$employeeDao   = new EmployeeDao  ($pdo);
$attendanceDao = new AttendanceDao($pdo);

$employeeRepository   = new EmployeeRepository  ($employeeDao  );
$attendanceRepository = new AttendanceRepository($attendanceDao);

$payslipService = new PayslipService(
    employeeRepository  : $employeeRepository  ,
    attendanceRepository: $attendanceRepository
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
    cutoffPeriodEndDate  : '2025-01-04' ,
    paydayDate           : '2025-01-04'
);

print_r($generatePayslipResult);
