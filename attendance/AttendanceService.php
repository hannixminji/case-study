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
		        'message' => 'No employee found. This RFID may be invalid or not associated with any employee.'
		    ];
        }

        $employeeId = $employeeFetchResult['result_set'][0]['id'];

        $leaveRequestColumns = [
            'is_half_day'
        ];

        $leaveRequestFilterCriteria = [
            [
                'column'   => 'leave_request.deleted_at',
                'operator' => 'IS NULL'
            ],
            [
                'column'   => 'leave_request.employee_id',
                'operator' => '='                        ,
                'value'    => $employeeId
            ],
            [
                'column'   => 'leave_request.status',
                'operator' => ''                   ,
                'value'    => ''
            ]
        ];

        $attendanceColumns = [
			'id'                             ,
            'work_schedule_history_id'       ,
			'date'                           ,
			'check_in_time'                  ,
			'check_out_time'                 ,
            'total_break_duration_in_minutes',
            'total_hours_worked'             ,
            'late_check_in'                  ,
            'early_check_out'                ,
            'overtime_hours'                 ,
            'is_overtime_approved'           ,
            'attendance_status'              ,
            'remarks'
		];

		$attendanceFilterCriteria = [
			[
				'column'   => 'attendance.deleted_at',
				'operator' => 'IS NULL'
			],
			[
				'column'   => 'work_schedule_history.employee_id',
				'operator' => '='                                ,
				'value'    => $employeeId
			]
		];

        $attendanceSortCriteria = [
            [
                'column'    => 'attendance.date',
                'direction' => 'DESC'
            ],
            [
                'column'    => 'attendance.check_in_time',
                'direction' => 'DESC'
            ]
        ];

        $attendanceFetchResult = $this->attendanceRepository->fetchAllAttendance(
            columns       : $attendanceColumns       ,
            filterCriteria: $attendanceFilterCriteria,
            sortCriteria  : $attendanceSortCriteria  ,
            limit         : 1
        );

        if ($attendanceFetchResult === ActionResult::FAILURE) {
            return [
		        'status'  => 'error',
		        'message' => 'An unexpected error occurred. Please try again later.'
		    ];
        }

        $lastAttendanceRecord =
            ! empty($attendanceFetchResult['result_set'])
                ? $attendanceFetchResult['result_set'][0]
                : [];

        if (empty($lastAttendanceRecord) ||

           ($lastAttendanceRecord['check_in_time' ] !== null  &&
            $lastAttendanceRecord['check_out_time'] !== null)) {

            $isCheckIn = true;

            $currentDateTime = new ($currentDateTime                         );
            $currentDate     = new DateTime($currentDateTime->format('Y-m-d'));
            $previousDate    = (clone $currentDate)->modify('-1 day'         );

            $formattedCurrentDate  = $currentDate ->format('Y-m-d');
            $formattedPreviousDate = $previousDate->format('Y-m-d');

            $workScheduleColumns = [
                'id'              ,
                'start_time'      ,
                'end_time'        ,
                'total_work_hours',
                'recurrent_rule'
            ];

            $workScheduleFilterCriteria = [
                [
                    'column'   => 'work_schedule.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'   => 'work_schedule.employee_id',
                    'operator' => '='                        ,
                    'value'    => $employeeId
                ],
                [
                    'column'   => 'work_schedule.start_date',
                    'operator' => '<='                      ,
                    'value'    => $formattedCurrentDate
                ]
            ];

            $workScheduleSortCriteria = [
                [
                    'column'    => 'work_schedule.start_date',
                    'direction' => 'ASC'
                ],
                [
                    'column'    => 'work_schedule.start_time',
                    'direction' => 'ASC'
                ]
            ];

            $workScheduleFetchResult = $this->workScheduleRepository->fetchAllWorkSchedules(
                columns       : $workScheduleColumns       ,
                filterCriteria: $workScheduleFilterCriteria,
                sortCriteria  : $workScheduleSortCriteria
            );

            if ($workScheduleFetchResult === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            if (empty($workScheduleFetchResult['result_set'])) {
                return [
                    'status'  => 'warning',
                    'message' => 'You don\'t have an assigned work schedule, or your schedule starts on a later date.'
                ];
            }

            $workSchedules = $workScheduleFetchResult['result_set'];

            $currentWorkSchedules = [];

            foreach ($workSchedules as $workSchedule) {
                $workScheduleDates = $this->workScheduleRepository->getRecurrenceDates(
                    $workSchedule['recurrence_rule'],
                    $formattedPreviousDate          ,
                    $formattedCurrentDate
                );

                if ($workScheduleDates === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.'
                    ];
                }

                foreach ($workScheduleDates as $workScheduleDate) {
                    $currentWorkSchedules[$workScheduleDate][] = $workSchedule;
                }
            }



        }
    }
}
