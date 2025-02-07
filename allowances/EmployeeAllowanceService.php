<?php

require_once __DIR__ . '/EmployeeAllowanceRepository.php';

class EmployeeAllowanceService
{
    private readonly EmployeeAllowanceRepository $employeeAllowanceRepository;

    public function __construct(EmployeeAllowanceRepository $employeeAllowanceRepository)
    {
        $this->employeeAllowanceRepository = $employeeAllowanceRepository;
    }

    public function createEmployeeAllowance(EmployeeAllowance $employeeAllowance): ActionResult
    {
        return $this->employeeAllowanceRepository->createEmployeeAllowance($employeeAllowance);
    }

    public function fetchAllEmployeeAllowances(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): ActionResult|array {
        
        return $this->employeeAllowanceRepository->fetchAllEmployeeAllowances(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function deleteEmployeeAllowance(int|string $employeeAllowanceId, bool $isHashedId = false): ActionResult
    {
        return $this->employeeAllowanceRepository->deleteEmployeeAllowance($employeeAllowanceId, $isHashedId);
    }
}
