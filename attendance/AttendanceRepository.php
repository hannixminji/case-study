<?php

require_once __DIR__ . '/AttendanceDao.php';

class AttendanceRepository
{
    private readonly AttendanceDao $attendanceDao;

    public function __construct(AttendanceDao $attendanceDao)
    {
        $this->attendanceDao = $attendanceDao;
    }

    public function createAttendance(Attendance $attendance): ActionResult
    {
        return $this->attendanceDao->create($attendance);
    }

    public function checkIn(Attendance $attendance): ActionResult
    {
        return $this->attendanceDao->checkIn($attendance);
    }

    public function checkOut(Attendance $attendance): ActionResult
    {
        return $this->attendanceDao->checkOut($attendance);
    }

    public function fetchAllAttendance(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->attendanceDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function fetchEmployeeLastAttendanceRecord(int $employeeId, string $currentDateTime): array|ActionResult
    {
        return $this->attendanceDao->fetchEmployeeLastAttendanceRecord($employeeId, $currentDateTime);
    }

    public function updateAttendance(Attendance $attendance, bool $isHashedId = false): ActionResult
    {
        return $this->attendanceDao->update($attendance, $isHashedId);
    }

    public function approveOvertime(int|string $attendanceId, bool $isHashedId = false): ActionResult
    {
        return $this->attendanceDao->approveOvertime($attendanceId, $isHashedId);
    }

    public function markAsProcessedForNextPayroll(int $attendanceId): ActionResult
    {
        return $this->attendanceDao->markAsProcessedForNextPayroll($attendanceId);
    }

    public function deleteAttendance(int|string $attendanceId, bool $isHashedId = false): ActionResult
    {
        return $this->attendanceDao->delete($attendanceId, $isHashedId);
    }
}
