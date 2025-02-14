<?php

require_once __DIR__ . '/EmployeeAllowanceDao.php';

class EmployeeAllowanceRepository
{
    private readonly EmployeeAllowanceDao $employeeAllowanceDao;

    public function __construct(EmployeeAllowanceDao $employeeAllowanceDao)
    {
        $this->employeeAllowanceDao = $employeeAllowanceDao;
    }

    public function createEmployeeAllowance(EmployeeAllowance $employeeAllowance): ActionResult
    {
        return $this->employeeAllowanceDao->create($employeeAllowance);
    }

    public function fetchAllEmployeeAllowances(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->employeeAllowanceDao->fetchAll(
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
        return $this->employeeAllowanceDao->delete($employeeAllowanceId, $isHashedId);
    }
}
