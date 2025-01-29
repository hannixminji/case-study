<?php

require_once __DIR__ . '/Payslip.php'                                           ;

require_once __DIR__ . '/PayslipRepository.php'                                 ;

require_once __DIR__ . '/../employees/EmployeeRepository.php'                   ;
require_once __DIR__ . '/../work-schedules/WorkScheduleRepository.php'          ;
require_once __DIR__ . '/../attendance/AttendanceRepository.php'                ;
require_once __DIR__ . '/../holidays/HolidayRepository.php'                     ;
require_once __DIR__ . '/../leaves/LeaveRequestRepository.php'                  ;
require_once __DIR__ . '/../breaks/BreakScheduleRepository.php'                 ;
require_once __DIR__ . '/../breaks/EmployeeBreakRepository.php'                 ;
require_once __DIR__ . '/../settings/SettingRepository.php'                     ;
require_once __DIR__ . '/../allowances/EmployeeAllowanceRepository.php'         ;
require_once __DIR__ . '/../deductions/EmployeeDeductionRepository.php'         ;
require_once __DIR__ . '/../overtime-rates/OvertimeRateRepository.php'          ;
require_once __DIR__ . '/../overtime-rates/OvertimeRateAssignmentRepository.php';
require_once __DIR__ . '/../leaves/LeaveEntitlementRepository.php'              ;

class PayslipService
{
    private readonly PayslipRepository                $payslipRepository               ;
    private readonly EmployeeRepository               $employeeRepository              ;
    private readonly WorkScheduleRepository           $workScheduleRepository          ;
    private readonly AttendanceRepository             $attendanceRepository            ;
    private readonly HolidayRepository                $holidayRepository               ;
    private readonly LeaveRequestRepository           $leaveRequestRepository          ;
    private readonly BreakScheduleRepository          $breakScheduleRepository         ;
    private readonly EmployeeBreakRepository          $employeeBreakRepository         ;
    private readonly SettingRepository                $settingRepository               ;
    private readonly EmployeeAllowanceRepository      $employeeAllowanceRepository     ;
    private readonly EmployeeDeductionRepository      $employeeDeductionRepository     ;
    private readonly OvertimeRateRepository           $overtimeRateRepository          ;
    private readonly OvertimeRateAssignmentRepository $overtimeRateAssignmentRepository;
    private readonly LeaveEntitlementRepository       $leaveEntitlementRepository      ;

    public function __construct(
        PayslipRepository                $payslipRepository               ,
        EmployeeRepository               $employeeRepository              ,
        WorkScheduleRepository           $workScheduleRepository          ,
        AttendanceRepository             $attendanceRepository            ,
        HolidayRepository                $holidayRepository               ,
        LeaveRequestRepository           $leaveRequestRepository          ,
        BreakScheduleRepository          $breakScheduleRepository         ,
        EmployeeBreakRepository          $employeeBreakRepository         ,
        SettingRepository                $settingRepository               ,
        EmployeeAllowanceRepository      $employeeAllowanceRepository     ,
        EmployeeDeductionRepository      $employeeDeductionRepository     ,
        OvertimeRateRepository           $overtimeRateRepository          ,
        OvertimeRateAssignmentRepository $overtimeRateAssignmentRepository,
        LeaveEntitlementRepository       $leaveEntitlementRepository
    ) {
        $this->payslipRepository                = $payslipRepository               ;
        $this->employeeRepository               = $employeeRepository              ;
        $this->workScheduleRepository           = $workScheduleRepository          ;
        $this->attendanceRepository             = $attendanceRepository            ;
        $this->holidayRepository                = $holidayRepository               ;
        $this->leaveRequestRepository           = $leaveRequestRepository          ;
        $this->breakScheduleRepository          = $breakScheduleRepository         ;
        $this->employeeBreakRepository          = $employeeBreakRepository         ;
        $this->settingRepository                = $settingRepository               ;
        $this->employeeAllowanceRepository      = $employeeAllowanceRepository     ;
        $this->employeeDeductionRepository      = $employeeDeductionRepository     ;
        $this->overtimeRateRepository           = $overtimeRateRepository          ;
        $this->overtimeRateAssignmentRepository = $overtimeRateAssignmentRepository;
        $this->leaveEntitlementRepository       = $leaveEntitlementRepository      ;
    }

    public function generatePayslip(PayrollGroup $payrollGroup, string $cutoffPeriodStartDate, string $cutoffPeriodEndDate, string $paydayDate, string $action = "create")
    {
        $cutoffPeriodStartDate = new DateTime($cutoffPeriodStartDate);
        $cutoffPeriodEndDate   = new DateTime($cutoffPeriodEndDate  );

        $dateBeforeCutoffStartDate = clone $cutoffPeriodStartDate;
        $dateBeforeCutoffStartDate->modify('-1 day');

        $employeeColumns = [
            'id'           ,
            'job_title_id' ,
            'department_id',
            'basic_salary'
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
                'value'    => $payrollGroup->getId()
            ],
        ];

        $employees = $this->employeeRepository->fetchAllEmployees(
            columns       : $employeeColumns       ,
            filterCriteria: $employeeFilterCriteria
        );

        $employees = $employees['result_set'];

