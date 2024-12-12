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

//

        $payrollGroupId = 1;

        $cutoffStartDate = '2024-11-26';
        $cutoffEndDate   = '2024-12-10';

        $cutoffStartDate = new DateTime($cutoffStartDate);
        $cutoffEndDate   = new DateTime($cutoffEndDate  );

        $formattedCutoffStartDate = $cutoffStartDate->format('Y-m-d');
        $formattedCutoffEndDate   = $cutoffEndDate  ->format('Y-m-d');

        $employeeTableColumns = [
            'id'           ,
            'job_title_id' ,
            'department_id',
            'hourly_rate'
        ];

        $employeeFilterCriteria = [
            [
                'column'   => 'employee.access_role',
                'operator' => '!=',
                'value'    => 'Admin'
            ],
            [
                'column'   => 'employee.payroll_group_id',
                'operator' => '=',
                'value'    => $payrollGroupId
            ]
        ];

        $employees = $employeeRepository->fetchAllEmployees($employeeTableColumns, $employeeFilterCriteria);

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
            $hourlyRate   = $employee['hourly_rate'  ];

            $employeeWorkSchedules = $workScheduleRepository->getEmployeeWorkSchedules(
                $employeeId,
                $formattedCutoffStartDate,
                $formattedCutoffEndDate
            );

            if ($employeeWorkSchedules === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            if ($employeeWorkSchedules === ActionResult::NO_WORK_SCHEDULE_FOUND) {
                return [
                    'status'  => 'warning',
                    'message' => 'No schedule assigned.'
                ];
            }

            $attendanceTableColumns = [
            ];

            $attendanceFilterCriteria = [
                [
                    'column'   => 'work_schedule.employee_id',
                    'operator' => '=',
                    'value'    => $employeeId
                ],
                [
                    'column'   => 'attendance.date',
                    'operator' => '>=',
                    'value'    => $formattedCutoffStartDate
                ],
                [
                    'column'   => 'attendance.date',
                    'operator' => '<=',
                    'value'    => $formattedCutoffEndDate
                ]
            ];

            $employeeAttendanceRecords = $attendanceRepository->fetchAllAttendance($attendanceTableColumns, $attendanceFilterCriteria);

            if ($employeeAttendanceRecords === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $employeeAttendanceRecords = $employeeAttendanceRecords['result_set'];

            $attendanceRecords = [];

            foreach ($employeeWorkSchedules as $dateOfSchedule => $workSchedules) {
                foreach ($workSchedules as $workSchedule) {
                    $workScheduleAttendanceRecords = [];

                    foreach ($employeeAttendanceRecords as $attendanceRecord) {
                        if ($attendanceRecord['date'] === $dateOfSchedule && $attendanceRecord['work_schedule_id'] === $workSchedule['id']) {
                            $workScheduleAttendanceRecords[] = $attendanceRecord;
                        }
                    }

                    if ( ! empty($workScheduleAttendanceRecords)) {
                        $attendanceRecords[$dateOfSchedule][] = [
                            'work_schedule'      => $workSchedule,
                            'attendance_records' => $workScheduleAttendanceRecords
                        ];
                    } else {
                        $attendanceRecords[$dateOfSchedule][] = [
                            'work_schedule'      => $workSchedule,
                            'attendance_records' => []
                        ];
                    }
                }
            }

            $datesMarkedAsHoliday = $holidayRepository->getHolidayDatesForPeriod(
                $formattedCutoffStartDate,
                $formattedCutoffEndDate
            );

            if ($datesMarkedAsHoliday === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $datesMarkedAsLeave = $leaveRequestRepository->getLeaveDatesForPeriod(
                $employeeId,
                $formattedCutoffStartDate,
                $formattedCutoffEndDate
            );

            if ($datesMarkedAsLeave === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $actualHoursWorkedSummary = [
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

            foreach ($attendanceRecords as $currentDate => $records) {
                foreach ($records as $record) {
                    $workSchedule = $record['work_schedule'     ];
                    $attendance   = $record['attendance_records'];

                    $workScheduleId             = $workSchedule['id'              ];
                    $workScheduleTotalWorkHours = $workSchedule['total_work_hours'];

                    $workScheduleStartTime = (new DateTime($workSchedule['start_time']))->format('H:i:s');
                    $workScheduleEndTime   = (new DateTime($workSchedule['end_time'  ]))->format('H:i:s');

                    $workScheduleStartTime = new DateTime($currentDate . ' ' . $workScheduleStartTime);
                    $workScheduleEndTime   = new DateTime($currentDate . ' ' . $workScheduleEndTime  );

                    $formattedWorkScheduleStartTime = $workScheduleStartTime->format('Y-m-d H:i:s');
                    $formattedWorkScheduleEndTime   = $workScheduleEndTime  ->format('Y-m-d H:i:s');

                    if ($workScheduleEndTime->format('H:i:s') < $workScheduleStartTime->format('H:i:s')) {
                        $workScheduleEndTime->modify('+1 day');
                    }

                    if ( ! empty($attendance)) {
                        $employeeBreaks = $employeeBreakRepository->fetchOrderedEmployeeBreaks(
                            $workScheduleId,
                            $employeeId,
                            $formattedWorkScheduleStartTime,
                            $formattedWorkScheduleEndTime
                        );
                    }

                    //
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
        }


        $cutoffStartDate = '2024-11-26';
        $cutoffEndDate   = '2024-12-10';

        $cutoffStartDate = new DateTime($cutoffStartDate);
        $cutoffEndDate   = new DateTime($cutoffEndDate  );

        $formattedCutoffStartDate = $cutoffStartDate->format('Y-m-d');
        $formattedCutoffEndDate   = $cutoffEndDate  ->format('Y-m-d');

        $previousDate = clone $cutoffStartDate;
        $previousDate->modify('-1 day');

        $formattedPreviousDateTime = $previousDate->format('Y-m-d H:i:s');
        $formattedPreviousDate     = $previousDate->format('Y-m-d');

        $workSchedules = $workScheduleRepository->getEmployeeWorkSchedules(
            $employeeId,
            $formattedPreviousDate,
            $formattedPreviousDate
        );

        if ($workSchedules === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        if ($workSchedules === ActionResult::NO_WORK_SCHEDULE_FOUND) {
            
        }

        $datesMarkedAsHoliday = $holidayRepository->getHolidayDatesForPeriod(
            $formattedPreviousDate,
            $formattedPreviousDate
        );

        if ($datesMarkedAsHoliday === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $datesMarkedAsLeave = $leaveRequestRepository->getLeaveDatesForPeriod(
            $employeeId,
            $formattedPreviousDate,
            $formattedPreviousDate
        );

        if ($datesMarkedAsLeave === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $attendanceTableColumns = [
        ];

        $attendanceFilterCriteria = [
            [
                'column'   => 'work_schedule.employee_id',
                'operator' => '=',
                'value'    => $employeeId
            ],
            [
                'column'   => 'attendance.date',
                'operator' => '>=',
                'value'    => $formattedPreviousDate
            ],
            [
                'column'   => 'attendance.date',
                'operator' => '<=',
                'value'    => $formattedPreviousDate
            ]
        ];

        $employeeAttendanceRecords = $attendanceRepository->fetchAllAttendance($attendanceTableColumns, $attendanceFilterCriteria);

        if ($attendanceRecords === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $attendanceRecords = $attendanceRecords['result_set'];
