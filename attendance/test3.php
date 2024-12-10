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
                'value'    => "'Admin'"
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

        /*
        foreach ($attendanceRecords as $attendanceRecord) {
            $workScheduleStartTime = new DateTime($workSchedule['start_time']);
            $workScheduleEndTime = new DateTime($workSchedule['end_time']);
            $workScheduleStartTime = new DateTime($attendanceRecord['date'] . ' ' . (new DateTime($workSchedule['start_time']))->format('H:i:s'));
            $workScheduleEndTime = new DateTime($attendanceRecord['date'] . ' ' . (new DateTime($workSchedule['end_time']))->format('H:i:s'));
            $workScheduleStartTimeDate = new DateTime($workScheduleStartTime->format('Y-m-d'));

            if ($workScheduleEndTime->format('H:i:s') < $workScheduleStartTime->format('H:i:s')) {
                $workScheduleEndTime->modify('+1 day');
            }

            $attendanceCheckInTime = new DateTime($attendanceRecord['check_in_time']);
            $attendanceCheckOutTime = $attendanceRecord['check_out_time']
                ? new DateTime($attendanceRecord['check_out_time'])
                : $workScheduleEndTime;

            if ( ! $workSchedule['is_flextime']) {
                if ($attendanceCheckInTime <= $workScheduleStartTime) {
                    $attendanceCheckInTime = $workScheduleStartTime;
                }

                $gracePeriod = (int) $settingRepository->fetchSettingValue('grace_period', 'work_schedule');

                if ($gracePeriod === ActionResult::FAILURE) {
                    return [
                        'status' => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $adjustedStartTime = (clone $workScheduleStartTime)->modify("+{$gracePeriod} minutes");

                if ($attendanceCheckInTime <= $adjustedStartTime) {
                    $attendanceCheckInTime = $workScheduleStartTime;
                } else {
                    $lateByMinutes = $attendanceCheckInTime->diff($adjustedStartTime)->i;
                    $attendanceCheckInTime = (clone $workScheduleStartTime)->modify("+{$lateByMinutes} minutes");
                }
            }

            $isOvertimeApproved = $attendanceRecord['is_overtime_approved'];
            $isOvertimeApproved = 1;
            echo 'Attendance: <br>';
            echo $attendanceCheckInTime->format('Y-m-d H:i:s') . '<br>';
            echo $attendanceCheckOutTime->format('Y-m-d H:i:s') . '<br>';
            $startMinutes = (int)$attendanceCheckInTime->format('i');
            $cloneAttendanceCheckInTime = clone $attendanceCheckInTime;
            if ($startMinutes > 0) {
                $remainingMinutes = 60 - $startMinutes;
                $hour = (int)$attendanceCheckInTime->format('H');
                $date = $attendanceCheckInTime->format('Y-m-d');
                $isNightShift = ($hour >= 22 || $hour < 6);

                $dayOfWeek = (new DateTime($date))->format('l');
                $dayType = $dayOfWeek === 'Sunday' ? 'rest_day' : 'regular_day';

                $isHoliday = !empty($datesMarkedAsHoliday[$date]);
                $holidayType = 'non_holiday';

                if ($isHoliday) {
                    if (count($datesMarkedAsHoliday[$date]) > 1) {
                        $holidayType = 'double_holiday';
                    } elseif ($datesMarkedAsHoliday[$date]['is_paid']) {
                        $holidayType = 'regular_holiday';
                    } else {
                        $holidayType = 'special_holiday';
                    }
                }

                $hoursWorked += $remainingMinutes / 60;
                if ($isNightShift) {
                    if ($hoursWorked > $workSchedule['total_work_hours'] && ( ! $workSchedule['is_flextime'])) {
                        if ($isOvertimeApproved) {
                            $hourSummary[$dayType][$holidayType]['night_differential_overtime'] += $remainingMinutes / 60;
                        }
                    } else {
                        $hourSummary[$dayType][$holidayType]['night_differential'] += $remainingMinutes / 60;
                    }
                } else {
                    if ($hoursWorked > $workSchedule['total_work_hours'] && ( ! $workSchedule['is_flextime'])) {
                        if ($isOvertimeApproved) {
                            $hourSummary[$dayType][$holidayType]['overtime_hours'] += $remainingMinutes / 60;
                        }
                    } else {
                        $hourSummary[$dayType][$holidayType]['regular_hours'] += $remainingMinutes / 60;
                    }
                }

                $cloneAttendanceCheckInTime->modify('+' . $remainingMinutes . ' minutes');
            }

            $endMinutes = (int)$attendanceCheckOutTime->format('i');
            $roundedCheckOutTime = clone $attendanceCheckOutTime;
            $roundedCheckOutTime->modify('-' . $endMinutes . ' minutes');

            $dateInterval = new DateInterval('PT1H');
            $datePeriod = new DatePeriod($cloneAttendanceCheckInTime, $dateInterval, $roundedCheckOutTime);

            foreach ($datePeriod as $currentTime) {
                $currentDate = $currentTime->format('Y-m-d');
                $hour = (int) $currentTime->format('H');
                $isNightShift = ($hour >= 22 || $hour < 6);

                $dayOfWeek = (new DateTime($currentDate))->format('l');
                $dayType = $dayOfWeek === 'Sunday' ? 'rest_day' : 'regular_day';

                $isHoliday = ! empty($datesMarkedAsHoliday[$currentDate]);
                $holidayType = 'non_holiday';

                if ($isHoliday) {
                    if (count($datesMarkedAsHoliday[$currentDate]) > 1) {
                        $holidayType = 'double_holiday';
                    } elseif ($datesMarkedAsHoliday[$currentDate]['is_paid']) {
                        $holidayType = 'regular_holiday';
                    } else {
                        $holidayType = 'special_holiday';
                    }
                }

                $hoursWorked++;
                if ($isNightShift) {
                    if ($hoursWorked > $workSchedule['total_work_hours'] && ( ! $workSchedule['is_flextime'])) {
                        if ($isOvertimeApproved) {
                            $hourSummary[$dayType][$holidayType]['night_differential_overtime']++;
                        }
                    } else {
                        $hourSummary[$dayType][$holidayType]['night_differential']++;
                    }
                } else {
                    if ($hoursWorked > $workSchedule['total_work_hours'] && ( ! $workSchedule['is_flextime'])) {
                        if ($isOvertimeApproved) {
                            $hourSummary[$dayType][$holidayType]['overtime_hours']++;
                        }
                    } else {
                        $hourSummary[$dayType][$holidayType]['regular_hours']++;
                    }
                }
            }

            $endMinutes = (int)$attendanceCheckOutTime->format('i');
            $roundedCheckOutTime = clone $attendanceCheckOutTime;
            if ($endMinutes > 0) {
                $roundedCheckOutTime->modify('-' . $endMinutes . ' minutes');
                $date = $attendanceCheckOutTime->format('Y-m-d');

                $hour = (int)$attendanceCheckOutTime->format('H');
                $isNightShift = ($hour >= 22 || $hour < 6);

                $dayOfWeek = (new DateTime($date))->format('l');
                $dayType = $dayOfWeek === 'Sunday' ? 'rest_day' : 'regular_day';

                $isHoliday = !empty($datesMarkedAsHoliday[$date]);
                $holidayType = 'non_holiday';

                if ($isHoliday) {
                    if (count($datesMarkedAsHoliday[$date]) > 1) {
                        $holidayType = 'double_holiday';
                    } elseif ($datesMarkedAsHoliday[$date]['is_paid']) {
                        $holidayType = 'regular_holiday';
                    } else {
                        $holidayType = 'special_holiday';
                    }
                }

                $hoursWorked += $endMinutes / 60;
                if ($isNightShift) {
                    if ($hoursWorked > $workSchedule['total_work_hours'] && ( ! $workSchedule['is_flextime'])) {
                        if ($isOvertimeApproved) {
                            $hourSummary[$dayType][$holidayType]['night_differential_overtime'] += $endMinutes / 60;
                        }
                    } else {
                        $hourSummary[$dayType][$holidayType]['night_differential'] += $endMinutes / 60;
                    }
                } else {
                    if ($hoursWorked > $workSchedule['total_work_hours'] && ( ! $workSchedule['is_flextime'])) {
                        if ($isOvertimeApproved) {
                            $hourSummary[$dayType][$holidayType]['overtime_hours'] += $endMinutes / 60;
                        }
                    } else {
                        $hourSummary[$dayType][$holidayType]['regular_hours'] += $endMinutes / 60;
                    }
                }
            }
        }
        */
