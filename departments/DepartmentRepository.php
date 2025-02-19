<?php

require_once __DIR__ . '/DepartmentDao.php';

class DepartmentRepository
{
    private readonly DepartmentDao $departmentDao;

    public function __construct(DepartmentDao $departmentDao)
    {
        $this->departmentDao = $departmentDao;
    }

    public function createDepartment(Department $department): ActionResult
    {
        return $this->departmentDao->create($department);
    }

    public function fetchAllDepartments(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->departmentDao->fetchAll(
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
        return $this->departmentDao->fetchEmployeeCountsPerDepartment();
    }

    public function isEmployeeDepartmentHead(int|string $employeeId): bool|ActionResult
    {
        return $this->departmentDao->isDepartmentHead($employeeId);
    }

    public function updateDepartment(Department $department): ActionResult
    {
        return $this->departmentDao->update($department);
    }

    public function deleteDepartment(int|string $departmentId): ActionResult
    {
        return $this->departmentDao->delete($departmentId);
    }
}
