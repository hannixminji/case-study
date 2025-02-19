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
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->leaveEntitlementDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateLeaveEntitlementBalance(LeaveEntitlement $leaveEntitlement): ActionResult
    {
        return $this->leaveEntitlementDao->updateBalance($leaveEntitlement);
    }

    public function resetEmployeeAllLeaveBalances(int|string $employeeId): ActionResult
    {
        return $this->leaveEntitlementDao->resetEmployeeAllLeaveBalances($employeeId);
    }

    public function deleteLeaveEntitlement(int|string $leaveEntitlementId): ActionResult
    {
        return $this->leaveEntitlementDao->delete($leaveEntitlementId);
    }
}
