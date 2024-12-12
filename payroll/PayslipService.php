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

    public function generatePaySlips(PayrollGroup $payrollGroup, string $cutoffStartDate, string $cutoffEndDate)
    {

    }
}
