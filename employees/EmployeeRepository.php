<?php

require_once __DIR__ . '/EmployeeDao.php';

class EmployeeRepository
{
    private readonly EmployeeDao $employeeDao;

    public function __construct(EmployeeDao $employeeDao)
    {
        $this->employeeDao = $employeeDao;
    }

    public function createEmployee(Employee $employee): ActionResult
    {
        return $this->employeeDao->create($employee);
    }

    public function fetchAllEmployees(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->employeeDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function fetchLastEmployeeId(): int
    {
        return $this->employeeDao->fetchLastInsertedId();
    }

    public function updateEmployee(Employee $employee, bool $isHashedId = false): ActionResult
    {
        return $this->employeeDao->update($employee, $isHashedId);
    }

    public function changePassword(int|string $employeeId, string $newHashedPassword, bool $isHashedId = false): ActionResult
    {
        return $this->employeeDao->changePassword($employeeId, $newHashedPassword, $isHashedId);
    }

    public function countTotalRecords(): int|ActionResult
    {
        return $this->employeeDao->countTotalRecords();
    }

    public function deleteEmployee(int|string $employeeId, bool $isHashedId = false): ActionResult
    {
        return $this->employeeDao->delete($employeeId, $isHashedId);
    }
}
