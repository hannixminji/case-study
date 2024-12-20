<?php

require_once __DIR__ . '/AttendanceService.php';
require_once __DIR__ . '/../database/database.php';
require_once __DIR__ . '/../breaks/EmployeeBreakService.php';
require_once __DIR__ . '/../overtime-rates/OvertimeRateRepository.php';
require_once __DIR__ . '/../overtime-rates/OvertimeRateAssignmentRepository.php';
require_once __DIR__ . '/../overtime-rates/OvertimeRateAssignment.php';
require_once __DIR__ . '/../holidays/HolidayRepository.php';
require_once __DIR__ . '/../allowances/EmployeeAllowanceRepository.php';
require_once __DIR__ . '/../deductions/EmployeeDeductionRepository.php';

require_once __DIR__ . '/../payroll/PayrollGroup.php';
require_once __DIR__ . '/../payroll/PayslipService.php';

require_once __DIR__ . '/../payroll/PayslipRepository.php'                                 ;
require_once __DIR__ . '/../payroll/EmployeeHourSummaryRepository.php'                     ;
require_once __DIR__ . '/../leaves/LeaveEntitlementRepository.php';

$attendanceDao    = new AttendanceDao($pdo);
$employeeDao      = new EmployeeDao($pdo);
$leaveRequestDao  = new LeaveRequestDao($pdo);
$workScheduleDao  = new WorkScheduleDao($pdo);
$settingDao       = new SettingDao($pdo);
$breakScheduleDao = new BreakScheduleDao($pdo);
$employeeBreakDao = new EmployeeBreakDao($pdo);
$overtimeRateDao = new OvertimeRateDao($pdo);
$overtimeRateAssignmentDao = new OvertimeRateAssignmentDao($pdo, $overtimeRateDao);
$holidayDao = new HolidayDao($pdo);
$employeeAllowanceDao = new EmployeeAllowanceDao($pdo);
$employeeDeductionDao = new EmployeeDeductionDao($pdo);
$payslipDao = new PayslipDao($pdo);
$employeeHourSummaryDao = new EmployeeHourSummaryDao($pdo);
$leaveEntitlementDao = new LeaveEntitlementDao($pdo);

$attendanceRepository    = new AttendanceRepository($attendanceDao);
$employeeRepository      = new EmployeeRepository($employeeDao);
$leaveRequestRepository  = new LeaveRequestRepository($leaveRequestDao);
$workScheduleRepository  = new WorkScheduleRepository($workScheduleDao);
$settingRepository       = new SettingRepository($settingDao);
$breakScheduleRepository = new BreakScheduleRepository($breakScheduleDao);
$employeeBreakRepository = new EmployeeBreakRepository($employeeBreakDao);
$overtimeRateRepository  = new OvertimeRateRepository($overtimeRateDao);
$overtimeRateAssignmentRepository = new OvertimeRateAssignmentRepository($overtimeRateAssignmentDao);
$holidayRepository = new HolidayRepository($holidayDao);
$employeeAllowanceRepository = new EmployeeAllowanceRepository($employeeAllowanceDao);
$employeeDeductionRepository = new EmployeeDeductionRepository($employeeDeductionDao);
$payslipRepository = new PayslipRepository($payslipDao);
$employeeHourSummaryRepository = new EmployeeHourSummaryRepository($employeeHourSummaryDao);
$leaveEntitlementRepository = new LeaveEntitlementRepository($leaveEntitlementDao);

$attendanceService = new AttendanceService(
    $attendanceRepository,
    $employeeRepository,
    $leaveRequestRepository,
    $workScheduleRepository,
    $settingRepository,
    $breakScheduleRepository,
    $employeeBreakRepository
);

$employeeBreakService = new EmployeeBreakService(
    $employeeBreakRepository,
    $employeeRepository,
    $attendanceRepository,
    $breakScheduleRepository
);

$payslipService = new PayslipService(
    $employeeRepository,
    $workScheduleRepository,
    $attendanceRepository,
    $overtimeRateAssignmentRepository,
    $overtimeRateRepository,
    $holidayRepository,
    $leaveRequestRepository,
    $employeeAllowanceRepository,
    $settingRepository,
    $employeeBreakRepository,
    $breakScheduleRepository,
    $employeeDeductionRepository,
    $payslipRepository,
    $employeeHourSummaryRepository,
    $leaveEntitlementRepository
);

$cutoffStartDate = '2024-11-01';
$cutoffEndDate   = '2024-12-07';

$payrollGroup = new PayrollGroup(
    1,
    'sds',
    'Monthly',
    'Active'
);

$payslipService = $payslipService->calculate($payrollGroup, $cutoffStartDate, $cutoffEndDate, '2024-12-10');
