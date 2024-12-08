<?php

require_once __DIR__ . '/AttendanceService.php';
require_once __DIR__ . '/../database/database.php';
require_once __DIR__ . '/../breaks/EmployeeBreakService.php';
require_once __DIR__ . '/../overtime-rates/OvertimeRateRepository.php';
require_once __DIR__ . '/../overtime-rates/OvertimeRateAssignmentRepository.php';
require_once __DIR__ . '/../overtime-rates/OvertimeRateAssignment.php';
require_once __DIR__ . '/../holidays/HolidayRepository.php';


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

$cutoffStartDate = '2024-11-26';
$cutoffEndDate = '2024-12-10';

$employeeColumns = [
    'id'           ,
    'job_title_id' ,
    'department_id',
    'hourly_rate'  ,
    'annual_salary'
];

$filterCriteria = [
    [
        'column'   => 'employee.access_role',
        'operator' => '!=',
        'value'    => "'Admin'"
    ],
    [
        'column'   => 'employee.payroll_group_id',
        'operator' => '=',
        'value'    => 1
    ],
];

$employees = $employeeRepository->fetchAllEmployees($employeeColumns, $filterCriteria);

if ($employees === ActionResult::FAILURE) {
    return [
        'status'  => 'error',
        'message' => 'An unexpected error occurred. Please try again later.'
    ];
}

$employees = $employees['result_set'];

foreach ($employees as $employee) {
    $employeeId   = $employee['id'           ];
    $jobTitleId   = $employee['job_title_id' ];
    $departmentId = $employee['department_id'];

    $workSchedules = $workScheduleRepository->getEmployeeWorkSchedules(
        $employeeId,
        '2024-11-26',
        '2024-12-10'
    );

    if ($workSchedules === ActionResult::FAILURE) {
        return [
            'status'  => 'error',
            'message' => 'An unexpected error occurred. Please try again later.'
        ];
    }

    if ($workSchedules === ActionResult::NO_WORK_SCHEDULE_FOUND) {
        return [
            'status'  => 'error',
            'message' => 'No schedule found'
        ];
    }

    $filterCriteria = [
        [
            'column'   => 'work_schedule.employee_id',
            'operator' => '=',
            'value'    => $employeeId
        ],
        [
            'column'   => 'attendance.date',
            'operator' => '>=',
            'value'    => '2024-11-26'
        ],
        [
            'column'   => 'attendance.date',
            'operator' => '<=',
            'value'    => '2024-12-10'
        ]
    ];

    $attendanceRecords = $attendanceRepository->fetchAllAttendance([], $filterCriteria);

    if ($attendanceRecords === ActionResult::FAILURE) {
        return [
            'status'  => 'error',
            'message' => 'An unexpected error occurred. Please try again later.'
        ];
    }

    $attendanceRecords = $attendanceRecords['result_set'];
    $records = [];

    foreach ($workSchedules as $date => $schedules) {
        foreach ($schedules as $workSchedule) {
            $matchingAttendanceRecords = array_filter($attendanceRecords, function ($attendanceRecord) use ($date, $workSchedule) {
                return $attendanceRecord['date'] === $date && $attendanceRecord['work_schedule_id'] === $workSchedule['id'];
            });

            if (empty($matchingAttendanceRecords)) {
                $records[$date][] = [
                    'work_schedule' => $workSchedule,
                    'attendance_records' => []
                ];
            } else {
                $records[$date][] = [
                    'work_schedule' => $workSchedule,
                    'attendance_records' => array_values($matchingAttendanceRecords)
                ];
            }
        }
    }

    $overtimeRateAssignment = new OvertimeRateAssignment(
        id          : null         ,
        departmentId: $departmentId,
        jobTitleId  : $jobTitleId  ,
        employeeId  : $employeeId
    );

    $overtimeRateAssignmentId = $overtimeRateAssignmentRepository->findId($overtimeRateAssignment);

    if ($overtimeRateAssignmentId === ActionResult::FAILURE) {
        return [
            'status'  => 'error',
            'message' => 'An unexpected error occurred. Please try again later.'
        ];
    }

    $overtimeRates = $overtimeRateRepository->fetchOvertimeRates($overtimeRateAssignmentId);

    if ($overtimeRates === ActionResult::FAILURE) {
        return [
            'status'  => 'error',
            'message' => 'An unexpected error occurred. Please try again later.'
        ];
    }

    $datesMarkedAsHoliday = $holidayRepository->getHolidayDatesForPeriod(
        $cutoffStartDate,
        $cutoffEndDate
    );

    if ($datesMarkedAsHoliday === ActionResult::FAILURE) {
        return [
            'status'  => 'error',
            'message' => 'An unexpected error occurred. Please try again later.'
        ];
    }

    $datesMarkedAsLeave = $leaveRequestRepository->getLeaveDatesForPeriod(
        $employeeId,
        $cutoffStartDate,
        $cutoffEndDate
    );

    if ($datesMarkedAsLeave === ActionResult::FAILURE) {
        return [
            'status'  => 'error',
            'message' => 'An unexpected error occurred. Please try again later.'
        ];
    }

    $hourSummary = [
        'regular_day' => [
            'non_holiday' => [
                'regular_hours'               => 0,
                'overtime_hours'              => 0,
                'night_differential'          => 0,
                'night_differential_overtime' => 0
            ],
            'special_holiday' => [
                'regular_hours'               => 0,
                'overtime_hours'              => 0,
                'night_differential'          => 0,
                'night_differential_overtime' => 0
            ],
            'regular_holiday' => [
                'regular_hours'               => 0,
                'overtime_hours'              => 0,
                'night_differential'          => 0,
                'night_differential_overtime' => 0
            ],
            'double_holiday' => [
                'regular_hours'               => 0,
                'overtime_hours'              => 0,
                'night_differential'          => 0,
                'night_differential_overtime' => 0
            ]
        ],
        'rest_day' => [
            'non_holiday' => [
                'regular_hours'               => 0,
                'overtime_hours'              => 0,
                'night_differential'          => 0,
                'night_differential_overtime' => 0
            ],
            'special_holiday' => [
                'regular_hours'               => 0,
                'overtime_hours'              => 0,
                'night_differential'          => 0,
                'night_differential_overtime' => 0
            ],
            'regular_holiday' => [
                'regular_hours'               => 0,
                'overtime_hours'              => 0,
                'night_differential'          => 0,
                'night_differential_overtime' => 0
            ],
            'double_holiday' => [
                'regular_hours'               => 0,
                'overtime_hours'              => 0,
                'night_differential'          => 0,
                'night_differential_overtime' => 0
            ]
        ]
    ];

}
