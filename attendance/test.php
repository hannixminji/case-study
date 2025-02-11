<?php

echo '<pre>';

require_once __DIR__ . '/Attendance.php';

require_once __DIR__ . '/AttendanceServices.php';
require_once __DIR__ . '/../database/database.php';
require_once __DIR__ . '/../breaks/EmployeeBreakService.php';
require_once __DIR__ . '/../overtime-rates/OvertimeRateRepository.php';
require_once __DIR__ . '/../overtime-rates/OvertimeRateAssignmentRepository.php';
require_once __DIR__ . '/../overtime-rates/OvertimeRateAssignment.php';
require_once __DIR__ . '/../holidays/HolidayRepository.php';

require_once __DIR__ . '/../leaves/LeaveEntitlement.php';
require_once __DIR__ . '/../leaves/LeaveEntitlementService.php';

require_once __DIR__ . '/../employment-type-benefits/EmploymentTypeBenefit.php';
require_once __DIR__ . '/../employment-type-benefits/EmploymentTypeBenefitService.php';

$employmentTypeBenefitDao = new EmploymentTypeBenefitDao($pdo);
$employmentTypeBenefitRepository = new EmploymentTypeBenefitRepository($employmentTypeBenefitDao);
$employmentTypeBenefitService = new EmploymentTypeBenefitService($employmentTypeBenefitRepository);

$employmentTypeBenefit = new EmploymentTypeBenefit(null, "Regular/Permanent", 1, null, null);

$employmentTypeBenefitService->createEmploymentTypeBenefit($employmentTypeBenefit);


$leaveEntitlementDao = new LeaveEntitlementDao($pdo);
$leaveEntitlementRepository = new LeaveEntitlementRepository($leaveEntitlementDao);
$leaveEntitlementService = new LeaveEntitlementService($leaveEntitlementRepository);

$newLeaveEntitlement = new LeaveEntitlement(
    id: null,
    employeeId: 6,
    leaveTypeId: 1,
    numberOfEntitledDays: 10,
    numberOfDaysTaken: 0,
    remainingDays: 10
);

//$assignResult = $leaveEntitlementService->createLeaveEntitlement($newLeaveEntitlement);

$attendanceDao    = new AttendanceDao($pdo);
$employeeDao      = new EmployeeDao($pdo);
$leaveRequestDao  = new LeaveRequestDao($pdo);
$settingDao       = new SettingDao($pdo);
$workScheduleDao  = new WorkScheduleDao($pdo, $settingDao);
$breakTypeDao = new BreakTypeDao($pdo);
$breakScheduleDao = new BreakScheduleDao($pdo, $workScheduleDao, $breakTypeDao);
$employeeBreakDao = new EmployeeBreakDao($pdo);
$overtimeRateDao = new OvertimeRateDao($pdo);
$overtimeRateAssignmentDao = new OvertimeRateAssignmentDao($pdo, $overtimeRateDao);
$holidayDao = new HolidayDao($pdo);

$attendanceRepository    = new AttendanceRepository($attendanceDao);
$employeeRepository      = new EmployeeRepository($employeeDao);
$leaveRequestRepository  = new LeaveRequestRepository($leaveRequestDao);
$workScheduleRepository  = new WorkScheduleRepository($workScheduleDao);
$settingRepository       = new SettingRepository($settingDao);
$breakScheduleRepository = new BreakScheduleRepository($breakScheduleDao);
$employeeBreakRepository = new EmployeeBreakRepository($employeeBreakDao);
$overtimeRateRepository = new OvertimeRateRepository($overtimeRateDao);
$overtimeRateAssignmentRepository = new OvertimeRateAssignmentRepository($overtimeRateAssignmentDao);
$holidayRepository = new HolidayRepository($holidayDao);

$attendanceService = new AttendanceService(
    $pdo,
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

$rfidUid = '123456789';
$currentDateTime = '2025-01-01 17:00:00';
$response = $attendanceService->handleRfidTap($rfidUid, $currentDateTime);
print_r($response);
/*
$currentDateTime = '2025-01-01 12:00:00';
$response = $attendanceService->handleRfidTap($rfidUid, $currentDateTime);

$currentDateTime = '2025-01-01 17:00:00';
$response = $attendanceService->handleRfidTap($rfidUid, $currentDateTime);

$response = $employeeBreakService->handleRfidTap('123456789', '2025-01-01 12:00:00');
print_r($response);

*/


/*
$attendance = new Attendance(
    id: 2,
    workScheduleId: 1,
    date: '2024-11-02',
    checkInTime: '2024-11-02 08:00:00',
    checkOutTime: '2024-11-02 17:00:00',
    totalBreakDurationInMinutes: 60,
    totalHoursWorked: 8.00,
    lateCheckIn: 0,
    earlyCheckOut: 0,
    overtimeHours: 0.00,
    isOvertimeApproved: null,
    attendanceStatus: 'Present',
    remarks: null
);

print_r($attendanceService->updateAttendance($attendance));

$currentDateTime = '2024-11-26 08:00:00';

$response = $attendanceService->handleRfidTap($rfidUid, $currentDateTime);

$response = $employeeBreakService->handleRfidTap($rfidUid, '2024-11-26 12:00:00');
$response = $employeeBreakService->handleRfidTap($rfidUid, '2024-11-26 12:30:00');

$currentDateTime = '2024-11-26 12:40:00';

$response = $attendanceService->handleRfidTap($rfidUid, $currentDateTime);


$currentDateTime = '2024-12-02 08:00:00';

$response = $attendanceService->handleRfidTap($rfidUid, $currentDateTime);

$currentDateTime = '2024-12-02 17:00:00';

$response = $attendanceService->handleRfidTap($rfidUid, $currentDateTime);
*/
