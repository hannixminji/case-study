<?php

require_once __DIR__ . '/LeaveRequestDao.php';

class LeaveRequestRepository
{
    private readonly LeaveRequestDao $leaveRequestDao;

    public function __construct(LeaveRequestDao $leaveRequestDao)
    {
        $this->leaveRequestDao = $leaveRequestDao;
    }

    public function createLeaveRequest(LeaveRequest $leaveRequest): ActionResult
    {
        return $this->leaveRequestDao->create($leaveRequest);
    }

    public function fetchAllLeaveRequests(
        ?array $columns        = null,
        ?array $filterCriteria = null,
        ?array $sortCriteria   = null,
        ?int   $limit          = null,
        ?int   $offset         = null
    ): ActionResult|array {
        return $this->leaveRequestDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateLeaveRequest(LeaveRequest $leaveRequest): ActionResult
    {
        return $this->leaveRequestDao->update($leaveRequest);
    }

    public function updateLeaveRequestStatus(int $leaveRequestId, string $status): ActionResult
    {
        return $this->leaveRequestDao->updateStatus($leaveRequestId, $status);
    }

    public function isEmployeeOnLeave(int $employeeId): ActionResult|bool
    {
        return $this->leaveRequestDao->isEmployeeOnLeave($employeeId);
    }

    public function deleteLeaveRequest(int $leaveRequestId): ActionResult
    {
        return $this->leaveRequestDao->delete($leaveRequestId);
    }
}
