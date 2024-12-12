<?php

require_once __DIR__ . '/AttendanceService.php'                                 ;
require_once __DIR__ . '/../database/database.php'                              ;
require_once __DIR__ . '/../breaks/EmployeeBreakService.php'                    ;
require_once __DIR__ . '/../overtime-rates/OvertimeRateRepository.php'          ;
require_once __DIR__ . '/../overtime-rates/OvertimeRateAssignmentRepository.php';
require_once __DIR__ . '/../overtime-rates/OvertimeRateAssignment.php'          ;
require_once __DIR__ . '/../holidays/HolidayRepository.php'                     ;


$attendanceDao             = new AttendanceDao            ($pdo);
$employeeDao               = new EmployeeDao              ($pdo);
$leaveRequestDao           = new LeaveRequestDao          ($pdo);
$workScheduleDao           = new WorkScheduleDao          ($pdo);
$settingDao                = new SettingDao               ($pdo);
$breakScheduleDao          = new BreakScheduleDao         ($pdo);
$employeeBreakDao          = new EmployeeBreakDao         ($pdo);
$overtimeRateDao           = new OvertimeRateDao          ($pdo);
$holidayDao                = new HolidayDao               ($pdo);
$overtimeRateAssignmentDao = new OvertimeRateAssignmentDao($pdo, $overtimeRateDao);

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

$employeeId = 6;

$cutoffStartDate = '2024-12-02';
$cutoffEndDate   = '2024-12-10';

$cutoffStartDate = new DateTime($cutoffStartDate);
$cutoffEndDate   = new DateTime($cutoffEndDate);


if ($foundAbsence) {
    echo 'True';
} else {
    echo 'false';
}
