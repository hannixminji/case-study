<?php

require_once __DIR__ . '/DepartmentRepository.php';

require_once __DIR__ . '/DepartmentValidator.php' ;

class DepartmentService
{
    private readonly DepartmentRepository $departmentRepository;

    private readonly DepartmentValidator $departmentValidator;

    public function __construct(DepartmentRepository $departmentRepository)
    {
        $this->departmentRepository = $departmentRepository;

        $this->departmentValidator = new DepartmentValidator($departmentRepository);
    }

    public function createDepartment(array $department): array
    {
        $this->departmentValidator->setGroup('create');

        $this->departmentValidator->setData($department);

        $this->departmentValidator->validate([
            'name'              ,
            'department_head_id',
            'description'       ,
            'status'
        ]);

        $validationErrors = $this->departmentValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $departmentHeadId = $department['department_head_id'];

        if (filter_var($departmentHeadId, FILTER_VALIDATE_INT) !== false) {
            $departmentHeadId = (int) $departmentHeadId;
        } elseif (is_string($departmentHeadId) && trim($departmentHeadId) === '') {
            $departmentHeadId = null;
        }

        $newDepartment = new Department(
            id              : null                      ,
            name            : $department['name'       ],
            departmentHeadId: $departmentHeadId         ,
            description     : $department['description'],
            status          : $department['status'     ]
        );

        $createDepartmentResult = $this->departmentRepository->createDepartment($newDepartment);

        if ($createDepartmentResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while creating the department. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Department created successfully.'
        ];
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

    public function isEmployeeDepartmentHead(int|string $employeeId): bool|ActionResult
    {
        return $this->departmentRepository->isEmployeeDepartmentHead($employeeId);
    }

    public function updateDepartment(array $department): array
    {
        $this->departmentValidator->setGroup('update');

        $this->departmentValidator->setData($department);

        $this->departmentValidator->validate([
            'id'                ,
            'name'              ,
            'department_head_id',
            'description'       ,
            'status'
        ]);

        $validationErrors = $this->departmentValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $departmentId     = $department['id'                ];
        $departmentHeadId = $department['department_head_id'];

        if (filter_var($departmentId, FILTER_VALIDATE_INT) !== false) {
            $departmentId = (int) $departmentId;
        }

        if (filter_var($departmentHeadId, FILTER_VALIDATE_INT) !== false) {
            $departmentHeadId = (int) $departmentHeadId;
        } elseif (is_string($departmentHeadId) && trim($departmentHeadId) === '') {
            $departmentHeadId = null;
        }

        $newDepartment = new Department(
            id              : $departmentId             ,
            name            : $department['name'       ],
            departmentHeadId: $departmentHeadId         ,
            description     : $department['description'],
            status          : $department['status'     ]
        );

        $updateDepartmentResult = $this->departmentRepository->updateDepartment($newDepartment);

        if ($updateDepartmentResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while updating the department. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Department updated successfully.'
        ];
    }

    public function deleteDepartment(mixed $departmentId): array
    {
        $this->departmentValidator->setGroup('delete');

        $this->departmentValidator->setData([
            'id' => $departmentId
        ]);

        $this->departmentValidator->validate([
            'id'
        ]);

        $validationErrors = $this->departmentValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        if (filter_var($departmentId, FILTER_VALIDATE_INT) !== false) {
            $departmentId = (int) $departmentId;
        }

        $deleteDepartmentResult = $this->departmentRepository->deleteDepartment($departmentId);

        if ($deleteDepartmentResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while deleting the department. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Department deleted successfully.'
        ];
    }
}
