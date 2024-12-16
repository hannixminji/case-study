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
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $lastAttendanceRecord = $this->attendanceRepository->getLastAttendanceRecord($employeeId);

        if ($lastAttendanceRecord === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
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
                'status'  => 'error',
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

            $lastBreakRecord = $this->employeeBreakRepository->fetchEmployeeLastBreakRecord($lastAttendanceRecord['work_schedule_id'], $employeeId);

            if ($lastBreakRecord === ActionResult::FAILURE) {
                return [
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred. Please try again later.',
                ];
            }

            if ( ! empty($lastBreakRecord)) {
                $lastBreakRecord = $lastBreakRecord[0];
            }

            if ( empty($lastBreakRecord) ||
                ($lastBreakRecord['start_time'] !== null  &&
                 $lastBreakRecord['end_time'  ] !== null) ||

                ($lastBreakRecord['start_time'] === null &&
                 $lastBreakRecord['end_time'  ] === null)) {

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

                if ($breakSchedules === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred. Please try again later.',
                    ];
                }

                if (empty($breakSchedules)) {
                    return [
                        'status'  => 'error',
                        'message' => 'No breaks have been scheduled for this schedule.',
                    ];
                }

                $breakSchedules = $breakSchedules['result_set'];

                $currentBreakSchedule = $this->getCurrentBreakSchedule(
                    $breakSchedules,
                    $currentDateTime->format('Y-m-d H:i:s'),
                    $workScheduleStartDateTime->format('Y-m-d H:i:s'),
                    $workScheduleEndDateTime->format('Y-m-d H:i:s')
                );

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
                    $breakScheduleStartTime = new DateTime($currentBreakSchedule['start_time']);
                    $breakScheduleEndTime   = new DateTime($currentBreakSchedule['end_time'  ]);
                } else {
                    $breakScheduleStartTime = new DateTime($currentBreakSchedule['earliest_start_time']);
                    $breakScheduleEndTime   = new DateTime($currentBreakSchedule['latest_end_time'    ]);
                }

                if ($currentDateTime < $breakScheduleStartTime) {
                    $formattedStartTime = (new DateTime($breakScheduleStartTime->format('Y-m-d H:i:s')))->format('g:i A');
                    $formattedEndTime = (new DateTime($breakScheduleEndTime->format('Y-m-d H:i:s')))->format('g:i A');

                    return [
                        'status'  => 'warning',
                        'message' => "The break time has not started yet. Your scheduled break is from $formattedStartTime to $formattedEndTime.",
                    ];
                }

                $employeeBreak = new EmployeeBreak(
                    id                    : null                       ,
                    breakScheduleId       : $currentBreakSchedule['id'],
                    startTime             : $currentDateTime->format('Y-m-d H:i:s'),
                    endTime               : null                       ,
                    breakDurationInMinutes: 0,
                    createdAt             : $currentDateTime->format('Y-m-d H:i:s')
                );

                $result = $this->employeeBreakRepository->breakIn($employeeBreak);

                if ($result === ActionResult::FAILURE) {
                    return [
                        'status'  => 'error6',
                        'message' => 'An unexpected error occurred. Please try again later.',
                    ];
                }

            } elseif ($lastBreakRecord['start_time'] !== null &&
                      $lastBreakRecord['end_time'  ] === null) {

                $startTime = new DateTime($lastBreakRecord['start_time']);
                $endTime = clone $currentDateTime;

                $interval = $startTime->diff($endTime);
                $breakDurationInMinutes = $interval->h * 60 + $interval->i;

                $employeeBreak = new EmployeeBreak(
                    id                    : $lastBreakRecord['id'                 ],
                    breakScheduleId       : $lastBreakRecord['break_schedule_id'  ],
                    startTime             : $lastBreakRecord['start_time'         ],
                    endTime               : $currentDateTime->format('Y-m-d H:i:s'),
                    breakDurationInMinutes: $breakDurationInMinutes                ,
                    createdAt             : $lastBreakRecord['created_at']
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

    private function getCurrentBreakSchedule(array $breakSchedules, string $currentTime, string $workScheduleStartTime, string $workScheduleEndTime): array
    {
        $currentBreakSchedule = [];
        $nextBreakSchedule    = [];

        $workScheduleStartTime = new DateTime($workScheduleStartTime   );
        $workScheduleEndTime   = new DateTime($workScheduleEndTime     );
        $workScheduleDate      = $workScheduleStartTime->format('Y-m-d');

        $currentTime = new DateTime($currentTime);

        foreach ($breakSchedules as $breakSchedule) {
            if ( ! $breakSchedule['is_flexible']) {
                $startTime = new DateTime($breakSchedule['start_time']);
                $endTime   = clone $startTime;

                $breakTypeDurationInMinutes = $breakSchedule['break_type_duration_in_minutes'];
                $endTime->modify("+{$breakTypeDurationInMinutes} minutes");

                $startTime = new DateTime($workScheduleDate . ' ' . $startTime->format('H:i:s'));
                $endTime   = new DateTime($workScheduleDate . ' ' . $endTime  ->format('H:i:s'));

                if ($startTime < $workScheduleStartTime) {
                    $startTime->modify('+1 day');
                }

                if ($endTime < $workScheduleStartTime) {
                    $endTime->modify('+1 day');
                }

                if ($endTime < $startTime) {
                    $endTime->modify('+1 day');
                }

                $breakSchedule['start_time'] = $startTime->format('Y-m-d H:i:s');
                $breakSchedule['end_time'  ] = $endTime  ->format('Y-m-d H:i:s');

                if ($currentTime >= $startTime && $currentTime <= $endTime) {
                    $currentBreakSchedule = $breakSchedule;
                    break;
                }

                if (empty($nextBreakSchedule) && $currentTime < $startTime) {
                    $nextBreakSchedule = $breakSchedule;
                }

            } else {
                $earliestStartTime = new DateTime($breakSchedule['earliest_start_time']);
                $latestEndTime     = new DateTime($breakSchedule['latest_end_time'    ]);

                $earliestStartTime = new DateTime($workScheduleDate . ' ' . $earliestStartTime->format('H:i:s'));
                $latestEndTime     = new DateTime($workScheduleDate . ' ' . $latestEndTime    ->format('H:i:s'));

                if ($earliestStartTime < $workScheduleStartTime) {
                    $earliestStartTime->modify('+1 day');
                }

                if ($latestEndTime < $workScheduleStartTime) {
                    $latestEndTime->modify('+1 day');
                }

                if ($latestEndTime < $earliestStartTime) {
                    $latestEndTime->modify('+1 day');
                }

                $breakSchedule['earliest_start_time'] = $earliestStartTime->format('Y-m-d H:i:s');
                $breakSchedule['latest_end_time'    ] = $latestEndTime    ->format('Y-m-d H:i:s');

                if ($currentTime >= $earliestStartTime && $currentTime <= $latestEndTime) {
                    $currentBreakSchedule = $breakSchedule;
                    break;
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

    public function updateEmployeeBreak(EmployeeBreak $employeeBreak, bool $isHashedId = false): ActionResult
    {
        return $this->employeeBreakRepository->updateEmployeeBreak($employeeBreak, $isHashedId);
    }
}
