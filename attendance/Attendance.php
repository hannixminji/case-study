<?php

class Attendance
{
    public function __construct(
        private readonly ?int    $id                ,
        private readonly int     $employeeId        ,
        private readonly string  $date              ,
        private readonly ?string $shiftType         ,
        private readonly ?string $checkInTime       ,
        private readonly ?string $checkOutTime      ,
        private readonly ?string $breakStartTime    ,
        private readonly ?string $breakEndTime      ,
        private readonly bool    $isOvertimeApproved,
        private readonly string  $attendanceStatus  ,
        private readonly ?string $remarks
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getShiftType(): ?string
    {
        return $this->shiftType;
    }

    public function getCheckInTime(): ?string
    {
        return $this->checkInTime;
    }

    public function getCheckOutTime(): ?string
    {
        return $this->checkOutTime;
    }

    public function getBreakStartTime(): ?string
    {
        return $this->breakStartTime;
    }

    public function getBreakEndTime(): ?string
    {
        return $this->breakEndTime;
    }

    public function isOvertimeApproved(): bool
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
