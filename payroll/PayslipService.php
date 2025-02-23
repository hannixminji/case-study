<?php

require_once __DIR__ . '/../employees/EmployeeRepository.php'   ;
require_once __DIR__ . '/../attendance/AttendanceRepository.php';

class PayslipService
{
    private readonly EmployeeRepository   $employeeRepository  ;
    private readonly AttendanceRepository $attendanceRepository;

    public function __construct(
        EmployeeRepository   $employeeRepository  ,
        AttendanceRepository $attendanceRepository
    ) {
        $this->employeeRepository   = $employeeRepository  ;
        $this->attendanceRepository = $attendanceRepository;
    }

    public function generatePayslip(
        PayrollGroup $payrollGroup         ,
        string       $cutoffPeriodStartDate,
        string       $cutoffPeriodEndDate  ,
        string       $paydayDate
    ) {

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
                'status'  => 'information',
                'message' => ''
            ];
        }

        foreach ($employees as $employee) {
            $employeeId   = $employee['id'           ];
            $jobTitleId   = $employee['job_title_id' ];
            $departmentId = $employee['department_id'];
            $basicSalary  = $employee['basic_salary' ];

            $adjustedCutoffPeriodStartDate =
                (new DateTime($cutoffPeriodStartDate))
                    ->modify('-1 day')
                    ->format('Y-m-d' );

            $attendanceRecordColumns = [
                'date'                                                    ,
                'check_in_time'                                           ,
                'check_out_time'                                          ,
                'is_overtime_approved'                                    ,
                'attendance_status'                                       ,
                'is_processed_for_next_payroll'                           ,

                'work_schedule_snapshot_work_schedule_id'                 ,
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

            if ( ! empty($employeeAttendanceRecords)) {
                foreach ($employeeAttendanceRecords as $attendanceRecord) {
                    $date           = $attendanceRecord['date'                                   ];
                    $workScheduleId = $attendanceRecord['work_schedule_snapshot_work_schedule_id'];

                    if ( ! isset($attendanceRecords[$date][$workScheduleId])) {
                        $attendanceRecords[$date][$workScheduleId] = [
                            'work_schedule' => [
                                'id'                                => $attendanceRecord['work_schedule_snapshot_work_schedule_id'                 ],
                                'start_time'                        => $attendanceRecord['work_schedule_snapshot_start_time'                       ],
                                'end_time'                          => $attendanceRecord['work_schedule_snapshot_end_time'                         ],
                                'is_flextime'                       => $attendanceRecord['work_schedule_snapshot_is_flextime'                      ],
                                'total_work_hours'                  => $attendanceRecord['work_schedule_snapshot_total_work_hours'                 ],
                                'grace_period'                      => $attendanceRecord['work_schedule_snapshot_grace_period'                     ],
                                'minutes_can_check_in_before_shift' => $attendanceRecord['work_schedule_snapshot_minutes_can_check_in_before_shift'],
                            ],

                            'attendance_records' => []
                        ];
                    }

                    $attendanceRecords[$date][$workScheduleId]['attendance_records'][] = [
                        'date'                          => $attendanceRecord['date'                         ] ,
                        'check_in_time'                 => $attendanceRecord['check_in_time'                ] ,
                        'check_out_time'                => $attendanceRecord['check_out_time'               ] ,
                        'is_overtime_approved'          => $attendanceRecord['is_overtime_approved'         ] ,
                        'attendance_status'             => strtolower($attendanceRecord['attendance_status' ]),
                        'is_processed_for_next_payroll' => $attendanceRecord['is_processed_for_next_payroll']
                    ];
                }

                $firstDate = array_key_first($attendanceRecords);

                if ($firstDate === $adjustedCutoffPeriodStartDate) {
                    $lastWorkSchedule     = end($attendanceRecords[$firstDate          ]);
                    $lastAttendanceRecord = end($lastWorkSchedule ['attendance_records']);

                    $attendanceRecords[$firstDate] = [$lastWorkSchedule];

                    if (   $lastAttendanceRecord['attendance_status'            ] === 'absent' ||
                         ! $lastAttendanceRecord['is_processed_for_next_payroll']) {

                        unset($attendanceRecords[$firstDate]);
                    }
                }

                if ( ! empty($attendanceRecords)) {
                    $lastDate             = array_key_last($attendanceRecords           );
                    $lastWorkSchedule     = end($attendanceRecords[$lastDate           ]);
                    $lastAttendanceRecord = end($lastWorkSchedule ['attendance_records']);

                    
                }

                //
            }
        }
    }
}
