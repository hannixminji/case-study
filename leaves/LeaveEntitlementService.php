<?php

require_once __DIR__ . '/LeaveEntitlementRepository.php';

class LeaveEntitlementService
{
    private readonly LeaveEntitlementRepository $leaveEntitlementRepository;

    public function __construct(LeaveEntitlementRepository $leaveEntitlementRepository)
    {
        $this->leaveEntitlementRepository = $leaveEntitlementRepository;
    }

    public function createLeaveEntitlement(LeaveEntitlement $leaveEntitlement): ActionResult
    {
        return $this->leaveEntitlementRepository->createLeaveEntitlement($leaveEntitlement);
    }

    public function getAllLeaveEntitlements(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->leaveEntitlementRepository->fetchAllLeaveEntitlements(
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
        return $this->leaveEntitlementRepository->updateLeaveEntitlementBalance($leaveEntitlement);
    }

    public function resetEmployeeAllLeaveBalances(int|string $employeeId): ActionResult
    {
        return $this->resetEmployeeAllLeaveBalances($employeeId);
    }

    public function deleteLeaveEntitlement(int|string $leaveEntitlementId): ActionResult
    {
        return $this->leaveEntitlementRepository->deleteLeaveEntitlement($leaveEntitlementId);
    }
}
