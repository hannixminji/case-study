<?php

class Attendance
{
    public function __construct(
        private readonly   int|string|null $id                         ,
        private readonly   int|string      $workScheduleHistoryId      ,
        private readonly   string          $date                       ,
        private readonly ? string          $checkInTime                ,
        private readonly ? string          $checkOutTime               ,
        private readonly ? float           $totalBreakDurationInMinutes,
        private readonly ? float           $totalHoursWorked           ,
        private readonly ? int             $lateCheckIn                ,
        private readonly ? int             $earlyCheckOut              ,
        private readonly ? float           $overtimeHours              ,
        private readonly ? bool            $isOvertimeApproved         ,
        private readonly   string          $attendanceStatus           ,
        private readonly ? string          $remarks
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getWorkScheduleHistoryId(): int|string
    {
        return $this->workScheduleHistoryId;
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
