<?php

require_once __DIR__ . '/DeductionRepository.php';

require_once __DIR__ . '/DeductionValidator.php' ;

class DeductionService
{
    private readonly DeductionRepository $deductionRepository;

    private readonly DeductionValidator $deductionValidator;

    public function __construct(DeductionRepository $deductionRepository)
    {
        $this->deductionRepository = $deductionRepository;

        $this->deductionValidator = new DeductionValidator($deductionRepository);
    }

    public function createDeduction(array $deduction): array
    {
        $this->deductionValidator->setGroup('create');

        $this->deductionValidator->setData($deduction);

        $this->deductionValidator->validate([
            'name'       ,
            'amount'     ,
            'frequency'  ,
            'description',
            'status'
        ]);

        $validationErrors = $this->deductionValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $deduction = new Deduction(
            id         :         null                     ,
            name       :         $deduction['name'       ],
            amount     : (float) $deduction['amount'     ],
            frequency  :         $deduction['frequency'  ],
            description:         $deduction['description'],
            status     :         $deduction['status'     ]
        );

        $createDeductionResult = $this->deductionRepository->createDeduction($deduction);

        if ($createDeductionResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while creating the deduction type. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Deduction type created successfully.'
        ];
    }

    public function fetchAllDeductions(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->deductionRepository->fetchAllDeductions(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateDeduction(array $deduction): array
    {
        $this->deductionValidator->setGroup('update');

        $this->deductionValidator->setData($deduction);

        $this->deductionValidator->validate([
            'id'         ,
            'name'       ,
            'amount'     ,
            'frequency'  ,
            'description',
            'status'
        ]);

        $validationErrors = $this->deductionValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $deductionId = $deduction['id'];

        if (is_string($deductionId) && preg_match('/^[1-9]\d*$/', $deductionId)) {
            $deductionId = (int) $deductionId;
        }

        $deduction = new Deduction(
            id         :         $deductionId             ,
            name       :         $deduction['name'       ],
            amount     : (float) $deduction['amount'     ],
            frequency  :         $deduction['frequency'  ],
            description:         $deduction['description'],
            status     :         $deduction['status'     ]
        );

        $updateDeductionResult = $this->deductionRepository->updateDeduction($deduction);

        if ($updateDeductionResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while updating the deduction type. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Deduction type updated successfully.'
        ];
    }

    public function deleteDeduction(mixed $deductionId): array
    {
        $this->deductionValidator->setGroup('delete');

        $this->deductionValidator->setData([
            'id' => $deductionId
        ]);

        $this->deductionValidator->validate([
            'id'
        ]);

        $validationErrors = $this->deductionValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        if (is_string($deductionId) && preg_match('/^[1-9]\d*$/', $deductionId)) {
            $deductionId = (int) $deductionId;
        }

        $deleteDeductionResult = $this->deductionRepository->deleteDeduction($deductionId);

        if ($deleteDeductionResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while deleting the deduction type. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Deduction type deleted successfully.'
        ];
    }
}
