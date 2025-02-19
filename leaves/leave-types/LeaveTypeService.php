<?php

require_once __DIR__ . '/LeaveTypeRepository.php';

class LeaveTypeService
{
    private readonly LeaveTypeRepository $leaveTypeRepository;

    public function __construct(LeaveTypeRepository $leaveTypeRepository)
    {
        $this->leaveTypeRepository = $leaveTypeRepository;
    }

    public function createLeaveType(LeaveType $leaveType): ActionResult
    {
        return $this->leaveTypeRepository->createLeaveType($leaveType);
    }

    public function fetchAllLeaveTypes(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->leaveTypeRepository->fetchAllLeaveTypes(
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
        return $this->leaveTypeRepository->updateLeaveType($leaveType);
    }

    public function deleteLeaveType(int|string $leaveTypeId): ActionResult
    {
        return $this->leaveTypeRepository->deleteLeaveType($leaveTypeId);
    }
}
