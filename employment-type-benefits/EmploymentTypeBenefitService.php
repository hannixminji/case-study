<?php

require_once __DIR__ . '/EmploymentTypeBenefitRepository.php';

require_once __DIR__ . '/EmploymentTypeBenefitValidator.php' ;

class EmploymentTypeBenefitService
{
    private readonly PDO $pdo;

    private readonly EmploymentTypeBenefitRepository $employmentTypeBenefitRepository;

    private readonly EmploymentTypeBenefitValidator $employmentTypeBenefitValidator;

    public function __construct(PDO $pdo, EmploymentTypeBenefitRepository $employmentTypeBenefitRepository)
    {
        $this->pdo = $pdo;

        $this->employmentTypeBenefitRepository = $employmentTypeBenefitRepository;

        $this->employmentTypeBenefitValidator = new EmploymentTypeBenefitValidator();
    }

    public function createEmploymentTypeBenefit(array $employmentTypeBenefits): array
    {
        $this->employmentTypeBenefitValidator->setGroup('create');

        foreach ($employmentTypeBenefits as $employmentTypeBenefit) {
            $this->employmentTypeBenefitValidator->setData($employmentTypeBenefit);

            $this->employmentTypeBenefitValidator->validate([
                'employment_type',
                'leave_type_id'  ,
                'allowance_id'   ,
                'deduction_id'
            ]);

            $validationErrors = $this->employmentTypeBenefitValidator->getErrors();

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
            foreach ($employmentTypeBenefits as $employmentTypeBenefit) {
                $leaveTypeId = $employmentTypeBenefit['leave_type_id'];
                $allowanceId = $employmentTypeBenefit['allowance_id' ];
                $deductionId = $employmentTypeBenefit['deduction_id' ];

                if (is_string($leaveTypeId) && preg_match('/^[1-9]\d*$/', $leaveTypeId)) {
                    $leaveTypeId = (int) $leaveTypeId;
                }

                if (is_string($allowanceId) && preg_match('/^[1-9]\d*$/', $allowanceId)) {
                    $allowanceId = (int) $allowanceId;
                }

                if (is_string($deductionId) && preg_match('/^[1-9]\d*$/', $deductionId)) {
                    $deductionId = (int) $deductionId;
                }

                $newEmploymentTypeBenefit = new EmploymentTypeBenefit(
                    id            : null                                     ,
                    employmentType: $employmentTypeBenefit['employment_type'],
                    leaveTypeId   : $leaveTypeId                             ,
                    allowanceId   : $allowanceId                             ,
                    deductionId   : $deductionId
                );

                $assignBenefitToEmploymentTypeResult = $this->employmentTypeBenefitRepository->createEmploymentTypeBenefit($newEmploymentTypeBenefit);

                if ($assignBenefitToEmploymentTypeResult === ActionResult::FAILURE) {
                    $this->pdo->rollBack();

                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred while assigning benefit to an employment type. Please try again later.'
                    ];
                }
            }

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while assigning benefit to an employment type. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' =>
                count($employmentTypeBenefits) > 1
                    ? 'Benefits assigned successfully.'
                    : 'Benefit assigned successfully.'
        ];
    }

    public function fetchAllEmploymentTypeBenefits(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->employmentTypeBenefitRepository->fetchAllEmploymentTypeBenefits(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function deleteEmploymentTypeBenefit(mixed $employmentTypeBenefitId): array
    {
        $this->employmentTypeBenefitValidator->setGroup('create');

        $this->employmentTypeBenefitValidator->setData([
            'id' => $employmentTypeBenefitId
        ]);

        $this->employmentTypeBenefitValidator->validate([
            'id'
        ]);

        $validationErrors = $this->employmentTypeBenefitValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        if (is_string($employmentTypeBenefitId) && preg_match('/^[1-9]\d*$/', $employmentTypeBenefitId)) {
            $employmentTypeBenefitId = (int) $employmentTypeBenefitId;
        }

        $deleteEmploymentTypeBenefitResult = $this->employmentTypeBenefitRepository->deleteEmploymentTypeBenefit($employmentTypeBenefitId);

        if ($deleteEmploymentTypeBenefitResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while deleting the benefit from an employment type. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Benefit deleted successfully from the employment type.'
        ];
    }
}
