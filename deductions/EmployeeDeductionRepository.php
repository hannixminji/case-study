<?php

require_once __DIR__ . '/EmployeeDeductionDao.php';

class EmployeeDeductionRepository
{
    private readonly EmployeeDeductionDao $employeeDeductionDao;

    public function __construct(EmployeeDeductionDao $employeeDeductionDao)
    {
        $this->employeeDeductionDao = $employeeDeductionDao;
    }

    public function createEmployeeDeduction(EmployeeDeduction $employeeDeduction): ActionResult
    {
        return $this->employeeDeductionDao->create($employeeDeduction);
    }

    public function fetchAllEmployeeDeductions(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->employeeDeductionDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function deleteEmployeeDeduction(int|string $employeeDeductionId): ActionResult
    {
        return $this->employeeDeductionDao->delete($employeeDeductionId);
    }
}
