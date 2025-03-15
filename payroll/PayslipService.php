<?php

require_once __DIR__ . '/PayslipRepository.php'                                 ;

require_once __DIR__ . '/../employees/EmployeeRepository.php'                   ;
require_once __DIR__ . '/../holidays/HolidayRepository.php'                     ;
require_once __DIR__ . '/../attendance/AttendanceRepository.php'                ;
require_once __DIR__ . '/../leaves/LeaveRequestRepository.php'                  ;
require_once __DIR__ . '/../overtime-rates/OvertimeRateAssignmentRepository.php';
require_once __DIR__ . '/../overtime-rates/OvertimeRateRepository.php'          ;
require_once __DIR__ . '/../breaks/EmployeeBreakRepository.php'                 ;
require_once __DIR__ . '/../allowances/EmployeeAllowanceRepository.php'         ;
require_once __DIR__ . '/../deductions/EmployeeDeductionRepository.php'         ;
require_once __DIR__ . '/../leaves/LeaveEntitlementRepository.php'              ;

class PayslipService
{
    private readonly PayslipRepository                $payslipRepository               ;
    private readonly EmployeeRepository               $employeeRepository              ;
    private readonly HolidayRepository                $holidayRepository               ;
    private readonly AttendanceRepository             $attendanceRepository            ;
    private readonly LeaveRequestRepository           $leaveRequestRepository          ;
    private readonly OvertimeRateAssignmentRepository $overtimeRateAssignmentRepository;
    private readonly OvertimeRateRepository           $overtimeRateRepository          ;
    private readonly EmployeeBreakRepository          $employeeBreakRepository         ;
    private readonly EmployeeAllowanceRepository      $employeeAllowanceRepository     ;
    private readonly EmployeeDeductionRepository      $employeeDeductionRepository     ;
    private readonly LeaveEntitlementRepository       $leaveEntitlementRepository      ;

    public function __construct(
        PayslipRepository                $payslipRepository               ,
        EmployeeRepository               $employeeRepository              ,
        HolidayRepository                $holidayRepository               ,
        AttendanceRepository             $attendanceRepository            ,
        LeaveRequestRepository           $leaveRequestRepository          ,
        OvertimeRateAssignmentRepository $overtimeRateAssignmentRepository,
        OvertimeRateRepository           $overtimeRateRepository          ,
        EmployeeBreakRepository          $employeeBreakRepository         ,
        EmployeeAllowanceRepository      $employeeAllowanceRepository     ,
        EmployeeDeductionRepository      $employeeDeductionRepository     ,
        LeaveEntitlementRepository       $leaveEntitlementRepository
    ) {
        $this->payslipRepository                = $payslipRepository               ;
        $this->employeeRepository               = $employeeRepository              ;
        $this->holidayRepository                = $holidayRepository               ;
        $this->attendanceRepository             = $attendanceRepository            ;
        $this->leaveRequestRepository           = $leaveRequestRepository          ;
        $this->overtimeRateAssignmentRepository = $overtimeRateAssignmentRepository;
        $this->overtimeRateRepository           = $overtimeRateRepository          ;
        $this->employeeBreakRepository          = $employeeBreakRepository         ;
        $this->employeeAllowanceRepository      = $employeeAllowanceRepository     ;
        $this->employeeDeductionRepository      = $employeeDeductionRepository     ;
        $this->leaveEntitlementRepository       = $leaveEntitlementRepository      ;
    }

