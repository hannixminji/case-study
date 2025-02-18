<?php

require_once __DIR__ . '/EmployeeBreak.php'                     ;

require_once __DIR__ . '/EmployeeBreakRepository.php'           ;
require_once __DIR__ . '/../employees/EmployeeRepository.php'   ;
require_once __DIR__ . '/../attendance/AttendanceRepository.php';
require_once __DIR__ . '/BreakScheduleRepository.php'           ;

class EmployeeBreakService
{
    private readonly EmployeeBreakRepository $employeeBreakRepository;
    private readonly EmployeeRepository      $employeeRepository     ;
    private readonly AttendanceRepository    $attendanceRepository   ;
    private readonly BreakScheduleRepository $breakScheduleRepository;

    public function __construct(
        EmployeeBreakRepository $employeeBreakRepository,
        EmployeeRepository      $employeeRepository     ,
        AttendanceRepository    $attendanceRepository   ,
        BreakScheduleRepository $breakScheduleRepository
    ) {
        $this->employeeBreakRepository = $employeeBreakRepository;
        $this->employeeRepository      = $employeeRepository     ;
        $this->attendanceRepository    = $attendanceRepository   ;
        $this->breakScheduleRepository = $breakScheduleRepository;
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

        $employeeId = $this->employeeRepository->fetchAllEmployees(
            columns             : $employeeColumns       ,
            filterCriteria      : $employeeFilterCriteria,
            limit               : 1                      ,
            includeTotalRowCount: false
        );

        if ($employeeId === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $employeeId =
            ! empty($employeeId['result_set'])
                ? $employeeId['result_set'][0]['id']
                : [];

        if (empty($employeeId)) {
            return [
                'status'  => 'warning',
                'message' => 'No employee found. This RFID may be invalid or ' .
                			 'not associated with any employee.'
            ];
        }

        $attendanceRecordColumns = [
            'id'                                     ,
            'date'                                   ,
            'check_in_time'                          ,
            'check_out_time'                         ,

            'work_schedule_snapshot_work_schedule_id',
            'work_schedule_snapshot_start_time'      ,
            'work_schedule_snapshot_end_time'        ,
            'work_schedule_snapshot_is_flextime'
        ];

        $attendanceRecordFilterCriteria = [
            [
                'column'   => 'attendance.deleted_at',
                'operator' => 'IS NULL'
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
                'direction' => 'DESC'
            ],
            [
                'column'    => 'attendance.check_in_time',
                'direction' => 'DESC'
            ],
            [
                'column'    => 'work_schedule_snapshot_start_time',
                'direction' => 'DESC'
            ]
        ];

        $lastAttendanceRecord = $this->attendanceRepository->fetchAllAttendance(
            columns             : $attendanceRecordColumns       ,
            filterCriteria      : $attendanceRecordFilterCriteria,
            sortCriteria        : $attendanceRecordSortCriteria  ,
            limit               : 1                              ,
            includeTotalRowCount: false
        );

        if ($lastAttendanceRecord === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $lastAttendanceRecord =
            ! empty($lastAttendanceRecord['result_set'])
                ? $lastAttendanceRecord['result_set'][0]
                : [];

        if ( ! empty($lastAttendanceRecord) &&
                     $lastAttendanceRecord['work_schedule_snapshot_is_flextime']) {

            return [
                'status'  => 'information',
                'message' => 'You are on a flexible schedule. Breaks may ' .
                             'be taken at your convenience.'
            ];
        }

        if (empty($lastAttendanceRecord)                      ||

           ($lastAttendanceRecord['check_in_time' ] !== null  &&
            $lastAttendanceRecord['check_out_time'] !== null) ||

           ($lastAttendanceRecord['check_in_time' ] === null  &&
            $lastAttendanceRecord['check_out_time'] !== null) ||

           ($lastAttendanceRecord['check_in_time' ] === null  &&
            $lastAttendanceRecord['check_out_time'] === null)) {

            return [
                'status'  => 'warning',
                'message' => 'You must check in before taking a break.'
            ];
        }

        if ($lastAttendanceRecord['check_in_time' ] !== null &&
            $lastAttendanceRecord['check_out_time'] === null) {

            $workScheduleId = $lastAttendanceRecord['work_schedule_snapshot_work_schedule_id'];

            $employeeBreakColumns = [
                'id',
                'break_schedule_snapshot_id'             ,
                'start_time'                             ,
                'end_time'                               ,

                'work_schedule_snapshot_work_schedule_id'
            ];

            $employeeBreakFilterCriteria = [
                [
                    'column'   => 'employee_break.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'   => '',
                    'operator' => '',
                    'value'    => ''
                ]
            ];

            $employeeBreakSortCriteria = [
                [
                    'column'    => 'employee_break.created_at',
                    'direction' => 'DESC'
                ],
                [
                    'column'    => 'employee_break.start_time',
                    'direction' => 'DESC'
                ]
            ];

            $lastBreakRecord = $this->employeeBreakRepository->fetchAllEmployeeBreaks(
                columns             : $employeeBreakColumns       ,
                filterCriteria      : $employeeBreakFilterCriteria,
                sortCriteria        : $employeeBreakSortCriteria  ,
                limit               : 1                           ,
                includeTotalRowCount: false
            );

            if ($lastBreakRecord === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.'
                ];
            }

            $lastBreakRecord =
                ! empty($lastBreakRecord['result_set'])
                    ? $lastBreakRecord['result_set'][0]
                    : [];
            $a = 1;
            


        }
    }

    private function getCurrentBreakSchedule(
        array  $breakSchedules           ,
        string $currentDateTime          ,
        string $workScheduleStartDateTime,
        string $workScheduleEndDateTime
    ): array {

        $currentDateTime = new DateTime($currentDateTime);

        $workScheduleStartDateTime = new DateTime($workScheduleStartDateTime   );
        $workScheduleEndDateTime   = new DateTime($workScheduleEndDateTime     );
        $workScheduleStartDate     = $workScheduleStartDateTime->format('Y-m-d');

        $nextBreakSchedule = [];

        foreach ($breakSchedules as $breakSchedule) {
            if ($breakSchedule['is_flexible']) {
                $breakStartTime = $breakSchedule['earliest_start_time'];
                $breakEndTime   = $breakSchedule['latest_end_time'    ];
            } else {
                $breakStartTime = $breakSchedule['start_time'];
                $breakEndTime   = $breakSchedule['end_time'  ];
            }

            $breakStartDateTime = new DateTime($workScheduleStartDate . ' ' . $breakStartTime);
            $breakEndDateTime   = new DateTime($workScheduleStartDate . ' ' . $breakEndTime  );

            if ($breakStartDateTime < $workScheduleStartDateTime) {
                $breakStartDateTime->modify('+1 day');
            }

            if ($breakEndDateTime < $workScheduleStartDateTime) {
                $breakEndDateTime->modify('+1 day');
            }

            if ($breakEndDateTime < $breakStartDateTime) {
                $breakEndDateTime->modify('+1 day');
            }

            $formattedBreakStartDateTime = $breakStartDateTime->format('Y-m-d H:i:s');
            $formattedBreakEndDateTime   = $breakEndDateTime  ->format('Y-m-d H:i:s');

            if ($breakSchedule['is_flexible']) {
                $breakSchedule['earliest_start_time'] = $formattedBreakStartDateTime;
                $breakSchedule['latest_end_time'    ] = $formattedBreakEndDateTime  ;
            } else {
                $breakSchedule['start_time'] = $formattedBreakStartDateTime;
                $breakSchedule['end_time'  ] = $formattedBreakEndDateTime  ;
            }

            if ($currentDateTime >= $breakStartDateTime && $currentDateTime < $breakEndDateTime) {
                return $breakSchedule;
            }

            if ($currentDateTime < $breakStartDateTime && empty($nextBreakSchedule)) {
                $nextBreakSchedule = $breakSchedule;
            }
        }

        return $nextBreakSchedule;
    }
}