        foreach ($employees as $employee) {
            $employeeId   = $employee['id'           ];
            $jobTitleId   = $employee['job_title_id' ];
            $departmentId = $employee['department_id'];
            $basicSalary  = $employee['basic_salary' ];

            $workSchedules = $this->workScheduleRepository->getEmployeeWorkSchedules(
                $employeeId,
                $dateBeforeCutoffStartDate->format('Y-m-d'),
                $cutoffPeriodEndDate->format('Y-m-d')
            );

            if ($workSchedules === ActionResult::FAILURE) {
                return [
                    'status'  => 'error11',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            if ($workSchedules === ActionResult::NO_WORK_SCHEDULE_FOUND) {
                continue;
            }

            $attendanceColumns = [
            ];

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
                    'value'    => $cutoffPeriodEndDate->format('Y-m-d')
                ]
            ];

            $attendanceRecords = $this->attendanceRepository->fetchAllAttendance($attendanceColumns, $filterCriteria);

            if ($attendanceRecords === ActionResult::FAILURE) {
                return [
                    'status'  => 'error13',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $attendanceRecords = $attendanceRecords['result_set'];

            if ( ! empty($workSchedules[$dateBeforeCutoffStartDate->format('Y-m-d')])) {
                $schedules = &$workSchedules[$dateBeforeCutoffStartDate->format('Y-m-d')];
                $lastSchedule = $schedules[count($schedules) - 1];

                $start = (new DateTime($lastSchedule['start_time']))->format('H:i:s');
                $end   = (new DateTime($lastSchedule['end_time'  ]))->format('H:i:s');

                $start = new DateTime($dateBeforeCutoffStartDate->format('Y-m-d') . ' ' . $start);
                $end   = new DateTime($dateBeforeCutoffStartDate->format('Y-m-d') . ' ' . $end  );

                if ($end->format('H:i:s') < $start->format('H:i:s')) {
                    $end->modify('+1 day');
                }

                if ($end->format('Y-m-d') === $dateBeforeCutoffStartDate->format('Y-m-d')) {
                    unset($workSchedules[$dateBeforeCutoffStartDate->format('Y-m-d')]);
                } else {
                    $schedules = [$lastSchedule];
                }
            }

            if ( ! empty($workSchedules[$cutoffPeriodEndDate->format('Y-m-d')])) {
                $schedules = &$workSchedules[$cutoffPeriodEndDate->format('Y-m-d')];
                $lastSchedule = end($schedules);

                $start = (new DateTime($lastSchedule['start_time']))->format('H:i:s');
                $end   = (new DateTime($lastSchedule['end_time'  ]))->format('H:i:s');

                $start = new DateTime($cutoffPeriodEndDate->format('Y-m-d') . ' ' . $start);
                $end   = new DateTime($cutoffPeriodEndDate->format('Y-m-d') . ' ' . $end  );

                if ($end->format('H:i:s') < $start->format('H:i:s')) {
                    $end->modify('+1 day');
                }

                if ($end->format('Y-m-d') !== $cutoffPeriodEndDate->format('Y-m-d')) {
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

            $datesMarkedAsHoliday = $this->holidayRepository->getHolidayDatesForPeriod(
                $cutoffPeriodStartDate->format('Y-m-d'),
                $cutoffPeriodEndDate->format('Y-m-d')
            );

            $datesMarkedAsLeave = $this->leaveRequestRepository->getLeaveDatesForPeriod(
                $employeeId,
                $cutoffPeriodStartDate->format('Y-m-d'),
                $cutoffPeriodEndDate->format('Y-m-d')
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
            $previousCutoffStartDate = clone $cutoffPeriodStartDate;
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

            $isFirstSchedule = true;
            $workHoursPerDay = 0;

            $hoursWithoutOvertime = 0;

            $totalDaysWorked = 0;
            $totalActualHoursWorked = 0;

            foreach ($records as $date => $recordEntries) {
                $totalRequiredHours = 0;

                if ($recordEntries[0]['work_schedule']['is_flextime']) {
                    $totalRequiredHours += $recordEntries[0]['work_schedule']['total_hours_per_week'] / 6;
                } else {
                    foreach ($recordEntries as $record) {
                        $totalRequiredHours += $record['work_schedule']['total_work_hours'];
                    }
                }

                if ($isFirstSchedule) {
                    $workHoursPerDay = $totalRequiredHours;
                    $isFirstSchedule = false;
                }

                $hoursWorked = 0;

                foreach ($recordEntries as $record) {
                    $workSchedule      = $record['work_schedule'     ];
                    $attendanceRecords = $record['attendance_records'];

                    $workScheduleStartTime     = (new DateTime($workSchedule['start_time']))->format('H:i:s');
                    $workScheduleEndTime       = (new DateTime($workSchedule['end_time'  ]))->format('H:i:s');
                    $workScheduleStartTime     =  new DateTime($date . ' ' . $workScheduleStartTime);
                    $workScheduleEndTime       =  new DateTime($date . ' ' . $workScheduleEndTime  );
                    $workScheduleStartTimeDate =  new DateTime($workScheduleStartTime->format('Y-m-d'));

                    if ($workScheduleEndTime->format('H:i:s') < $workScheduleStartTime->format('H:i:s')) {
                        $workScheduleEndTime->modify('+1 day');
                    }

                    if ($attendanceRecords) {
                        $result = $this->employeeBreakRepository->fetchOrderedEmployeeBreaks(
                            $workSchedule['id'],
                            $employeeId,
                            $workScheduleStartTime->format('Y-m-d H:i:s'),
                            $workScheduleEndTime->format('Y-m-d H:i:s')
                        );

                        if ($result === ActionResult::FAILURE) {
                            return [
                                'status'  => 'error14',
                                'message' => 'An unexpected error occurred. Please try again later.'
                            ];
                        }

                        $employeeBreaks = $result;

                        $isFirstRecord = true;

                        foreach ($attendanceRecords as $attendanceRecord) {
                            $attendanceCheckInTime = new DateTime($attendanceRecord['check_in_time']);
                            $attendanceCheckOutTime = $attendanceRecord['check_out_time']
                                ? new DateTime($attendanceRecord['check_out_time'])
                                : clone $workScheduleEndTime;

                            if ($attendanceRecord['check_out_time'] === null && ( ! $workSchedule['is_flextime'])) {
                                $breakScheduleColumns = [
                                    'id'                               ,
                                    'start_time'                       ,
                                    'break_type_duration_in_minutes'   ,
                                    'is_require_break_in_and_break_out',
                                    'is_flexible'                      ,
                                    'earliest_start_time'              ,
                                    'latest_end_time'                  ,
                                    'break_type_is_paid'               ,
                                    'created_at'
                                ];

                                $filterCriteria = [
                                    [
                                        'column'   => 'break_schedule.deleted_at',
                                        'operator' => 'IS NULL'
                                    ],
                                    [
                                        'column'   => 'break_schedule.work_schedule_id',
                                        'operator' => '=',
                                        'value'    => $workSchedule['id']
                                    ]
                                ];

                                $result = $this->breakScheduleRepository->fetchAllBreakSchedules($breakScheduleColumns, $filterCriteria);

                                if ($result === ActionResult::FAILURE) {
                                    return [
                                        'status'  => 'error',
                                        'message' => 'An unexpected error occurred. Please try again later.'
                                    ];
                                }

                                $breakSchedules = $result['result_set'];

                                $employeeBreakColumns = [
                                    'id'                                ,
                                    'break_schedule_id'                 ,
                                    'start_time'                        ,
                                    'end_time'                          ,
                                    'break_duration_in_minutes'         ,
                                    'created_at'                        ,

                                    'break_schedule_start_time'         ,
                                    'break_schedule_is_flexible'        ,
                                    'break_schedule_earliest_start_time',

                                    'employee_id'                       ,

                                    'break_type_duration_in_minutes'    ,
                                    'break_type_is_paid'                ,
                                    'is_require_break_in_and_break_out'
                                ];

                                $filterCriteria = [
                                    [
                                        'column'   => 'break_schedule.work_schedule_id',
                                        'operator' => '=',
                                        'value'    => $workSchedule['id']
                                    ],
                                    [
                                        'column'   => 'work_schedule.employee_id',
                                        'operator' => '='                        ,
                                        'value'    => $employeeId
                                    ],
                                    [
                                        'column'      => 'employee_break.created_at',
                                        'operator'    => 'BETWEEN'                  ,
                                        'lower_bound' => $workScheduleStartTime->format('Y-m-d H:i:s'),
                                        'upper_bound' => $workScheduleEndTime->format('Y-m-d H:i:s')
                                    ]
                                ];

                                $sortCriteria = [
                                    [
                                        'column'    => 'employee_break.created_at',
                                        'direction' => 'DESC'
                                    ],
                                    [
                                        'column'    => 'employee_break.start_time',
                                        'direction' => 'DESC'
                                    ]
                                ];

                                $result = $this->employeeBreakRepository->fetchAllEmployeeBreaks(
                                    columns       : $employeeBreakColumns,
                                    filterCriteria: $filterCriteria      ,
                                    sortCriteria  : $sortCriteria
                                );

                                if ($result === ActionResult::FAILURE) {
                                    return [
                                        'status'  => 'error',
                                        'message' => 'An unexpected error occurred. Please try again later.'
                                    ];
                                }

                                $employeeBreaks = $result['result_set'];

                                $completedBreakIds = array_column($employeeBreaks, 'break_schedule_id');

                                foreach ($breakSchedules as $breakSchedule) {
                                    if ( ! in_array($breakSchedule['id'], $completedBreakIds)) {
                                        $employeeBreak = new EmployeeBreak(
                                            id                    : null                   ,
                                            attendanceId          : $attendanceRecord['id'],
                                            breakScheduleId       : $breakSchedule['id'   ],
                                            startTime             : null                   ,
                                            endTime               : null                   ,
                                            breakDurationInMinutes: 0                      ,
                                            createdAt             : $workScheduleEndTime->format('Y-m-d H:i:s')
                                        );

                                        $result = $this->employeeBreakRepository->breakIn($employeeBreak);

                                        if ($result === ActionResult::FAILURE) {
                                            return [
                                                'status'  => 'error',
                                                'message' => 'An unexpected error occurred. Please try again later.'
                                            ];
                                        }

                                        $lastBreakRecord = $this->employeeBreakRepository->fetchEmployeeLastBreakRecord($workSchedule['id'], $employeeId);

                                        if ($lastBreakRecord === ActionResult::FAILURE) {
                                            return [
                                                'status'  => 'error8',
                                                'message' => 'An unexpected error occurred. Please try again later.'
                                            ];
                                        }

                                        $lastBreakRecord = $lastBreakRecord[0];

                                        $employeeBreak = new EmployeeBreak(
                                            id                    : $lastBreakRecord['id'               ],
                                            attendanceId          : $attendanceRecord['id'              ],
                                            breakScheduleId       : $lastBreakRecord['break_schedule_id'],
                                            startTime             : null                                 ,
                                            endTime               : null                                 ,
                                            breakDurationInMinutes: 0                                    ,
                                            createdAt             : $lastBreakRecord['created_at']
                                        );

                                        $result = $this->employeeBreakRepository->breakOut($employeeBreak);

                                        if ($result === ActionResult::FAILURE) {
                                            return [
                                                'status'  => 'error',
                                                'message' => 'An unexpected error occurred. Please try again later.'
                                            ];
                                        }
                                    }
                                }

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
                            }

                            if ( ! $workSchedule['is_flextime'] && $isFirstRecord) {
                                if ($attendanceCheckInTime <= $workScheduleStartTime) {
                                    $attendanceCheckInTime = $workScheduleStartTime;
                                }

                                $gracePeriod = (int) $this->settingRepository->fetchSettingValue('grace_period', 'work_schedule');

                                if ($gracePeriod === ActionResult::FAILURE) {
                                    return [
                                        'status' => 'error7',
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
                                                } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                                    $holidayType = 'regular_holiday';
                                                } else {
                                                    $holidayType = 'special_holiday';
                                                }
                                            }

                                            $hoursWorked -= $breakDuration / 60;
                                            if ($holidayType !== 'regular_holiday' && $holidayType !== 'double_holiday') {
                                                $hoursWithoutOvertime -= $breakDuration / 60;
                                            }
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
                                                    } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                                        $holidayType = 'regular_holiday';
                                                    } else {
                                                        $holidayType = 'special_holiday';
                                                    }
                                                }

                                                $hoursWorked -= $remainingMinutes / 60;
                                                if ($holidayType !== 'regular_holiday' && $holidayType !== 'double_holiday') {
                                                    $hoursWithoutOvertime -= $remainingMinutes / 60;
                                                }
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

                                                $isHoliday = ! empty($datesMarkedAsHoliday[$currentDate]);
                                                $holidayType = 'non_holiday';

                                                if ($isHoliday) {
                                                    if (count($datesMarkedAsHoliday[$currentDate]) > 1) {
                                                        $holidayType = 'double_holiday';
                                                    } elseif ($datesMarkedAsHoliday[$currentDate][0]['is_paid']) {
                                                        $holidayType = 'regular_holiday';
                                                    } else {
                                                        $holidayType = 'special_holiday';
                                                    }
                                                }

                                                $hoursWorked--;
                                                if ($holidayType !== 'regular_holiday' && $holidayType !== 'double_holiday') {
                                                    $hoursWithoutOvertime--;
                                                }
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
                                                    } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                                        $holidayType = 'regular_holiday';
                                                    } else {
                                                        $holidayType = 'special_holiday';
                                                    }
                                                }

                                                $hoursWorked -= $endMinutes / 60;
                                                if ($holidayType !== 'regular_holiday' && $holidayType !== 'double_holiday') {
                                                    $hoursWithoutOvertime -= $endMinutes / 60;
                                                }
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
                                    } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                        $holidayType = 'regular_holiday';
                                    } else {
                                        $holidayType = 'special_holiday';
                                    }
                                }

                                $hoursWorked += $remainingMinutes / 60;
                                if ($isNightShift) {
                                    if ($hoursWorked > $totalRequiredHours) {
                                        if ($isOvertimeApproved) {
                                            $hourSummary[$dayType][$holidayType]['night_differential_overtime'] += $remainingMinutes / 60;
                                        }
                                    } else {
                                        if ($holidayType !== 'regular_holiday' && $holidayType !== 'double_holiday') {
                                            $hoursWithoutOvertime += $remainingMinutes / 60;
                                        }
                                        $hourSummary[$dayType][$holidayType]['night_differential'] += $remainingMinutes / 60;
                                    }
                                } else {
                                    if ($hoursWorked > $totalRequiredHours) {
                                        if ($isOvertimeApproved || $workSchedule['is_flextime']) {
                                            $hourSummary[$dayType][$holidayType]['overtime_hours'] += $remainingMinutes / 60;
                                        }
                                    } else {
                                        if ($holidayType !== 'regular_holiday' && $holidayType !== 'double_holiday') {
                                            $hoursWithoutOvertime += $remainingMinutes / 60;
                                        }
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
                                    } elseif ($datesMarkedAsHoliday[$currentDate][0]['is_paid']) {
                                        $holidayType = 'regular_holiday';
                                    } else {
                                        $holidayType = 'special_holiday';
                                    }
                                }

                                $hoursWorked++;
                                if ($isNightShift) {
                                    if ($hoursWorked > $totalRequiredHours) {
                                        if ($isOvertimeApproved || $workSchedule['is_flextime']) {
                                            $hourSummary[$dayType][$holidayType]['night_differential_overtime']++;
                                        }
                                    } else {
                                        if ($holidayType !== 'regular_holiday' && $holidayType !== 'double_holiday') {
                                            $hoursWithoutOvertime++;
                                        }
                                        $hourSummary[$dayType][$holidayType]['night_differential']++;
                                    }
                                } else {
                                    if ($hoursWorked > $totalRequiredHours) {
                                        if ($isOvertimeApproved || $workSchedule['is_flextime']) {
                                            $hourSummary[$dayType][$holidayType]['overtime_hours']++;
                                        }
                                    } else {
                                        if ($holidayType !== 'regular_holiday' && $holidayType !== 'double_holiday') {
                                            $hoursWithoutOvertime++;
                                        }
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
                                    } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                        $holidayType = 'regular_holiday';
                                    } else {
                                        $holidayType = 'special_holiday';
                                    }
                                }

