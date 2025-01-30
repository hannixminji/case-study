<?php

class Attendance
{
    public function __construct(
        private readonly   int|string|null $id                           = null,
        private readonly   int|string      $workScheduleId                     ,
        private readonly   string          $date                               ,
        private readonly   string          $checkInTime                        ,
        private readonly ? string          $checkOutTime                 = null,
        private readonly ? float           $totalBreakDurationInMinutes  = null,
        private readonly ? float           $totalHoursWorked             = null,
        private readonly ? int             $lateCheckIn                  = null,
        private readonly ? int             $earlyCheckOut                = null,
        private readonly ? float           $overtimeHours                = null,
        private readonly   int             $gracePeriod                        ,
        private readonly   int             $minutesCanCheckInBeforeShift       ,
        private readonly ? bool            $isOvertimeApproved           = null,
        private readonly   string          $attendanceStatus                   ,
        private readonly ? string          $remarks                      = null
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getWorkScheduleId(): int|string
    {
        return $this->workScheduleId;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getCheckInTime(): string
    {
        return $this->checkInTime;
    }

    public function getCheckOutTime(): ?string
    {
        return $this->checkOutTime;
    }

    public function getTotalBreakDurationInMinutes(): ?float
    {
        return $this->totalBreakDurationInMinutes;
    }

    public function getTotalHoursWorked(): ?float
    {
        return $this->totalHoursWorked;
    }

    public function getLateCheckIn(): ?int
    {
        return $this->lateCheckIn;
    }

    public function getEarlyCheckOut(): ?int
    {
        return $this->earlyCheckOut;
    }

    public function getOvertimeHours(): ?float
    {
        return $this->overtimeHours;
    }

    public function getGracePeriod(): int
    {
        return $this->gracePeriod;
    }

    public function getMinutesCanCheckInBeforeShift(): int
    {
        return $this->minutesCanCheckInBeforeShift;
    }

    public function isOvertimeApproved(): ?bool
    {
        return $this->isOvertimeApproved;
    }

    public function getAttendanceStatus(): string
    {
        return $this->attendanceStatus;
    }

    public function getRemarks(): ?string
    {
        return $this->remarks;
    }
}
