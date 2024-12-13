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
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->employeeRepository->fetchAllEmployees($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateEmployee(Employee $employee): ActionResult
    {
        return $this->employeeRepository->updateEmployee($employee);
    }

    public function getEmployeeIdBy(string $column, string $value): ActionResult|int
    {
        return $this->employeeRepository->getEmployeeIdBy($column, $value);
    }

    public function changePassword(int $employeeId, string $newHashedPassword): ActionResult
    {
        return $this->employeeRepository->changePassword($employeeId, $newHashedPassword);
    }

    public function countTotalRecords(): ActionResult|int
    {
        return $this->employeeRepository->countTotalRecords();
    }

    public function deleteEmployee(int $employeeId): ActionResult
    {
        return $this->employeeRepository->deleteEmployee($employeeId);
    }
}
