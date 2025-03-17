<?php

require_once __DIR__ . '/AllowanceRepository.php';

require_once __DIR__ . '/AllowanceValidator.php' ;

class AllowanceService
{
    private readonly AllowanceRepository $allowanceRepository;

    private AllowanceValidator $allowanceValidator;

    public function __construct(AllowanceRepository $allowanceRepository)
    {
        $this->allowanceRepository = $allowanceRepository;

        $this->allowanceValidator = new AllowanceValidator($allowanceRepository);
    }

    public function createAllowance(Allowance $allowance): array
    {
        $this->allowanceValidator->setGroup('create');

        $this->allowanceValidator->validate([
            'name'       ,
            'amount'     ,
            'frequency'  ,
            'description',
            'status'
        ]);

        $validationErrors = $this->allowanceValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $createAllowanceTypeResult = $this->allowanceRepository->createAllowance($allowance);

        if ($createAllowanceTypeResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while creating the allowance type. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Allowance type created successfully.'
        ];
    }

    public function fetchAllAllowances(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->allowanceRepository->fetchAllAllowances(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateAllowance(Allowance $allowance): array
    {
        $this->allowanceValidator->setGroup('update');

        $this->allowanceValidator->validate([
            'id'         ,
            'name'       ,
            'amount'     ,
            'frequency'  ,
            'description',
            'status'
        ]);

        $validationErrors = $this->allowanceValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $updateAllowanceTypeResult = $this->allowanceRepository->updateAllowance($allowance);

        if ($updateAllowanceTypeResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while updating the allowance type. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Allowance type updated successfully.'
        ];
    }

    public function deleteAllowance(int|string $allowanceId): array
    {
        $this->allowanceValidator->setGroup('delete');

        $this->allowanceValidator->validate([
            'id'
        ]);

        $validationErrors = $this->allowanceValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $deleteAllowanceTypeResult = $this->allowanceRepository->deleteAllowance($allowanceId);

        if ($deleteAllowanceTypeResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while deleting the allowance type. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Allowance type deleted successfully.'
        ];
    }
}
