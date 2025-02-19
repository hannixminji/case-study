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
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->leaveTypeDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateLeaveType(LeaveType $leaveType): ActionResult
    {
        return $this->leaveTypeDao->update($leaveType);
    }

    public function deleteLeaveType(int|string $leaveTypeId): ActionResult
    {
        return $this->leaveTypeDao->delete($leaveTypeId);
    }
}
