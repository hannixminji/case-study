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
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        $this->leaveRequestRepository->updateLeaveRequestStatuses();

        return $this->leaveRequestRepository->fetchAllLeaveRequests($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateLeaveRequest(LeaveRequest $leaveRequest, bool $isHashedId = false): ActionResult
    {
        return $this->leaveRequestRepository->updateLeaveRequest($leaveRequest, $isHashedId);
    }

    public function updateLeaveRequestStatus(int|string $leaveRequestId, string $status, bool $isHashedId = false): ActionResult
    {
        return $this->leaveRequestRepository->updateLeaveRequestStatus($leaveRequestId, $status, $isHashedId);
    }

    public function isEmployeeOnLeave(int|string $employeeId, bool $isHashedId = false): ActionResult|bool
    {
        return $this->leaveRequestRepository->isEmployeeOnLeave($employeeId, $isHashedId);
    }

    public function getLeaveDatesForPeriod(int|string $employeeId, string $startDate, string $endDate): ActionResult|array
    {
        return $this->leaveRequestRepository->getLeaveDatesForPeriod($employeeId, $startDate, $endDate);
    }

    public function deleteLeaveRequest(int|string $leaveRequestId, bool $isHashedId = false): ActionResult
    {
        return $this->leaveRequestRepository->deleteLeaveRequest($leaveRequestId, $isHashedId);
    }
}
