<?php

require_once __DIR__ . '/LeaveRequestRepository.php';

class LeaveRequestService
{
    private readonly LeaveRequestRepository $leaveRequestRepository;

    public function __construct(LeaveRequestRepository $leaveRequestRepository)
    {
        $this->leaveRequestRepository = $leaveRequestRepository;
    }

    public function createLeaveRequest(LeaveRequest $leaveRequest): ActionResult
    {
        return $this->leaveRequestRepository->createLeaveRequest($leaveRequest);
    }

    public function getAllLeaveRequests(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->leaveRequestRepository->fetchAllLeaveRequests($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateLeaveRequest(LeaveRequest $leaveRequest): ActionResult
    {
        return $this->leaveRequestRepository->updateLeaveRequest($leaveRequest);
    }

    public function updateLeaveRequestStatus(int $leaveRequestId, string $status): ActionResult
    {
        return $this->leaveRequestRepository->updateLeaveRequestStatus($leaveRequestId, $status);
    }

    public function isEmployeeOnLeave(int $employeeId): ActionResult|bool
    {
        return $this->leaveRequestRepository->isEmployeeOnLeave($employeeId);
    }

    public function getLeaveDatesForPeriod(int $employeeId, string $startDate, string $endDate): ActionResult|array
    {
        return $this->leaveRequestRepository->getLeaveDatesForPeriod($employeeId, $startDate, $endDate);
    }

    public function deleteLeaveRequest(int $leaveRequestId): ActionResult
    {
        return $this->leaveRequestRepository->deleteLeaveRequest($leaveRequestId);
    }
}
