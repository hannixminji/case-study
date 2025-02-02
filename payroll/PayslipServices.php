<?php

require_once __DIR__ . '/Payslip.php'                                           ;

require_once __DIR__ . '/PayslipRepository.php'                                 ;

require_once __DIR__ . '/../employees/EmployeeRepository.php'                   ;
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

class PayslipServices
{
    private readonly PayslipRepository                $payslipRepository               ;
    private readonly EmployeeRepository               $employeeRepository              ;
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

    public function generatePayslip(
        int    $payrollGroupId       ,
        string $payrollFrequency     ,
        string $cutoffPeriodStartDate,
        string $cutoffPeriodEndDate  ,
        string $paydayDate
    ): array {

        $employeeColumns = [
            'id'           ,
            'job_title_id' ,
            'department_id',
            'basic_salary'
        ];

        $employeeFilterCriteria = [
            [
                'column'   => 'employee.deleted_at',
                'operator' => 'IS NULL'
            ],
            [
                'column'   => 'employee.access_role',
                'operator' => '!='                  ,
                'value'    => 'Admin'
            ],
            [
                'column'   => 'employee.payroll_group_id',
                'operator' => '='                        ,
                'value'    => $payrollGroupId
            ]
        ];

        $employeeFetchResult = $this->employeeRepository->fetchAllEmployees(
            columns       : $employeeColumns       ,
            filterCriteria: $employeeFilterCriteria
        );

        if ($employeeFetchResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        if (empty($employeeFetchResult['result_set'])) {
            return [
                'status'  => 'warning',
                'message' => ''
            ];
        }

        $employees = $employeeFetchResult['result_set'];

        foreach ($employees as $employee) {
            $employeeId   = $employee['id'           ];
            $jobTitleId   = $employee['job_title_id' ];
            $departmentId = $employee['department_id'];
            $basicSalary  = $employee['basic_salary' ];

            $attendanceColumns = [
                'id'                                ,
                'work_schedule_id'                  ,
                'date'                              ,
                'check_in_time'                     ,
                'check_out_time'                    ,
                'is_overtime_approved'              ,
                'attendance_status'                 ,
                'is_processed_for_next_payroll'     ,

                'work_schedule_start_time'          ,
                'work_schedule_end_time'            ,
                'work_schedule_is_flextime'         ,
                'work_schedule_total_hours_per_week',
                'work_schedule_total_work_hours'
            ];

            $attendanceFilterCriteria = [
                [
                    'column'   => 'work_schedule.employee_id',
                    'operator' => '='                        ,
                    'value'    => $employeeId
                ],
                [
                    'column'      => 'attendance.date',
                    'operator'    => 'BETWEEN'        ,
                    'lower_bound' => ((new DateTime($cutoffPeriodStartDate))->modify('-2 day'))->format('Y-m-d'),
                    'upper_bound' => $cutoffPeriodEndDate
                ]
            ];

            $attendanceSortCriteria = [
                [
                    'column'    => 'attendance.date',
                    'direction' => 'ASC'
                ],
                [
                    'column'    => 'attendance.check_in_time',
                    'direction' => 'ASC'
                ]
            ];

            $attendanceFetchResult = $this->attendanceRepository->fetchAllAttendance(
                columns       : $attendanceColumns       ,
                filterCriteria: $attendanceFilterCriteria,
                sortCriteria  : $attendanceSortCriteria
            );

            if ($attendanceFetchResult === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            if ( ! empty($attendanceFetchResult['result_set'])) {
                $attendanceRecords = [];

                foreach ($attendanceFetchResult['result_set'] as $attendanceRecord) {
                    $date           = $attendanceRecord['date'            ];
                    $workScheduleId = $attendanceRecord['work_schedule_id'];

                    if ( ! isset($attendanceRecords[$date][$workScheduleId])) {
                        $attendanceRecords[$date][$workScheduleId] = [
                            'work_schedule' => [
                                'id'                   => $attendanceRecord['work_schedule_id'                  ],
                                'start_time'           => $attendanceRecord['work_schedule_start_time'          ],
                                'end_time'             => $attendanceRecord['work_schedule_end_time'            ],
                                'is_flextime'          => $attendanceRecord['work_schedule_is_flextime'         ],
                                'total_hours_per_week' => $attendanceRecord['work_schedule_total_hours_per_week'],
                                'total_work_hours'     => $attendanceRecord['work_schedule_total_work_hours'    ]
                            ],

                            'attendance_records' => []
                        ];
                    }

                    $attendanceRecords[$date][$workScheduleId]['attendance_records'][] = [
                        'id'                            => $attendanceRecord['id'                           ],
                        'date'                          => $attendanceRecord['date'                         ],
                        'check_in_time'                 => $attendanceRecord['check_in_time'                ],
                        'check_out_time'                => $attendanceRecord['check_out_time'               ],
                        'is_overtime_approved'          => $attendanceRecord['is_overtime_approved'         ],
                        'attendance_status'             => $attendanceRecord['attendance_status'            ],
                        'is_processed_for_next_payroll' => $attendanceRecord['is_processed_for_next_payroll']
                    ];
                }

                $firstAttendanceRecordDate = array_key_first($attendanceRecords                );
                $lastWorkSchedule          = end($attendanceRecords[$firstAttendanceRecordDate]);
                $lastAttendanceRecord      = end($lastWorkSchedule ['attendance_records'      ]);

                $attendanceRecords[$firstAttendanceRecordDate] = [$lastWorkSchedule];

                if ( ! $lastAttendanceRecord['is_processed_for_next_payroll'] || $lastAttendanceRecord['attendance_status'] === 'Absent') {
                    unset($attendanceRecords[$firstAttendanceRecordDate]);
                }

                if ( ! empty($attendanceRecords)) {
                    $lastAttendanceRecordDate = array_key_last($attendanceRecords                );
                    $lastWorkSchedule         = end($attendanceRecords[$lastAttendanceRecordDate]);
                    $lastAttendanceRecord     = end($lastWorkSchedule ['attendance_records'     ]);

                    if ($lastAttendanceRecord['attendance_status'] !== 'Absent') {
                        $workScheduleStartTime = $lastWorkSchedule['work_schedule']['start_time'];
                        $workScheduleEndTime   = $lastWorkSchedule['work_schedule']['end_time'  ];

                        $workScheduleStartTime = new DateTime($lastAttendanceRecordDate . ' ' . (new DateTime($workScheduleStartTime))->format('H:i:s'));
                        $workScheduleEndTime   = new DateTime($lastAttendanceRecordDate . ' ' . (new DateTime($workScheduleEndTime  ))->format('H:i:s'));

                        if ($workScheduleEndTime <= $workScheduleStartTime) {
                            $workScheduleEndTime->modify('+1 day');
                        }

                        if ($workScheduleEndTime > (new DateTime($cutoffPeriodEndDate))->modify('+1 day') || $lastAttendanceRecord['check_out_time'] === null) {
                            foreach ($lastWorkSchedule['attendance_records'] as $attendanceRecord) {
                                $this->attendanceRepository->markAsProcessedForNextPayroll($attendanceRecord['id']);
                            }

                            array_pop($attendanceRecords[$lastAttendanceRecordDate]);
                        }
                    }
                }

                $adjustedCutoffStartDate =
                    ! empty($attendanceRecords)
                        ? array_key_first($attendanceRecords)
                        : $cutoffPeriodStartDate;

                $datesMarkedAsHolidays = $this->holidayRepository->getHolidayDatesForPeriod(
                    startDate: $adjustedCutoffStartDate,
                    endDate  : $cutoffPeriodEndDate
                );

                if ($datesMarkedAsHolidays === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $datesMarkedAsLeaves = $this->leaveRequestRepository->getLeaveDatesForPeriod(
                    employeeId: $employeeId             ,
                    startDate : $adjustedCutoffStartDate,
                    endDate   : $cutoffPeriodEndDate
                );

                if ($datesMarkedAsLeaves === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                $employeeWorkHoursMetrics = [
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
                    ],

                    'non_worked_paid_hours' => [
                        'leave'           => 0,
                        'regular_holiday' => 0,
                        'double_holiday'  => 0
                    ]
                ];

                foreach ($attendanceRecords as $date => $workSchedules) {
                    foreach ($workSchedules as $workSchedule) {
                        $workScheduleStartTime = $workSchedule['work_schedule']['start_time'];
                        $workScheduleEndTime   = $workSchedule['work_schedule']['end_time'  ];

                        $workScheduleStartTime = new DateTime($date . ' ' . (new DateTime($workScheduleStartTime))->format('H:i:s'));
                        $workScheduleEndTime   = new DateTime($date . ' ' . (new DateTime($workScheduleEndTime  ))->format('H:i:s'));

                        if ($workScheduleEndTime <= $workScheduleStartTime) {
                            $workScheduleEndTime->modify('+1 day');
                        }

                        if ( ! empty($workSchedule['attendance_records']) && $workSchedule['attendance_records'][0]['attendance_status'] !== 'Absent') {
                            $employeeBreakColumns = [
                                'start_time'                        ,
                                'end_time'                          ,

                                'break_schedule_start_time'         ,
                                'break_schedule_is_flexible'        ,
                                'break_schedule_earliest_start_time',
                                'break_schedule_latest_end_time'    ,
                                'break_type_duration_in_minutes'    ,
                                'break_type_is_paid'
                            ];

                            $employeeBreakFilterCriteria = [
                                [
                                    'column'   => 'employee_break.deleted_at',
                                    'operator' => 'IS NULL'
                                ],
                                [
                                    'column'      => 'employee_break.created_at'                  ,
                                    'operator'    => 'BETWEEN'                                    ,
                                    'lower_bound' => $workScheduleStartTime->format('Y-m-d H:i:s'),
                                    'upper_bound' => $workScheduleEndTime  ->format('Y-m-d H:i:s')
                                ]
                            ];

                            $employeeBreakFetchResult = $this->employeeBreakRepository->fetchAllEmployeeBreaks(
                                columns       : $employeeBreakColumns       ,
                                filterCriteria: $employeeBreakFilterCriteria
                            );

                            if ($employeeBreakFetchResult === ActionResult::FAILURE) {
                                return [
                                    'status'  => 'error',
                                    'message' => 'An unexpected error occurred. Please try again later.'
                                ];
                            }

                            $employeeBreaks = $employeeBreakFetchResult['result_set'];

                            foreach ($workSchedule['attendance_records'] as $attendanceRecord) {
                            }



                        }
                    }
                }



            }



        }

        return [
            'status'  => 'success',
            'message' => ''
        ];
    }
}
