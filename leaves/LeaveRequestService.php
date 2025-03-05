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

        return $this->leaveRequestRepository->fetchAllLeaveRequests(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateLeaveRequest(LeaveRequest $leaveRequest): ActionResult
    {
        return $this->leaveRequestRepository->updateLeaveRequest($leaveRequest);
    }

    public function updateLeaveRequestStatus(int|string $leaveRequestId, string $status): ActionResult
    {
        return $this->leaveRequestRepository->updateLeaveRequestStatus($leaveRequestId, $status);
    }

    public function updateLeaveRequestStatuses(string $currentDate): ActionResult
    {
        return $this->leaveRequestRepository->updateLeaveRequestStatuses($currentDate);
    }

    public function isEmployeeOnLeave(int|string $employeeId): array|null|ActionResult
    {
        return $this->leaveRequestRepository->isEmployeeOnLeave($employeeId);
    }

    public function getLeaveDatesForPeriod(int|string $employeeId, string $startDate, string $endDate): array|null|ActionResult
    {
        return $this->leaveRequestRepository->getLeaveDatesForPeriod($employeeId, $startDate, $endDate);
    }

    public function deleteLeaveRequest(int|string $leaveRequestId): ActionResult
    {
        return $this->leaveRequestRepository->deleteLeaveRequest($leaveRequestId);
    }

    public function createLeaveRequestAttachment(LeaveRequestAttachment $leaveRequestAttachment): ActionResult
    {
        return $this->leaveRequestAttachmentRepository->createLeaveRequestAttachment($leaveRequestAttachment);
    }

    public function fetchAllLeaveRequestAttachments(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->leaveRequestAttachmentRepository->fetchAllLeaveRequestAttachments(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function deleteLeaveRequestAttachment(int|string $leaveRequestAttachmentId): ActionResult
    {
        return $this->leaveRequestAttachmentRepository->deleteLeaveRequestAttachment($leaveRequestAttachmentId);
    }
}
