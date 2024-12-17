<?php

require_once __DIR__ . '/LeaveEntitlementDao.php';

class LeaveEntitlementRepository
{
    private readonly LeaveEntitlementDao $leaveEntitlementDao;

    public function __construct(LeaveEntitlementDao $leaveEntitlementDao)
    {
        $this->leaveEntitlementDao = $leaveEntitlementDao;
    }

    public function createLeaveEntitlement(LeaveEntitlement $leaveEntitlement): ActionResult
    {
        return $this->leaveEntitlementDao->create($leaveEntitlement);
    }

    public function fetchAllLeaveEntitlements(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->leaveEntitlementDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateLeaveEntitlementBalance(LeaveEntitlement $leaveEntitlement, bool $isHashedId = false): ActionResult
    {
        return $this->leaveEntitlementDao->updateBalance($leaveEntitlement, $isHashedId);
    }

    public function resetEmployeeAllLeaveBalances(int|string $employeeId, bool $isHashedId = false): ActionResult
    {
        return $this->resetEmployeeAllLeaveBalances($employeeId, $isHashedId);
    }

    public function deleteLeaveEntitlement(int|string $leaveEntitlementId, bool $isHashedId = false): ActionResult
    {
        return $this->leaveEntitlementDao->delete($leaveEntitlementId, $isHashedId);
    }
}
