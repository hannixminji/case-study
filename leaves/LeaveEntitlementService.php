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
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->leaveEntitlementRepository->fetchAllLeaveEntitlements($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateLeaveEntitlementBalance(LeaveEntitlement $leaveEntitlement, bool $isHashedId = false): ActionResult
    {
        return $this->leaveEntitlementRepository->updateLeaveEntitlementBalance($leaveEntitlement, $isHashedId);
    }

    public function resetEmployeeAllLeaveBalances(int $employeeId, bool $isHashedId = false): ActionResult
    {
        return $this->resetEmployeeAllLeaveBalances($employeeId, $isHashedId);
    }

    public function deleteLeaveEntitlement(int $leaveEntitlementId, bool $isHashedId = false): ActionResult
    {
        return $this->leaveEntitlementRepository->deleteLeaveEntitlement($leaveEntitlementId, $isHashedId);
    }
}
