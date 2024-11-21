<?php

require_once __DIR__ . '/AttendanceDao.php'                 ;

require_once __DIR__ . '/../includes/Helper.php'            ;
require_once __DIR__ . '/../includes/enums/ActionResult.php';
require_once __DIR__ . '/../includes/enums/ErrorCode.php'   ;

class AttendanceRepository
{
    private readonly AttendanceDao $attendanceDao;

    public function __construct(AttendanceDao $attendanceDao)
    {
        $this->attendanceDao = $attendanceDao;
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
        ?array $columns        = null,
        ?array $filterCriteria = null,
        ?array $sortCriteria   = null,
        ?int   $limit          = null,
        ?int   $offset         = null
    ): ActionResult|array {
        return $this->attendanceDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateAttendance(Attendance $attendance): ActionResult
    {
        return $this->attendanceDao->update($attendance);
    }

    public function approveOvertime(int $attendanceId): ActionResult
    {
        return $this->attendanceDao->approveOvertime($attendanceId);
    }
}
