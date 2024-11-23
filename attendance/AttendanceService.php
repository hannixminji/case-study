<?php

require_once __DIR__ . '/AttendanceRepository.php'                    ;
require_once __DIR__ . '/../employees/EmployeeRepository.php'         ;
require_once __DIR__ . '/../leaves/LeaveRequestRepository.php'        ;
require_once __DIR__ . '/../work-schedules/WorkScheduleRepository.php';
require_once __DIR__ . '/../settings/SettingRepository.php'           ;

class AttendanceService
{
    private readonly AttendanceRepository   $attendanceRepository  ;
    private readonly EmployeeRepository     $employeeRepository    ;
    private readonly LeaveRequestRepository $leaveRequestRepository;
    private readonly WorkScheduleRepository $workScheduleRepository;
    private readonly SettingRepository      $settingRepository     ;

    public function __construct(
        AttendanceRepository   $attendanceRepository  ,
        EmployeeRepository     $employeeRepository    ,
        LeaveRequestRepository $leaveRequestRepository,
        WorkScheduleRepository $workScheduleRepository,
        SettingRepository      $settingRepository
    ) {
        $this->attendanceRepository   = $attendanceRepository  ;
        $this->employeeRepository     = $employeeRepository    ;
        $this->leaveRequestRepository = $leaveRequestRepository;
        $this->workScheduleRepository = $workScheduleRepository;
        $this->settingRepository      = $settingRepository     ;
    }

    public function handleRfidTap(string $rfidUid, string $currentTime, string $currentDate)
    {
        $employeeId = $this->employeeRepository->getEmployeeIdBy('employee.rfid_uid', $rfidUid);

        if ($employeeId === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        $isOnLeave = $this->leaveRequestRepository->isEmployeeOnLeave($employeeId);

        if ($isOnLeave === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.',
            ];
        }

        if ($isOnLeave) {
            return [
                'status'  => 'error',
                'message' => 'You are currently on leave. You cannot check in or check out.'
            ];
        }

        $workSchedules = $this->workScheduleRepository->getEmployeeWorkSchedules($employeeId, $currentDate, $currentDate);

        if ($workSchedules === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }

        if ($workSchedules === ActionResult::NO_WORK_SCHEDULE_FOUND) {
            return [
                'status'  => 'error',
                'message' => 'You do not have a work schedule for today.'
            ];
        }

        foreach ($workSchedules as $workSchedule) {
            if ($workSchedule['is_flexible'] === false) {
                $startTime = new DateTime($workSchedule['start_time']);
                $endTime   = new DateTime($workSchedule['end_time'  ]);

                if ($currentTime >= $startTime && $currentTime <= $endTime) {

                }
            }
        }
    }
}
