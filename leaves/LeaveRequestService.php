<?php

require_once __DIR__ . '/LeaveRequestRepository.php'          ;
require_once __DIR__ . '/LeaveRequestAttachmentRepository.php';

class LeaveRequestService
{
    private readonly LeaveRequestRepository           $leaveRequestRepository          ;
    private readonly LeaveRequestAttachmentRepository $leaveRequestAttachmentRepository;

    public function __construct(
        LeaveRequestRepository           $leaveRequestRepository          ,
        LeaveRequestAttachmentRepository $leaveRequestAttachmentRepository
    ) {
        $this->leaveRequestRepository           = $leaveRequestRepository          ;
        $this->leaveRequestAttachmentRepository = $leaveRequestAttachmentRepository;
    }

    public function createLeaveRequest(LeaveRequest $leaveRequest): ActionResult
    {
        return $this->leaveRequestRepository->createLeaveRequest($leaveRequest);
    }

    public function fetchAllLeaveRequests(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        $this->leaveRequestRepository->updateLeaveRequestStatuses((new DateTime())->format('Y-m-d'));

        return $this->leaveRequestRepository->fetchAllLeaveRequests(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateLeaveRequest(LeaveRequest $leaveRequest, bool $isHashedId = false): ActionResult
    {
        return $this->leaveRequestRepository->updateLeaveRequest($leaveRequest, $isHashedId);
    }

    public function updateLeaveRequestStatus(int|string $leaveRequestId, string $status, bool $isHashedId = false): ActionResult
    {
        return $this->leaveRequestRepository->updateLeaveRequestStatus($leaveRequestId, $status, $isHashedId);
    }

    public function isEmployeeOnLeave(int|string $employeeId, bool $isHashedId = false): array|null|ActionResult
    {
        return $this->leaveRequestRepository->isEmployeeOnLeave($employeeId, $isHashedId);
    }

    public function getLeaveDatesForPeriod(int|string $employeeId, string $startDate, string $endDate): array|null|ActionResult
    {
        return $this->leaveRequestRepository->getLeaveDatesForPeriod($employeeId, $startDate, $endDate);
    }

    public function deleteLeaveRequest(int|string $leaveRequestId, bool $isHashedId = false): ActionResult
    {
        return $this->leaveRequestRepository->deleteLeaveRequest($leaveRequestId, $isHashedId);
    }

    public function createLeaveRequestAttachment(LeaveRequestAttachment $leaveRequestAttachment): ActionResult
    {
        return $this->leaveRequestAttachmentRepository->createLeaveRequestAttachment($leaveRequestAttachment);
    }

    public function fetchAllLeaveRequestAttachments(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): array|ActionResult {
        return $this->leaveRequestAttachmentRepository->fetchAllLeaveRequestAttachments($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function deleteLeaveRequestAttachment(int|string $leaveRequestAttachmentId, bool $isHashedId = false): ActionResult
    {
        return $this->leaveRequestAttachmentRepository->deleteLeaveRequestAttachment($leaveRequestAttachmentId, $isHashedId);
    }
}
