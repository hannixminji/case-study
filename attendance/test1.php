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

                    $totalUnpaidBreakInMinutes = 0;
                    foreach ($records as $date => $recordEntries) {
                        $actualDate = $date;
                        foreach ($recordEntries as $record) {
                            $workSchedule = $record['work_schedule'];

                            $attendanceRecords = $record['attendance_records'];

                            $workScheduleStartTime = new DateTime($workSchedule['start_time']);
                            $workScheduleEndTime = new DateTime($workSchedule['end_time']);
                            $workScheduleStartTime = new DateTime($date . ' ' . (new DateTime($workSchedule['start_time']))->format('H:i:s'));
                            $workScheduleEndTime = new DateTime($date . ' ' . (new DateTime($workSchedule['end_time']))->format('H:i:s'));

                            if ($workScheduleEndTime->format('H:i:s') < $workScheduleStartTime->format('H:i:s')) {
                                $workScheduleEndTime->modify('+1 day');
                            }

                            if ($attendanceRecords) {
                                $result = $employeeBreakRepository->fetchOrderedEmployeeBreaks(
                                    (int) $workSchedule['id'],
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
                                $defaultBreaks = [];

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
                                    $isFlexible = $breaks[0]['is_flexible'];
                                    $breakScheduleStartTime = $breaks[0]['break_schedule_start_time'];
                                    $breakTypeDurationInMinutes = $breaks[0]['break_type_duration_in_minutes'];

                                    if ($breaks[0]['start_time'] !== null && $breaks[0]['end_time'] !== null) {
                                        $firstStartTime = new DateTime($breaks[0]['start_time']);
                                        $lastEndTime = new DateTime($breaks[0]['end_time']);

                                        foreach ($breaks as $break) {
                                            if ($break['end_time'] !== null) {
                                                $currentEndTime = new DateTime($break['end_time']);

                                                if ($currentEndTime > $lastEndTime) {
                                                    $lastEndTime = $currentEndTime;
                                                }
                                            }
                                        }

                                        if ( ! $isFlexible && $breakScheduleStartTime) {
                                            $firstStartTime = new DateTime($breakScheduleStartTime);
                                        }

                                        $expectedEndTime = clone $firstStartTime;
                                        $expectedEndTime->add(new DateInterval("PT{$breakTypeDurationInMinutes}M"));

                                        if ($lastEndTime < $expectedEndTime) {
                                            $lastEndTime = $expectedEndTime;
                                        }

                                        $mergedBreak = $breaks[0];
                                        $mergedBreak['start_time'] = $firstStartTime->format('Y-m-d H:i:s');
                                        $mergedBreak['end_time'] = $lastEndTime->format('Y-m-d H:i:s');
                                        $mergedBreaks[] = $mergedBreak;
                                    } else {
                                        $mergedBreaks[] = $breaks[0];
                                    }
                                }

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
                                            if ($endTime < $breakScheduleEndTime) {
                                                $break['end_time'] = $breakScheduleEndTime->format('Y-m-d H:i:s');
                                            }

                                        } else {
                                            $breakStartTime = new DateTime($break['start_time']);
                                            $breakEndTime = new DateTime($break['end_time']);
                                            $breakTypeDurationInMinutes = $break['break_type_duration_in_minutes'];

                                            $expectedEndTime = clone $breakStartTime;
                                            $expectedEndTime->add(new DateInterval("PT{$breakTypeDurationInMinutes}M"));

                                            if ($breakEndTime < $expectedEndTime) {
                                                $break['end_time'] = $expectedEndTime->format('Y-m-d H:i:s');
                                            }
                                        }

                                        if ( ! $break['break_type_is_paid']) {
                                            $interval = (new DateTime($break['end_time']))->diff(new DateTime($break['start_time']));
                                            $totalUnpaidBreakInMinutes += ($interval->h * 60) + $interval->i;
                                        }

                                        if ( ! empty($attendanceRecords) && $attendanceRecords[0]['check_in_time'] <= $break['start_time']) {
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
                                            $breakScheduleEndTime = new DateTime($break['break_schedule_start_time']);
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
                                            $break['end_time'] = $breakScheduleEndTime->format('Y-m-d H:i:s');

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
                                        }

                                        if ( ! $break['break_type_is_paid']) {
                                            $interval = (new DateTime($break['start_time']))->diff(new DateTime($break['end_time']));
                                            $totalUnpaidBreakInMinutes += ($interval->h * 60) + $interval->i;
                                        }

                                        if ( ! empty($attendanceRecords) && $attendanceRecords[0]['check_in_time'] <= $break['start_time']) {
                                            $defaultBreaks[] = [
                                                'start_time' => $break['start_time'],
                                                'end_time' => $break['end_time'],
                                                'is_paid' => $break['break_type_is_paid'],
                                                'break_type_duration_in_minutes' => $break['break_type_duration_in_minutes']
                                            ];
                                        }
                                    }
                                }

                                foreach ($defaultBreaks as $break) {
                                    if ( ! $break['is_paid']) {
                                        $breakStartTime = new DateTime($break['start_time']);
                                        $breakEndTime = new DateTime($break['end_time']);

                                        $expectedEndTime = clone $breakStartTime;
                                        $expectedEndTime->add(new DateInterval("PT{$breakTypeDurationInMinutes}M"));

                                        if ($breakStartTime->format('H') === $breakEndTime->format('H')) {
                                            $interval = $breakStartTime->diff($breakEndTime);
                                            $breakDuration = $interval->i;

                                            $hour = (int) $breakStartTime->format('H');
                                            $date = $breakStartTime->format('Y-m-d');
                                            $isNightShift = ($hour >= 22 || $hour < 6);

                                            $dayOfWeek = (new DateTime($date))->format('l');
                                            $dayType = $dayOfWeek === 'Sunday' ? 'rest_day' : 'regular_day';

                                            $isHoliday = ! empty($datesMarkedAsHoliday[$date]);
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

                                            if ($isNightShift) {
                                                $hourSummary[$dayType][$holidayType]['night_differential'] -= $breakDuration / 60;
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['regular_hours'] -= $breakDuration / 60;
                                            }
                                        } else {
                                            $startMinutes = (int) $breakStartTime->format('i');
                                            if ($startMinutes > 0) {
                                                $remainingMinutes = 60 - $startMinutes;
                                                $hour = (int) $breakStartTime->format('H');
                                                $date = $breakStartTime->format('Y-m-d');
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

                                                if ($isNightShift) {
                                                    $hourSummary[$dayType][$holidayType]['night_differential'] -= $remainingMinutes / 60;
                                                } else {
                                                    $hourSummary[$dayType][$holidayType]['regular_hours'] -= $remainingMinutes / 60;
                                                }

                                                $breakStartTime->modify('+' . $remainingMinutes . ' minutes');
                                            }

                                            $endMinutes = (int) $breakEndTime->format('i');
                                            if ($endMinutes > 0) {
                                                $roundedBreakEndTime = clone $breakEndTime;
                                                $roundedBreakEndTime->modify('-' . $endMinutes . ' minutes');
                                                $date = $breakEndTime->format('Y-m-d');
                                                $hour = (int) $breakEndTime->format('H');
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

                                                if ($isNightShift) {
                                                    $hourSummary[$dayType][$holidayType]['night_differential'] -= $endMinutes / 60;
                                                } else {
                                                    $hourSummary[$dayType][$holidayType]['regular_hours'] -= $endMinutes / 60;
                                                }

                                                $breakEndTime = $roundedBreakEndTime;
                                            }

                                            $dateInterval = new DateInterval('PT1H');
                                            $datePeriod = new DatePeriod($breakStartTime, $dateInterval, $breakEndTime);

                                            foreach ($datePeriod as $currentTime) {
                                                $currentDate = $currentTime->format('Y-m-d');
                                                $hour = (int) $currentTime->format('H');
                                                $isNightShift = ($hour >= 22 || $hour < 6);

                                                $dayOfWeek = (new DateTime($currentDate))->format('l');
                                                $dayType = $dayOfWeek === 'Sunday' ? 'rest_day' : 'regular_day';

                                                $isHoliday = !empty($datesMarkedAsHoliday[$currentDate]);
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

                                                if ($isNightShift) {
                                                    $hourSummary[$dayType][$holidayType]['night_differential']--;
                                                } else {
                                                    $hourSummary[$dayType][$holidayType]['regular_hours']--;
                                                }
                                            }
                                        }
                                    }
                                }

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
                                        }
                                    }

                                    $isOvertimeApproved = $attendanceRecord['is_overtime_approved'];

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

                                        if ($isNightShift) {
                                            if ($attendanceCheckInTime >= $workScheduleEndTime && ( ! $workSchedule['is_flextime'])) {
                                                if ($isOvertimeApproved) {
                                                    $hourSummary[$dayType][$holidayType]['night_differential_overtime'] += $remainingMinutes / 60;
                                                }
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['night_differential'] += $remainingMinutes / 60;
                                            }
                                        } else {
                                            if ($attendanceCheckInTime >= $workScheduleEndTime && ( ! $workSchedule['is_flextime'])) {
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

                                        if ($isNightShift) {
                                            if ($roundedCheckOutTime >= $workScheduleEndTime && ( ! $workSchedule['is_flextime'])) {
                                                if ($isOvertimeApproved) {
                                                    $hourSummary[$dayType][$holidayType]['night_differential_overtime'] += $endMinutes / 60;
                                                }
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['night_differential'] += $endMinutes / 60;
                                            }
                                        } else {
                                            if ($roundedCheckOutTime >= $workScheduleEndTime && ( ! $workSchedule['is_flextime'])) {
                                                if ($isOvertimeApproved) {
                                                    $hourSummary[$dayType][$holidayType]['overtime_hours'] += $endMinutes / 60;
                                                }
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['regular_hours'] += $endMinutes / 60;
                                            }
                                        }
                                    }

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

                                        if ($isNightShift) {
                                            if ($currentTime >= $workScheduleEndTime && ( ! $workSchedule['is_flextime'])) {
                                                if ($isOvertimeApproved) {
                                                    $hourSummary[$dayType][$holidayType]['night_differential_overtime']++;
                                                }
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['night_differential']++;
                                            }
                                        } else {
                                            if ($currentTime >= $workScheduleEndTime && ( ! $workSchedule['is_flextime'])) {
                                                if ($isOvertimeApproved) {
                                                    $hourSummary[$dayType][$holidayType]['overtime_hours']++;
                                                }
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['regular_hours']++;
                                            }
                                        }
                                    }

                                }
                            } else {
                                $workScheduleStartTime = new DateTime($workSchedule['start_time']);
                                $workScheduleEndTime = new DateTime($workSchedule['end_time']);


                            }
                        }
                    }
                    
                    print_r($hourSummary);
}

/*

$currentDateTime = '2024-11-26 22:00:00';

$response = $attendanceService->handleRfidTap($rfidUid, $currentDateTime);

$currentDateTime = '2024-11-27 06:00:00';

$response = $attendanceService->handleRfidTap($rfidUid, $currentDateTime);


$currentDateTime = '2024-11-27 06:00:00';

$response = $attendanceService->handleRfidTap($rfidUid, $currentDateTime);


Eto yung sa break in at out, kada tap matatawag toh $employeeBreakService->handleRfidTap($rfidUid, $currentDateTime);

$currentDateTime = '2024-11-26 22:00:00';

$response = $employeeBreakService->handleRfidTap($rfidUid, $currentDateTime);

echo '<pre>';
print_r($response);
echo '<pre>';
*/
