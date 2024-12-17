<?php

require_once __DIR__ . '/LeaveTypeDao.php';

class LeaveTypeRepository
{
    private LeaveTypeDao $leaveTypeDao;

    public function __construct(LeaveTypeDao $leaveTypeDao)
    {
        $this->leaveTypeDao = $leaveTypeDao;
    }

    public function createLeaveType(LeaveType $leaveType): ActionResult
    {
        return $this->leaveTypeDao->create($leaveType);
    }

    public function fetchAllLeaveTypes(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->leaveTypeDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateLeaveType(LeaveType $leaveType, bool $isHashedId = false): ActionResult
    {
        return $this->leaveTypeDao->update($leaveType, $isHashedId);
    }

    public function deleteLeaveType(int|string $leaveTypeId, bool $isHashedId = false): ActionResult
    {
        return $this->leaveTypeDao->delete($leaveTypeId, $isHashedId);
    }
}
