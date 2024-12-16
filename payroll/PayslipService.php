<?php

require_once __DIR__ . '/PayrollGroup.php'                                      ;

require_once __DIR__ . '/../employees/EmployeeRepository.php'                   ;
require_once __DIR__ . '/../work-schedules/WorkScheduleRepository.php'          ;
require_once __DIR__ . '/../AttendanceRepository.php'                           ;
require_once __DIR__ . '/../overtime-rates/OvertimeRateAssignmentRepository.php';
require_once __DIR__ . '/../overtime-rates/OvertimeRateRepository.php'          ;
require_once __DIR__ . '/../holidays/HolidayRepository.php'                     ;
require_once __DIR__ . '/../leaves/LeaveRequestRepository.php'                  ;
require_once __DIR__ . '/../allowances/EmployeeAllowanceRepository.php'         ;
require_once __DIR__ . '/../settings/SettingRepository.php'                     ;
require_once __DIR__ . '/../breaks/EmployeeBreakRepository.php'                 ;
require_once __DIR__ . '/../breaks/BreakScheduleRepository.php'                 ;

class PayslipService
{
    private readonly EmployeeRepository               $employeeRepository              ;
    private readonly WorkScheduleRepository           $workScheduleRepository          ;
    private readonly AttendanceRepository             $attendanceRepository            ;
    private readonly OvertimeRateAssignmentRepository $overtimeRateAssignmentRepository;
    private readonly OvertimeRateRepository           $overtimeRateRepository          ;
    private readonly HolidayRepository                $holidayRepository               ;
    private readonly LeaveRequestRepository           $leaveRequestRepository          ;
    private readonly EmployeeAllowanceRepository      $employeeAllowanceRepository     ;
    private readonly EmployeeDeductionRepository      $employeeDeductionRepository     ;
    private readonly SettingRepository                $settingRepository               ;
    private readonly EmployeeBreakRepository          $employeeBreakRepository         ;
    private readonly BreakScheduleRepository          $breakScheduleRepository         ;

    public function __construct(
        EmployeeRepository               $employeeRepository              ,
        WorkScheduleRepository           $workScheduleRepository          ,
        AttendanceRepository             $attendanceRepository            ,
        OvertimeRateAssignmentRepository $overtimeRateAssignmentRepository,
        OvertimeRateRepository           $overtimeRateRepository          ,
        HolidayRepository                $holidayRepository               ,
        LeaveRequestRepository           $leaveRequestRepository          ,
        EmployeeAllowanceRepository      $employeeAllowanceRepository     ,
        SettingRepository                $settingRepository               ,
        EmployeeBreakRepository          $employeeBreakRepository         ,
        BreakScheduleRepository          $breakScheduleRepository         ,
        EmployeeDeductionRepository      $employeeDeductionRepository
    ) {
        $this->employeeRepository               = $employeeRepository              ;
        $this->workScheduleRepository           = $workScheduleRepository          ;
        $this->attendanceRepository             = $attendanceRepository            ;
        $this->overtimeRateAssignmentRepository = $overtimeRateAssignmentRepository;
        $this->overtimeRateRepository           = $overtimeRateRepository          ;
        $this->holidayRepository                = $holidayRepository               ;
        $this->leaveRequestRepository           = $leaveRequestRepository          ;
        $this->employeeAllowanceRepository      = $employeeAllowanceRepository     ;
        $this->settingRepository                = $settingRepository               ;
        $this->employeeBreakRepository          = $employeeBreakRepository         ;
        $this->breakScheduleRepository          = $breakScheduleRepository         ;
        $this->employeeDeductionRepository      = $employeeDeductionRepository     ;
    }

