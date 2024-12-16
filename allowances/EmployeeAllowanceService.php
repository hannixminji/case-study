<?php

require_once __DIR__ . '/EmployeeAllowanceDao.php';

class EmployeeAllowanceService
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
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->employeeAllowanceDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function deleteEmployeeAllowance(int $employeeAllowanceId, bool $isHashedId = false): ActionResult
    {
        return $this->employeeAllowanceDao->delete($employeeAllowanceId, $isHashedId);
    }
}