                                $hoursWorked += $endMinutes / 60;
                                if ($isNightShift) {
                                    if ($hoursWorked > $totalRequiredHours) {
                                        if ($isOvertimeApproved || $workSchedule['is_flextime']) {
                                            $hourSummary[$dayType][$holidayType]['night_differential_overtime'] += $endMinutes / 60;
                                        }
                                    } else {
                                        if ($holidayType !== 'regular_holiday' && $holidayType !== 'double_holiday') {
                                            $hoursWithoutOvertime += $endMinutes / 60;
                                        }
                                        $hourSummary[$dayType][$holidayType]['night_differential'] += $endMinutes / 60;
                                    }
                                } else {
                                    if ($hoursWorked > $totalRequiredHours) {
                                        if ($isOvertimeApproved || $workSchedule['is_flextime']) {
                                            $hourSummary[$dayType][$holidayType]['overtime_hours'] += $endMinutes / 60;
                                        }
                                    } else {
                                        if ($holidayType !== 'regular_holiday' && $holidayType !== 'double_holiday') {
                                            $hoursWithoutOvertime += $endMinutes / 60;
                                        }
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
                                    } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                        $holidayType = 'regular_holiday';
                                    }
                                }

                                if ($holidayType === 'non_holiday') {
                                    if ($datesMarkedAsLeave[$date]['is_leave'] && $datesMarkedAsLeave[$date]['is_paid'] && ( ! $datesMarkedAsLeave[$date]['is_half_day']) && empty($attendanceRecords)) {
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
                                    } elseif ($datesMarkedAsHoliday[$currentDate][0]['is_paid']) {
                                        $holidayType = 'regular_holiday';
                                    }
                                }

                                if ($holidayType === 'non_holiday') {
                                    if ($datesMarkedAsLeave[$date]['is_leave'] && $datesMarkedAsLeave[$date]['is_paid'] && ( ! $datesMarkedAsLeave[$date]['is_half_day']) && empty($attendanceRecords)) {
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
                                    } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                        $holidayType = 'regular_holiday';
                                    }
                                }

                                if ($holidayType === 'non_holiday') {
                                    if ($datesMarkedAsLeave[$date]['is_leave'] && $datesMarkedAsLeave[$date]['is_paid'] && ( ! $datesMarkedAsLeave[$date]['is_half_day']) && empty($attendanceRecords)) {
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
                            } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                $holidayType = 'regular_holiday';
                            }
                        }

                        if ($holidayType === 'non_holiday') {
                            if ($datesMarkedAsLeave[$date]['is_leave'] && $datesMarkedAsLeave[$date]['is_paid'] && ( ! $datesMarkedAsLeave[$date]['is_half_day']) && empty($attendanceRecords)) {
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
                            } elseif ($datesMarkedAsHoliday[$currentDate][0]['is_paid']) {
                                $holidayType = 'regular_holiday';
                            }
                        }

                        if ($holidayType === 'non_holiday') {
                            if ($datesMarkedAsLeave[$date]['is_leave'] && $datesMarkedAsLeave[$date]['is_paid'] && ( ! $datesMarkedAsLeave[$date]['is_half_day']) && empty($attendanceRecords)) {
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
                            } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                $holidayType = 'regular_holiday';
                            }
                        }

                        if ($holidayType === 'non_holiday') {
                            if ($datesMarkedAsLeave[$date]['is_leave'] && $datesMarkedAsLeave[$date]['is_paid'] && ( ! $datesMarkedAsLeave[$date]['is_half_day']) && empty($attendanceRecords)) {
                                $hourSummary1[$dayType][$holidayType]['regular_hours'] += $endMinutes / 60;
                            }
                        } else {
                            $hourSummary1[$dayType][$holidayType]['regular_hours'] += $endMinutes / 60;
                        }
                    }
                }

                $totalDaysWorked += $hoursWorked / $totalRequiredHours;

                if ($datesMarkedAsLeave[$date]['is_leave'   ] &&
                    $datesMarkedAsLeave[$date]['is_paid'    ] &&
                    $datesMarkedAsLeave[$date]['is_half_day'] &&
                    empty($datesMarkedAsHoliday[$date      ])) {
                    $hourSummary1['regular_day']['non_holiday']['regular_hours'] += $totalRequiredHours / 2;
                }

                $totalActualHoursWorked += $hoursWorked;
            }

            $hourlyRate = $basicSalary / ($workHoursPerDay * 26);

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
                    'status'  => 'error6',
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

            $payrollGroupFrequency = strtolower($payrollGroup->getPayrollFrequency());

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
                    'status'  => 'error5',
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

            $overtimeRateAssignmentId = $this->overtimeRateAssignmentRepository->findOvertimeRateAssignmentId($overtimeRateAssignment);

            if ($overtimeRateAssignmentId === ActionResult::FAILURE) {
                return [
                    'status'  => 'error4',
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

            $totalRegularHours              = 0;
            $totalOvertimeHours             = 0;
            $totalNightDifferential         = 0;
            $totalNightDifferentialOvertime = 0;
            $totalRegularHolidayHours       = 0;
            $totalSpecialHolidayHours       = 0;

            $grossPay = 0;

            foreach ($overtimeRates as $rate) {
                $dayType     = strtolower(str_replace(['-', ' '], '_', $rate['day_type'    ]));
                $holidayType = strtolower(str_replace(['-', ' '], '_', $rate['holiday_type']));

                $regularTimeRate       = $rate['regular_time_rate'      ];
                $nightDifferentialRate = $rate['night_differential_rate'];

                if ($holidayType === 'regular_holiday') {
                    $regularTimeRate       -= 1.0;
                    $nightDifferentialRate -= 1.0;
                } elseif ($holidayType === 'double_holiday') {
                    $regularTimeRate       -= 2.0;
                    $nightDifferentialRate -= 2.0;
                }

                $regularTimeRate       = max(0, $regularTimeRate      );
                $nightDifferentialRate = max(0, $nightDifferentialRate);

                $grossPay += $hourSummary[$dayType][$holidayType]['regular_hours'              ] * $hourlyRate * $regularTimeRate                             ;
                $grossPay += $hourSummary[$dayType][$holidayType]['overtime_hours'             ] * $hourlyRate * $rate['overtime_rate'                       ];
                $grossPay += $hourSummary[$dayType][$holidayType]['night_differential'         ] * $hourlyRate * $nightDifferentialRate                       ;
                $grossPay += $hourSummary[$dayType][$holidayType]['night_differential_overtime'] * $hourlyRate * $rate['night_differential_and_overtime_rate'];

                if ($holidayType === 'regular_holiday') {
                    $totalRegularHolidayHours += $hourSummary[$dayType][$holidayType]['regular_hours'] + $hourSummary[$dayType][$holidayType]['overtime_hours'] + $hourSummary[$dayType][$holidayType]['night_differential'] + $hourSummary[$dayType][$holidayType]['night_differential_overtime'];
                } elseif ($holidayType === 'special_holiday') {
                    $totalSpecialHolidayHours += $hourSummary[$dayType][$holidayType]['regular_hours'] + $hourSummary[$dayType][$holidayType]['overtime_hours'] + $hourSummary[$dayType][$holidayType]['night_differential'] + $hourSummary[$dayType][$holidayType]['night_differential_overtime'];
                }

                $totalRegularHours              += $hourSummary[$dayType][$holidayType]['regular_hours'              ];
                $totalOvertimeHours             += $hourSummary[$dayType][$holidayType]['overtime_hours'             ];
                $totalNightDifferential         += $hourSummary[$dayType][$holidayType]['night_differential'         ];
                $totalNightDifferentialOvertime += $hourSummary[$dayType][$holidayType]['night_differential_overtime'];
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

            $grossPay += $totalAllowances;

            $sssContribution         = $this->calculateSssContribution        ($basicSalary);
            $philhealthContribution  = $this->calculatePhilhealthContribution ($basicSalary, (int) $cutoffPeriodStartDate->format('Y'));
            $pagibigFundContribution = $this->calculatePagibigFundContribution($basicSalary);

            $totalSssDeduction         = 0;
            $totalPhilhealthDeduction  = 0;
            $totalPagibigFundDeduction = 0;
            $withholdingTax            = 0;

            if (strtolower($payrollGroup->getPayrollFrequency()) === 'weekly') {
                $totalSssDeduction         = $sssContribution        ['employee_share'] / 4;
                $totalPhilhealthDeduction  = $philhealthContribution ['employee_share'] / 4;
                $totalPagibigFundDeduction = $pagibigFundContribution['employee_share'] / 4;
            } elseif (strtolower($payrollGroup->getPayrollFrequency()) === 'bi-weekly' || strtolower($payrollGroup->getPayrollFrequency()) === 'semi-monthly') {
                $totalSssDeduction         = $sssContribution        ['employee_share'] / 2;
                $totalPhilhealthDeduction  = $philhealthContribution ['employee_share'] / 2;
                $totalPagibigFundDeduction = $pagibigFundContribution['employee_share'] / 2;
            } elseif (strtolower($payrollGroup->getPayrollFrequency()) === 'monthly') {
                $totalSssDeduction         = $sssContribution        ['employee_share'] / 1;
                $totalPhilhealthDeduction  = $philhealthContribution ['employee_share'] / 1;
                $totalPagibigFundDeduction = $pagibigFundContribution['employee_share'] / 1;
            }

            $netPay = $grossPay - ($totalSssDeduction + $totalPhilhealthDeduction + $totalPagibigFundDeduction + $totalDeductions);
            $withholdingTax = $this->calculateWithholdingTax($netPay, strtolower($payrollGroup->getPayrollFrequency()));
            $netPay = $netPay - $withholdingTax;

            $thirteenMonthPay = 0;
            $leaveSalary = 0;

            switch(strtolower($payrollGroup->getPayrollFrequency())) {
                case 'weekly':
                    if ($cutoffPeriodEndDate->format('m') === '12' && (int) $cutoffPeriodStartDate->format('W') === 2) {
                        $unusedCredits = $this->leaveEntitlementRepository->fetchAllLeaveEntitlements(['remaining_days'], [
                            [
                                'column'   => 'leave_entitlement.employee_id',
                                'operator' => '=',
                                'value'    => $employeeId
                            ]
                        ]);

                        if ($unusedCredits === ActionResult::FAILURE) {
                            return [
                                'status'  => 'error',
                                'message' => 'An unexpected error occurred. Please try again later.'
                            ];
                        }

                        $unusedCredits = $unusedCredits['result_set'];

                        $creditsToEncash = 0;

                        if ( ! empty($unusedCredits)) {
                            foreach ($unusedCredits as $unusedCredit) {
                                $creditsToEncash += $unusedCredit['remaining_days'];
                            }
                        }

                        $leaveSalary = ($basicSalary / 26) * $creditsToEncash;
                        $this->leaveEntitlementRepository->resetEmployeeAllLeaveBalances($employeeId);

                        //$numberOfMonthsWorked = $this->attendanceRepository->checkAttendancePerMonth($employeeId);
                        //$thirteenMonthPay = $basicSalary * count($numberOfMonthsWorked) / 12;
                    }

                    break;

                case 'semi-monthly':
                    if ($cutoffPeriodStartDate->format('m') === '12' && $cutoffPeriodEndDate->format('m') === '12') {
                        $unusedCredits = $this->leaveEntitlementRepository->fetchAllLeaveEntitlements(['remaining_days'], [
                            [
                                'column'   => 'leave_entitlement.employee_id',
                                'operator' => '=',
                                'value'    => $employeeId
                            ]
                        ]);

                        if ($unusedCredits === ActionResult::FAILURE) {
                            return [
                                'status'  => 'error',
                                'message' => 'An unexpected error occurred. Please try again later.'
                            ];
                        }

                        $unusedCredits = $unusedCredits['result_set'];

                        $creditsToEncash = 0;

                        if ( ! empty($unusedCredits)) {
                            foreach ($unusedCredits as $unusedCredit) {
                                $creditsToEncash += $unusedCredit['remaining_days'];
                            }
                        }

                        $leaveSalary = ($basicSalary / 26) * $creditsToEncash;
                        $this->leaveEntitlementRepository->resetEmployeeAllLeaveBalances($employeeId);

                        //$numberOfMonthsWorked = $this->attendanceRepository->checkAttendancePerMonth($employeeId);
                        //$thirteenMonthPay = $basicSalary * count($numberOfMonthsWorked) / 12;
                    }

                    break;

                case 'monthly':
                    if ($cutoffPeriodEndDate->format('m') === '12') {
                        $unusedCredits = $this->leaveEntitlementRepository->fetchAllLeaveEntitlements(['remaining_days'], [
                            [
                                'column'   => 'leave_entitlement.employee_id',
                                'operator' => '=',
                                'value'    => $employeeId
                            ]
                        ]);

                        if ($unusedCredits === ActionResult::FAILURE) {
                            return [
                                'status'  => 'error',
                                'message' => 'An unexpected error occurred. Please try again later.'
                            ];
                        }

                        $unusedCredits = $unusedCredits['result_set'];

                        $creditsToEncash = 0;

                        if ( ! empty($unusedCredits)) {
                            foreach ($unusedCredits as $unusedCredit) {
                                $creditsToEncash += $unusedCredit['remaining_days'];
                            }
                        }

                        $leaveSalary = ($basicSalary / 26) * $creditsToEncash;
                        $this->leaveEntitlementRepository->resetEmployeeAllLeaveBalances($employeeId);

                        /*
                        $numberOfMonths = $this->attendanceRepository->checkAttendancePerMonth($employeeId);

                        if ($numberOfMonths === ActionResult::FAILURE) {
                            return [
                                'status'  => 'error2',
                                'message' => 'An unexpected error occurred. Please try again later.'
                            ];
                        }

                        $numberOfMonths = count($numberOfMonths);
                        $thirteenMonthPay = $basicSalary * $numberOfMonths / 12;
                        */
                    }

                    break;

                default:
                    // Do nothing
            }

            $payslip = new Payslip(
                id                            : null                                     ,
                employeeId                    : $employeeId                              ,
                payrollGroupId                : $payrollGroup->getId()                   ,
                paydayDate                    : $paydayDate                             ,
                cutoffStartDate               : $cutoffPeriodStartDate->format('Y-m-d'),
                cutoffEndDate                 : $cutoffPeriodEndDate  ->format('Y-m-d'),
                totalRegularHours             : $totalRegularHours                       ,
                totalOvertimeHours            : $totalOvertimeHours                      ,
                totalNightDifferential        : $totalNightDifferential                  ,
                totalNightDifferentialOvertime: $totalNightDifferentialOvertime          ,
                totalRegularHolidayHours      : $totalRegularHolidayHours                ,
                totalSpecialHolidayHours      : $totalSpecialHolidayHours                ,
                totalDaysWorked               : $totalDaysWorked                         ,
                totalHoursWorked              : $totalActualHoursWorked                  ,
                grossPay                      : $grossPay                                ,
                netPay                        : $netPay                                  ,
                sssDeduction                  : $totalSssDeduction                       ,
                philhealthDeduction           : $totalPhilhealthDeduction                ,
                pagibigFundDeduction          : $totalPagibigFundDeduction               ,
                withholdingTax                : $withholdingTax                          ,
                thirteenMonthPay              : $thirteenMonthPay                        ,
                leaveSalary                   : $leaveSalary
            );

            $result = $this->payslipRepository->createPayslip($payslip);

            if ($result === ActionResult::FAILURE) {
                return [
                    'status'  => 'error1',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }
        }
    }

    private function isAbsentBefore(int $employeeId, string $date): array|bool
    {
        $previousDate = new DateTime($date);

        $foundAbsence = false;

        while ( ! $foundAbsence) {
            $formattedPreviousDate = $previousDate->format('Y-m-d');

            $employeeWorkSchedules = $this->workScheduleRepository->getEmployeeWorkSchedules(
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
                if ($previousDate->format('l') !== 'Sunday') {
                    $datesMarkedAsHoliday = $this->holidayRepository->getHolidayDatesForPeriod(
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
                        $datesMarkedAsLeave = $this->leaveRequestRepository->getLeaveDatesForPeriod(
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

                            $employeeAttendanceRecords = $this->attendanceRepository->fetchAllAttendance([], $attendanceFilterCriteria);

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
            }

            $previousDate->modify('-1 day');
        }

        return $foundAbsence;
    }

    private function calculateSssContribution(float $salary): array
    {
        $contributionTable = [
            ["range" => [0    , 4249.99 ], "employee_share" => 180.00 , "employer_share" => 390.00 ],
            ["range" => [4250 , 4749.99 ], "employee_share" => 202.50 , "employer_share" => 437.50 ],
            ["range" => [4750 , 5249.99 ], "employee_share" => 225.00 , "employer_share" => 485.00 ],
            ["range" => [5250 , 5749.99 ], "employee_share" => 247.50 , "employer_share" => 532.50 ],
            ["range" => [5750 , 6249.99 ], "employee_share" => 270.00 , "employer_share" => 580.00 ],
            ["range" => [6250 , 6749.99 ], "employee_share" => 292.50 , "employer_share" => 627.50 ],
            ["range" => [6750 , 7249.99 ], "employee_share" => 315.00 , "employer_share" => 675.00 ],
            ["range" => [7250 , 7749.99 ], "employee_share" => 337.50 , "employer_share" => 722.50 ],
            ["range" => [7750 , 8249.99 ], "employee_share" => 360.00 , "employer_share" => 770.00 ],
            ["range" => [8250 , 8749.99 ], "employee_share" => 382.50 , "employer_share" => 817.50 ],
            ["range" => [8750 , 9249.99 ], "employee_share" => 405.00 , "employer_share" => 865.00 ],
            ["range" => [9250 , 9749.99 ], "employee_share" => 427.50 , "employer_share" => 912.50 ],
            ["range" => [9750 , 10249.99], "employee_share" => 450.00 , "employer_share" => 960.00 ],
            ["range" => [10250, 10749.99], "employee_share" => 472.50 , "employer_share" => 1007.50],
            ["range" => [10750, 11249.99], "employee_share" => 495.00 , "employer_share" => 1055.00],
            ["range" => [11250, 11749.99], "employee_share" => 517.50 , "employer_share" => 1102.50],
            ["range" => [11750, 12249.99], "employee_share" => 540.00 , "employer_share" => 1150.00],
            ["range" => [12250, 12749.99], "employee_share" => 562.50 , "employer_share" => 1197.50],
            ["range" => [12750, 13249.99], "employee_share" => 585.00 , "employer_share" => 1245.00],
            ["range" => [13250, 13749.99], "employee_share" => 607.50 , "employer_share" => 1292.50],
            ["range" => [13750, 14249.99], "employee_share" => 630.00 , "employer_share" => 1340.00],
            ["range" => [14250, 14749.99], "employee_share" => 652.50 , "employer_share" => 1387.50],
            ["range" => [14750, 15249.99], "employee_share" => 675.00 , "employer_share" => 1455.00],
            ["range" => [15250, 15749.99], "employee_share" => 697.50 , "employer_share" => 1502.50],
            ["range" => [15750, 16249.99], "employee_share" => 720.00 , "employer_share" => 1550.00],
            ["range" => [16250, 16749.99], "employee_share" => 742.50 , "employer_share" => 1597.50],
            ["range" => [16750, 17249.99], "employee_share" => 765.00 , "employer_share" => 1645.00],
            ["range" => [17250, 17749.99], "employee_share" => 787.50 , "employer_share" => 1692.50],
            ["range" => [17750, 18249.99], "employee_share" => 810.00 , "employer_share" => 1740.00],
            ["range" => [18250, 18749.99], "employee_share" => 832.50 , "employer_share" => 1787.50],
            ["range" => [18750, 19249.99], "employee_share" => 855.00 , "employer_share" => 1835.00],
            ["range" => [19250, 19749.99], "employee_share" => 877.50 , "employer_share" => 1882.50],
            ["range" => [19750, 20249.99], "employee_share" => 900.00 , "employer_share" => 1930.00],
            ["range" => [20250, 20749.99], "employee_share" => 922.50 , "employer_share" => 1977.50],
            ["range" => [20750, 21249.99], "employee_share" => 945.00 , "employer_share" => 2025.00],
            ["range" => [21250, 21749.99], "employee_share" => 967.50 , "employer_share" => 2072.50],
            ["range" => [21750, 22249.99], "employee_share" => 990.00 , "employer_share" => 2120.00],
            ["range" => [22250, 22749.99], "employee_share" => 1012.50, "employer_share" => 2167.50],
            ["range" => [22750, 23249.99], "employee_share" => 1035.00, "employer_share" => 2215.00],
            ["range" => [23250, 23749.99], "employee_share" => 1057.50, "employer_share" => 2262.50],
            ["range" => [23750, 24249.99], "employee_share" => 1080.00, "employer_share" => 2310.00],
            ["range" => [24250, 24749.99], "employee_share" => 1102.50, "employer_share" => 2357.50],
            ["range" => [24750, 25249.99], "employee_share" => 1125.00, "employer_share" => 2405.00],
            ["range" => [25250, 25749.99], "employee_share" => 1147.50, "employer_share" => 2452.50],
            ["range" => [25750, 26249.99], "employee_share" => 1170.00, "employer_share" => 2500.00],
            ["range" => [26250, 26749.99], "employee_share" => 1192.50, "employer_share" => 2547.50],
            ["range" => [26750, 27249.99], "employee_share" => 1215.00, "employer_share" => 2595.00],
            ["range" => [27250, 27749.99], "employee_share" => 1237.50, "employer_share" => 2642.50],
            ["range" => [27750, 28249.99], "employee_share" => 1260.00, "employer_share" => 2690.00],
            ["range" => [28250, 28749.99], "employee_share" => 1282.50, "employer_share" => 2737.50],
            ["range" => [28750, 29249.99], "employee_share" => 1305.00, "employer_share" => 2785.00],
            ["range" => [29250, 29749.99], "employee_share" => 1327.50, "employer_share" => 2832.50],
            ["range" => [29750, "Over"  ], "employee_share" => 1350.00, "employer_share" => 2880.00]
        ];

        foreach ($contributionTable as $row) {
            if ($row['range'][1] === 'Over') {
                if ($salary >= $row['range'][0]) {
                    return [
                        'employee_share' => $row['employee_share'],
                        'employer_share' => $row['employer_share']
                    ];
                }
            } else {
                if ($salary >= $row['range'][0] && $salary <= $row['range'][1]) {
                    return [
                        'employee_share' => $row['employee_share'],
                        'employer_share' => $row['employer_share']
                    ];
                }
            }
        }

        return [];
    }

    private function calculatePhilhealthContribution(float $salary, int $year): array
    {
        $totalContribution = 0.00;

        if ($year === 2024 || $year === 2025) {
            if ($salary <= 10000.00) {
                $totalContribution = 500.00;
            } elseif ($salary >= 10000.01 && $salary <= 99999.99) {
                $totalContribution = max(500.00, $salary * 0.05);
                if ($totalContribution > 5000.00) {
                    $totalContribution = 5000.00;
                }
            }
        }

        $employeeShare = $totalContribution / 2.0;
        $employerShare = $totalContribution / 2.0;

        return [
            'employee_share' => $employeeShare,
            'employer_share' => $employerShare
        ];
    }

    private function calculatePagibigFundContribution(float $salary): array
    {
        $employeeShare = 0.00;
        $employerShare = 0.00;

        if ($salary <= 1500) {
            $employeeShare = $salary * 0.01;
            $employerShare = $salary * 0.02;
        } else {
            $employeeShare = $salary * 0.02;
            $employerShare = $salary * 0.02;
        }

        return [
            'employee_share' => $employeeShare,
            'employer_share' => $employerShare
        ];
    }

    private function calculateWithholdingTax(float $compensation, string $frequency): float
    {
        $withholdingTax = 0.00;

        switch ($frequency) {
            case 'daily':
                if ($compensation <= 685.00) {
                    $withholdingTax = 0.00;
                } elseif ($compensation <= 1095.00) {
                    $withholdingTax = ($compensation - 685.00) * 0.15;
                } elseif ($compensation <= 2191.00) {
                    $withholdingTax = 61.65 + ($compensation - 1095.00) * 0.20;
                } elseif ($compensation <= 5478.00) {
                    $withholdingTax = 280.85 + ($compensation - 2191.00) * 0.25;
                } elseif ($compensation <= 21917.00) {
                    $withholdingTax = 1102.60 + ($compensation - 5478.00) * 0.30;
                } else {
                    $withholdingTax = 6034.30 + ($compensation - 21917.00) * 0.35;
                }
                break;

            case 'weekly':
                if ($compensation <= 4808.00) {
                    $withholdingTax = 0.00;
                } elseif ($compensation <= 7691.00) {
                    $withholdingTax = ($compensation - 4808.00) * 0.15;
                } elseif ($compensation <= 15384.00) {
                    $withholdingTax = 432.60 + ($compensation - 7691.00) * 0.20;
                } elseif ($compensation <= 38461.00) {
                    $withholdingTax = 1971.20 + ($compensation - 15384.00) * 0.25;
                } elseif ($compensation <= 153845.00) {
                    $withholdingTax = 7740.45 + ($compensation - 38461.00) * 0.30;
                } else {
                    $withholdingTax = 42355.65 + ($compensation - 153845.00) * 0.35;
                }
                break;

            case 'bi-weekly':
                if ($compensation <= 9616.00) {
                    $withholdingTax = 0.00;
                } elseif ($compensation <= 15382.00) {
                    $withholdingTax = ($compensation - 9616.00) * 0.15;
                } elseif ($compensation <= 30768.00) {
                    $withholdingTax = 865.20 + ($compensation - 15382.00) * 0.20;
                } elseif ($compensation <= 76922.00) {
                    $withholdingTax = 3942.40 + ($compensation - 30768.00) * 0.25;
                } elseif ($compensation <= 307690.00) {
                    $withholdingTax = 15480.90 + ($compensation - 76922.00) * 0.30;
                } else {
                    $withholdingTax = 84671.30 + ($compensation - 307690.00) * 0.35;
                }
                break;

            case 'semi-monthly':
                if ($compensation <= 10417.00) {
                    $withholdingTax = 0.00;
                } elseif ($compensation <= 16666.00) {
                    $withholdingTax = ($compensation - 10417.00) * 0.15;
                } elseif ($compensation <= 33332.00) {
                    $withholdingTax = 937.50 + ($compensation - 16666.00) * 0.20;
                } elseif ($compensation <= 83332.00) {
                    $withholdingTax = 4270.70 + ($compensation - 33332.00) * 0.25;
                } elseif ($compensation <= 333332.00) {
                    $withholdingTax = 16770.70 + ($compensation - 83332.00) * 0.30;
                } else {
                    $withholdingTax = 91770.70 + ($compensation - 333332.00) * 0.35;
                }
                break;

            case 'monthly':
                if ($compensation <= 20833.00) {
                    $withholdingTax = 0.00;
                } elseif ($compensation <= 33332.00) {
                    $withholdingTax = ($compensation - 20833.00) * 0.15;
                } elseif ($compensation <= 66666.00) {
                    $withholdingTax = 1875.00 + ($compensation - 33332.00) * 0.20;
                } elseif ($compensation <= 166666.00) {
                    $withholdingTax = 8541.80 + ($compensation - 66666.00) * 0.25;
                } elseif ($compensation <= 666666.00) {
                    $withholdingTax = 33541.80 + ($compensation - 166666.00) * 0.30;
                } else {
                    $withholdingTax = 183541.80 + ($compensation - 666666.00) * 0.35;
                }
                break;

            default:
                // Do nothing
        }

        return round($withholdingTax, 2);
    }
}
