<?php

require_once __DIR__ . '/EmployeeDao.php';

class EmployeeRepository
{
    private readonly EmployeeDao $employeeDao;

    public function __construct(PDO $pdo)
    {
        $this->employeeDao = new EmployeeDao($pdo);
    }

    public function createEmployee(Employee $employee): ActionResult
    {
        return $this->employeeDao->create($employee);
    }

    public function fetchAllEmployees(
        ?array $columns        = null,
        ?array $filterCriteria = null,
        ?array $sortCriteria   = null,
        ?int   $limit          = null,
        ?int   $offset         = null
    ): ActionResult|array {
        return $this->employeeDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateEmployee(Employee $employee): ActionResult
    {
        return $this->employeeDao->update($employee);
    }

    public function getEmployeeIdBy(string $column, string $value): ActionResult|int
    {
        $filterCriteria = [
            [
                'column'   => $column,
                'operator' => '='    ,
                'value'    => $value
            ],
        ];

        $result = $this->fetchAllEmployees(columns: ['id'], filterCriteria: $filterCriteria, limit: 1);

        if ($result === ActionResult::FAILURE) {
            return ActionResult::FAILURE;
        }

        return (int) $result['result_set'][0]['id'];
    }

    public function changePassword(int $employeeId, string $newHashedPassword): ActionResult
    {
        return $this->employeeDao->changePassword($employeeId, $newHashedPassword);
    }

    public function deleteEmployee(int $employeeId): ActionResult
    {
        return $this->employeeDao->delete($employeeId);
    }
}
