<?php

require_once __DIR__ . '/EmployeeBreak.php'                           ;

require_once __DIR__ . '/EmployeeBreakRepository.php'                 ;
require_once __DIR__ . '/../employees/EmployeeRepository.php'         ;
require_once __DIR__ . '/../attendance/AttendanceRepository.php'      ;
require_once __DIR__ . '/BreakScheduleRepository.php'                 ;

class EmployeeBreakService
{
    private readonly EmployeeBreakRepository $employeeBreakRepository;
    private readonly EmployeeRepository      $employeeRepository     ;
    private readonly AttendanceRepository    $attendanceRepository   ;
    private readonly BreakScheduleRepository $breakScheduleRepository;

    public function __construct(
        EmployeeBreakRepository $employeeBreakRepository,
        EmployeeRepository      $employeeRepository     ,
        AttendanceRepository    $AttendanceRepository   ,
        BreakScheduleRepository $breakScheduleRepository
    ) {
        $this->employeeBreakRepository = $employeeBreakRepository;
        $this->employeeRepository      = $employeeRepository     ;
        $this->attendanceRepository    = $AttendanceRepository   ;
        $this->breakScheduleRepository = $breakScheduleRepository;
    }

    public function handleRfidTap(string $rfidUid, string $currentDateTime): ActionResult|array
    {
        $employeeId = $this->employeeRepository->getEmployeeIdBy('employee.rfid_uid', $rfidUid);

        if ($employeeId === ActionResult::FAILURE) {
            return [
                'status'  => 'error1',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $lastAttendanceRecord = $this->attendanceRepository->getLastAttendanceRecord($employeeId);

        if ($lastAttendanceRecord === ActionResult::FAILURE) {
            return [
                'status'  => 'error2',
                'message' => 'An unexpected error occurred. Please try again later.',
            ];
        }

        if ( ! empty($lastAttendanceRecord)) {
            $lastAttendanceRecord = $lastAttendanceRecord[0];
        }

        if ( empty($lastAttendanceRecord) ||
            ($lastAttendanceRecord['check_in_time' ] !== null  &&
             $lastAttendanceRecord['check_out_time'] !== null)) {
            return [
                'status'  => 'error3',
                'message' => 'You cannot take a break without checking in first.',
            ];
        }

        $isBreakIn = false;

        if ($lastAttendanceRecord['check_in_time' ] !== null &&
            $lastAttendanceRecord['check_out_time'] === null) {

            $currentDateTime = new DateTime($currentDateTime );
            $currentTime     = $currentDateTime->format('H:i:s');

            $workScheduleStartTime = $lastAttendanceRecord['work_schedule_start_time'];
            $workScheduleEndTime   = $lastAttendanceRecord['work_schedule_end_time'  ];

            $workScheduleStartDateTime = new DateTime($lastAttendanceRecord['date'] . ' ' . (new DateTime($workScheduleStartTime))->format('H:i:s'));
            $workScheduleEndDateTime   = new DateTime($lastAttendanceRecord['date'] . ' ' . (new DateTime($workScheduleEndTime  ))->format('H:i:s'));

            if ($workScheduleEndDateTime < $workScheduleStartDateTime) {
                $workScheduleEndDateTime->modify('+1 day');
            }

            if ($currentDateTime > $workScheduleEndDateTime) {
                return [
                    'status'  => 'error',
                    'message' => 'You may have forgotten to check out.',
                ];
            }

            $lastBreakRecord = $this->employeeBreakRepository->fetchEmployeeLastBreakRecord($employeeId);

            if ($lastBreakRecord === ActionResult::FAILURE) {
                return [
                    'status'  => 'error4',
                    'message' => 'An unexpected error occurred. Please try again later.',
                ];
            }

            if ( ! empty($lastBreakRecord)) {
                $lastBreakRecord = $lastBreakRecord[0];
            }

            if ( empty($lastBreakRecord) ||
                ($lastBreakRecord['start_time'] !== null  &&
                 $lastBreakRecord['end_time'  ] !== null)) {

                $isBreakIn = true;

                $workScheduleId = (int) $lastAttendanceRecord['work_schedule_id'];

                $columns = [
                    'id'                               ,
                    'start_time'                       ,
                    'break_type_duration_in_minutes'   ,
                    'is_flexible'                      ,
                    'earliest_start_time'              ,
                    'latest_end_time'                  ,
                    'is_require_break_in_and_break_out'
                ];

                $filterCriteria = [
                    [
                        'column'   => 'break_schedule.deleted_at',
                        'operator' => 'IS NULL'
                    ],
                    [
                        'column'   => 'break_schedule.work_schedule_id',
                        'operator' => '=',
                        'value'    => $workScheduleId
                    ]
                ];

                $breakSchedules = $this->breakScheduleRepository->fetchAllBreakSchedules($columns, $filterCriteria);
                $breakSchedules = $breakSchedules['result_set'];

                if ($breakSchedules === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error5',
                        'message' => 'An unexpected error occurred. Please try again later.',
                    ];
                }

                if (empty($breakSchedules)) {
                    return [
                        'status'  => 'error',
                        'message' => 'No breaks have been scheduled for this schedule.',
                    ];
                }

                $currentBreakSchedule = $this->getCurrentBreakSchedule($breakSchedules, $currentTime);

                if (empty($currentBreakSchedule)) {
                    return [
                        'status'  => 'error',
                        'message' => 'Break already ended.',
                    ];
                }

                if ( ! $currentBreakSchedule['is_require_break_in_and_break_out']) {
                    return [
                        'status'  => 'warning',
                        'message' => 'Break-in and break-out times are not required for this schedule.',
                    ];
                }

                $breakScheduleStartTime = null;
                $breakScheduleEndTime   = null;

                if ( ! $currentBreakSchedule['is_flexible']) {
                    $breakScheduleStartTime = (new DateTime($currentBreakSchedule['start_time']))->format('H:i:s');
                    $breakScheduleEndTime   = (new DateTime($currentBreakSchedule['end_time'  ]))->format('H:i:s');
                } else {
                    $breakScheduleStartTime = (new DateTime($currentBreakSchedule['earliest_start_time']))->format('H:i:s');
                    $breakScheduleEndTime   = (new DateTime($currentBreakSchedule['latest_end_time'    ]))->format('H:i:s');
                }

                if ($currentTime < $breakScheduleStartTime) {
                    $formattedStartTime = (new DateTime($breakScheduleStartTime))->format('g:i A');
                    $formattedEndTime = (new DateTime($breakScheduleEndTime))->format('g:i A');

                    return [
                        'status'  => 'error',
                        'message' => "The break time has not started yet. Your scheduled break is from $formattedStartTime to $formattedEndTime.",
                    ];
                }

                $employeeBreak = new EmployeeBreak(
                    id                    : null                       ,
                    breakScheduleId       : $currentBreakSchedule['id'],
                    startTime             : $currentDateTime->format('Y-m-d H:i:s'),
                    endTime               : null                       ,
                    breakDurationInMinutes: 0
                );

                $result = $this->employeeBreakRepository->breakIn($employeeBreak);

                if ($result === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error6',
                        'message' => 'An unexpected error occurred. Please try again later.',
                    ];
                }
            } else {
                $startTime = new DateTime($lastBreakRecord['start_time']);
                $endTime = new DateTime($currentTime);

                $interval = $startTime->diff($endTime);
                $breakDurationInMinutes = $interval->h * 60 + $interval->i;

                $employeeBreak = new EmployeeBreak(
                    id                    : $lastBreakRecord['id'               ],
                    breakScheduleId       : $lastBreakRecord['break_schedule_id'],
                    startTime             : $lastBreakRecord['start_time'       ],
                    endTime               : $currentDateTime->format('Y-m-d H:i:s'),
                    breakDurationInMinutes: $breakDurationInMinutes
                );

                $result = $this->employeeBreakRepository->breakOut($employeeBreak);

                if ($result === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error7',
                        'message' => 'An unexpected error occurred. Please try again later.',
                    ];
                }
            }
        }

        if ($isBreakIn) {
            return [
                'status' => 'success',
                'message' => 'Break-in recorded successfully.'
            ];
        } else {
            return [
                'status' => 'success',
                'message' => 'Break-out recorded successfully.'
            ];
        }
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
