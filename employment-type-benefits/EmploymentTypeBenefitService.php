<?php

require_once __DIR__ . '/EmploymentTypeBenefitRepository.php';

require_once __DIR__ . '/EmploymentTypeBenefitValidator.php' ;

class EmploymentTypeBenefitService
{
    private readonly EmploymentTypeBenefitRepository $employmentTypeBenefitRepository;

    private readonly EmploymentTypeBenefitValidator $employmentTypeBenefitValidator;

    public function __construct(EmploymentTypeBenefitRepository $employmentTypeBenefitRepository)
    {
        $this->employmentTypeBenefitRepository = $employmentTypeBenefitRepository;

        $this->employmentTypeBenefitValidator = new EmploymentTypeBenefitValidator();
    }

    public function createEmploymentTypeBenefit(array $employmentTypeBenefit): array
    {
        $this->employmentTypeBenefitValidator->setGroup('create');

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

        $leaveTypeId = $employmentTypeBenefit['leave_type_id'];
        $allowanceId = $employmentTypeBenefit['allowance_id' ];
        $deductionId = $employmentTypeBenefit['deduction_id' ];

        if (filter_var($leaveTypeId, FILTER_VALIDATE_INT) !== false) {
            $leaveTypeId = (int) $leaveTypeId;
        } elseif (is_string($leaveTypeId) && trim($leaveTypeId) === '') {
            $leaveTypeId = null;
        }

        if (filter_var($allowanceId, FILTER_VALIDATE_INT) !== false) {
            $allowanceId = (int) $allowanceId;
        } elseif (is_string($allowanceId) && trim($allowanceId) === '') {
            $allowanceId = null;
        }

        if (filter_var($deductionId, FILTER_VALIDATE_INT) !== false) {
            $deductionId = (int) $deductionId;
        } elseif (is_string($deductionId) && trim($deductionId) === '') {
            $deductionId = null;
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
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while assigning benefit to an employment type. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Benefit successfully assigned to an employment type.'
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
        $this->employmentTypeBenefitValidator->setGroup('delete');

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

        if (filter_var($employmentTypeBenefitId, FILTER_VALIDATE_INT) !== false) {
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
