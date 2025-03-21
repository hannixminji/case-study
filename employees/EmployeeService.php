<?php

require_once __DIR__ . '/EmployeeRepository.php';

require_once __DIR__ . '/EmployeeValidator.php' ;

class EmployeeService
{
    private readonly EmployeeRepository $employeeRepository;

    private readonly EmployeeValidator $employeeValidator;

    public function __construct(EmployeeRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;

        $this->employeeValidator = new EmployeeValidator($employeeRepository);
    }

    public function createEmployee(Employee $employee): array
    {
        //$this->employeeRepository->createEmployee($employee);

        return [];
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

    public function updateEmployee(Employee $employee): array
    {
        //$this->employeeRepository->updateEmployee($employee);

        return [];
    }

    public function changePassword(int|string $employeeId, string $newHashedPassword): array
    {
        //$this->employeeRepository->changePassword($employeeId, $newHashedPassword);

        return [];
    }

    public function countTotalRecords(): int|ActionResult
    {
        return $this->employeeRepository->countTotalRecords();
    }

    public function deleteEmployee(int|string $employeeId): array
    {
        //$this->employeeRepository->deleteEmployee($employeeId);

        return [];
    }
}
