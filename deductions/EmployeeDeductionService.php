<?php

require_once __DIR__ . '/EmployeeDeductionRepository.php';

class EmployeeDeductionService
{
    private readonly EmployeeDeductionRepository $employeeDeductionRepository;

    public function __construct(EmployeeDeductionRepository $employeeDeductionRepository)
    {
        $this->employeeDeductionRepository = $employeeDeductionRepository;
    }

    public function createEmployeeDeduction(EmployeeDeduction $employeeDeduction): ActionResult
    {
        return $this->employeeDeductionRepository->createEmployeeDeduction($employeeDeduction);
    }

    public function fetchAllEmployeeDeductions(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->employeeDeductionRepository->fetchAllEmployeeDeductions($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function deleteEmployeeDeduction(int $employeeDeductionId): ActionResult
    {
        return $this->employeeDeductionRepository->deleteEmployeeDeduction($employeeDeductionId);
    }
}
