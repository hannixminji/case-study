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
$payrollGroupFrequency = 'Semi-Monthly';

$cutoffStartDate = '2024-12-02';
$cutoffEndDate   = '2024-12-10';

$cutoffStartDate = new DateTime($cutoffStartDate);
$cutoffEndDate   = new DateTime($cutoffEndDate);

$employeeColumns = [
    'id'           ,
    'job_title_id' ,
    'department_id',
    'hourly_rate'
];

$filterCriteria = [
    [
        'column'   => 'employee.access_role',
        'operator' => '!=',
        'value'    => "Admin"
    ],
    [
        'column'   => 'employee.payroll_group_id',
        'operator' => '=',
        'value'    => 1
    ],
];

$employees = $employeeRepository->fetchAllEmployees($employeeColumns, $filterCriteria);
$employees = $employees['result_set'];

foreach ($employees as $employee) {
    $employeeId   = $employee['id'           ];
    $jobTitleId   = $employee['job_title_id' ];
    $departmentId = $employee['department_id'];
    $hourlyRate   = $employee['hourly_rate'  ];

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
            'message' => 'An unexpected error occurred. Please try again later.'
        ];
    }

    $attendanceColumns = [];

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
            'value'    =>  '2024-12-10'
        ]
    ];

    $attendanceRecords = $attendanceRepository->fetchAllAttendance($attendanceColumns, $filterCriteria);

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

                echo '<pre>';
                print_r($records);
                echo '<pre>';

                    $datesMarkedAsHoliday = $holidayRepository->getHolidayDatesForPeriod(
                        '2024-11-26',
                        '2024-12-10'
                    );

                    $datesMarkedAsLeave = $leaveRequestRepository->getLeaveDatesForPeriod(
                        $employeeId,
                        '2024-11-26',
                        '2024-12-10'
                    );

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

                    $hourSummary1 = [
                        'regular_day' => [
                            'non_holiday' => [
                                'regular_hours' => 0
                            ],
                            'regular_holiday' => [
                                'regular_hours' => 0
                            ],
                            'double_holiday' => [
                                'regular_hours' => 0
                            ]
                        ]
                    ];

                    $previousCutoffStartDate = clone $cutoffStartDate;
                    $previousCutoffStartDate->modify('-1 day');
                    $foundAbsence = isAbsentBefore(
                        $employeeId,
                        $previousCutoffStartDate->format('Y-m-d'),
                        $workScheduleRepository,
                        $holidayRepository,
                        $leaveRequestRepository,
                        $attendanceRepository
                    );

                    $totalUnworkedHoursPaid = 0;
                    $totalUnworkedHoursPaidDoubleHoliday = 0;
                    $totalActualHoursWorked = 0;

                    $totalNumberOfAbsences = 0;
                    $totalDaysOfPaidLeave = 0;
                    $totalDaysOfUnpaidLeave = 0;

                    foreach ($records as $date => $recordEntries) {
                        $totalRequiredHours = 0;
                        foreach ($recordEntries as $record) {
                            $totalRequiredHours += $record['work_schedule']['total_work_hours'];
                        }
                        $hoursWorked = 0;

                        foreach ($recordEntries as $record) {
                            $workSchedule = $record['work_schedule'];
                            $attendanceRecords = $record['attendance_records'];

                            $workScheduleStartTime = (new DateTime($workSchedule['start_time']))->format('H:i:s');
                            $workScheduleEndTime   = (new DateTime($workSchedule['end_time'  ]))->format('H:i:s');
                            $workScheduleStartTime =  new DateTime($date . ' ' . $workScheduleStartTime);
                            $workScheduleEndTime   =  new DateTime($date . ' ' . $workScheduleEndTime  );
                            $workScheduleStartTimeDate = new DateTime($workScheduleStartTime->format('Y-m-d'));

                            if ($workScheduleEndTime->format('H:i:s') < $workScheduleStartTime->format('H:i:s')) {
                                $workScheduleEndTime->modify('+1 day');
                            }

                            if ($attendanceRecords) {
                                $isFirstRecord = true;

                                $result = $employeeBreakRepository->fetchOrderedEmployeeBreaks(
                                    $workSchedule['id'],
                                    $employeeId,
                                    $workScheduleStartTime->format('Y-m-d H:i:s'),
                                    $workScheduleEndTime->format('Y-m-d H:i:s')
                                );

                                if ($result === ActionResult::FAILURE) {
                                    return [
                                        'status'  => 'error',
                                        'message' => 'An unexpected error occurred. Please try again later.'
                                    ];
                                }

                                $employeeBreaks = $result;

                                foreach ($attendanceRecords as $attendanceRecord) {
                                    $attendanceCheckInTime = new DateTime($attendanceRecord['check_in_time']);
                                    $attendanceCheckOutTime = $attendanceRecord['check_out_time']
                                        ? new DateTime($attendanceRecord['check_out_time'])
                                        : clone $workScheduleEndTime;

                                    if ( ! $workSchedule['is_flextime'] && $isFirstRecord) {
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
                                        }

                                        $isFirstRecord = false;
                                    }

                                    if ( ! empty($employeeBreaks)) {
                                        $groupedBreaks = [];
                                        foreach ($employeeBreaks as $break) {
                                            $breakScheduleId = $break['break_schedule_id'];
                                            if ( ! isset($groupedBreaks[$breakScheduleId])) {
                                                $groupedBreaks[$breakScheduleId] = [];
                                            }
                                            $groupedBreaks[$breakScheduleId][] = $break;
                                        }

                                        $mergedBreaks = [];
                                        foreach ($groupedBreaks as $breakScheduleId => $breaks) {
                                            $firstBreak = $breaks[0];

                                            if ($firstBreak['start_time'] !== null) {
                                                $mergedBreak = $firstBreak;

                                                $startTime = new DateTime($firstBreak['start_time']);
                                                foreach ($breaks as $break) {
                                                    $currentStartTime = new DateTime($break['start_time']);
                                                    if ($currentStartTime < $startTime) {
                                                        $startTime = $currentStartTime;
                                                    }
                                                }

                                                $endTime = null;
                                                foreach ($breaks as $break) {
                                                    if ($break['end_time'] !== null) {
                                                        $currentEndTime = new DateTime($break['end_time']);
                                                        if ($endTime === null || $currentEndTime > $endTime) {
                                                            $endTime = $currentEndTime;
                                                        }
                                                    }
                                                }

                                                $mergedBreak['start_time'] = $startTime->format('Y-m-d H:i:s');
                                                if ($endTime !== null) {
                                                    $mergedBreak['end_time'] = $endTime->format('Y-m-d H:i:s');
                                                } else {
                                                    $mergedBreak['end_time'] = null;
                                                }

                                                $mergedBreaks[] = $mergedBreak;
                                            } else {
                                                $mergedBreaks[] = $firstBreak;
                                            }
                                        }

                                        $defaultBreaks = [];

                                        $formattedAttendanceCheckInTime  = $attendanceCheckInTime ->format('Y-m-d H:i:s');
                                        $formattedAttendanceCheckOutTime = $attendanceCheckOutTime->format('Y-m-d H:i:s');

                                        foreach ($mergedBreaks as $break) {
                                            if ($break['start_time'] !== null && $break['end_time'] !== null) {
                                                if ( ! $break['is_flexible']) {
                                                    $breakStartTime = new DateTime($break['start_time']);

                                                    $breakScheduleStartTime = new DateTime($break['break_schedule_start_time']);
                                                    $breakTypeDurationInMinutes = $break['break_type_duration_in_minutes'];

                                                    $breakScheduleEndTime = clone $breakScheduleStartTime;
                                                    $breakScheduleEndTime->add(new DateInterval("PT{$breakTypeDurationInMinutes}M"));

                                                    $breakDate = $breakStartTime->format('Y-m-d');
                                                    $breakScheduleStartTime = new DateTime($breakDate . ' ' . $breakScheduleStartTime->format('H:i:s'));
                                                    $breakScheduleEndTime = new DateTime($breakDate . ' ' . $breakScheduleEndTime->format('H:i:s'));

                                                    if ($breakScheduleEndTime->format('H:i:s') < $breakScheduleStartTime->format('H:i:s')) {
                                                        $breakScheduleEndTime->modify('+1 day');
                                                    }

                                                    $break['start_time'] = $breakScheduleStartTime->format('Y-m-d H:i:s');
                                                    $endTime = new DateTime($break['end_time']);
                                                    if ($endTime <= $breakScheduleEndTime && $attendanceCheckOutTime >= $breakScheduleEndTime) {
                                                        $break['end_time'] = $breakScheduleEndTime->format('Y-m-d H:i:s');
                                                    } elseif ($endTime <= $breakScheduleEndTime && $attendanceCheckOutTime < $breakScheduleEndTime) {
                                                        $break['end_time'] = $formattedAttendanceCheckOutTime;
                                                    }

                                                } else {
                                                    $breakStartTime = new DateTime($break['start_time']);
                                                    $breakEndTime   = new DateTime($break['end_time'  ]);
                                                    $breakTypeDurationInMinutes = $break['break_type_duration_in_minutes'];

                                                    $expectedEndTime = clone $breakStartTime;
                                                    $expectedEndTime->add(new DateInterval("PT{$breakTypeDurationInMinutes}M"));

                                                    if ($breakEndTime <= $expectedEndTime && $attendanceCheckOutTime >= $expectedEndTime) {
                                                        $break['end_time'] = $expectedEndTime->format('Y-m-d H:i:s');
                                                    } elseif ($breakEndTime <= $expectedEndTime && $attendanceCheckOutTime < $expectedEndTime) {
                                                        $break['end_time'] = $formattedAttendanceCheckOutTime;
                                                    }
                                                }

                                                if ($formattedAttendanceCheckInTime >= $break['start_time']) {
                                                    $break['start_time'] = $formattedAttendanceCheckInTime;
                                                }

                                                if ($formattedAttendanceCheckOutTime >= $break['start_time']) {
                                                    $defaultBreaks[] = [
                                                        'start_time' => $break['start_time'],
                                                        'end_time' => $break['end_time'],
                                                        'is_paid' => $break['break_type_is_paid'],
                                                        'break_type_duration_in_minutes' => $break['break_type_duration_in_minutes']
                                                    ];
                                                }

                                            } elseif ($break['start_time'] === null || $break['end_time'] === null) {
                                                if ( ! $break['is_flexible']) {
                                                    $breakScheduleStartTime = new DateTime($break['break_schedule_start_time']);
                                                    $breakScheduleEndTime = clone $breakScheduleStartTime;
                                                    $breakTypeDurationInMinutes = $break['break_type_duration_in_minutes'];

                                                    $breakScheduleStartTime = new DateTime($workScheduleStartTimeDate->format('Y-m-d') . ' ' . $breakScheduleStartTime->format('H:i:s'));
                                                    $breakScheduleEndTime = clone $breakScheduleStartTime;
                                                    $breakScheduleEndTime->add(new DateInterval("PT{$breakTypeDurationInMinutes}M"));

                                                    if ($breakScheduleStartTime < $workScheduleStartTime) {
                                                        $breakScheduleStartTime->modify('+1 day');
                                                    }

                                                    if ($breakScheduleEndTime < $workScheduleStartTime) {
                                                        $breakScheduleEndTime->modify('+1 day');
                                                    }

                                                    if ($breakScheduleEndTime->format('H:i:s') < $breakScheduleStartTime->format('H:i:s')) {
                                                        $breakScheduleEndTime->modify('+1 day');
                                                    }

                                                    $break['start_time'] = $breakScheduleStartTime->format('Y-m-d H:i:s');
                                                    $break['end_time'  ] = $breakScheduleEndTime  ->format('Y-m-d H:i:s');

                                                    if ($attendanceCheckInTime >= $breakScheduleStartTime) {
                                                        $break['start_time'] = $formattedAttendanceCheckInTime;
                                                    }

                                                    if ($attendanceCheckOutTime >= $breakScheduleEndTime) {
                                                        $break['end_time'] = $breakScheduleEndTime->format('Y-m-d H:i:s');
                                                    } elseif ($attendanceCheckOutTime < $breakScheduleEndTime) {
                                                        $break['end_time'] = $formattedAttendanceCheckOutTime;
                                                    }

                                                } else {
                                                    $breakScheduleStartTime = new DateTime($break['break_schedule_earliest_start_time']);
                                                    $breakTypeDurationInMinutes = $break['break_type_duration_in_minutes'];

                                                    $breakScheduleStartTime = new DateTime($workScheduleStartTimeDate->format('Y-m-d') . ' ' . $breakScheduleStartTime->format('H:i:s'));
                                                    $breakScheduleEndTime = clone $breakScheduleStartTime;
                                                    $breakScheduleEndTime->add(new DateInterval("PT{$breakTypeDurationInMinutes}M"));

                                                    if ($breakScheduleStartTime < $workScheduleStartTime) {
                                                        $breakScheduleStartTime->modify('+1 day');
                                                    }

                                                    if ($breakScheduleEndTime < $workScheduleStartTime) {
                                                        $breakScheduleEndTime->modify('+1 day');
                                                    }

                                                    if ($breakScheduleEndTime->format('H:i:s') < $breakScheduleStartTime->format('H:i:s')) {
                                                        $breakScheduleEndTime->modify('+1 day');
                                                    }

                                                    $break['start_time'] = $breakScheduleStartTime->format('Y-m-d H:i:s');
                                                    $break['end_time'  ] = $breakScheduleEndTime  ->format('Y-m-d H:i:s');

                                                    if ($attendanceCheckInTime >= $breakScheduleStartTime) {
                                                        $break['start_time'] = $formattedAttendanceCheckInTime;
                                                    }

                                                    if ($attendanceCheckOutTime >= $breakScheduleEndTime) {
                                                        $break['end_time'] = $breakScheduleEndTime->format('Y-m-d H:i:s');
                                                    } elseif ($attendanceCheckOutTime < $breakScheduleEndTime) {
                                                        $break['end_time'] = $formattedAttendanceCheckOutTime;
                                                    }
                                                }

                                                if ($break['end_time'] > $formattedAttendanceCheckOutTime) {
                                                    $break['end_time'] = $formattedAttendanceCheckOutTime;
                                                }

                                                if ($formattedAttendanceCheckOutTime >= $break['start_time']) {
                                                    $defaultBreaks[] = [
                                                        'start_time' => $break['start_time'],
                                                        'end_time' => $break['end_time'],
                                                        'is_paid' => $break['break_type_is_paid'],
                                                        'break_type_duration_in_minutes' => $break['break_type_duration_in_minutes']
                                                    ];
                                                }
                                            }
                                        }

                                        usort($defaultBreaks, function ($a, $b) {
                                            $startTimeA = new DateTime($a['start_time']);
                                            $startTimeB = new DateTime($b['start_time']);
                                            return $startTimeA <=> $startTimeB;
                                        });

                                        foreach ($defaultBreaks as $break) {
                                            if ( ! $break['is_paid']) {
                                                $breakStartTime = new DateTime($break['start_time']);
                                                $breakEndTime = new DateTime($break['end_time']);

                                                if ($breakStartTime->format('Y-m-d H') === $breakEndTime->format('Y-m-d H')) {
                                                    $interval = $breakStartTime->diff($breakEndTime);
                                                    $breakDuration = $interval->i;

                                                    $hour = (int) $breakStartTime->format('H');
                                                    $date = $breakStartTime->format('Y-m-d');
                                                    $isNightShift = ($hour >= 22 || $hour < 6);

                                                    $dayOfWeek = (new DateTime($date))->format('l');
                                                    $dayType = $dayOfWeek === 'Sunday' ? 'rest_day' : 'regular_day';

                                                    $isHoliday = ! empty($datesMarkedAsHoliday[$date]);
                                                    $holidayType = 'non_holiday';

                                                    if ($isHoliday && ! $foundAbsence) {
                                                        if (count($datesMarkedAsHoliday[$date]) > 1) {
                                                            $holidayType = 'double_holiday';
                                                        } elseif ($datesMarkedAsHoliday[$date]['is_paid']) {
                                                            $holidayType = 'regular_holiday';
                                                        } else {
                                                            $holidayType = 'special_holiday';
                                                        }
                                                    }

                                                    $hoursWorked -= $breakDuration / 60;

                                                    if ($isNightShift) {
                                                        $hourSummary[$dayType][$holidayType]['night_differential'] -= $breakDuration / 60;
                                                    } else {
                                                        $hourSummary[$dayType][$holidayType]['regular_hours'] -= $breakDuration / 60;
                                                    }

                                                } else {
                                                    $startMinutes = (int) $breakStartTime->format('i');
                                                    $cloneBreakStartTime = clone $breakStartTime;
                                                    if ($startMinutes > 0) {
                                                        $remainingMinutes = 60 - $startMinutes;
                                                        $hour = (int) $breakStartTime->format('H');
                                                        $date = $breakStartTime->format('Y-m-d');
                                                        $isNightShift = ($hour >= 22 || $hour < 6);

                                                        $dayOfWeek = (new DateTime($date))->format('l');
                                                        $dayType = $dayOfWeek === 'Sunday' ? 'rest_day' : 'regular_day';

                                                        $isHoliday = !empty($datesMarkedAsHoliday[$date]);
                                                        $holidayType = 'non_holiday';

                                                        if ($isHoliday && ! $foundAbsence) {
                                                            if (count($datesMarkedAsHoliday[$date]) > 1) {
                                                                $holidayType = 'double_holiday';
                                                            } elseif ($datesMarkedAsHoliday[$date]['is_paid']) {
                                                                $holidayType = 'regular_holiday';
                                                            } else {
                                                                $holidayType = 'special_holiday';
                                                            }
                                                        }

                                                        $hoursWorked -= $remainingMinutes / 60;

                                                        if ($isNightShift) {
                                                            $hourSummary[$dayType][$holidayType]['night_differential'] -= $remainingMinutes / 60;
                                                        } else {
                                                            $hourSummary[$dayType][$holidayType]['regular_hours'] -= $remainingMinutes / 60;
                                                        }

                                                        $cloneBreakStartTime->modify('+' . $remainingMinutes . ' minutes');
                                                    }

                                                    $endMinutes = (int) $breakEndTime->format('i');
                                                    $roundedBreakEndTime = clone $breakEndTime;
                                                    $roundedBreakEndTime->modify('-' . $endMinutes . ' minutes');
                                                    $dateInterval = new DateInterval('PT1H');
                                                    $datePeriod = new DatePeriod($cloneBreakStartTime, $dateInterval, $roundedBreakEndTime);

                                                    foreach ($datePeriod as $currentTime) {
                                                        $currentDate = $currentTime->format('Y-m-d');
                                                        $hour = (int) $currentTime->format('H');
                                                        $isNightShift = ($hour >= 22 || $hour < 6);

                                                        $dayOfWeek = (new DateTime($currentDate))->format('l');
                                                        $dayType = $dayOfWeek === 'Sunday' ? 'rest_day' : 'regular_day';

                                                        $isHoliday = !empty($datesMarkedAsHoliday[$currentDate]);
                                                        $holidayType = 'non_holiday';

                                                        if ($isHoliday && ! $foundAbsence) {
                                                            if (count($datesMarkedAsHoliday[$currentDate]) > 1) {
                                                                $holidayType = 'double_holiday';
                                                            } elseif ($datesMarkedAsHoliday[$currentDate]['is_paid']) {
                                                                $holidayType = 'regular_holiday';
                                                            } else {
                                                                $holidayType = 'special_holiday';
                                                            }
                                                        }

                                                        $hoursWorked--;

                                                        if ($isNightShift) {
                                                            $hourSummary[$dayType][$holidayType]['night_differential']--;
                                                        } else {
                                                            $hourSummary[$dayType][$holidayType]['regular_hours']--;
                                                        }
                                                    }

                                                    if ($endMinutes > 0) {
                                                        $date = $breakEndTime->format('Y-m-d');
                                                        $hour = (int) $breakEndTime->format('H');
                                                        $isNightShift = ($hour >= 22 || $hour < 6);

                                                        $dayOfWeek = (new DateTime($date))->format('l');
                                                        $dayType = $dayOfWeek === 'Sunday' ? 'rest_day' : 'regular_day';

                                                        $isHoliday = !empty($datesMarkedAsHoliday[$date]);
                                                        $holidayType = 'non_holiday';

                                                        if ($isHoliday && ! $foundAbsence) {
                                                            if (count($datesMarkedAsHoliday[$date]) > 1) {
                                                                $holidayType = 'double_holiday';
                                                            } elseif ($datesMarkedAsHoliday[$date]['is_paid']) {
                                                                $holidayType = 'regular_holiday';
                                                            } else {
                                                                $holidayType = 'special_holiday';
                                                            }
                                                        }

                                                        $hoursWorked -= $endMinutes / 60;

                                                        if ($isNightShift) {
                                                            $hourSummary[$dayType][$holidayType]['night_differential'] -= $endMinutes / 60;
                                                        } else {
                                                            $hourSummary[$dayType][$holidayType]['regular_hours'] -= $endMinutes / 60;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    $isOvertimeApproved = $attendanceRecord['is_overtime_approved'];
                                    $isOvertimeApproved = 1;

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

                                        if ($isHoliday && ! $foundAbsence) {
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
                                            if ($hoursWorked > $totalRequiredHours && ( ! $workSchedule['is_flextime'])) {
                                                if ($isOvertimeApproved) {
                                                    $hourSummary[$dayType][$holidayType]['night_differential_overtime'] += $remainingMinutes / 60;
                                                }
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['night_differential'] += $remainingMinutes / 60;
                                            }
                                        } else {
                                            if ($hoursWorked > $totalRequiredHours && ( ! $workSchedule['is_flextime'])) {
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

                                        if ($isHoliday && ! $foundAbsence) {
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
                                            if ($hoursWorked > $totalRequiredHours && ( ! $workSchedule['is_flextime'])) {
                                                if ($isOvertimeApproved) {
                                                    $hourSummary[$dayType][$holidayType]['night_differential_overtime']++;
                                                }
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['night_differential']++;
                                            }
                                        } else {
                                            if ($hoursWorked > $totalRequiredHours && ( ! $workSchedule['is_flextime'])) {
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

                                        $isHoliday = ! empty($datesMarkedAsHoliday[$date]);
                                        $holidayType = 'non_holiday';

                                        if ($isHoliday && ! $foundAbsence) {
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
                                            if ($hoursWorked > $totalRequiredHours && ( ! $workSchedule['is_flextime'])) {
                                                if ($isOvertimeApproved) {
                                                    $hourSummary[$dayType][$holidayType]['night_differential_overtime'] += $endMinutes / 60;
                                                }
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['night_differential'] += $endMinutes / 60;
                                            }
                                        } else {
                                            if ($hoursWorked > $totalRequiredHours && ( ! $workSchedule['is_flextime'])) {
                                                if ($isOvertimeApproved) {
                                                    $hourSummary[$dayType][$holidayType]['overtime_hours'] += $endMinutes / 60;
                                                }
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['regular_hours'] += $endMinutes / 60;
                                            }
                                        }
                                    }
                                }

                                $hourSummary['regular_day']['non_holiday']['overtime_hours'] = floor($hourSummary['regular_day']['non_holiday']['overtime_hours']);
                                $hourSummary['regular_day']['non_holiday']['night_differential_overtime'] = floor($hourSummary['regular_day']['non_holiday']['night_differential_overtime']);

                                $hourSummary['regular_day']['special_holiday']['overtime_hours'] = floor($hourSummary['regular_day']['special_holiday']['overtime_hours']);
                                $hourSummary['regular_day']['special_holiday']['night_differential_overtime'] = floor($hourSummary['regular_day']['special_holiday']['night_differential_overtime']);

                                $hourSummary['regular_day']['regular_holiday']['overtime_hours'] = floor($hourSummary['regular_day']['regular_holiday']['overtime_hours']);
                                $hourSummary['regular_day']['regular_holiday']['night_differential_overtime'] = floor($hourSummary['regular_day']['regular_holiday']['night_differential_overtime']);

                                $hourSummary['regular_day']['double_holiday']['overtime_hours'] = floor($hourSummary['regular_day']['double_holiday']['overtime_hours']);
                                $hourSummary['regular_day']['double_holiday']['night_differential_overtime'] = floor($hourSummary['regular_day']['double_holiday']['night_differential_overtime']);

                                $hourSummary['rest_day']['non_holiday']['overtime_hours'] = floor($hourSummary['rest_day']['non_holiday']['overtime_hours']);
                                $hourSummary['rest_day']['non_holiday']['night_differential_overtime'] = floor($hourSummary['rest_day']['non_holiday']['night_differential_overtime']);

                                $hourSummary['rest_day']['special_holiday']['overtime_hours'] = floor($hourSummary['rest_day']['special_holiday']['overtime_hours']);
                                $hourSummary['rest_day']['special_holiday']['night_differential_overtime'] = floor($hourSummary['rest_day']['special_holiday']['night_differential_overtime']);

                                $hourSummary['rest_day']['regular_holiday']['overtime_hours'] = floor($hourSummary['rest_day']['regular_holiday']['overtime_hours']);
                                $hourSummary['rest_day']['regular_holiday']['night_differential_overtime'] = floor($hourSummary['rest_day']['regular_holiday']['night_differential_overtime']);

                                $hourSummary['rest_day']['double_holiday']['overtime_hours'] = floor($hourSummary['rest_day']['double_holiday']['overtime_hours']);
                                $hourSummary['rest_day']['double_holiday']['night_differential_overtime'] = floor($hourSummary['rest_day']['double_holiday']['night_differential_overtime']);
                            }

                            if (($datesMarkedAsLeave[$date]['is_leave'] && $datesMarkedAsLeave[$date]['is_paid'] && empty($attendanceRecords)) || ( ! empty($datesMarkedAsHoliday[$date]))) {
                                $breakSchedules = $breakScheduleRepository->fetchOrderedBreakSchedules($workSchedule['id']);

                                if ($breakSchedules === ActionResult::FAILURE) {
                                    return [
                                        'status' => 'error',
                                        'message' => 'An unexpected error occurred. Please try again later.'
                                    ];
                                }

                                if ( ! empty($breakSchedules)) {
                                    foreach ($breakSchedules as $breakSchedule) {
                                        $breakScheduleStartTime = null;
                                        $breakScheduleEndTime = null;
                                        if ( ! $breakSchedule['is_flexible']) {
                                            $breakScheduleStartTime = new DateTime($breakSchedule['start_time']);
                                            $breakScheduleEndTime = new DateTime($breakSchedule['start_time']);
                                            $breakTypeDurationInMinutes = $breakSchedule['break_type_duration_in_minutes'];

                                            $breakScheduleStartTime = new DateTime($workScheduleStartTimeDate->format('Y-m-d') . ' ' . $breakScheduleStartTime->format('H:i:s'));
                                            $breakScheduleEndTime = clone $breakScheduleStartTime;
                                            $breakScheduleEndTime->add(new DateInterval("PT{$breakTypeDurationInMinutes}M"));

                                            if ($breakScheduleStartTime < $workScheduleStartTime) {
                                                $breakScheduleStartTime->modify('+1 day');
                                            }

                                            if ($breakScheduleEndTime < $workScheduleStartTime) {
                                                $breakScheduleEndTime->modify('+1 day');
                                            }

                                            if ($breakScheduleEndTime->format('H:i:s') < $breakScheduleStartTime->format('H:i:s')) {
                                                $breakScheduleEndTime->modify('+1 day');
                                            }

                                        } else {
                                            $breakScheduleStartTime = new DateTime($breakSchedule['earliest_start_time']);
                                            $breakTypeDurationInMinutes = $breakSchedule['break_type_duration_in_minutes'];

                                            $breakScheduleStartTime = new DateTime($workScheduleStartTimeDate->format('Y-m-d') . ' ' . $breakScheduleStartTime->format('H:i:s'));
                                            $breakScheduleEndTime = clone $breakScheduleStartTime;
                                            $breakScheduleEndTime->add(new DateInterval("PT{$breakTypeDurationInMinutes}M"));

                                            if ($breakScheduleStartTime < $workScheduleStartTime) {
                                                $breakScheduleStartTime->modify('+1 day');
                                            }

                                            if ($breakScheduleEndTime < $workScheduleStartTime) {
                                                $breakScheduleEndTime->modify('+1 day');
                                            }

                                            if ($breakScheduleEndTime->format('H:i:s') < $breakScheduleStartTime->format('H:i:s')) {
                                                $breakScheduleEndTime->modify('+1 day');
                                            }
                                        }

                                        $startMinutes = (int) $breakScheduleStartTime->format('i');
                                        $cloneBreakScheduleStartTime = clone $breakScheduleStartTime;
                                        if ($startMinutes > 0) {
                                            $remainingMinutes = 60 - $startMinutes;
                                            $hour = (int) $breakScheduleStartTime->format('H');
                                            $date = $breakScheduleStartTime->format('Y-m-d');

                                            $dayType = 'regular_day';

                                            $isHoliday = ! empty($datesMarkedAsHoliday[$date]);
                                            $holidayType = 'non_holiday';

                                            if ($isHoliday && ! $foundAbsence) {
                                                if (count($datesMarkedAsHoliday[$date]) > 1) {
                                                    $holidayType = 'double_holiday';
                                                } elseif ($datesMarkedAsHoliday[$date]['is_paid']) {
                                                    $holidayType = 'regular_holiday';
                                                }
                                            }

                                            if ($holidayType === 'non_holiday') {
                                                if ($datesMarkedAsLeave[$date]['is_leave'] && $datesMarkedAsLeave[$date]['is_paid'] && empty($attendanceRecords)) {
                                                    $hourSummary1[$dayType][$holidayType]['regular_hours'] -= $remainingMinutes / 60;
                                                }
                                            } else {
                                                $hourSummary1[$dayType][$holidayType]['regular_hours'] -= $remainingMinutes / 60;
                                            }

                                            $cloneBreakScheduleStartTime->modify('+' . $remainingMinutes . ' minutes');
                                        }

                                        $endMinutes = (int) $breakScheduleEndTime->format('i');
                                        $roundedBreakScheduleEndTime = clone $breakScheduleEndTime;
                                        $roundedBreakScheduleEndTime->modify('-' . $endMinutes . ' minutes');

                                        $dateInterval = new DateInterval('PT1H');
                                        $datePeriod = new DatePeriod($cloneBreakScheduleStartTime, $dateInterval, $roundedBreakScheduleEndTime);

                                        foreach ($datePeriod as $currentTime) {
                                            $currentDate = $currentTime->format('Y-m-d');
                                            $hour = (int) $currentTime->format('H');

                                            $dayType = 'regular_day';

                                            $isHoliday = ! empty($datesMarkedAsHoliday[$currentDate]);
                                            $holidayType = 'non_holiday';

                                            if ($isHoliday && ! $foundAbsence) {
                                                if (count($datesMarkedAsHoliday[$currentDate]) > 1) {
                                                    $holidayType = 'double_holiday';
                                                } elseif ($datesMarkedAsHoliday[$currentDate]['is_paid']) {
                                                    $holidayType = 'regular_holiday';
                                                }
                                            }

                                            if ($holidayType === 'non_holiday') {
                                                if ($datesMarkedAsLeave[$date]['is_leave'] && $datesMarkedAsLeave[$date]['is_paid'] && empty($attendanceRecords)) {
                                                    $hourSummary1[$dayType][$holidayType]['regular_hours']--;
                                                }
                                            } else {
                                                $hourSummary1[$dayType][$holidayType]['regular_hours']--;
                                            }
                                        }

                                        if ($endMinutes > 0) {
                                            $date = $breakScheduleEndTime->format('Y-m-d');
                                            $hour = (int) $breakScheduleEndTime->format('H');

                                            $dayType = 'regular_day';

                                            $isHoliday = !empty($datesMarkedAsHoliday[$date]);
                                            $holidayType = 'non_holiday';

                                            if ($isHoliday && ! $foundAbsence) {
                                                if (count($datesMarkedAsHoliday[$date]) > 1) {
                                                    $holidayType = 'double_holiday';
                                                } elseif ($datesMarkedAsHoliday[$date]['is_paid']) {
                                                    $holidayType = 'regular_holiday';
                                                }
                                            }

                                            if ($holidayType === 'non_holiday') {
                                                if ($datesMarkedAsLeave[$date]['is_leave'] && $datesMarkedAsLeave[$date]['is_paid'] && empty($attendanceRecords)) {
                                                    $hourSummary1[$dayType][$holidayType]['regular_hours'] += $endMinutes / 60;
                                                }
                                            } else {
                                                $hourSummary1[$dayType][$holidayType]['regular_hours'] -= $endMinutes / 60;
                                            }
                                        }
                                    }
                                }

                                $startTime = $workScheduleStartTime;
                                $endTime = $workScheduleEndTime;

                                if ($workSchedule['is_flextime']) {
                                    $totalHoursPerWeek = $workSchedule['total_hours_per_week'];
                                    $totalHoursPerDay = $totalHoursPerDay / 6;
                                    $totalMinutesPerDay = $totalHoursPerDay * 60;

                                    $endTime = $workScheduleStartTime->modify("+{$totalMinutesPerDay} minutes");
                                }

                                echo $startTime->format('Y-m-d H:i:s') . '<br>';
                                echo $endTime->format('Y-m-d H:i:s') . '<br>';

                                $startMinutes = (int) $startTime->format('i');
                                $cloneWorkScheduleStartTime = clone $startTime;
                                if ($startMinutes > 0) {
                                    $remainingMinutes = 60 - $startMinutes;
                                    $hour = (int) $startTime->format('H');
                                    $date = $startTime->format('Y-m-d');

                                    $dayType = 'regular_day';

                                    $isHoliday = ! empty($datesMarkedAsHoliday[$date]);
                                    $holidayType = 'non_holiday';

                                    if ($isHoliday && ! $foundAbsence) {
                                        if (count($datesMarkedAsHoliday[$date]) > 1) {
                                            $holidayType = 'double_holiday';
                                        } elseif ($datesMarkedAsHoliday[$date]['is_paid']) {
                                            $holidayType = 'regular_holiday';
                                        }
                                    }

                                    if ($holidayType === 'non_holiday') {
                                        if ($datesMarkedAsLeave[$date]['is_leave'] && $datesMarkedAsLeave[$date]['is_paid'] && empty($attendanceRecords)) {
                                            $hourSummary1[$dayType][$holidayType]['regular_hours'] += $remainingMinutes / 60;
                                        }
                                    } else {
                                        $hourSummary1[$dayType][$holidayType]['regular_hours'] += $remainingMinutes / 60;
                                    }


                                    $cloneWorkScheduleStartTime->modify('+' . $remainingMinutes . ' minutes');
                                }

                                $endMinutes = (int) $endTime->format('i');
                                $roundedWorkScheduleEndTime = clone $endTime;
                                $roundedWorkScheduleEndTime->modify('-' . $endMinutes . ' minutes');

                                $dateInterval = new DateInterval('PT1H');
                                $datePeriod = new DatePeriod($cloneWorkScheduleStartTime, $dateInterval, $roundedWorkScheduleEndTime);

                                foreach ($datePeriod as $currentTime) {
                                    $currentDate = $currentTime->format('Y-m-d');
                                    $hour = (int) $currentTime->format('H');

                                    $dayType = 'regular_day';

                                    $isHoliday = ! empty($datesMarkedAsHoliday[$currentDate]);
                                    $holidayType = 'non_holiday';

                                    if ($isHoliday && ! $foundAbsence) {
                                        if (count($datesMarkedAsHoliday[$currentDate]) > 1) {
                                            $holidayType = 'double_holiday';
                                        } elseif ($datesMarkedAsHoliday[$currentDate]['is_paid']) {
                                            $holidayType = 'regular_holiday';
                                        }
                                    }

                                    if ($holidayType === 'non_holiday') {
                                        if ($datesMarkedAsLeave[$date]['is_leave'] && $datesMarkedAsLeave[$date]['is_paid'] && empty($attendanceRecords)) {
                                            $hourSummary1[$dayType][$holidayType]['regular_hours']++;
                                        }
                                    } else {
                                        $hourSummary1[$dayType][$holidayType]['regular_hours']++;
                                    }
                                }

                                if ($endMinutes > 0) {
                                    $date = $endTime->format('Y-m-d');
                                    $hour = (int) $endTime->format('H');

                                    $dayType = 'regular_day';

                                    $isHoliday = !empty($datesMarkedAsHoliday[$date]);
                                    $holidayType = 'non_holiday';

                                    if ($isHoliday && ! $foundAbsence) {
                                        if (count($datesMarkedAsHoliday[$date]) > 1) {
                                            $holidayType = 'double_holiday';
                                        } elseif ($datesMarkedAsHoliday[$date]['is_paid']) {
                                            $holidayType = 'regular_holiday';
                                        }
                                    }

                                    if ($holidayType === 'non_holiday') {
                                        if ($datesMarkedAsLeave[$date]['is_leave'] && $datesMarkedAsLeave[$date]['is_paid'] && empty($attendanceRecords)) {
                                            $hourSummary1[$dayType][$holidayType]['regular_hours'] += $endMinutes / 60;
                                        }
                                    } else {
                                        $hourSummary1[$dayType][$holidayType]['regular_hours'] += $endMinutes / 60;
                                    }
                                }
                                print_r($hourSummary1);
                            }

                            $foundAbsence = empty($attendanceRecords);

                            if ($datesMarkedAsLeave[$date]['is_leave'] && $datesMarkedAsLeave[$date]['is_paid'] && empty($attendanceRecords)) {
                                $foundAbsence = false;
                            }

                            if (empty($attendanceRecords) && !empty($datesMarkedAsHoliday[$date])) {
                                $foundAbsence = false;
                            }
                        }

                        $totalActualHoursWorked += $hoursWorked;
                    }

                    print_r($hourSummary);

                    $employeeAllowanceTableColumns = [
                        'allowance_is_taxable',
                        'allowance_frequency' ,
                        'allowance_status'    ,
                        'amount'
                    ];

                    $employeeAllowanceFilterCriteria = [
                        [
                            'column'   => 'employee_allowance.deleted_at',
                            'operator' => 'IS NULL'
                        ],
                        [
                            'column'   => 'allowance.status',
                            'operator' => '=',
                            'value'    => 'Active'
                        ],
                        [
                            'column'   => 'employee_allowance.employee_id',
                            'operator' => '=',
                            'value'    => $employeeId
                        ]
                    ];

                    $employeeAllowances = $employeeAllowanceRepository->fetchAllEmployeeAllowances($employeeAllowanceTableColumns, $employeeAllowanceFilterCriteria);

                    if ($employeeAllowances === ActionResult::FAILURE) {
                        return [
                            'status'  => 'error',
                            'message' => 'An unexpected error occurred. Please try again later.'
                        ];
                    }

                    $employeeAllowances = $employeeAllowances['result_set'];

                    $taxableAllowances = 0;
                    $nonTaxableAllowances = 0;

                    $frequencyMultiplier = [
                        'Weekly' => 4,
                        'Bi-weekly' => 2,
                        'Semi-monthly' => 2,
                        'Monthly' => 1
                    ];

                    foreach ($employeeAllowances as $employeeAllowance) {
                        $amount = $employeeAllowance['amount'];
                        $allowanceFrequency = $employeeAllowance['allowance_frequency'];
                        $isTaxable = $employeeAllowance['allowance_is_taxable'];

                        if (isset($frequencyMultiplier[$allowanceFrequency], $frequencyMultiplier[$payrollGroupFrequency])) {
                            $allowanceMultiplier = $frequencyMultiplier[$allowanceFrequency] / $frequencyMultiplier[$payrollGroupFrequency];
                            $proratedAmount = $amount * $allowanceMultiplier;
                        } else {
                            $proratedAmount = 0;
                        }

                        if ($isTaxable) {
                            $taxableAllowances += $proratedAmount;
                        } else {
                            $nonTaxableAllowances += $proratedAmount;
                        }
                    }

                    $employeeDeductionTableColumns = [
                        'deduction_is_pre_tax',
                        'deduction_frequency' ,
                        'deduction_status'    ,
                        'amount_type'         ,
                        'amount'
                    ];

                    $employeeDeductionFilterCriteria = [
                        [
                            'column'   => 'employee_deduction.deleted_at',
                            'operator' => 'IS NULL'
                        ],
                        [
                            'column'   => 'deduction.status',
                            'operator' => '=',
                            'value'    => 'Active'
                        ],
                        [
                            'column'   => 'employee_deduction.employee_id',
                            'operator' => '=',
                            'value'    => $employeeId
                        ]
                    ];

                    $employeeDeductions = $employeeDeductionRepository->fetchAllEmployeeDeductions($employeeDeductionTableColumns, $employeeDeductionFilterCriteria);

                    if ($employeeDeductions === ActionResult::FAILURE) {
                        return [
                            'status'  => 'error',
                            'message' => 'An unexpected error occurred. Please try again later.'
                        ];
                    }

                    $employeeDeductions = $employeeDeductions['result_set'];

                    $preTaxDeductions = 0;
                    $postTaxDeductions = 0;
                    $totalPercentageDeductions = 0;

                    foreach ($employeeDeductions as $deduction) {
                        $amount = $deduction['amount'];
                        $amountType = $deduction['amount_type'];
                        $isPreTax = $deduction['is_pre_tax'];
                        $frequency = $deduction['frequency'];

                        if (isset($frequencyMultiplier[$frequency], $frequencyMultiplier[$payrollGroupFrequency])) {
                            $allowanceMultiplier = $frequencyMultiplier[$frequency] / $frequencyMultiplier[$payrollGroupFrequency];
                            $proratedAmount = $amount * $allowanceMultiplier;

                            if ($amountType == 'Fixed Amount') {
                                $deductionAmount = $proratedAmount;
                            } elseif ($amountType == 'Percentage-based') {
                                $totalPercentageDeductions += $amount;
                                $deductionAmount = 0;
                            }

                            if ($isPreTax) {
                                $preTaxDeductions += $deductionAmount;
                            } else {
                                $postTaxDeductions += $deductionAmount;
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

                    $overtimeRates = $overtimeRateRepository->fetchOvertimeRates( (int) $overtimeRateAssignmentId);

                    if ($overtimeRates === ActionResult::FAILURE) {
            			return [
            				'status'  => 'error',
            				'message' => 'An unexpected error occurred. Please try again later.'
            			];
                    }

                    $grossPay = 0;

                    foreach ($overtimeRates as $rate) {
                        $dayType     = strtolower(str_replace(['-', ' '], '_', $rate['day_type'    ]));
                        $holidayType = strtolower(str_replace(['-', ' '], '_', $rate['holiday_type']));

                        $grossPay += $hourSummary[$dayType][$holidayType]['regular_hours'              ] * $hourlyRate * $rate['regular_time_rate'                   ];
                        $grossPay += $hourSummary[$dayType][$holidayType]['overtime_hours'             ] * $hourlyRate * $rate['overtime_rate'                       ];
                        $grossPay += $hourSummary[$dayType][$holidayType]['night_differential'         ] * $hourlyRate * $rate['night_differential_rate'             ];
                        $grossPay += $hourSummary[$dayType][$holidayType]['night_differential_overtime'] * $hourlyRate * $rate['night_differential_and_overtime_rate'];
                    }
}

/*

$currentDateTime = '2024-11-26 22:00:00';

$response = $attendanceService->handleRfidTap($rfidUid, $currentDateTime);

$currentDateTime = '2024-11-27 06:00:00';

$response = $attendanceService->handleRfidTap($rfidUid, $currentDateTime);


$currentDateTime = '2024-11-27 06:00:00';

$response = $attendanceService->handleRfidTap($rfidUid, $currentDateTime);


$currentDateTime = '2024-11-26 22:00:00';

$response = $employeeBreakService->handleRfidTap($rfidUid, $currentDateTime);

echo '<pre>';
print_r($response);
echo '<pre>';
*/

function isAbsentBefore(int $employeeId, string $date, WorkScheduleRepository $workScheduleRepository, HolidayRepository $holidayRepository, LeaveRequestRepository $leaveRequestRepository, AttendanceRepository $attendanceRepository): array|bool
{
    $previousDate = new DateTime($date);

    $foundAbsence = false;

    while ( ! $foundAbsence) {
        $formattedPreviousDate = $previousDate->format('Y-m-d');

        $employeeWorkSchedules = $workScheduleRepository->getEmployeeWorkSchedules(
            $employeeId,
            $formattedPreviousDate,
            $formattedPreviousDate
        );

        if ($employeeWorkSchedules === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        if ($employeeWorkSchedules === ActionResult::NO_WORK_SCHEDULE_FOUND) {
            break;
        }

        if ( ! empty($employeeWorkSchedules[$formattedPreviousDate])) {
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

            if (empty($datesMarkedAsHoliday[$formattedPreviousDate])) {
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

                if (($datesMarkedAsLeave[$formattedPreviousDate]['is_leave'] === true   &&
                     $datesMarkedAsLeave[$formattedPreviousDate]['is_paid' ] === false) ||

                    ($datesMarkedAsLeave[$formattedPreviousDate]['is_leave'] === false &&
                     $datesMarkedAsLeave[$formattedPreviousDate]['is_paid' ] === false)) {

                    $attendanceFilterCriteria = [
                        [
                            'column'   => 'work_schedule.employee_id',
                            'operator' => '=',
                            'value'    => $employeeId
                        ],
                        [
                            'column'   => 'attendance.date',
                            'operator' => '=',
                            'value'    => $formattedPreviousDate
                        ]
                    ];

                    $employeeAttendanceRecords = $attendanceRepository->fetchAllAttendance([], $attendanceFilterCriteria);

                    if ($employeeAttendanceRecords === ActionResult::FAILURE) {
                        return [
                            'status'  => 'error',
                            'message' => 'An unexpected error occurred. Please try again later.'
                        ];
                    }

                    $employeeAttendanceRecords = $employeeAttendanceRecords['result_set'];

                    foreach ($employeeWorkSchedules as $dateOfSchedule => $workSchedules) {
                        foreach ($workSchedules as $workSchedule) {
                            $workScheduleAttendanceRecords = [];

                            foreach ($employeeAttendanceRecords as $attendanceRecord) {
                                if ($attendanceRecord['date'] === $dateOfSchedule && $attendanceRecord['work_schedule_id'] === $workSchedule['id']) {
                                    $workScheduleAttendanceRecords[] = $attendanceRecord;
                                }
                            }

                            if (empty($workScheduleAttendanceRecords)) {
                                $foundAbsence = true;
                            } else {
                                break;
                            }
                        }
                    }
                }
            }
        }

        $previousDate->modify('-1 day');
    }

    return $foundAbsence;
}
