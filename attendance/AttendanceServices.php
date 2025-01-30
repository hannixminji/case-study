<?php

require_once __DIR__ . '/Attendance.php'                              ;
require_once __DIR__ . '/../breaks/EmployeeBreak.php'                 ;

require_once __DIR__ . '/AttendanceRepository.php'                    ;

require_once __DIR__ . '/../employees/EmployeeRepository.php'         ;
require_once __DIR__ . '/../leaves/LeaveRequestRepository.php'        ;
require_once __DIR__ . '/../work-schedules/WorkScheduleRepository.php';
require_once __DIR__ . '/../settings/SettingRepository.php'           ;
require_once __DIR__ . '/../breaks/BreakScheduleRepository.php'       ;
require_once __DIR__ . '/../breaks/EmployeeBreakRepository.php'       ;

class AttendanceService
{
    private readonly AttendanceRepository    $attendanceRepository   ;
    private readonly EmployeeRepository      $employeeRepository     ;
    private readonly LeaveRequestRepository  $leaveRequestRepository ;
    private readonly WorkScheduleRepository  $workScheduleRepository ;
    private readonly SettingRepository       $settingRepository      ;
    private readonly BreakScheduleRepository $breakScheduleRepository;
    private readonly EmployeeBreakRepository $employeeBreakRepository;

    public function __construct(
        AttendanceRepository    $attendanceRepository   ,
        EmployeeRepository      $employeeRepository     ,
        LeaveRequestRepository  $leaveRequestRepository ,
        WorkScheduleRepository  $workScheduleRepository ,
        SettingRepository       $settingRepository      ,
        BreakScheduleRepository $breakScheduleRepository,
        EmployeeBreakRepository $employeeBreakRepository
    ) {
        $this->attendanceRepository    = $attendanceRepository   ;
        $this->employeeRepository      = $employeeRepository     ;
        $this->leaveRequestRepository  = $leaveRequestRepository ;
        $this->workScheduleRepository  = $workScheduleRepository ;
        $this->settingRepository       = $settingRepository      ;
        $this->breakScheduleRepository = $breakScheduleRepository;
        $this->employeeBreakRepository = $employeeBreakRepository;
    }

    public function handleRfidTap(string $rfidUid, string $currentDateTime)
    {
        $employeeColumns = [
			'id'
		];

		$employeeFilterCriteria = [
			[
				'column'   => 'employee.deleted_at',
				'operator' => 'IS NULL'
			],
			[
				'column'   => 'employee.rfid_uid',
				'operator' => '='                ,
				'value'    => $rfidUid
			]
		];

        $employeeFetchResult = $this->employeeRepository->fetchAllEmployees(
		    columns       : $employeeColumns       ,
		    filterCriteria: $employeeFilterCriteria,
		    limit         : 1
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

        $employeeId = $employeeFetchResult['result_set'][0]['id'];

    }
}