    public function calculate(PayrollGroup $payrollGroup, string $cutoffStartDate, string $cutoffEndDate)
    {
        $rfidUid = '123456789';
        $payrollGroupFrequency = 'Semi-Monthly';

        $cutoffStartDate = '2024-11-26';
        $cutoffEndDate   = '2024-12-10';

        $cutoffStartDate = new DateTime($cutoffStartDate);
        $cutoffEndDate   = new DateTime($cutoffEndDate);

        $dateBeforeCutoffStartDate = clone $cutoffStartDate;
        $dateBeforeCutoffStartDate->modify('-1 day');

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

        $employees = $this->employeeRepository->fetchAllEmployees($employeeColumns, $filterCriteria);
        $employees = $employees['result_set'];

        foreach ($employees as $employee) {
            $employeeId   = $employee['id'           ];
            $jobTitleId   = $employee['job_title_id' ];
            $departmentId = $employee['department_id'];
            $hourlyRate   = $employee['hourly_rate'  ];

            $workSchedules = $this->workScheduleRepository->getEmployeeWorkSchedules(
                $employeeId,
                $dateBeforeCutoffStartDate->format('Y-m-d'),
                $cutoffEndDate->format('Y-m-d')
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
                    'value'    => $dateBeforeCutoffStartDate->format('Y-m-d')
                ],
                [
                    'column'   => 'attendance.date',
                    'operator' => '<=',
                    'value'    => $cutoffEndDate->format('Y-m-d')
                ]
            ];

            $attendanceRecords = $this->attendanceRepository->fetchAllAttendance($attendanceColumns, $filterCriteria);

            if ($attendanceRecords === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $attendanceRecords = $attendanceRecords['result_set'];

            if ( ! empty($workSchedules[$dateBeforeCutoffStartDate->format('Y-m-d')])) {
                $schedules = &$workSchedules[$dateBeforeCutoffStartDate->format('Y-m-d')];
                $lastSchedule = $schedules[count($schedules) - 1];

                $start = (new DateTime($lastSchedule['start_time']))->format('H:i:s');
                $end   = (new DateTime($lastSchedule['end_time']))->format('H:i:s');

                $start = new DateTime($dateBeforeCutoffStartDate->format('Y-m-d') . ' ' . $start);
                $end   = new DateTime($dateBeforeCutoffStartDate->format('Y-m-d') . ' ' . $end);

                if ($end->format('H:i:s') < $start->format('H:i:s')) {
                    $end->modify('+1 day');
                }

                if ($end->format('Y-m-d') !== $dateBeforeCutoffStartDate->format('Y-m-d')) {
                    $schedules = [$lastSchedule];
                }
            }

            if ( ! empty($workSchedules[$cutoffEndDate->format('Y-m-d')])) {
                $schedules = &$workSchedules[$cutoffEndDate->format('Y-m-d')];
                $lastSchedule = end($schedules);

                $start = (new DateTime($lastSchedule['start_time']))->format('H:i:s');
                $end   = (new DateTime($lastSchedule['end_time'  ]))->format('H:i:s');

                $start = new DateTime($cutoffEndDate->format('Y-m-d') . ' ' . $start);
                $end   = new DateTime($cutoffEndDate->format('Y-m-d') . ' ' . $end  );

                if ($end->format('H:i:s') < $start->format('H:i:s')) {
                    $end->modify('+1 day');
                }

                if ($end->format('Y-m-d') !== $cutoffEndDate->format('Y-m-d')) {
                    array_pop($schedules);
                    unset($schedules);
                }
            }

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

            $datesMarkedAsHoliday = $this->holidayRepository->getHolidayDatesForPeriod(
                '2024-11-26',
                '2024-12-10'
            );

            $datesMarkedAsLeave = $this->leaveRequestRepository->getLeaveDatesForPeriod(
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

            /*
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
            */

            $foundAbsence = false;
            $totalUnworkedHoursPaid = 0;
            $totalUnworkedHoursPaidDoubleHoliday = 0;
            $totalActualHoursWorked = 0;

            $totalNumberOfAbsences = 0;
            $totalDaysOfPaidLeave = 0;
            $totalDaysOfUnpaidLeave = 0;

            $isAbsent = false;

            $isFirstSchedule = true;
            $workHoursPerDay = 0;
            foreach ($records as $date => $recordEntries) {
                $totalRequiredHours = 0;
                foreach ($recordEntries as $record) {
                    $totalRequiredHours += $record['work_schedule']['total_work_hours'];
                }
                $hoursWorked = 0;

                if ($isFirstSchedule) {
                    $workHoursPerDay = $totalRequiredHours;
                    $isFirstSchedule = false;
                }

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

                        $result = $this->employeeBreakRepository->fetchOrderedEmployeeBreaks(
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

                                $gracePeriod = (int) $this->settingRepository->fetchSettingValue('grace_period', 'work_schedule');

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
                                    $attendanceCheckInTime->modify("-{$gracePeriod} minutes");
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

                                            if ($isHoliday) {
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

                                                if ($isHoliday) {
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

                                                if ($isHoliday) {
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

                                                if ($isHoliday) {
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
                                    if (($hoursWorked > $totalRequiredHours && ( ! $workSchedule['is_flextime']))) {
                                        if ($isOvertimeApproved) {
                                            $hourSummary[$dayType][$holidayType]['night_differential_overtime'] += $remainingMinutes / 60;
                                        }
                                    } else {
                                        $hourSummary[$dayType][$holidayType]['night_differential'] += $remainingMinutes / 60;
                                    }
                                } else {
                                    if (($hoursWorked > $totalRequiredHours && ( ! $workSchedule['is_flextime']))) {
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
                                    if (($hoursWorked > $totalRequiredHours && ( ! $workSchedule['is_flextime']))) {
                                        if ($isOvertimeApproved) {
                                            $hourSummary[$dayType][$holidayType]['night_differential_overtime']++;
                                        }
                                    } else {
                                        $hourSummary[$dayType][$holidayType]['night_differential']++;
                                    }
                                } else {
                                    if (($hoursWorked > $totalRequiredHours && ( ! $workSchedule['is_flextime']))) {
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
                                    if (($hoursWorked > $totalRequiredHours && ( ! $workSchedule['is_flextime']))) {
                                        if ($isOvertimeApproved) {
                                            $hourSummary[$dayType][$holidayType]['night_differential_overtime'] += $endMinutes / 60;
                                        }
                                    } else {
                                        $hourSummary[$dayType][$holidayType]['night_differential'] += $endMinutes / 60;
                                    }
                                } else {
                                    if (($hoursWorked > $totalRequiredHours && ( ! $workSchedule['is_flextime']))) {
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

                    $breakSchedules = $this->breakScheduleRepository->fetchOrderedBreakSchedules($workSchedule['id']);

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
                        $totalHoursPerDay = $totalHoursPerWeek / 6;
                        $totalMinutesPerDay = $totalHoursPerDay * 60;

                        $endTime = $workScheduleStartTime->modify("+{$totalMinutesPerDay} minutes");
                    }

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
                                $hourSummary1[$dayType][$holidayType]['regular_hours'] += $endMinutes / 60;
                            }
                        } else {
                            $hourSummary1[$dayType][$holidayType]['regular_hours'] += $endMinutes / 60;
                        }
                    }
                }

                $totalActualHoursWorked += $hoursWorked;
            }

            print_r($hourSummary);
            print_r($hourSummary1);

            $employeeAllowanceTableColumns = [
                'allowance_frequency',
                'allowance_status'   ,
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

            $employeeAllowances = $this->employeeAllowanceRepository->fetchAllEmployeeAllowances($employeeAllowanceTableColumns, $employeeAllowanceFilterCriteria);

            if ($employeeAllowances === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $employeeAllowances = $employeeAllowances['result_set'];

            $totalAllowances = 0;

            $frequencyMultiplier = [
                'weekly'       => 4,
                'bi-weekly'    => 2,
                'semi-monthly' => 2,
                'monthly'      => 1
            ];

            $payrollGroupFrequency = strtolower($payrollGroupFrequency);

            foreach ($employeeAllowances as $employeeAllowance) {
                $amount = $employeeAllowance['amount'];
                $allowanceFrequency = strtolower($employeeAllowance['allowance_frequency']);

                if (isset($frequencyMultiplier[$allowanceFrequency], $frequencyMultiplier[$payrollGroupFrequency])) {
                    $allowanceMultiplier = $frequencyMultiplier[$allowanceFrequency] / $frequencyMultiplier[$payrollGroupFrequency];
                    $proratedAmount = $amount * $allowanceMultiplier;
                } else {
                    $proratedAmount = 0;
                }

                $totalAllowances += $proratedAmount;
            }

            $employeeDeductionTableColumns = [
                'deduction_frequency',
                'deduction_status'   ,
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

            $employeeDeductions = $this->employeeDeductionRepository->fetchAllEmployeeDeductions($employeeDeductionTableColumns, $employeeDeductionFilterCriteria);

            if ($employeeDeductions === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $employeeDeductions = $employeeDeductions['result_set'];

            $totalDeductions = 0;

            foreach ($employeeDeductions as $deduction) {
                $amount = $deduction['amount'];
                $frequency = strtolower($deduction['frequency']);

                if (isset($frequencyMultiplier[$frequency], $frequencyMultiplier[$payrollGroupFrequency])) {
                    $allowanceMultiplier = $frequencyMultiplier[$frequency] / $frequencyMultiplier[$payrollGroupFrequency];
                    $proratedAmount = $amount * $allowanceMultiplier;

                    $totalDeductions += $proratedAmount;
                }
            }

            $overtimeRateAssignment = new OvertimeRateAssignment(
                id          : null         ,
                departmentId: $departmentId,
                jobTitleId  : $jobTitleId  ,
                employeeId  : $employeeId
            );

            $overtimeRateAssignmentId = $this->overtimeRateAssignmentRepository->findId($overtimeRateAssignment);

            if ($overtimeRateAssignmentId === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $overtimeRates = $this->overtimeRateRepository->fetchOvertimeRates( (int) $overtimeRateAssignmentId);

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

                $regularTimeRate       = $rate['regular_time_rate'      ];
                $nightDifferentialRate = $rate['night_differential_rate'];

                if ($holidayType === 'regular_holiday') {
                    $regularTimeRate -= 1.0;
                    $nightDifferentialRate -= 1.0;
                } elseif ($holidayType === 'double_holiday') {
                    $regularTimeRate -= 2.0;
                    $nightDifferentialRate -= 2.0;
                }

                if ($regularTimeRate < 0) {
                    $regularTimeRate = 0;
                }

                if ($nightDifferentialRate < 0) {
                    $nightDifferentialRate = 0;
                }

                $grossPay += $hourSummary[$dayType][$holidayType]['regular_hours'              ] * $hourlyRate * $regularTimeRate                             ;
                $grossPay += $hourSummary[$dayType][$holidayType]['overtime_hours'             ] * $hourlyRate * $rate['overtime_rate'                       ];
                $grossPay += $hourSummary[$dayType][$holidayType]['night_differential'         ] * $hourlyRate * $nightDifferentialRate                       ;
                $grossPay += $hourSummary[$dayType][$holidayType]['night_differential_overtime'] * $hourlyRate * $rate['night_differential_and_overtime_rate'];
            }

            foreach ($hourSummary1 as $dayType => $holidayTypes) {
                foreach ($holidayTypes as $holidayType => $hoursData) {
                    foreach ($hoursData as $key => $value) {
                        if ($key === 'regular_hours' && $value > 0) {
                            if ($holidayType === 'non_holiday' || $holidayType === 'regular_holiday') {
                                $grossPay += $value * $hourlyRate * 1.0;
                            } elseif ($holidayType === 'double_holiday') {
                                $grossPay += $value * $hourlyRate * 2.0;
                            }
                        }
                    }
                }
            }

            $grossPayWithoutAllowances = $grossPay;

            $grossPay += $totalAllowances;

            echo $grossPay;
        }
    }
}
