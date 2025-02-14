<?php

require_once __DIR__ . '/EmployeeRepository.php';

class EmployeeService
{
    private readonly EmployeeRepository $employeeRepository;

    public function __construct(EmployeeRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    public function createEmployee(Employee $employee): ActionResult
    {
        return $this->employeeRepository->createEmployee($employee);
    }

    public function fetchAllEmployees(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->employeeRepository->fetchAllEmployees(
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
        return $this->employeeRepository->fetchLastEmployeeId();
    }

    public function updateEmployee(Employee $employee, bool $isHashedId = false): ActionResult
    {
        return $this->employeeRepository->updateEmployee($employee, $isHashedId);
    }

    public function changePassword(int|string $employeeId, string $newHashedPassword, bool $isHashedId = false): ActionResult
    {
        return $this->employeeRepository->changePassword($employeeId, $newHashedPassword, $isHashedId);
    }

    public function countTotalRecords(): int|ActionResult
    {
        return $this->employeeRepository->countTotalRecords();
    }

    public function deleteEmployee(int|string $employeeId, bool $isHashedId = false): ActionResult
    {
        return $this->employeeRepository->deleteEmployee($employeeId, $isHashedId);
    }
}
