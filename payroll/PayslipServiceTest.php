<?php

require_once __DIR__ . '/../employees/EmployeeRepository.php'   ;
require_once __DIR__ . '/../attendance/AttendanceRepository.php';

class PayslipService
{
    private readonly EmployeeRepository   $employeeRepository  ;
    private readonly AttendanceRepository $attendanceRepository;

    public function construct(
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

        /*
        $payslipColumns = [
        ];

        $payslipFilterCriteria = [
            [
            ]
        ];

        $payslipSortCriteria = [
            [
            ]
        ];

        $previousPayrollCutoff = $this->payslipRepository->fetchAll(
            columns             : $payslipColumns       ,
            filterCriteria      : $payslipFilterCriteria,
            sortCriteria        : $payslipSortCriteria  ,
            includeTotalRowCount: false
        );

        if ($previousPayrollCutoff === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $previousPayrollCutoff =
            ! empty($previousPayrollCutoff['result_set'])
                ? $previousPayrollCutoff['result_set']
                : [];
        $a = 1;
        */

        foreach ($employees as $employee) {
            $employeeId   = $employee['id'           ];
            $jobTitleId   = $employee['job_title_id' ];
            $departmentId = $employee['department_id'];
            $basicSalary  = $employee['basic_salary' ];

            $attendanceRecordColumns = [
            ];

            $attendanceRecordFilterCriteria = [
            ];

            $attendanceRecordSortCriteria = [
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
                }
            }
        }
    }
}
