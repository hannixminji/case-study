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
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->departmentDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateDepartment(Department $department): ActionResult
    {
        return $this->departmentDao->update($department);
    }

    public function isEmployeeDepartmentHead(int $employeeId): ActionResult|bool
    {
        return $this->departmentDao->isDepartmentHead($employeeId);
    }

    public function deleteDepartment(int $departmentId): ActionResult
    {
        return $this->departmentDao->delete($departmentId);
    }
}
