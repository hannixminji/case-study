<?php

require_once __DIR__ . '/EmployeeAllowanceRepository.php';

require_once __DIR__ . '/EmployeeAllowanceValidator.php' ;

class EmployeeAllowanceService
{
    private readonly PDO $pdo;

    private readonly EmployeeAllowanceRepository $employeeAllowanceRepository;

    private readonly EmployeeAllowanceValidator $employeeAllowanceValidator;

    public function __construct(PDO $pdo, EmployeeAllowanceRepository $employeeAllowanceRepository)
    {
        $this->pdo = $pdo;

        $this->employeeAllowanceRepository = $employeeAllowanceRepository;

        $this->employeeAllowanceValidator = new EmployeeAllowanceValidator();
    }

    public function createEmployeeAllowance(array $employeeAllowances): array
    {
        $this->employeeAllowanceValidator->setGroup('create');

        foreach($employeeAllowances as $employeeAllowance) {
            $this->employeeAllowanceValidator->setData($employeeAllowance);

            $this->employeeAllowanceValidator->validate([
                'employee_id' ,
                'allowance_id',
                'amount'
            ]);

            $validationErrors = $this->employeeAllowanceValidator->getErrors();

            if ( ! empty($validationErrors)) {
                return [
                    'status'  => 'invalid_input',
                    'message' => 'There are validation errors. Please check the input values.',
                    'errors'  => $validationErrors
                ];
            }
        }

        $this->pdo->beginTransaction();

        try {
            foreach($employeeAllowances as $employeeAllowance) {
                $employeeId = $employeeAllowance['employee_id'];

                if (is_string($employeeId) && preg_match('/^[1-9]\d*$/', $employeeId)) {
                    $employeeId = (int) $employeeId;
                }

                $allowanceId = $employeeAllowance['allowance_id'];

                if (is_string($allowanceId) && preg_match('/^[1-9]\d*$/', $allowanceId)) {
                    $allowanceId = (int) $allowanceId;
                }

                $employeeAllowance = new EmployeeAllowance(
                    id         :         null                        ,
                    employeeId :         $employeeId                 ,
                    allowanceId:         $allowanceId                ,
                    amount     : (float) $employeeAllowance['amount']
                );

                $assignAllowanceToEmployeeResult = $this->employeeAllowanceRepository->createEmployeeAllowance($employeeAllowance);

                if ($assignAllowanceToEmployeeResult === ActionResult::FAILURE) {
                    $this->pdo->rollBack();

                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred while assigning the allowance to an employee. Please try again later.'
                    ];
                }
            }

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while assigning the allowance to an employee. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' =>
                (count($employeeAllowance) > 1)
                    ? 'Allowances assigned successfully.'
                    : 'Allowance assigned successfully.'
        ];

    }

    public function fetchAllEmployeeAllowances(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->employeeAllowanceRepository->fetchAllEmployeeAllowances(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function deleteEmployeeAllowance(mixed $employeeAllowanceId): array
    {
        $this->employeeAllowanceValidator->setGroup('delete');

        $this->employeeAllowanceValidator->setData([
            'id' => $employeeAllowanceId
        ]);

        $this->employeeAllowanceValidator->validate([
            'id'
        ]);

        $validationErrors = $this->employeeAllowanceValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        if (is_string($employeeAllowanceId) && preg_match('/^[1-9]\d*$/', $employeeAllowanceId)) {
            $employeeAllowanceId = (int) $employeeAllowanceId;
        }

        $deleteEmployeeAllowanceResult = $this->employeeAllowanceRepository->deleteEmployeeAllowance($employeeAllowanceId);

        if ($deleteEmployeeAllowanceResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while deleting the assigned allowance. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Assigned allowance deleted successfully.'
        ];
    }
}
