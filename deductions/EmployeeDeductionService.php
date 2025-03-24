<?php

require_once __DIR__ . '/EmployeeDeductionRepository.php';

require_once __DIR__ . '/EmployeeDeductionValidator.php' ;

class EmployeeDeductionService
{
    private readonly PDO $pdo;

    private readonly EmployeeDeductionRepository $employeeDeductionRepository;

    private readonly EmployeeDeductionValidator $employeeDeductionValidator;

    public function __construct(PDO $pdo, EmployeeDeductionRepository $employeeDeductionRepository)
    {
        $this->pdo = $pdo;

        $this->employeeDeductionRepository = $employeeDeductionRepository;

        $this->employeeDeductionValidator = new EmployeeDeductionValidator();
    }

    public function createEmployeeDeduction(array $employeeDeductions): array
    {
        $this->employeeDeductionValidator->setGroup('create');

        foreach ($employeeDeductions as $employeeDeduction) {
            $this->employeeDeductionValidator->setData($employeeDeduction);

            $this->employeeDeductionValidator->validate([
                'employee_id' ,
                'deduction_id',
                'amount'
            ]);

            $validationErrors = $this->employeeDeductionValidator->getErrors();

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
            foreach($employeeDeductions as $employeeDeduction) {
                $employeeId  = $employeeDeduction['employee_id' ];
                $deductionId = $employeeDeduction['deduction_id'];

                if (filter_var($employeeId, FILTER_VALIDATE_INT) !== false) {
                    $employeeId = (int) $employeeId;
                }

                if (filter_var($deductionId, FILTER_VALIDATE_INT) !== false) {
                    $deductionId = (int) $deductionId;
                }

                $newEmployeeDeduction = new EmployeeDeduction(
                    id         :         null                        ,
                    employeeId :         $employeeId                 ,
                    deductionId:         $deductionId                ,
                    amount     : (float) $employeeDeduction['amount']
                );

                $assignDeductionToEmployeeResult = $this->employeeDeductionRepository->createEmployeeDeduction($newEmployeeDeduction);

                if ($assignDeductionToEmployeeResult === ActionResult::FAILURE) {
                    $this->pdo->rollBack();

                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred while assigning the deduction to an employee. Please try again later.'
                    ];
                }
            }

            $this->pdo->commit();

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while assigning the deduction to an employee. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' =>
                count($employeeDeductions) > 1
                    ? 'Deductions assigned successfully.'
                    : 'Deduction assigned successfully.'
        ];
    }

    public function fetchAllEmployeeDeductions(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->employeeDeductionRepository->fetchAllEmployeeDeductions(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function deleteEmployeeDeduction(mixed $employeeDeductionId): array
    {
        $this->employeeDeductionValidator->setGroup('delete');

        $this->employeeDeductionValidator->setData([
            'id' => $employeeDeductionId
        ]);

        $this->employeeDeductionValidator->validate([
            'id'
        ]);

        $validationErrors = $this->employeeDeductionValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        if (filter_var($employeeDeductionId, FILTER_VALIDATE_INT) !== false) {
            $employeeDeductionId = (int) $employeeDeductionId;
        }

        $deleteEmployeeDeductionResult = $this->employeeDeductionRepository->deleteEmployeeDeduction($employeeDeductionId);

        if ($deleteEmployeeDeductionResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while deleting the assigned deduction. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Assigned deduction deleted successfully.'
        ];
    }
}
