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
    employeeBreakRepository: $employeeBreakRepository,
    employeeRepository     : $employeeRepository     ,
    attendanceRepository   : $attendanceRepository
);

// Dito mo lagay yung rfid uid ng employee, simulate lang para matest.
$employeeRfidUid = '123456789';

/*
    Dito mo babaguhin yung time, kailangan yung date na nasa database di dapat pababa,
    kunware 2025-01-01 nasa database kailangan 2025-01-01 onwards hindi 2024-12-31.
*/
$currentDateTime = '2025-01-01 08:00:00';

/*
    Kada tawag ng `handleRfidTap` dedetermine kung check in or check out, tingnan mo nalang
    sa database para malaman mo kung check in o check out.

    Ibig sabihin nyan check in yan
    check_in_time !== null && check_out_time !== null
    or
    check_in_time === null && check_out_time === null

    Ibig sabihin check out
    check_in_time !== null && check_out_time === null
*/
$attendanceResponse = $attendanceService->handleRfidTap($employeeRfidUid, $currentDateTime);

print_r($attendanceResponse);

//$breakResponse = $employeeBreakService->handleRfidTap($employeeRfidUid, $currentDateTime);
//print_r($breakResponse);
/*
$employeeRfidUid = '123456789';
$currentDateTime = '2025-01-01 08:00:00';

$attendanceResponse = $attendanceService->handleRfidTap($employeeRfidUid, $currentDateTime);

// Eto sa break in at break out
$breakResponse = $employeeBreakService->handleRfidTap($employeeRfidUid, $currentDateTime);
*/
