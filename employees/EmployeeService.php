<?php

require_once __DIR__ . '/EmployeeRepository.php';

class EmployeeService
{
    private readonly EmployeeRepository $employeeRepository;

    public function __construct(EmployeeRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    public function createEmployee(Employee $employee): array
    {
        $result = $this->employeeRepository->createEmployee($employee);

        if ($result === ActionResult::SUCCESS) {
            return [
                'status'  => 'success',
                'message' => 'Employee created successfully.'
            ];
        } elseif ($result === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'Failed to create the employee.'
            ];
        } else {
            return [
                'status'  => 'error',
                'message' => 'Error occurred: Duplicate entry for key "{$result}".',
            ];
        }
    }
}
