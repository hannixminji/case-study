<?php

class LeaveRequestAttachment
{
    public function __construct(
        private readonly int|string|null $id             = null,
        private readonly int|string      $leaveRequestId       ,
        private readonly string          $filePath
    ) {
    }

    public function getId(): int|string|null
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
