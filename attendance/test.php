<?php

require_once __DIR__ . '/AttendanceService.php';
require_once __DIR__ . '/../database/database.php';

$attendanceDao    = new AttendanceDao($pdo);
$employeeDao      = new EmployeeDao($pdo);
$leaveRequestDao  = new LeaveRequestDao($pdo);
$workScheduleDao  = new WorkScheduleDao($pdo);
$settingDao       = new SettingDao($pdo);
$breakScheduleDao = new BreakScheduleDao($pdo);
$employeeBreakDao = new EmployeeBreakDao($pdo);

$attendanceRepository    = new AttendanceRepository($attendanceDao);
$employeeRepository      = new EmployeeRepository($employeeDao);
$leaveRequestRepository  = new LeaveRequestRepository($leaveRequestDao);
$workScheduleRepository  = new WorkScheduleRepository($workScheduleDao);
$settingRepository       = new SettingRepository($settingDao);
$breakScheduleRepository = new BreakScheduleRepository($breakScheduleDao);
$employeeBreakRepository = new EmployeeBreakRepository($employeeBreakDao);

$attendanceService = new AttendanceService(
    $attendanceRepository,
    $employeeRepository,
    $leaveRequestRepository,
    $workScheduleRepository,
    $settingRepository,
    $breakScheduleRepository,
    $employeeBreakRepository
);

$rfidUid = '123456789';
$currentDateTime = '2024-11-25 22:00:00';

$response = $attendanceService->handleRfidTap($rfidUid, $currentDateTime);

$currentDateTime = '2024-11-26 00:00:00';

$response = $attendanceService->handleRfidTap($rfidUid, $currentDateTime);

echo '<pre>';
print_r($response);
echo '<pre>';
