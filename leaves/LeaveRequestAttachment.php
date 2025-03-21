<?php

class LeaveRequestAttachment
{
    public function __construct(
        private readonly null|int|string $id            ,
        private readonly int|string      $leaveRequestId,
        private readonly string          $filePath
    ) {
    }

    public function getId(): null|int|string
    {
        return $this->id;
    }

    public function getLeaveRequestId(): int|string
    {
        return $this->leaveRequestId;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}
