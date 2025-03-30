<?php

echo '<pre>';

require_once __DIR__ . '/database/database.php'           ;

require_once __DIR__ . '/attendance/AttendanceService.php';
require_once __DIR__ . '/breaks/EmployeeBreakService.php' ;

$attendanceDao    = new AttendanceDao   ($pdo);
$employeeDao      = new EmployeeDao     ($pdo);
$holidayDao       = new HolidayDao      ($pdo);
$leaveRequestDao  = new LeaveRequestDao ($pdo);
$workScheduleDao  = new WorkScheduleDao ($pdo);
$settingDao       = new SettingDao      ($pdo);
$breakScheduleDao = new BreakScheduleDao($pdo);
$employeeBreakDao = new EmployeeBreakDao($pdo);
$breakTypeDao     = new BreakTypeDao    ($pdo);

$attendanceRepository    = new AttendanceRepository   ($attendanceDao   );
$employeeRepository      = new EmployeeRepository     ($employeeDao     );
$holidayRepository       = new HolidayRepository      ($holidayDao      );
$leaveRequestRepository  = new LeaveRequestRepository ($leaveRequestDao );
$workScheduleRepository  = new WorkScheduleRepository ($workScheduleDao );
$settingRepository       = new SettingRepository      ($settingDao      );
$breakScheduleRepository = new BreakScheduleRepository($breakScheduleDao);
$employeeBreakRepository = new EmployeeBreakRepository($employeeBreakDao);
$breakTypeRepository     = new BreakTypeRepository    ($breakTypeDao    );

$attendanceService = new AttendanceService(
    pdo                    : $pdo                    ,
    attendanceRepository   : $attendanceRepository   ,
    employeeRepository     : $employeeRepository     ,
    holidayRepository      : $holidayRepository      ,
    leaveRequestRepository : $leaveRequestRepository ,
    workScheduleRepository : $workScheduleRepository ,
    settingRepository      : $settingRepository      ,
    breakScheduleRepository: $breakScheduleRepository,
    employeeBreakRepository: $employeeBreakRepository,
    breakTypeRepository    : $breakTypeRepository
);

$employeeBreakService = new EmployeeBreakService(
    pdo                    : $pdo                    ,
    employeeBreakRepository: $employeeBreakRepository,
    employeeRepository     : $employeeRepository     ,
    attendanceRepository   : $attendanceRepository
);

$employeeRfidUid = '123456789';

$currentDateTime = '2025-03-29 17:00:00';

$attendanceResponse = $attendanceService->handleRfidTap($employeeRfidUid, $currentDateTime);

print_r($attendanceResponse);
