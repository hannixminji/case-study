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
        BreakScheduleRepository          $breakScheduleRepository
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
    }

    public function generatePaySlips(PayrollGroup $payrollGroup, string $cutoffStartDate, string $cutoffEndDate): array|null
	{
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
				'value'    => $payrollGroup->getId()
			],
		];

		$employees = $this->employeeRepository->fetchAllEmployees($employeeColumns, $filterCriteria);

		if ($employees === ActionResult::FAILURE) {
			return [
				'status'  => 'error',
				'message' => 'An unexpected error occurred. Please try again later.'
			];
		}

		if (empty($employees) || empty($employees['result_set'])) {
			return null;
		}

		$employees = $employees['result_set'];

        $cutoffStartDate = new DateTime($cutoffStartDate);
        $cutoffEndDate   = new DateTime($cutoffEndDate  );

		foreach ($employees as $employee) {
		    $employeeId   = $employee['id'           ];
		    $jobTitleId   = $employee['job_title_id' ];
		    $departmentId = $employee['department_id'];

		    $workSchedules = $this->workScheduleRepository->getEmployeeWorkSchedules(
		        $employeeId,
		        $cutoffStartDate->format('Y-m-d'),
		        $cutoffEndDate->format('Y-m-d')
            );

            if ($workSchedules === ActionResult::FAILURE) {
    			return [
    				'status'  => 'error',
    				'message' => 'An unexpected error occurred. Please try again later.'
    			];
    		}

            if ( ! empty($workSchedules)) {
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
                        'value'    => $cutoffStartDate->format('Y-m-d')
                    ],
                                    [
                        'column'   => 'attendance.date',
                        'operator' => '<=',
                        'value'    =>  $cutoffEndDate->format('Y-m-d')
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
                
                if ( ! empty($attendanceRecords)) {
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

                    $datesMarkedAsHoliday = $this->holidayRepository->getHolidayDatesForPeriod(
                        $cutoffStartDate->format('Y-m-d'),
                        $cutoffEndDate->format('Y-m-d')
                    );

                    $datesMarkedAsLeave = $this->leaveRequestRepository->getLeaveDatesForPeriod(
                        $employeeId,
                        $cutoffStartDate->format('Y-m-d'),
                        $cutoffEndDate->format('Y-m-d')
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

                    foreach ($records as $date => $recordEntries) {
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

                        foreach ($recordEntries as $record) {
                            $workSchedule = $record['work_schedule'];
                            $attendanceRecords = $record['attendance_records'];

                            if ($attendanceRecords) {
                                foreach ($attendanceRecords as $attendanceRecord) {
                                    $workScheduleStartTime = new DateTime($workSchedule['start_time']);
                                    $workScheduleEndTime = new DateTime($workSchedule['end_time']);
                                    $workScheduleStartTime = new DateTime($attendanceRecord['date'] . ' ' . (new DateTime($workSchedule['start_time']))->format('H:i:s'));
                                    $workScheduleEndTime = new DateTime($attendanceRecord['date'] . ' ' . (new DateTime($workSchedule['end_time']))->format('H:i:s'));

                                    if ($workScheduleEndTime->format('H:i:s') < $workScheduleStartTime->format('H:i:s')) {
                                        $workScheduleEndTime->modify('+1 day');
                                    }

                                    $attendanceCheckInTime = new DateTime($attendanceRecord['check_in_time']);
                                    $attendanceCheckOutTime = $attendanceRecord['check_out_time']
                                        ? new DateTime($attendanceRecord['check_out_time'])
                                        : $workScheduleEndTime;

                                    if (!$workSchedule['is_flextime']) {
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
                                        }
                                    }

                                    $isOvertimeApproved = $attendanceRecord['is_overtime_approved'];

                                    $startMinutes = (int)$attendanceCheckInTime->format('i');
                                    if ($startMinutes > 0) {
                                        $remainingMinutes = 60 - $startMinutes;
                                        $hour = (int)$attendanceCheckInTime->format('H');
                                        $isNightShift = ($hour >= 22 || $hour < 6);

                                        if ($isNightShift) {
                                            if ($attendanceCheckInTime >= $workScheduleEndTime) {
                                                if ($isOvertimeApproved) {
                                                    $hourSummary[$dayType][$holidayType]['night_differential_overtime'] += $remainingMinutes / 60;
                                                }
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['night_differential'] += $remainingMinutes / 60;
                                            }
                                        } else {
                                            if ($attendanceCheckInTime >= $workScheduleEndTime) {
                                                if ($isOvertimeApproved) {
                                                    $hourSummary[$dayType][$holidayType]['overtime_hours'] += $remainingMinutes / 60;
                                                }
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['regular_hours'] += $remainingMinutes / 60;
                                            }
                                        }

                                        $attendanceCheckInTime->modify('+' . $remainingMinutes . ' minutes');
                                    }

                                    $endMinutes = (int)$attendanceCheckOutTime->format('i');
                                    if ($endMinutes > 0) {
                                        $roundedCheckOutTime = clone $attendanceCheckOutTime;
                                        $roundedCheckOutTime->modify('-' . $endMinutes . ' minutes');

                                        $hour = (int)$attendanceCheckOutTime->format('H');
                                        $isNightShift = ($hour >= 22 || $hour < 6);

                                        if ($isNightShift) {
                                            if ($roundedCheckOutTime >= $workScheduleEndTime) {
                                                if ($isOvertimeApproved) {
                                                    $hourSummary[$dayType][$holidayType]['night_differential_overtime'] += $endMinutes / 60;
                                                }
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['night_differential'] += $endMinutes / 60;
                                            }
                                        } else {
                                            if ($roundedCheckOutTime >= $workScheduleEndTime) {
                                                if ($isOvertimeApproved) {
                                                    $hourSummary[$dayType][$holidayType]['overtime_hours'] += $endMinutes / 60;
                                                }
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['regular_hours'] += $endMinutes / 60;
                                            }
                                        }

                                        $attendanceCheckOutTime = $roundedCheckOutTime;
                                    }

                                    $dateInterval = new DateInterval('PT1H');
                                    $datePeriod = new DatePeriod($attendanceCheckInTime, $dateInterval, $attendanceCheckOutTime);

                                    foreach ($datePeriod as $currentTime) {
                                        $hour = (int) $currentTime->format('H');
                                        $isNightShift = ($hour >= 22 || $hour < 6);

                                        if ($isNightShift) {
                                            if ($currentTime >= $workScheduleEndTime) {
                                                if ($isOvertimeApproved) {
                                                    $hourSummary[$dayType][$holidayType]['night_differential_overtime']++;
                                                }
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['night_differential']++;
                                            }
                                        } else {
                                            if ($currentTime >= $workScheduleEndTime) {
                                                if ($isOvertimeApproved) {
                                                    $hourSummary[$dayType][$holidayType]['overtime_hours']++;
                                                }
                                            } else {
                                                $hourSummary[$dayType][$holidayType]['regular_hours']++;
                                            }
                                        }
                                    }

                                    $employeeBreakColumns = [
                                        'id',
                                        'break_schedule_id',
                                        'start_time',
                                        'end_time',
                                        'break_duration_in_minutes',
                                        'work_schedule_id',
                                        'break_type_id',
                                        'break_schedule_start_time',
                                        'break_schedule_is_flexible',
                                        'break_schedule_earliest_start_time',
                                        'break_schedule_latest_end_time',
                                        'break_type_duration_in_minutes',
                                        'break_type_is_paid'
                                    ];

                                    $filterCriteria = [
                                        [
                                            'column'   => 'work_schedule.employee_id',
                                            'operator' => '=',
                                            'value'    => $employeeId
                                        ],
                                        [
                                            'column'      => 'employee_break.created_at',
                                            'operator'    => 'BETWEEN',
                                            'lower_bound' => $workScheduleStartTime->format('Y-m-d H:i:s'),
                                            'upper_bound' => $workScheduleEndTime->format('Y-m-d H:i:s')
                                        ]
                                    ];

                                    $sortCriteria = [
                                        [
                                            'column'    => 'break_schedule.start_time',
                                            'direction' => 'ASC'
                                        ],
                                        [
                                            'column'    => 'break_schedule.earliest_start_time',
                                            'direction' => 'ASC'
                                        ]
                                    ];

                                    $result = $this->employeeBreakRepository->fetchAllEmployeeBreaks(
                                        columns       : $employeeBreakColumns,
                                        filterCriteria: $filterCriteria,
                                        sortCriteria  : $sortCriteria
                                    );

                                    if ($result === ActionResult::FAILURE) {
                                        return [
                                            'status'  => 'error',
                                            'message' => 'An unexpected error occurred. Please try again later.'
                                        ];
                                    }

                                    $employeeBreaks = $result['result_set'];



























                                }
                            }
                        }
                    }






                }
            }
		}

        return [];
	}

    private function fetchEmployeeAllowances(int $employeeId): ActionResult|array
    {
        $columns = [
            'id'                      ,
            'allowance_id'            ,
            'allowance_is_taxable'    ,
            'allowance_frequency'     ,
            'allowance_status'        ,
            'allowance_effective_date',
            'allowance_end_date'      ,
            'amount'
        ];

        $filterCriteria = [
            [
                'column'   => 'employee_allowance.deleted_at',
                'operator' => 'IS NULL'
            ],
            [
                'column'   => 'employee_allowance.employee_id',
                'operator' => '=',
                'value'    => $employeeId
            ],
            [
                'column'   => 'allowance.status',
                'operator' => '=',
                'value'    => "'Active'"
            ]
        ];

        $employeeAllowances = $this->employeeAllowanceRepository->fetchAllEmployeeAllowances($columns, $filterCriteria);

        if ($employeeAllowances === ActionResult::FAILURE) {
            return ActionResult::FAILURE;
        }

        return empty($employeeAllowances)
            ? []
            : $employeeAllowances['result_set'];
    }

    private function getCurrentBreakSchedule(array $breakSchedules, string $currentTime): array
    {
        $currentBreakSchedule = [];
        $nextBreakSchedule    = [];

        $currentTime = (new DateTime($currentTime))->format('H:i:s');

        foreach ($breakSchedules as $breakSchedule) {
            if ( ! $breakSchedule['is_flexible']) {
                $startTime = (new DateTime($breakSchedule['start_time']))->format('H:i:s');

                $endTime = (new DateTime($breakSchedule['start_time']))
                    ->modify('+' . $breakSchedule['break_type_duration_in_minutes'] . ' minutes')
                    ->format('H:i:s');

                $breakSchedule['end_time'] = $endTime;

                if ($endTime < $startTime) {
                    if ($currentTime >= $startTime || $currentTime <= $endTime) {
                        $currentBreakSchedule = $breakSchedule;
                        break;
                    }
                } else {
                    if ($currentTime >= $startTime && $currentTime <= $endTime) {
                        $currentBreakSchedule = $breakSchedule;
                        break;
                    }
                }

                if (empty($nextBreakSchedule) && $currentTime < $startTime) {
                    $nextBreakSchedule = $breakSchedule;
                }

            } else {
                $earliestStartTime = (new DateTime($breakSchedule['earliest_start_time']))->format('H:i:s');
                $latestEndTime     = (new DateTime($breakSchedule['latest_end_time'    ]))->format('H:i:s');

                if ($latestEndTime < $earliestStartTime) {
                    if ($currentTime >= $earliestStartTime || $currentTime <= $latestEndTime) {
                        $currentBreakSchedule = $breakSchedule;
                        break;
                    }
                } else {
                    if ($currentTime >= $earliestStartTime && $currentTime <= $latestEndTime) {
                        $currentBreakSchedule = $breakSchedule;
                        break;
                    }
                }

                if (empty($nextBreakSchedule) && $currentTime < $earliestStartTime) {
                    $nextBreakSchedule = $breakSchedule;
                }
            }
        }

        if (empty($currentBreakSchedule) && ! empty($nextBreakSchedule)) {
            $currentBreakSchedule = $nextBreakSchedule;
        }

        return $currentBreakSchedule;
    }
}
