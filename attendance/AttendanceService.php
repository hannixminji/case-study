<?php

require_once __DIR__ . '/AttendanceRepository.php'            ;
require_once __DIR__ . '/../employees/EmployeeRepository.php' ;
require_once __DIR__ . '/../leaves/LeaveRequestRepository.php';

class AttendanceService
{
    private readonly AttendanceRepository   $attendanceRepository  ;
    private readonly EmployeeRepository     $employeeRepository    ;
    private readonly LeaveRequestRepository $leaveRequestRepository;

    public function __construct(
        AttendanceRepository   $attendanceRepository  ,
        EmployeeRepository     $employeeRepository    ,
        LeaveRequestRepository $leaveRequestRepository
    ) {
        $this->attendanceRepository   = $attendanceRepository  ;
        $this->employeeRepository     = $employeeRepository    ;
        $this->leaveRequestRepository = $leaveRequestRepository;
    }

    public function handleRfidTap(string $rfidUid, string $currentTime)
    {
        $employeeId = $this->employeeRepository->getEmployeeIdBy('employee.rfid_uid', $rfidUid);

        $isOnLeave = $this->leaveRequestRepository->isEmployeeOnLeave($employeeId);

        if ($isOnLeave === ActionResult::FAILURE) {
            return [
                'status' => 'error',
                'message' => 'An unexpected error occurred. Please try again later.',
            ];
        }

        if ($isOnLeave) {
            return [
                'status'  => 'error',
                'message' => 'You are currently on leave. You cannot check in or check out.'
            ];
        }
    }
}
