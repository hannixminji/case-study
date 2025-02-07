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
    ): ActionResult|array {

        return $this->leaveTypeRepository->fetchAllLeaveTypes(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateLeaveType(LeaveType $leaveType, bool $isHashedId = false): ActionResult
    {
        return $this->leaveTypeRepository->updateLeaveType($leaveType, $isHashedId);
    }

    public function deleteLeaveType(int|string $leaveTypeId, bool $isHashedId = false): ActionResult
    {
        return $this->leaveTypeRepository->deleteLeaveType($leaveTypeId, $isHashedId);
    }
}
