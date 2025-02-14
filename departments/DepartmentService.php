<?php

require_once __DIR__ . '/DepartmentRepository.php';

class DepartmentService
{
    private readonly DepartmentRepository $departmentRepository;

    public function __construct(DepartmentRepository $departmentRepository)
    {
        $this->departmentRepository = $departmentRepository;
    }

    public function createDepartment(Department $department): ActionResult
    {
        return $this->departmentRepository->createDepartment($department);
    }

    public function fetchAllDepartments(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->departmentRepository->fetchAllDepartments(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function fetchEmployeeCountsPerDepartment(): array|ActionResult
    {
        return $this->departmentRepository->fetchEmployeeCountsPerDepartment();
    }

    public function isEmployeeDepartmentHead(int|string $employeeId, bool $isHashedId = false): bool|ActionResult
    {
        return $this->departmentRepository->isEmployeeDepartmentHead($employeeId, $isHashedId);
    }

    public function updateDepartment(Department $department, bool $isHashedId = false): ActionResult
    {
        return $this->departmentRepository->updateDepartment($department, $isHashedId);
    }

    public function deleteDepartment(int|string $departmentId, bool $isHashedId = false): ActionResult
    {
        return $this->departmentRepository->deleteDepartment($departmentId, $isHashedId);
    }
}
