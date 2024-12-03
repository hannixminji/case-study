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

    public function __construct(
        EmployeeRepository               $employeeRepository              ,
        WorkScheduleRepository           $workScheduleRepository          ,
        AttendanceRepository             $attendanceRepository            ,
        OvertimeRateAssignmentRepository $overtimeRateAssignmentRepository,
        OvertimeRateRepository           $overtimeRateRepository          ,
        HolidayRepository                $holidayRepository               ,
        LeaveRequestRepository           $leaveRequestRepository          ,
        EmployeeAllowanceRepository      $employeeAllowanceRepository
    ) {
        $this->employeeRepository               = $employeeRepository              ;
        $this->workScheduleRepository           = $workScheduleRepository          ;
        $this->attendanceRepository             = $attendanceRepository            ;
        $this->overtimeRateAssignmentRepository = $overtimeRateAssignmentRepository;
        $this->overtimeRateRepository           = $overtimeRateRepository          ;
        $this->holidayRepository                = $holidayRepository               ;
        $this->leaveRequestRepository           = $leaveRequestRepository          ;
        $this->employeeAllowanceRepository      = $employeeAllowanceRepository     ;
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

                if ( ! empty($attendanceRecords)) {
                    $records = [];

                    foreach ($workSchedules as $date => $schedules) {
                        foreach ($schedules as $workSchedule) {
                            $isAttendanceRecordFound = false;

                            foreach ($attendanceRecords as $attendanceRecord) {
                                if ($attendanceRecord['date'] === $date && $attendanceRecord['work_schedule_id'] === $workSchedule['id']) {
                                    $isAttendanceRecordFound = true;

                                    $records[$date][] = [
                                        'work_schedule'     => $workSchedule    ,
                                        'attendance_record' => $attendanceRecord
                                    ];

                                    break;
                                }
                            }

                            if ( ! $isAttendanceRecordFound) {
                                $records[$date][] = [
                                    'work_schedule'     => $workSchedule,
                                    'attendance_record' => []
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

                    $totalScheduledHours = 0;

                    $uniqueWorkSchedules = [];

                    foreach ($workSchedules as $date => $schedules) {
                        $uniqueWorkSchedules = [];

                        foreach ($schedules as $workSchedule) {
                            $uniqueWorkSchedules[$workSchedule['id']] = $workSchedule;
                        }

                        foreach ($uniqueWorkSchedules as $uniqueWorkSchedule) {
                            $startTime = new DateTime($uniqueWorkSchedule['start_time']);
                            $endTime   = new DateTime($uniqueWorkSchedule['end_time'  ]);

                            $interval = $startTime->diff($endTime);
                            $totalScheduledHours += $interval->h + ($interval->i / 60);
                        }
                    }

                    $totalDaysPresent        = 0;
                    $totalDaysAbsent         = 0;
                    $totalPartialAbsent      = 0;
                    $totalMinutesLate        = 0;
                    $totalUndertimeInMinutes = 0;
                    $totalDaysUnpaidLeave    = 0;
                    $totalDaysPaidLeave      = 0;

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
                        $dayType   = $dayOfWeek === 'Sunday' ? 'rest_day' : 'regular_day';

                        $isHoliday = empty($datesMarkedAsHoliday[$date]);
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
                            $workSchedule     = $record['work_schedule'    ];
                            $attendanceRecord = $record['attendance_record'];

                            $attendanceCheckInTime  = new DateTime($attendanceRecord['check_in_time' ]);
                            $attendanceCheckOutTime = new DateTime($attendanceRecord['check_out_time']);
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
}