    public function generatePayslip(
        PayrollGroup $payrollGroup         ,
        string       $cutoffPeriodStartDate,
        string       $cutoffPeriodEndDate  ,
        string       $paydayDate
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
                'column'   => 'employee.payroll_group_id',
                'operator' => '='                        ,
                'value'    => $payrollGroup->getId()
            ],
            [
                'column'   => 'employee.access_role',
                'operator' => '!='                  ,
                'value'    => 'Admin'
            ]
        ];

        $employees = $this->employeeRepository->fetchAllEmployees(
            columns             : $employeeColumns       ,
            filterCriteria      : $employeeFilterCriteria,
            includeTotalRowCount: false
        );

        if ($employees === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $employees =
            ! empty($employees['result_set'])
                ? $employees['result_set']
                : [];

        if (empty($employees)) {
            return [
                'status'  => 'warning',
                'message' => 'No employees found for payroll processing.'
            ];
        }

        $adjustedCutoffPeriodStartDate =
            (new DateTime($cutoffPeriodStartDate))
                ->modify('-1 day')
                ->format('Y-m-d' );

        $datesMarkedAsHoliday = $this->holidayRepository->getHolidayDatesForPeriod(
            startDate: $adjustedCutoffPeriodStartDate,
            endDate  : $cutoffPeriodEndDate
        );

        if ($datesMarkedAsHoliday === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        foreach ($employees as $employee) {
            $employeeId   = $employee['id'           ];
            $jobTitleId   = $employee['job_title_id' ];
            $departmentId = $employee['department_id'];
            $basicSalary  = $employee['basic_salary' ];

            $attendanceRecordColumns = [
                'work_schedule_snapshot_id'                               ,
                'date'                                                    ,
                'check_in_time'                                           ,
                'check_out_time'                                          ,
                'is_overtime_approved'                                    ,
                'attendance_status'                                       ,

                'work_schedule_snapshot_start_time'                       ,
                'work_schedule_snapshot_end_time'                         ,
                'work_schedule_snapshot_is_flextime'                      ,
                'work_schedule_snapshot_total_work_hours'                 ,
                'work_schedule_snapshot_grace_period'                     ,
                'work_schedule_snapshot_minutes_can_check_in_before_shift'
            ];

            $attendanceRecordFilterCriteria = [
                [
                    'column'   => 'attendance.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'      => 'attendance.date'             ,
                    'operator'    => 'BETWEEN'                     ,
                    'lower_bound' => $adjustedCutoffPeriodStartDate,
                    'upper_bound' => $cutoffPeriodEndDate
                ],
                [
                    'column'   => 'work_schedule_snapshot.employee_id',
                    'operator' => '='                                 ,
                    'value'    => $employeeId
                ]
            ];

            $attendanceRecordSortCriteria = [
                [
                    'column'    => 'attendance.date',
                    'direction' => 'ASC'
                ],
                [
                    'column'    => 'work_schedule_snapshot.start_time',
                    'direction' => 'ASC'
                ],
                [
                    'column'    => 'attendance.check_in_time',
                    'direction' => 'ASC'
                ]
            ];

            $employeeAttendanceRecords = $this->attendanceRepository->fetchAllAttendance(
                columns             : $attendanceRecordColumns       ,
                filterCriteria      : $attendanceRecordFilterCriteria,
                sortCriteria        : $attendanceRecordSortCriteria  ,
                includeTotalRowCount: false
            );

            if ($employeeAttendanceRecords === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $employeeAttendanceRecords =
                ! empty($employeeAttendanceRecords['result_set'])
                    ? $employeeAttendanceRecords['result_set']
                    : [];

            $workHours = [
                'regular_day' => [
                    'non_holiday' => [
                        'regular_hours'               => 0.0,
                        'overtime_hours'              => 0.0,
                        'night_differential'          => 0.0,
                        'night_differential_overtime' => 0.0
                    ],

                    'special_holiday' => [
                        'regular_hours'               => 0.0,
                        'overtime_hours'              => 0.0,
                        'night_differential'          => 0.0,
                        'night_differential_overtime' => 0.0
                    ],

                    'regular_holiday' => [
                        'regular_hours'               => 0.0,
                        'overtime_hours'              => 0.0,
                        'night_differential'          => 0.0,
                        'night_differential_overtime' => 0.0
                    ],

                    'double_special_holiday' => [
                        'regular_hours'               => 0.0,
                        'overtime_hours'              => 0.0,
                        'night_differential'          => 0.0,
                        'night_differential_overtime' => 0.0
                    ],

                    'double_holiday' => [
                        'regular_hours'               => 0.0,
                        'overtime_hours'              => 0.0,
                        'night_differential'          => 0.0,
                        'night_differential_overtime' => 0.0
                    ]
                ],

                'rest_day' => [
                    'non_holiday' => [
                        'regular_hours'               => 0.0,
                        'overtime_hours'              => 0.0,
                        'night_differential'          => 0.0,
                        'night_differential_overtime' => 0.0
                    ],

                    'special_holiday' => [
                        'regular_hours'               => 0.0,
                        'overtime_hours'              => 0.0,
                        'night_differential'          => 0.0,
                        'night_differential_overtime' => 0.0
                    ],

                    'regular_holiday' => [
                        'regular_hours'               => 0.0,
                        'overtime_hours'              => 0.0,
                        'night_differential'          => 0.0,
                        'night_differential_overtime' => 0.0
                    ],

                    'double_special_holiday' => [
                        'regular_hours'               => 0.0,
                        'overtime_hours'              => 0.0,
                        'night_differential'          => 0.0,
                        'night_differential_overtime' => 0.0
                    ],

                    'double_holiday' => [
                        'regular_hours'               => 0.0,
                        'overtime_hours'              => 0.0,
                        'night_differential'          => 0.0,
                        'night_differential_overtime' => 0.0
                    ]
                ],

                'non_worked_paid_hours' => [
                    'leave'           => 0.0,
                    'regular_holiday' => 0.0,
                    'double_holiday'  => 0.0
                ]
            ];

            $overtimeRateAssignment = new OvertimeRateAssignment(
                id          : null         ,
                departmentId: $departmentId,
                jobTitleId  : $jobTitleId  ,
                employeeId  : $employeeId
            );

            $overtimeRateAssignmentId = $this->overtimeRateAssignmentRepository
                ->findOvertimeRateAssignmentId($overtimeRateAssignment);

            if ($overtimeRateAssignmentId === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $overtimeRates = $this->overtimeRateRepository
                ->fetchOvertimeRates($overtimeRateAssignmentId);

            if ($overtimeRates === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            if (empty($overtimeRates)) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            foreach ($overtimeRates as $overtimeRate) {
                $dayType     = strtolower(str_replace([' ', '-'], '_', $overtimeRate['day_type'    ]));
                $holidayType = strtolower(str_replace([' ', '-'], '_', $overtimeRate['holiday_type']));

                $workHourRates[$dayType][$holidayType] = [
                    'regular_hours'               => $overtimeRate['regular_time_rate'                   ],
                    'overtime_hours'              => $overtimeRate['overtime_rate'                       ],
                    'night_differential'          => $overtimeRate['night_differential_rate'             ],
                    'night_differential_overtime' => $overtimeRate['night_differential_and_overtime_rate']
                ];
            }

            $grossPay = 0;
            $basicPay = 0;

            if ( ! empty($employeeAttendanceRecords)) {
                $result = $this->calculateAttendanceRecords(
                    employeeId                   : $employeeId                   ,
                    basicSalary                  : $basicSalary                  ,
                    employeeAttendanceRecords    : $employeeAttendanceRecords    ,
                    adjustedCutoffPeriodStartDate: $adjustedCutoffPeriodStartDate,
                    cutoffPeriodEndDate          : $cutoffPeriodEndDate          ,
                    datesMarkedAsHoliday         : $datesMarkedAsHoliday         ,
                    workHours                    : $workHours                    ,
                    workHourRates                : $workHourRates                ,
                    grossPay                     : $grossPay                     ,
                    basicPay                     : $basicPay
                );

                if ($result === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }
            }

            $employeeAllowanceColumns = [
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
                    'operator' => '='               ,
                    'value'    => 'Active'
                ],
                [
                    'column'   => 'employee_allowance.employee_id',
                    'operator' => '='                             ,
                    'value'    => $employeeId
                ]
            ];

            $employeeAllowances = $this->employeeAllowanceRepository->fetchAllEmployeeAllowances(
                columns             : $employeeAllowanceColumns       ,
                filterCriteria      : $employeeAllowanceFilterCriteria,
                includeTotalRowCount: false
            );

            if ($employeeAllowances === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $employeeAllowances =
                ! empty($employeeAllowances['result_set'])
                    ? $employeeAllowances['result_set']
                    : [];

            $employeeDeductionColumns = [
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
                    'operator' => '='               ,
                    'value'    => 'Active'
                ],
                [
                    'column'   => 'employee_deduction.employee_id',
                    'operator' => '='                             ,
                    'value'    => $employeeId
                ]
            ];

            $employeeDeductions = $this->employeeDeductionRepository->fetchAllEmployeeDeductions(
                columns             : $employeeDeductionColumns       ,
                filterCriteria      : $employeeDeductionFilterCriteria,
                includeTotalRowCount: false
            );

            if ($employeeDeductions === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $employeeDeductions =
                ! empty($employeeDeductions['result_set'])
                    ? $employeeDeductions['result_set']
                    : [];

            $payrollFrequency = strtolower($payrollGroup->getPayrollFrequency());

            $payFrequencyMultiplier = [
                'weekly'       => 4,
                'bi-weekly'    => 2,
                'semi-monthly' => 2,
                'monthly'      => 1
            ];

            $totalAllowances = 0;
            $totalDeductions = 0;

            foreach ($employeeAllowances as $employeeAllowance) {
                $allowancePayFrequency = strtolower($employeeAllowance['allowance_frequency']);

                $proratedAmount = ($employeeAllowance['amount'] * $payFrequencyMultiplier[$allowancePayFrequency]) / $payFrequencyMultiplier[$payrollFrequency];

                $totalAllowances += $proratedAmount;
            }

            foreach ($employeeDeductions as $employeeDeduction) {
                $deductionFrequency = strtolower($employeeDeduction['deduction_frequency']);

                $proratedAmount = ($employeeDeduction['amount'] * $payFrequencyMultiplier[$deductionFrequency]) / $payFrequencyMultiplier[$payrollFrequency];

                $totalDeductions += $proratedAmount;
            }

            $grossPay += $totalAllowances;

            $sssContribution         = $this->calculateSssContribution        ($basicSalary);
            $philhealthContribution  = $this->calculatePhilhealthContribution ($basicSalary, (int) (new DateTime($cutoffPeriodEndDate))->format('Y'));
            $pagibigFundContribution = $this->calculatePagibigFundContribution($basicSalary);

            $sssDeduction         = 0;
            $philhealthDeduction  = 0;
            $pagibigFundDeduction = 0;
            $withholdingTax       = 0;

            if (strtolower($payrollGroup->getPayrollFrequency()) === 'weekly') {
                $sssDeduction         = $sssContribution        ['employee_share'] / 4;
                $philhealthDeduction  = $philhealthContribution ['employee_share'] / 4;
                $pagibigFundDeduction = $pagibigFundContribution['employee_share'] / 4;

            } elseif (strtolower($payrollGroup->getPayrollFrequency()) === 'bi-weekly'    ||
                      strtolower($payrollGroup->getPayrollFrequency()) === 'semi-monthly') {

                $sssDeduction         = $sssContribution        ['employee_share'] / 2;
                $philhealthDeduction  = $philhealthContribution ['employee_share'] / 2;
                $pagibigFundDeduction = $pagibigFundContribution['employee_share'] / 2;

            } elseif (strtolower($payrollGroup->getPayrollFrequency()) === 'monthly') {
                $sssDeduction         = $sssContribution        ['employee_share'] / 1;
                $philhealthDeduction  = $philhealthContribution ['employee_share'] / 1;
                $pagibigFundDeduction = $pagibigFundContribution['employee_share'] / 1;
            }

            $thirteenMonthPay = 0;
            $leaveSalary      = 0;

            $cutoffPeriodEndDate = new DateTime($cutoffPeriodEndDate);

            $currentYear  = $cutoffPeriodEndDate->format('Y');
            $previousYear = (clone $cutoffPeriodEndDate)->modify('-1 year')->format('Y');
            $currentMonth = (int) $cutoffPeriodEndDate->format('m');

            $paydayOffset = $payrollGroup->getPaydayOffset();

            switch ($payrollFrequency) {
                case 'weekly':
                    $nextPayDate = (clone $cutoffPeriodEndDate)
                        ->modify('+' . (7 + $paydayOffset) . ' days');

                    break;

                case 'bi-weekly':
                    $nextPayDate = (clone $cutoffPeriodEndDate)
                        ->modify('+' . (14 + $paydayOffset) . ' days');

                    break;

                case 'semi-monthly':
                    if ( (int) $cutoffPeriodEndDate->format('j') === $payrollGroup->getSemiMonthlyFirstCutoff()) {
                        $numberOfDaysInMonth = (int) $cutoffPeriodEndDate->format('t');

                        if ($payrollGroup->getSemiMonthlyFirstCutoff() === 15 || $numberOfDaysInMonth <= $payrollGroup->getSemiMonthlySecondCutoff()) {
                            $nextPayDate = (clone $cutoffPeriodEndDate)
                                ->modify('last day of this month');

                        } else {
                            $secondCutoffDay = sprintf('%02d', $payrollGroup->getSemiMonthlySecondCutoff());
                            $nextPayDate = new DateTime($currentYear . '-' . $currentMonth . '-' . $secondCutoffDay);
                        }

                        $nextPayDate->modify('+' . $paydayOffset . ' days');

                    } elseif ( (int) $cutoffPeriodEndDate->format('j') === $payrollGroup->getSemiMonthlySecondCutoff()) {
                        $nextMonth = (clone $cutoffPeriodEndDate)->modify('+1 month');
                        $nextPayDate = $nextMonth->setDate($nextMonth->format('Y'), $nextMonth->format('m'), $payrollGroup->getSemiMonthlyFirstCutoff());

                        $nextPayDate->modify('+' . $paydayOffset . ' days');
                    }

                    break;
            }

            if ($nextPayDate->format('l') === 'Sunday') {
                switch (strtolower($payrollGroup->getPaydayAdjustment())) {
                    case 'on the saturday before':
                        if ($paydayOffset > 0) {
                            $nextPayDate->modify('-1 day');
                        }

                        break;

                    case 'on the monday after':
                        $nextPayDate->modify('+1 day');

                        break;
                }
            }

            if ($nextPayDate->format('Y-m-d') > ($currentYear . '-12-24')) {
                $payslipColumns = [
                    'id'
                ];

                $payslipFilterCriteria = [
                    [
                        'column'   => 'payslip.pay_date'      ,
                        'operator' => '>'                     ,
                        'value'    => $previousYear . '-12-24'
                    ],
                    [
                        'column'   => 'payslip.pay_date'     ,
                        'operator' => '<='                   ,
                        'value'    => $currentYear . '-12-24'
                    ],
                    [
                        'column'   => 'payslip.thirteen_month_pay',
                        'operator' => 'IS NOT NULL'
                    ]
                ];

                $isThirteenMonthPayPaidAlready = $this->payslipRepository->fetchAllPayslips(
                    columns             : $payslipColumns       ,
                    filterCriteria      : $payslipFilterCriteria,
                    includeTotalRowCount: false
                );

                if ($isThirteenMonthPayPaidAlready === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                if (empty($isThirteenMonthPayPaidAlready)) {
                    $payslipColumns = [
                        'total_basic_pay'
                    ];

                    $payslipFilterCriteria = [
                        [
                            'column'   => 'payslip.pay_date'      ,
                            'operator' => '>'                     ,
                            'value'    => $previousYear . '-12-24'
                        ],
                        [
                            'column'   => 'payslip.pay_date'     ,
                            'operator' => '<='                   ,
                            'value'    => $currentYear . '-12-24'
                        ]
                    ];

                    $totalBasicSalary = $this->payslipRepository->fetchAllPayslips(
                        columns             : $payslipColumns       ,
                        filterCriteria      : $payslipFilterCriteria,
                        includeTotalRowCount: false
                    );

                    if ($totalBasicSalary === ActionResult::FAILURE) {
                        return [
                            'status'  => 'error',
                            'message' => 'An unexpected error occurred. Please try again later.'
                        ];
                    }

                    $totalBasicSalary =
                        ! empty($totalBasicSalary['result_set'])
                            ? $totalBasicSalary['result_set'][0]['total_basic_pay']
                            : null;

                    if ($totalBasicSalary !== null) {
                        $thirteenMonthPay = $totalBasicSalary / 12;
                    }
                }
            }

            $grossPay += $thirteenMonthPay;
            $taxable13thMonthPay = $thirteenMonthPay > 90000 ? $thirteenMonthPay - 90000 : 0;
            $netPay = $grossPay - ($sssDeduction + $philhealthDeduction + $pagibigFundDeduction + $totalDeductions);

            $withholdingTax = $this->calculateWithholdingTax($netPay + $taxable13thMonthPay, strtolower($payrollGroup->getPayrollFrequency()));
            $netPay -= $withholdingTax;

            $netPay = max(0, $netPay);

            $cutoffPeriodEndDate = $cutoffPeriodEndDate->format('Y-m-d');

            $payslip = new Payslip(
                id                  : null                       ,
                employeeId          : $employeeId                ,
                payrollGroupId      : $payrollGroup->getId()     ,
                payDate             : $paydayDate                ,
                payPeriodStartDate  : $cutoffPeriodStartDate     ,
                payPeriodEndDate    : $cutoffPeriodEndDate       ,
                basicSalary         : $basicSalary               ,
                basicPay            : $basicPay                  ,
                grossPay            : $grossPay                  ,
                netPay              : $netPay                    ,
                sssDeduction        : $sssDeduction              ,
                philhealthDeduction : $philhealthDeduction       ,
                pagibigFundDeduction: $pagibigFundDeduction      ,
                withholdingTax      : $withholdingTax            ,
                thirteenMonthPay    : $thirteenMonthPay          ,
                leaveSalary         : $leaveSalary               ,
                workHours           : json_encode($workHours)    ,
                overtimeRates       : json_encode($workHourRates)
            );

            $createPayslipResult = $this->payslipRepository->createPayslip($payslip);

            if ($createPayslipResult === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }
        }

        return [
            'status'  => 'success',
            'message' => 'Payroll has been processed successfully.'
        ];
    }

    private function calculateAttendanceRecords(
        int     $employeeId                   ,
        float   $basicSalary                  ,
        array   $employeeAttendanceRecords    ,
        string  $adjustedCutoffPeriodStartDate,
        string  $cutoffPeriodEndDate          ,
        array   $datesMarkedAsHoliday         ,
        array  &$workHours                    ,
        array   $workHourRates                ,
        float  &$grossPay                     ,
        float  &$basicPay
    ): ActionResult {

        $attendanceRecords = [];

        foreach ($employeeAttendanceRecords as $attendanceRecord) {
            $date                   = $attendanceRecord['date'                     ];
            $workScheduleSnapshotId = $attendanceRecord['work_schedule_snapshot_id'];

            if ( ! isset($attendanceRecords[$date][$workScheduleSnapshotId])) {
                $attendanceRecords[$date][$workScheduleSnapshotId] = [
                    'work_schedule' => [
                        'snapshot_id'                       => $attendanceRecord['work_schedule_snapshot_id'                               ],
                        'start_time'                        => $attendanceRecord['work_schedule_snapshot_start_time'                       ],
                        'end_time'                          => $attendanceRecord['work_schedule_snapshot_end_time'                         ],
                        'is_flextime'                       => $attendanceRecord['work_schedule_snapshot_is_flextime'                      ],
                        'total_work_hours'                  => $attendanceRecord['work_schedule_snapshot_total_work_hours'                 ],
                        'grace_period'                      => $attendanceRecord['work_schedule_snapshot_grace_period'                     ],
                        'minutes_can_check_in_before_shift' => $attendanceRecord['work_schedule_snapshot_minutes_can_check_in_before_shift']
                    ],

                    'attendance_records' => []
                ];
            }

            $attendanceRecords[$date][$workScheduleSnapshotId]['attendance_records'][] = [
                'date'                          => $attendanceRecord['date'                        ] ,
                'check_in_time'                 => $attendanceRecord['check_in_time'               ] ,
                'check_out_time'                => $attendanceRecord['check_out_time'              ] ,
                'is_overtime_approved'          => $attendanceRecord['is_overtime_approved'        ] ,
                'attendance_status'             => strtolower($attendanceRecord['attendance_status'])
            ];
        }

        $firstDate = array_key_first($attendanceRecords);

        if ($firstDate === $adjustedCutoffPeriodStartDate) {
            $lastWorkSchedule = end($attendanceRecords[$firstDate]);

            $attendanceRecords[$firstDate] = $lastWorkSchedule;

            $workScheduleStartTime = $lastWorkSchedule['work_schedule']['start_time'];
            $workScheduleEndTime   = $lastWorkSchedule['work_schedule']['end_time'  ];

            $workScheduleStartDateTime = new DateTime($firstDate . ' ' . $workScheduleStartTime);
            $workScheduleEndDateTime   = new DateTime($firstDate . ' ' . $workScheduleEndTime  );

            if ($workScheduleEndDateTime <= $workScheduleStartDateTime) {
                $workScheduleEndDateTime->modify('+1 day');
            }

            if ($workScheduleEndDateTime <= (new DateTime($cutoffPeriodEndDate))->modify('+1 day')) {
                unset($attendanceRecords[$firstDate]);
            }
        }

        if ( ! empty($attendanceRecords)) {
            $lastDate = array_key_last($attendanceRecords);

            $lastWorkSchedule = end($attendanceRecords[$lastDate]);

            $workScheduleStartTime = $lastWorkSchedule['work_schedule']['start_time'];
            $workScheduleEndTime   = $lastWorkSchedule['work_schedule']['end_time'  ];

            $workScheduleStartDateTime = new DateTime($firstDate . ' ' . $workScheduleStartTime);
            $workScheduleEndDateTime   = new DateTime($firstDate . ' ' . $workScheduleEndTime  );

            if ($workScheduleEndDateTime <= $workScheduleStartDateTime) {
                $workScheduleEndDateTime->modify('+1 day');
            }

            if ($workScheduleEndDateTime > (new DateTime($cutoffPeriodEndDate))->modify('+1 day')) {
                array_pop($attendanceRecords[$lastDate]);
            }

            if (empty($attendanceRecords[$lastDate])) {
                unset($attendanceRecords[$lastDate]);
            }
        }

        $startDate = array_key_first($attendanceRecords);
        $endDate   = array_key_last ($attendanceRecords);

        $datesMarkedAsLeave = $this->leaveRequestRepository->getLeaveDatesForPeriod(
            employeeId: $employeeId,
            startDate : $startDate ,
            endDate   : $endDate
        );

        if ($datesMarkedAsLeave === ActionResult::FAILURE) {
            return ActionResult::FAILURE;
        }

        foreach ($attendanceRecords as $workDate => $workSchedules) {
            $numberOfHoursWorked    = 0.0;
            $totalRequiredWorkHours = 0.0;

            foreach ($workSchedules as $workSchedule) {
                $totalRequiredWorkHours += $workSchedule['work_schedule']['total_work_hours'];
            }

            $hourlyRate = $basicSalary / ($totalRequiredWorkHours * 26.0);
            $basicPay   = 0;
            $grossPay   = 0;

            foreach ($workSchedules as $workSchedule) {
                $workScheduleSnapshotId = $workSchedule['work_schedule']['snapshot_id'];
                $isFlextime             = $workSchedule['work_schedule']['is_flextime'];

                $workScheduleStartTime = $workSchedule['work_schedule']['start_time'];
                $workScheduleEndTime   = $workSchedule['work_schedule']['end_time'  ];

                $workScheduleStartDateTime = new DateTime($workDate . ' ' . $workScheduleStartTime);
                $workScheduleEndDateTime   = new DateTime($workDate . ' ' . $workScheduleEndTime  );

                if ($workScheduleEndDateTime <= $workScheduleStartDateTime) {
                    $workScheduleEndDateTime->modify('+1 day');
                }

                $earlyCheckInWindow = $workSchedule['work_schedule']['minutes_can_check_in_before_shift'];
                $adjustedWorkScheduleStartDateTime = (clone $workScheduleStartDateTime)
                    ->modify('-' . $earlyCheckInWindow . ' minutes');

                $formattedWorkScheduleStartDateTime         = $workScheduleStartDateTime        ->format('Y-m-d H:i:s');
                $formattedWorkScheduleEndDateTime           = $workScheduleEndDateTime          ->format('Y-m-d H:i:s');
                $formattedAdjustedWorkScheduleStartDateTime = $adjustedWorkScheduleStartDateTime->format('Y-m-d H:i:s');

                if ( ! empty($workSchedule['attendance_records'])) {
                    if ( ! $isFlextime) {

                        $employeeBreakColumns = [
                            'break_schedule_snapshot_id'             ,
                            'start_time'                             ,
                            'end_time'                               ,

                            'break_schedule_snapshot_start_time'     ,
                            'break_schedule_snapshot_end_time'       ,

                            'break_type_snapshot_duration_in_minutes',
                            'break_type_snapshot_is_paid'
                        ];

                        $employeeBreakFilterCriteria = [
                            [
                                'column'   => 'employee_break.deleted_at',
                                'operator' => 'IS NULL'
                            ],
                            [
                                'column'   => 'break_schedule_snapshot.work_schedule_snapshot_id',
                                'operator' => '='                                                ,
                                'value'    => $workScheduleSnapshotId
                            ],
                            [
                                'column'      => 'employee_break.created_at'                ,
                                'operator'    => 'BETWEEN'                                  ,
                                'lower_bound' => $formattedAdjustedWorkScheduleStartDateTime,
                                'upper_bound' => $formattedWorkScheduleEndDateTime
                            ]
                        ];

                        $employeeBreakRecords = $this->employeeBreakRepository->fetchAllEmployeeBreaks(
                            columns             : $employeeBreakColumns       ,
                            filterCriteria      : $employeeBreakFilterCriteria,
                            includeTotalRowCount: false
                        );

                        if ($employeeBreakRecords === ActionResult::FAILURE) {
                            return ActionResult::FAILURE;
                        }

                        $employeeBreakRecords =
                            ! empty($employeeBreakRecords['result_set'])
                                ? $employeeBreakRecords['result_set']
                                : [];

                        if ( ! empty($employeeBreakRecords)) {
                            $groupedBreakRecords = [];
                            foreach ($employeeBreakRecords as $breakRecord) {
                                $groupedBreakRecords[$breakRecord['break_schedule_snapshot_id']][] = $breakRecord;
                            }

                            $mergedBreakRecords = [];
                            foreach ($groupedBreakRecords as $breakRecords) {
                                $firstBreakRecord = $breakRecords[0];

                                if ($firstBreakRecord['start_time'] !== null) {
                                    $earliestStartDateTime = new DateTime($firstBreakRecord['start_time']);
                                    $latestEndDateTime     = null;

                                    foreach ($breakRecords as $breakRecord) {
                                        $currentStartDateTime = new DateTime($breakRecord['start_time']);

                                        if ($currentStartDateTime < $earliestStartDateTime) {
                                            $earliestStartDateTime = $currentStartDateTime;
                                        }

                                        if ($breakRecord['end_time'] !== null) {
                                            $currentEndDateTime = new DateTime($breakRecord['end_time']);

                                            if ($latestEndDateTime === null || $currentEndDateTime > $latestEndDateTime) {
                                                $latestEndDateTime = $currentEndDateTime;
                                            }
                                        }
                                    }

                                    $mergedBreakRecords[] = array_merge(
                                        $firstBreakRecord,
                                        [
                                            'start_time' => $earliestStartDateTime->format('Y-m-d H:i:s'),
                                            'end_time' =>
                                                $latestEndDateTime
                                                    ? $latestEndDateTime->format('Y-m-d H:i:s')
                                                    : null
                                        ]
                                    );

                                } else {
                                    $mergedBreakRecords[] = $firstBreakRecord;
                                }
                            }

                            $employeeBreakRecords = $mergedBreakRecords;
                        }
                    }

                    $isFirstAttendanceRecord = true;

                    foreach ($workSchedule['attendance_records'] as $attendanceRecord) {
                        if ($attendanceRecord['check_in_time'    ] !== null     ||
                            $attendanceRecord['attendance_status'] !== 'absent') {

                            $checkInDateTime = new DateTime($attendanceRecord['check_in_time']);

                            $checkOutDateTime =
                                $attendanceRecord['check_out_time'] !== null
                                    ? new DateTime($attendanceRecord['check_out_time'])
                                    : $workScheduleEndDateTime;

                            if ( ! $isFlextime && $isFirstAttendanceRecord) {
                                if ($checkInDateTime < $workScheduleStartDateTime) {
                                    $checkInDateTime = $workScheduleStartDateTime;
                                }

                                $gracePeriod = $workSchedule['work_schedule']['grace_period'];

                                $gracePeriodStartDateTime = (clone $workScheduleStartDateTime)->modify('+' . $gracePeriod . ' minutes');

                                if ($checkInDateTime <= $gracePeriodStartDateTime) {
                                    $checkInDateTime = clone $workScheduleStartDateTime;
                                }

                                $isFirstAttendanceRecord = false;
                            }

                            if ( ! $isFlextime && ! empty($mergedBreakRecords)) {
                                $breakRecords = [];

                                $previousBreakRecordEndDateTime = null;

                                foreach ($employeeBreakRecords as $breakRecord) {
                                    $isPaid = $breakRecord['break_type_snapshot_is_paid'];

                                    $breakScheduleStartTime = $breakRecord['break_schedule_snapshot_start_time'];
                                    $breakScheduleEndTime   = $breakRecord['break_schedule_snapshot_end_time'  ];

                                    $breakScheduleStartDateTime = new DateTime($workDate . ' ' . $breakScheduleStartTime);
                                    $breakScheduleEndDateTime   = new DateTime($workDate . ' ' . $breakScheduleEndTime  );

                                    if ($breakScheduleStartDateTime < $workScheduleStartDateTime) {
                                        $breakScheduleStartDateTime->modify('+1 day');
                                    }

                                    if ($breakScheduleEndDateTime < $workScheduleStartDateTime) {
                                        $breakScheduleEndDateTime->modify('+1 day');
                                    }

                                    if ($breakScheduleEndDateTime < $breakScheduleStartDateTime) {
                                        $breakScheduleEndDateTime->modify('+1 day');
                                    }

                                    if ($checkInDateTime > $breakScheduleStartDateTime) {
                                        $breakScheduleStartDateTime = clone $checkInDateTime;
                                    } elseif ( ! $isPaid && $previousBreakRecordEndDateTime !== null && $previousBreakRecordEndDateTime > $breakScheduleStartDateTime) {
                                        $breakScheduleStartDateTime = clone $previousBreakRecordEndDateTime;
                                    }

                                    if ($checkOutDateTime > $breakScheduleStartDateTime) {
                                        $breakRecordEndDateTime =
                                            $breakRecord['end_time'] !== null
                                                ? new DateTime($breakRecord['end_time'])
                                                : null;

                                        if ($checkOutDateTime < $breakRecordEndDateTime) {
                                            $breakRecordEndDateTime = clone $checkOutDateTime;
                                        }

                                        if ($breakRecordEndDateTime !== null &&
                                            $breakRecordEndDateTime >   $breakScheduleEndDateTime) {

                                            if ($breakScheduleStartDateTime > $breakScheduleEndDateTime) {
                                                $breakScheduleEndDateTime = clone $breakScheduleStartDateTime;
                                            }

                                            $breakScheduleEndDateTime       = clone $breakRecordEndDateTime  ;
                                            $previousBreakRecordEndDateTime = clone $breakScheduleEndDateTime;

                                        } else {
                                            $breakScheduleEndDateTime =
                                                $checkOutDateTime >= $breakScheduleEndDateTime
                                                    ? $breakScheduleEndDateTime
                                                    : $checkOutDateTime;

                                            if ($breakScheduleStartDateTime > $breakScheduleEndDateTime) {
                                                $breakScheduleStartDateTime = clone $breakScheduleEndDateTime;
                                            }
                                        }

                                        $breakRecords[] = [
                                            'start_time' => $breakScheduleStartDateTime->format('Y-m-d H:i:s'),
                                            'end_time'   => $breakScheduleEndDateTime  ->format('Y-m-d H:i:s'),
                                            'is_paid'    => $isPaid
                                        ];
                                    }

                                    if ($previousBreakRecordEndDateTime === null) {
                                        $previousBreakRecordEndDateTime = $breakScheduleEndDateTime;
                                    }
                                }

                                if ( ! empty($breakRecords)) {
                                    usort($breakRecords, function ($breakRecordStartTimeA, $breakRecordStartTimeB) {
                                        $breakStartTimeA = new DateTime($breakRecordStartTimeA['start_time']);
                                        $breakStartTimeB = new DateTime($breakRecordStartTimeB['start_time']);

                                        return $breakStartTimeA <=> $breakStartTimeB;
                                    });

                                    foreach ($breakRecords as $breakRecord) {
                                        if ( ! $breakRecord['is_paid']) {
                                            $breakRecordStartDateTime = new DateTime($breakRecord['start_time']);
                                            $breakRecordEndDateTime   = new DateTime($breakRecord['end_time'  ]);

                                            if ($breakRecordStartDateTime->format('Y-m-d H') === $breakRecordEndDateTime->format('Y-m-d H')) {
                                                $dayOfWeek =       $breakRecordStartDateTime->format('l'    );
                                                $date      =       $breakRecordStartDateTime->format('Y-m-d');
                                                $hour      = (int) $breakRecordStartDateTime->format('H'    );

                                                $dayType = 'regular_day';

                                                if ($dayOfWeek === 'Sunday') {
                                                    $dayType = 'rest_day';
                                                }

                                                $holidayType = 'non_holiday';

                                                if ( ! empty($datesMarkedAsHoliday[$date])) {
                                                    if (count($datesMarkedAsHoliday[$date]) > 1) {
                                                        if (count($datesMarkedAsHoliday[$date]) === 2) {
                                                            if ($datesMarkedAsHoliday[$date][0]['is_paid'] ||
                                                                $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                                $holidayType = 'double_holiday';

                                                            } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid'] &&
                                                                       ! $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                                $holidayType = 'double_special_holiday';

                                                            } else {
                                                                $holidayType = 'special_holiday';
                                                            }

                                                        } else {
                                                            $hasPaidHoliday = false;

                                                            foreach ($datesMarkedAsHoliday[$date] as $holiday) {
                                                                if ($holiday['is_paid']) {
                                                                    $hasPaidHoliday = true;

                                                                    break;
                                                                }
                                                            }

                                                            if ($hasPaidHoliday) {
                                                                $holidayType = 'regular_holiday';
                                                            } else {
                                                                $holidayType = 'special_holiday';
                                                            }
                                                        }

                                                    } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                                        $holidayType = 'regular_holiday';
                                                    } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid']) {
                                                        $holidayType = 'special_holiday';
                                                    }
                                                }

                                                $isNightShift = $hour >= 22 || $hour < 6;

                                                $breakDuration        = $breakRecordStartDateTime->diff($breakRecordEndDateTime);
                                                $breakDurationInHours = ($breakDuration->h * 60 + $breakDuration->i) / 60       ;

                                                if ($isNightShift) {
                                                    $workHours[$dayType][$holidayType]['night_differential'] -= $breakDurationInHours;
                                                } else {
                                                    $workHours[$dayType][$holidayType]['regular_hours'] -= $breakDurationInHours;
                                                }

                                                $numberOfHoursWorked -= $breakDurationInHours;

                                            } else {
                                                $remainingMinutes = 60 - (int) $breakRecordStartDateTime->format('i');

                                                if ($remainingMinutes < 60) {
                                                    $dayOfWeek =       $breakRecordStartDateTime->format('l'    );
                                                    $date      =       $breakRecordStartDateTime->format('Y-m-d');
                                                    $hour      = (int) $breakRecordStartDateTime->format('H'    );

                                                    $dayType = 'regular_day';

                                                    if ($dayOfWeek === 'Sunday') {
                                                        $dayType = 'rest_day';
                                                    }

                                                    $holidayType = 'non_holiday';

                                                    if ( ! empty($datesMarkedAsHoliday[$date])) {
                                                        if (count($datesMarkedAsHoliday[$date]) > 1) {
                                                            if (count($datesMarkedAsHoliday[$date]) === 2) {
                                                                if ($datesMarkedAsHoliday[$date][0]['is_paid'] ||
                                                                    $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                                    $holidayType = 'double_holiday';

                                                                } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid'] &&
                                                                           ! $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                                    $holidayType = 'double_special_holiday';

                                                                } else {
                                                                    $holidayType = 'special_holiday';
                                                                }

                                                            } else {
                                                                $hasPaidHoliday = false;

                                                                foreach ($datesMarkedAsHoliday[$date] as $holiday) {
                                                                    if ($holiday['is_paid']) {
                                                                        $hasPaidHoliday = true;

                                                                        break;
                                                                    }
                                                                }

                                                                if ($hasPaidHoliday) {
                                                                    $holidayType = 'regular_holiday';
                                                                } else {
                                                                    $holidayType = 'special_holiday';
                                                                }
                                                            }

                                                        } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                                            $holidayType = 'regular_holiday';
                                                        } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid']) {
                                                            $holidayType = 'special_holiday';
                                                        }
                                                    }

                                                    $isNightShift = $hour >= 22 || $hour < 6;

                                                    $breakDurationInHours = $remainingMinutes / 60;

                                                    if ($isNightShift) {
                                                        $workHours[$dayType][$holidayType]['night_differential'] -= $breakDurationInHours;
                                                    } else {
                                                        $workHours[$dayType][$holidayType]['regular_hours'] -= $breakDurationInHours;
                                                    }

                                                    $numberOfHoursWorked -= $breakDurationInHours;
                                                }

                                                $adjustedBreakRecordStartDateTime = (clone $breakRecordStartDateTime)
                                                    ->setTime(
                                                        (int) $breakRecordStartDateTime->format('i') > 0
                                                            ? (int) $breakRecordStartDateTime->format('H') + 1
                                                            :       $breakRecordStartDateTime->format('H'),

                                                        0, 0
                                                    );

                                                $adjustedBreakRecordEndDateTime = (clone $breakRecordEndDateTime)
                                                    ->setTime($breakRecordEndDateTime->format('H'), 0, 0);

                                                $breakTimeInterval = new DateInterval('PT1H');

                                                $breakTimePeriod = new DatePeriod(
                                                    $adjustedBreakRecordStartDateTime,
                                                    $breakTimeInterval               ,
                                                    $adjustedBreakRecordEndDateTime
                                                );

                                                foreach ($breakTimePeriod as $currentBreakDateTime) {
                                                    $dayOfWeek =       $currentBreakDateTime->format('l'    );
                                                    $date      =       $currentBreakDateTime->format('Y-m-d');
                                                    $hour      = (int) $currentBreakDateTime->format('H'    );

                                                    $dayType = 'regular_day';

                                                    if ($dayOfWeek === 'Sunday') {
                                                        $dayType = 'rest_day';
                                                    }

                                                    $holidayType = 'non_holiday';

                                                    if ( ! empty($datesMarkedAsHoliday[$date])) {
                                                        if (count($datesMarkedAsHoliday[$date]) > 1) {
                                                            if (count($datesMarkedAsHoliday[$date]) === 2) {
                                                                if ($datesMarkedAsHoliday[$date][0]['is_paid'] ||
                                                                    $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                                    $holidayType = 'double_holiday';

                                                                } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid'] &&
                                                                           ! $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                                    $holidayType = 'double_special_holiday';

                                                                } else {
                                                                    $holidayType = 'special_holiday';
                                                                }

                                                            } else {
                                                                $hasPaidHoliday = false;

                                                                foreach ($datesMarkedAsHoliday[$date] as $holiday) {
                                                                    if ($holiday['is_paid']) {
                                                                        $hasPaidHoliday = true;

                                                                        break;
                                                                    }
                                                                }

                                                                if ($hasPaidHoliday) {
                                                                    $holidayType = 'regular_holiday';
                                                                } else {
                                                                    $holidayType = 'special_holiday';
                                                                }
                                                            }

                                                        } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                                            $holidayType = 'regular_holiday';
                                                        } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid']) {
                                                            $holidayType = 'special_holiday';
                                                        }
                                                    }

                                                    $isNightShift = $hour >= 22 || $hour < 6;

                                                    $breakDurationInHours = 1.0;

                                                    if ($isNightShift) {
                                                        $workHours[$dayType][$holidayType]['night_differential'] -= $breakDurationInHours;
                                                    } else {
                                                        $workHours[$dayType][$holidayType]['regular_hours'] -= $breakDurationInHours;
                                                    }

                                                    $numberOfHoursWorked -= $breakDurationInHours;
                                                }

                                                $remainingMinutes = 60 - (int) $breakRecordEndDateTime->format('i');

                                                if ($remainingMinutes < 60) {
                                                    $dayOfWeek =       $breakRecordEndDateTime->format('l'    );
                                                    $date      =       $breakRecordEndDateTime->format('Y-m-d');
                                                    $hour      = (int) $breakRecordEndDateTime->format('H'    );

                                                    $dayType = 'regular_day';

                                                    if ($dayOfWeek === 'Sunday') {
                                                        $dayType = 'rest_day';
                                                    }

                                                    $holidayType = 'non_holiday';

                                                    if ( ! empty($datesMarkedAsHoliday[$date])) {
                                                        if (count($datesMarkedAsHoliday[$date]) > 1) {
                                                            if (count($datesMarkedAsHoliday[$date]) === 2) {
                                                                if ($datesMarkedAsHoliday[$date][0]['is_paid'] ||
                                                                    $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                                    $holidayType = 'double_holiday';

                                                                } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid'] &&
                                                                           ! $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                                    $holidayType = 'double_special_holiday';

                                                                } else {
                                                                    $holidayType = 'special_holiday';
                                                                }

                                                            } else {
                                                                $hasPaidHoliday = false;

                                                                foreach ($datesMarkedAsHoliday[$date] as $holiday) {
                                                                    if ($holiday['is_paid']) {
                                                                        $hasPaidHoliday = true;

                                                                        break;
                                                                    }
                                                                }

                                                                if ($hasPaidHoliday) {
                                                                    $holidayType = 'regular_holiday';
                                                                } else {
                                                                    $holidayType = 'special_holiday';
                                                                }
                                                            }

                                                        } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                                            $holidayType = 'regular_holiday';
                                                        } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid']) {
                                                            $holidayType = 'special_holiday';
                                                        }
                                                    }

                                                    $isNightShift = $hour >= 22 || $hour < 6;

                                                    $breakDurationInHours = $remainingMinutes / 60;

                                                    if ($isNightShift) {
                                                        $workHours[$dayType][$holidayType]['night_differential'] -= $breakDurationInHours;
                                                    } else {
                                                        $workHours[$dayType][$holidayType]['regular_hours'] -= $breakDurationInHours;
                                                    }

                                                    $numberOfHoursWorked -= $breakDurationInHours;
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            $isOvertimeApproved = $attendanceRecord['is_overtime_approved'];

                            if ($checkInDateTime->format('Y-m-d H') === $checkOutDateTime->format('Y-m-d H')) {
                                $dayOfWeek =       $checkInDateTime->format('l'    );
                                $date      =       $checkInDateTime->format('Y-m-d');
                                $hour      = (int) $checkInDateTime->format('H'    );

                                $dayType = 'regular_day';

                                if ($dayOfWeek === 'Sunday') {
                                    $dayType = 'rest_day';
                                }

                                $holidayType = 'non_holiday';

                                if ( ! empty($datesMarkedAsHoliday[$date])) {
                                    if (count($datesMarkedAsHoliday[$date]) > 1) {
                                        if (count($datesMarkedAsHoliday[$date]) === 2) {
                                            if ($datesMarkedAsHoliday[$date][0]['is_paid'] ||
                                                $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                $holidayType = 'double_holiday';

                                            } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid'] &&
                                                       ! $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                $holidayType = 'double_special_holiday';

                                            } else {
                                                $holidayType = 'special_holiday';
                                            }

                                        } else {
                                            $hasPaidHoliday = false;

                                            foreach ($datesMarkedAsHoliday[$date] as $holiday) {
                                                if ($holiday['is_paid']) {
                                                    $hasPaidHoliday = true;

                                                    break;
                                                }
                                            }

                                            if ($hasPaidHoliday) {
                                                $holidayType = 'regular_holiday';
                                            } else {
                                                $holidayType = 'special_holiday';
                                            }
                                        }

                                    } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                        $holidayType = 'regular_holiday';
                                    } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid']) {
                                        $holidayType = 'special_holiday';
                                    }
                                }

                                $isNightShift = $hour >= 22 || $hour < 6;

                                $workDuration        = $checkInDateTime->diff($checkOutDateTime)      ;
                                $workDurationInHours = ($workDuration->h * 60 + $workDuration->i) / 60;

                                if ($isNightShift) {
                                    if ($numberOfHoursWorked > $totalRequiredWorkHours && $isOvertimeApproved) {
                                        if ($workHours[$dayType][$holidayType]['night_differential_overtime'] + $workDurationInHours > 0) {
                                            $remainingHours =
                                                max(0, $workHours[$dayType][$holidayType]['night_differential_overtime'] + $workDurationInHours) -
                                                max(0, $workHours[$dayType][$holidayType]['night_differential_overtime']);

                                            $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['night_differential_overtime'];
                                        }

                                        $workHours[$dayType][$holidayType]['night_differential_overtime'] += $workDurationInHours;

                                    } else {
                                        if ($workHours[$dayType][$holidayType]['night_differential'] + $workDurationInHours > 0) {
                                            $remainingHours =
                                                max(0, $workHours[$dayType][$holidayType]['night_differential'] + $workDurationInHours) -
                                                max(0, $workHours[$dayType][$holidayType]['night_differential']);

                                            $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['night_differential'];
                                        }

                                        $workHours[$dayType][$holidayType]['night_differential'] += $workDurationInHours;
                                    }

                                } else {
                                    if ($numberOfHoursWorked > $totalRequiredWorkHours && $isOvertimeApproved) {
                                        if ($workHours[$dayType][$holidayType]['overtime_hours'] + $workDurationInHours > 0) {
                                            $remainingHours =
                                                max(0, $workHours[$dayType][$holidayType]['overtime_hours'] + $workDurationInHours) -
                                                max(0, $workHours[$dayType][$holidayType]['overtime_hours']);

                                            $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['overtime_hours'];
                                        }

                                        $workHours[$dayType][$holidayType]['overtime_hours'] += $workDurationInHours;

                                    } else {
                                        if ($workHours[$dayType][$holidayType]['regular_hours'] + $workDurationInHours > 0) {
                                            $remainingHours =
                                                max(0, $workHours[$dayType][$holidayType]['regular_hours'] + $workDurationInHours) -
                                                max(0, $workHours[$dayType][$holidayType]['regular_hours']);

                                            $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['regular_hours'];
                                        }

                                        $workHours[$dayType][$holidayType]['regular_hours'] += $workDurationInHours;
                                    }
                                }

                                $numberOfHoursWorked += $workDurationInHours;

                            } else {
                                $remainingMinutes = 60 - (int) $checkInDateTime->format('i');

                                if ($remainingMinutes < 60) {
                                    $dayOfWeek =       $checkInDateTime->format('l'    );
                                    $date      =       $checkInDateTime->format('Y-m-d');
                                    $hour      = (int) $checkInDateTime->format('H'    );

                                    $dayType = 'regular_day';

                                    if ($dayOfWeek === 'Sunday') {
                                        $dayType = 'rest_day';
                                    }

                                    $holidayType = 'non_holiday';

                                    if ( ! empty($datesMarkedAsHoliday[$date])) {
                                        if (count($datesMarkedAsHoliday[$date]) > 1) {
                                            if (count($datesMarkedAsHoliday[$date]) === 2) {
                                                if ($datesMarkedAsHoliday[$date][0]['is_paid'] ||
                                                    $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                    $holidayType = 'double_holiday';

                                                } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid'] &&
                                                           ! $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                    $holidayType = 'double_special_holiday';

                                                } else {
                                                    $holidayType = 'special_holiday';
                                                }

                                            } else {
                                                $hasPaidHoliday = false;

                                                foreach ($datesMarkedAsHoliday[$date] as $holiday) {
                                                    if ($holiday['is_paid']) {
                                                        $hasPaidHoliday = true;

                                                        break;
                                                    }
                                                }

                                                if ($hasPaidHoliday) {
                                                    $holidayType = 'regular_holiday';
                                                } else {
                                                    $holidayType = 'special_holiday';
                                                }
                                            }

                                        } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                            $holidayType = 'regular_holiday';
                                        } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid']) {
                                            $holidayType = 'special_holiday';
                                        }
                                    }

                                    $isNightShift = $hour >= 22 || $hour < 6;

                                    $workDurationInHours = $remainingMinutes / 60;

                                    if ($isNightShift) {
                                        if ($numberOfHoursWorked > $totalRequiredWorkHours && $isOvertimeApproved) {
                                            if ($workHours[$dayType][$holidayType]['night_differential_overtime'] + $workDurationInHours > 0) {
                                                $remainingHours =
                                                    max(0, $workHours[$dayType][$holidayType]['night_differential_overtime'] + $workDurationInHours) -
                                                    max(0, $workHours[$dayType][$holidayType]['night_differential_overtime']);

                                                $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['night_differential_overtime'];
                                            }

                                            $workHours[$dayType][$holidayType]['night_differential_overtime'] += $workDurationInHours;

                                        } else {
                                            if ($workHours[$dayType][$holidayType]['night_differential'] + $workDurationInHours > 0) {
                                                $remainingHours =
                                                    max(0, $workHours[$dayType][$holidayType]['night_differential'] + $workDurationInHours) -
                                                    max(0, $workHours[$dayType][$holidayType]['night_differential']);

                                                $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['night_differential'];
                                            }

                                            $workHours[$dayType][$holidayType]['night_differential'] += $workDurationInHours;
                                        }

                                    } else {
                                        if ($numberOfHoursWorked > $totalRequiredWorkHours && $isOvertimeApproved) {
                                            if ($workHours[$dayType][$holidayType]['overtime_hours'] + $workDurationInHours > 0) {
                                                $remainingHours =
                                                    max(0, $workHours[$dayType][$holidayType]['overtime_hours'] + $workDurationInHours) -
                                                    max(0, $workHours[$dayType][$holidayType]['overtime_hours']);

                                                $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['overtime_hours'];
                                            }

                                            $workHours[$dayType][$holidayType]['overtime_hours'] += $workDurationInHours;

                                        } else {
                                            if ($workHours[$dayType][$holidayType]['regular_hours'] + $workDurationInHours > 0) {
                                                $remainingHours =
                                                    max(0, $workHours[$dayType][$holidayType]['regular_hours'] + $workDurationInHours) -
                                                    max(0, $workHours[$dayType][$holidayType]['regular_hours']);

                                                $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['regular_hours'];
                                            }

                                            $workHours[$dayType][$holidayType]['regular_hours'] += $workDurationInHours;
                                        }
                                    }

                                    $numberOfHoursWorked += $workDurationInHours;
                                }

                                $adjustedCheckInDateTime = (clone $checkInDateTime)
                                    ->setTime(
                                        (int) $checkInDateTime->format('i') > 0
                                            ? (int) $checkInDateTime->format('H') + 1
                                            :       $checkInDateTime->format('H'),

                                        0, 0
                                    );

                                $adjustedCheckOutDateTime = (clone $checkOutDateTime)
                                    ->setTime($checkOutDateTime->format('H'), 0, 0);

                                $workTimeInterval = new DateInterval('PT1H');

                                $workTimePeriod = new DatePeriod(
                                    $adjustedCheckInDateTime ,
                                    $workTimeInterval        ,
                                    $adjustedCheckOutDateTime
                                );

                                foreach ($workTimePeriod as $currentWorkDateTime) {
                                    $dayOfWeek =       $currentWorkDateTime->format('l'    );
                                    $date      =       $currentWorkDateTime->format('Y-m-d');
                                    $hour      = (int) $currentWorkDateTime->format('H'    );

                                    $dayType = 'regular_day';

                                    if ($dayOfWeek === 'Sunday') {
                                        $dayType = 'rest_day';
                                    }

                                    $holidayType = 'non_holiday';

                                    if ( ! empty($datesMarkedAsHoliday[$date])) {
                                        if (count($datesMarkedAsHoliday[$date]) > 1) {
                                            if (count($datesMarkedAsHoliday[$date]) === 2) {
                                                if ($datesMarkedAsHoliday[$date][0]['is_paid'] ||
                                                    $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                    $holidayType = 'double_holiday';

                                                } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid'] &&
                                                           ! $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                    $holidayType = 'double_special_holiday';

                                                } else {
                                                    $holidayType = 'special_holiday';
                                                }

                                            } else {
                                                $hasPaidHoliday = false;

                                                foreach ($datesMarkedAsHoliday[$date] as $holiday) {
                                                    if ($holiday['is_paid']) {
                                                        $hasPaidHoliday = true;

                                                        break;
                                                    }
                                                }

                                                if ($hasPaidHoliday) {
                                                    $holidayType = 'regular_holiday';
                                                } else {
                                                    $holidayType = 'special_holiday';
                                                }
                                            }

                                        } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                            $holidayType = 'regular_holiday';
                                        } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid']) {
                                            $holidayType = 'special_holiday';
                                        }
                                    }

                                    $isNightShift = $hour >= 22 || $hour < 6;

                                    $workDurationInHours = 1.0;

                                    if ($isNightShift) {
                                        if ($numberOfHoursWorked > $totalRequiredWorkHours && $isOvertimeApproved) {
                                            if ($workHours[$dayType][$holidayType]['night_differential_overtime'] + $workDurationInHours > 0) {
                                                $remainingHours =
                                                    max(0, $workHours[$dayType][$holidayType]['night_differential_overtime'] + $workDurationInHours) -
                                                    max(0, $workHours[$dayType][$holidayType]['night_differential_overtime']);

                                                $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['night_differential_overtime'];
                                            }

                                            $workHours[$dayType][$holidayType]['night_differential_overtime'] += $workDurationInHours;

                                        } else {
                                            if ($workHours[$dayType][$holidayType]['night_differential'] + $workDurationInHours > 0) {
                                                $remainingHours =
                                                    max(0, $workHours[$dayType][$holidayType]['night_differential'] + $workDurationInHours) -
                                                    max(0, $workHours[$dayType][$holidayType]['night_differential']);

                                                $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['night_differential'];
                                            }

                                            $workHours[$dayType][$holidayType]['night_differential'] += $workDurationInHours;
                                        }

                                    } else {
                                        if ($numberOfHoursWorked > $totalRequiredWorkHours && $isOvertimeApproved) {
                                            if ($workHours[$dayType][$holidayType]['overtime_hours'] + $workDurationInHours > 0) {
                                                $remainingHours =
                                                    max(0, $workHours[$dayType][$holidayType]['overtime_hours'] + $workDurationInHours) -
                                                    max(0, $workHours[$dayType][$holidayType]['overtime_hours']);

                                                $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['overtime_hours'];
                                            }

                                            $workHours[$dayType][$holidayType]['overtime_hours'] += $workDurationInHours;

                                        } else {
                                            if ($workHours[$dayType][$holidayType]['regular_hours'] + $workDurationInHours > 0) {
                                                $remainingHours =
                                                    max(0, $workHours[$dayType][$holidayType]['regular_hours'] + $workDurationInHours) -
                                                    max(0, $workHours[$dayType][$holidayType]['regular_hours']);

                                                $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['regular_hours'];
                                            }

                                            $workHours[$dayType][$holidayType]['regular_hours'] += $workDurationInHours;
                                        }
                                    }

                                    $numberOfHoursWorked += $workDurationInHours;
                                }

                                $remainingMinutes = 60 - (int) $checkOutDateTime->format('i');

                                if ($remainingMinutes < 60) {
                                    $dayOfWeek =       $checkOutDateTime->format('l'    );
                                    $date      =       $checkOutDateTime->format('Y-m-d');
                                    $hour      = (int) $checkOutDateTime->format('H'    );

                                    $dayType = 'regular_day';

                                    if ($dayOfWeek === 'Sunday') {
                                        $dayType = 'rest_day';
                                    }

                                    $holidayType = 'non_holiday';

                                    if ( ! empty($datesMarkedAsHoliday[$date])) {
                                        if (count($datesMarkedAsHoliday[$date]) > 1) {
                                            if (count($datesMarkedAsHoliday[$date]) === 2) {
                                                if ($datesMarkedAsHoliday[$date][0]['is_paid'] ||
                                                    $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                    $holidayType = 'double_holiday';

                                                } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid'] &&
                                                           ! $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                                    $holidayType = 'double_special_holiday';

                                                } else {
                                                    $holidayType = 'special_holiday';
                                                }

                                            } else {
                                                $hasPaidHoliday = false;

                                                foreach ($datesMarkedAsHoliday[$date] as $holiday) {
                                                    if ($holiday['is_paid']) {
                                                        $hasPaidHoliday = true;

                                                        break;
                                                    }
                                                }

                                                if ($hasPaidHoliday) {
                                                    $holidayType = 'regular_holiday';
                                                } else {
                                                    $holidayType = 'special_holiday';
                                                }
                                            }

                                        } elseif ($datesMarkedAsHoliday[$date][0]['is_paid']) {
                                            $holidayType = 'regular_holiday';
                                        } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid']) {
                                            $holidayType = 'special_holiday';
                                        }
                                    }

                                    $isNightShift = $hour >= 22 || $hour < 6;

                                    $workDurationInHours = $remainingMinutes / 60;

                                    if ($isNightShift) {
                                        if ($numberOfHoursWorked > $totalRequiredWorkHours && $isOvertimeApproved) {
                                            if ($workHours[$dayType][$holidayType]['night_differential_overtime'] + $workDurationInHours > 0) {
                                                $remainingHours =
                                                    max(0, $workHours[$dayType][$holidayType]['night_differential_overtime'] + $workDurationInHours) -
                                                    max(0, $workHours[$dayType][$holidayType]['night_differential_overtime']);

                                                $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['night_differential_overtime'];
                                            }

                                            $workHours[$dayType][$holidayType]['night_differential_overtime'] += $workDurationInHours;

                                        } else {
                                            if ($workHours[$dayType][$holidayType]['night_differential'] + $workDurationInHours > 0) {
                                                $remainingHours =
                                                    max(0, $workHours[$dayType][$holidayType]['night_differential'] + $workDurationInHours) -
                                                    max(0, $workHours[$dayType][$holidayType]['night_differential']);

                                                $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['night_differential'];
                                            }

                                            $workHours[$dayType][$holidayType]['night_differential'] += $workDurationInHours;
                                        }

                                    } else {
                                        if ($numberOfHoursWorked > $totalRequiredWorkHours && $isOvertimeApproved) {
                                            if ($workHours[$dayType][$holidayType]['overtime_hours'] + $workDurationInHours > 0) {
                                                $remainingHours =
                                                    max(0, $workHours[$dayType][$holidayType]['overtime_hours'] + $workDurationInHours) -
                                                    max(0, $workHours[$dayType][$holidayType]['overtime_hours']);

                                                $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['overtime_hours'];
                                            }

                                            $workHours[$dayType][$holidayType]['overtime_hours'] += $workDurationInHours;

                                        } else {
                                            if ($workHours[$dayType][$holidayType]['regular_hours'] + $workDurationInHours > 0) {
                                                $remainingHours =
                                                    max(0, $workHours[$dayType][$holidayType]['regular_hours'] + $workDurationInHours) -
                                                    max(0, $workHours[$dayType][$holidayType]['regular_hours']);

                                                $grossPay += $remainingHours * $hourlyRate * $workHourRates[$dayType][$holidayType]['regular_hours'];
                                            }

                                            $workHours[$dayType][$holidayType]['regular_hours'] += $workDurationInHours;
                                        }
                                    }

                                    $numberOfHoursWorked += $workDurationInHours;
                                }
                            }
                        }
                    }
                }
            }

            if ( ! empty($attendanceRecords[$workDate])) {
                $regularHoursWorked = min($numberOfHoursWorked, $totalRequiredWorkHours);
                $basicPay += $regularHoursWorked * $hourlyRate;

                $holidayType = 'non_holiday';

                if ( ! empty($datesMarkedAsHoliday[$workDate])) {
                    if (count($datesMarkedAsHoliday[$workDate]) > 1) {
                        if (count($datesMarkedAsHoliday[$workDate]) === 2) {
                            if ($datesMarkedAsHoliday[$date][0]['is_paid'] ||
                                $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                $holidayType = 'double_holiday';

                            } elseif ( ! $datesMarkedAsHoliday[$date][0]['is_paid'] &&
                                       ! $datesMarkedAsHoliday[$date][1]['is_paid']) {

                                $holidayType = 'double_special_holiday';

                            } else {
                                $holidayType = 'special_holiday';
                            }

                        } else {
                            $hasPaidHoliday = false;

                            foreach ($datesMarkedAsHoliday[$workDate] as $holiday) {
                                if ($holiday['is_paid']) {
                                    $hasPaidHoliday = true;

                                    break;
                                }
                            }

                            if ($hasPaidHoliday) {
                                $holidayType = 'regular_holiday';
                            } else {
                                $holidayType = 'special_holiday';
                            }
                        }

                    } elseif ($datesMarkedAsHoliday[$workDate][0]['is_paid']) {
                        $holidayType = 'regular_holiday';
                    } elseif ( ! $datesMarkedAsHoliday[$workDate][0]['is_paid']) {
                        $holidayType = 'special_holiday';
                    }
                }

                $remainingHours = $totalRequiredWorkHours - $numberOfHoursWorked;

                if ($remainingHours > 0) {
                    if ($holidayType === 'regular_holiday') {
                        $workHours['non_worked_paid_hours']['regular_holiday'] += $remainingHours;

                        $grossPay += $remainingHours * $hourlyRate;
                        $basicPay += $remainingHours * $hourlyRate;

                    } elseif ($holidayType === 'double_holiday') {
                        $workHours['non_worked_paid_hours']['double_holiday'] += $remainingHours;

                        $grossPay += $remainingHours * ($hourlyRate * 2);
                        $basicPay += $remainingHours *  $hourlyRate     ;
                    }
                }

                if ($holidayType === 'non_holiday' || $holidayType === 'special_holiday' || $holidayType === 'double_special_holiday') {
                    if (   $datesMarkedAsLeave[$workDate]['is_leave'   ] &&
                           $datesMarkedAsLeave[$workDate]['is_paid'    ] &&
                         ! $datesMarkedAsLeave[$workDate]['is_half_day']) {

                        if ($remainingHours > 0) {
                            $workHours['non_worked_paid_hours']['leave'] += $remainingHours;

                            $grossPay += $remainingHours * $hourlyRate;
                            $basicPay += $remainingHours * $hourlyRate;
                        }

                    } elseif ($datesMarkedAsLeave[$workDate]['is_leave'   ] &&
                              $datesMarkedAsLeave[$workDate]['is_paid'    ] &&
                              $datesMarkedAsLeave[$workDate]['is_half_day']) {

                        $remainingHours = $totalRequiredWorkHours / 2;

                        $workHours['non_worked_paid_hours']['leave'] += $remainingHours;

                        $grossPay += $remainingHours * $hourlyRate;
                        $basicPay += $remainingHours * $hourlyRate;
                    }
                }
            }
        }

        return ActionResult::SUCCESS;
    }

    private function calculateSssContribution(float $salary): array
    {
        $contributions = [
            ['range' => [0    , 4249.99 ], 'employee_share' => 180.00 , 'employer_share' => 390.00 ],
            ['range' => [4250 , 4749.99 ], 'employee_share' => 202.50 , 'employer_share' => 437.50 ],
            ['range' => [4750 , 5249.99 ], 'employee_share' => 225.00 , 'employer_share' => 485.00 ],
            ['range' => [5250 , 5749.99 ], 'employee_share' => 247.50 , 'employer_share' => 532.50 ],
            ['range' => [5750 , 6249.99 ], 'employee_share' => 270.00 , 'employer_share' => 580.00 ],
            ['range' => [6250 , 6749.99 ], 'employee_share' => 292.50 , 'employer_share' => 627.50 ],
            ['range' => [6750 , 7249.99 ], 'employee_share' => 315.00 , 'employer_share' => 675.00 ],
            ['range' => [7250 , 7749.99 ], 'employee_share' => 337.50 , 'employer_share' => 722.50 ],
            ['range' => [7750 , 8249.99 ], 'employee_share' => 360.00 , 'employer_share' => 770.00 ],
            ['range' => [8250 , 8749.99 ], 'employee_share' => 382.50 , 'employer_share' => 817.50 ],
            ['range' => [8750 , 9249.99 ], 'employee_share' => 405.00 , 'employer_share' => 865.00 ],
            ['range' => [9250 , 9749.99 ], 'employee_share' => 427.50 , 'employer_share' => 912.50 ],
            ['range' => [9750 , 10249.99], 'employee_share' => 450.00 , 'employer_share' => 960.00 ],
            ['range' => [10250, 10749.99], 'employee_share' => 472.50 , 'employer_share' => 1007.50],
            ['range' => [10750, 11249.99], 'employee_share' => 495.00 , 'employer_share' => 1055.00],
            ['range' => [11250, 11749.99], 'employee_share' => 517.50 , 'employer_share' => 1102.50],
            ['range' => [11750, 12249.99], 'employee_share' => 540.00 , 'employer_share' => 1150.00],
            ['range' => [12250, 12749.99], 'employee_share' => 562.50 , 'employer_share' => 1197.50],
            ['range' => [12750, 13249.99], 'employee_share' => 585.00 , 'employer_share' => 1245.00],
            ['range' => [13250, 13749.99], 'employee_share' => 607.50 , 'employer_share' => 1292.50],
            ['range' => [13750, 14249.99], 'employee_share' => 630.00 , 'employer_share' => 1340.00],
            ['range' => [14250, 14749.99], 'employee_share' => 652.50 , 'employer_share' => 1387.50],
            ['range' => [14750, 15249.99], 'employee_share' => 675.00 , 'employer_share' => 1455.00],
            ['range' => [15250, 15749.99], 'employee_share' => 697.50 , 'employer_share' => 1502.50],
            ['range' => [15750, 16249.99], 'employee_share' => 720.00 , 'employer_share' => 1550.00],
            ['range' => [16250, 16749.99], 'employee_share' => 742.50 , 'employer_share' => 1597.50],
            ['range' => [16750, 17249.99], 'employee_share' => 765.00 , 'employer_share' => 1645.00],
            ['range' => [17250, 17749.99], 'employee_share' => 787.50 , 'employer_share' => 1692.50],
            ['range' => [17750, 18249.99], 'employee_share' => 810.00 , 'employer_share' => 1740.00],
            ['range' => [18250, 18749.99], 'employee_share' => 832.50 , 'employer_share' => 1787.50],
            ['range' => [18750, 19249.99], 'employee_share' => 855.00 , 'employer_share' => 1835.00],
            ['range' => [19250, 19749.99], 'employee_share' => 877.50 , 'employer_share' => 1882.50],
            ['range' => [19750, 20249.99], 'employee_share' => 900.00 , 'employer_share' => 1930.00],
            ['range' => [20250, 20749.99], 'employee_share' => 922.50 , 'employer_share' => 1977.50],
            ['range' => [20750, 21249.99], 'employee_share' => 945.00 , 'employer_share' => 2025.00],
            ['range' => [21250, 21749.99], 'employee_share' => 967.50 , 'employer_share' => 2072.50],
            ['range' => [21750, 22249.99], 'employee_share' => 990.00 , 'employer_share' => 2120.00],
            ['range' => [22250, 22749.99], 'employee_share' => 1012.50, 'employer_share' => 2167.50],
            ['range' => [22750, 23249.99], 'employee_share' => 1035.00, 'employer_share' => 2215.00],
            ['range' => [23250, 23749.99], 'employee_share' => 1057.50, 'employer_share' => 2262.50],
            ['range' => [23750, 24249.99], 'employee_share' => 1080.00, 'employer_share' => 2310.00],
            ['range' => [24250, 24749.99], 'employee_share' => 1102.50, 'employer_share' => 2357.50],
            ['range' => [24750, 25249.99], 'employee_share' => 1125.00, 'employer_share' => 2405.00],
            ['range' => [25250, 25749.99], 'employee_share' => 1147.50, 'employer_share' => 2452.50],
            ['range' => [25750, 26249.99], 'employee_share' => 1170.00, 'employer_share' => 2500.00],
            ['range' => [26250, 26749.99], 'employee_share' => 1192.50, 'employer_share' => 2547.50],
            ['range' => [26750, 27249.99], 'employee_share' => 1215.00, 'employer_share' => 2595.00],
            ['range' => [27250, 27749.99], 'employee_share' => 1237.50, 'employer_share' => 2642.50],
            ['range' => [27750, 28249.99], 'employee_share' => 1260.00, 'employer_share' => 2690.00],
            ['range' => [28250, 28749.99], 'employee_share' => 1282.50, 'employer_share' => 2737.50],
            ['range' => [28750, 29249.99], 'employee_share' => 1305.00, 'employer_share' => 2785.00],
            ['range' => [29250, 29749.99], 'employee_share' => 1327.50, 'employer_share' => 2832.50],
            ['range' => [29750, 'Over'  ], 'employee_share' => 1350.00, 'employer_share' => 2880.00]
        ];

        foreach ($contributions as $contribution) {
            if (($contribution['range'][1] === 'Over'  &&
                 $salary >= $contribution['range'][0]) ||

                ($salary >= $contribution['range'][0]  &&
                 $salary <= $contribution['range'][1])) {

                return [
                    'employee_share' => $contribution['employee_share'],
                    'employer_share' => $contribution['employer_share']
                ];
            }
        }

        return [];
    }

    private function calculatePhilhealthContribution(float $salary, int $year): array
    {
        $totalContribution = 0.00;

        if ($year === 2024 ||
            $year === 2025) {

            if ($salary <= 10000.00) {
                $totalContribution = 500.00;

            } elseif ($salary >= 10000.01 &&
                      $salary <= 99999.99) {

                $totalContribution = max(500.00, $salary * 0.05);

                if ($totalContribution > 5000.00) {
                    $totalContribution = 5000.00;
                }
            }
        }

        $employeeShare = $totalContribution / 2.00;
        $employerShare = $totalContribution / 2.00;

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

    private function calculateWithholdingTax(float $compensation, string $payFrequency): float
    {
        $withholdingTax = 0.00;

        switch (strtolower($payFrequency)) {
            case 'daily':
                if     ($compensation <=    685.00) { $withholdingTax = 0.00;                                          }
                elseif ($compensation <=  1_095.00) { $withholdingTax =            ($compensation -    685.00) * 0.15; }
                elseif ($compensation <=  2_191.00) { $withholdingTax =    61.65 + ($compensation -  1_095.00) * 0.20; }
                elseif ($compensation <=  5_478.00) { $withholdingTax =   280.85 + ($compensation -  2_191.00) * 0.25; }
                elseif ($compensation <= 21_917.00) { $withholdingTax = 1_102.60 + ($compensation -  5_478.00) * 0.30; }
                else                                { $withholdingTax = 6_034.30 + ($compensation - 21_917.00) * 0.35; }

                break;

            case 'weekly':
                if     ($compensation <=   4_808.00) { $withholdingTax = 0.00;                                            }
                elseif ($compensation <=   7_691.00) { $withholdingTax =             ($compensation -   4_808.00) * 0.15; }
                elseif ($compensation <=  15_384.00) { $withholdingTax =    432.60 + ($compensation -   7_691.00) * 0.20; }
                elseif ($compensation <=  38_461.00) { $withholdingTax =  1_971.20 + ($compensation -  15_384.00) * 0.25; }
                elseif ($compensation <= 153_845.00) { $withholdingTax =  7_740.45 + ($compensation -  38_461.00) * 0.30; }
                else                                 { $withholdingTax = 42_355.65 + ($compensation - 153_845.00) * 0.35; }

                break;

            case 'bi-weekly':
                if     ($compensation <=   9_616.00) { $withholdingTax = 0.00;                                            }
                elseif ($compensation <=  15_382.00) { $withholdingTax =             ($compensation -   9_616.00) * 0.15; }
                elseif ($compensation <=  30_768.00) { $withholdingTax =    865.20 + ($compensation -  15_382.00) * 0.20; }
                elseif ($compensation <=  76_922.00) { $withholdingTax =  3_942.40 + ($compensation -  30_768.00) * 0.25; }
                elseif ($compensation <= 307_690.00) { $withholdingTax = 15_480.90 + ($compensation -  76_922.00) * 0.30; }
                else                                 { $withholdingTax = 84_671.30 + ($compensation - 307_690.00) * 0.35; }

                break;

            case 'semi-monthly':
                if     ($compensation <=  10_417.00) { $withholdingTax = 0.00;                                            }
                elseif ($compensation <=  16_666.00) { $withholdingTax =             ($compensation -  10_417.00) * 0.15; }
                elseif ($compensation <=  33_332.00) { $withholdingTax =    937.50 + ($compensation -  16_666.00) * 0.20; }
                elseif ($compensation <=  83_332.00) { $withholdingTax =  4_270.70 + ($compensation -  33_332.00) * 0.25; }
                elseif ($compensation <= 333_332.00) { $withholdingTax = 16_770.70 + ($compensation -  83_332.00) * 0.30; }
                else                                 { $withholdingTax = 91_770.70 + ($compensation - 333_332.00) * 0.35; }
                break;

            case 'monthly':
                if     ($compensation <=  20_833.00) { $withholdingTax = 0.00;                                             }
                elseif ($compensation <=  33_332.00) { $withholdingTax =              ($compensation -  20_833.00) * 0.15; }
                elseif ($compensation <=  66_666.00) { $withholdingTax =   1_875.00 + ($compensation -  33_332.00) * 0.20; }
                elseif ($compensation <= 166_666.00) { $withholdingTax =   8_541.80 + ($compensation -  66_666.00) * 0.25; }
                elseif ($compensation <= 666_666.00) { $withholdingTax =  33_541.80 + ($compensation - 166_666.00) * 0.30; }
                else                                 { $withholdingTax = 183_541.80 + ($compensation - 666_666.00) * 0.35; }

                break;
        }

        return $withholdingTax;
    }
}
