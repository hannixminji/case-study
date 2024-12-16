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
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->departmentRepository->fetchAllDepartments($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateDepartment(Department $department, bool $isHashedId = false): ActionResult
    {
        return $this->departmentRepository->updateDepartment($department, $isHashedId);
    }

    public function isEmployeeDepartmentHead(int $employeeId, bool $isHashedId = false): ActionResult|bool
    {
        return $this->departmentRepository->isEmployeeDepartmentHead($employeeId, $isHashedId);
    }

    public function deleteDepartment(int $departmentId, bool $isHashedId = false): ActionResult
    {
        return $this->departmentRepository->deleteDepartment($departmentId, $isHashedId);
    }
}
