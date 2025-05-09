<?php

require_once __DIR__ . '/AllowanceRepository.php';

require_once __DIR__ . '/AllowanceValidator.php' ;

class AllowanceService
{
    private readonly AllowanceRepository $allowanceRepository;

    private readonly AllowanceValidator  $allowanceValidator ;

    public function __construct(AllowanceRepository $allowanceRepository)
    {
        $this->allowanceRepository = $allowanceRepository;

        $this->allowanceValidator = new AllowanceValidator($allowanceRepository);
    }

    public function createAllowance(array $allowance): array
    {
        $this->allowanceValidator->setGroup('create');

        $this->allowanceValidator->setData($allowance);

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
                'status'  => 'warning',
                'message' => 'Invalid input detected. Please ensure all fields are valid.',
                'errors'  => $validationErrors
            ];
        }

        $newAllowanceType = new Allowance(
            id         :         null                     ,
            name       :         $allowance['name'       ],
            amount     : (float) $allowance['amount'     ],
            frequency  :         $allowance['frequency'  ],
            description:         $allowance['description'],
            status     :         $allowance['status'     ]
        );

        $createAllowanceTypeResult = $this->allowanceRepository->createAllowance($newAllowanceType);

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

    public function updateAllowance(array $allowance): array
    {
        $this->allowanceValidator->setGroup('update');

        $this->allowanceValidator->setData($allowance);

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
                'status'  => 'warning',
                'message' => 'Invalid input detected. Please ensure all fields are valid.',
                'errors'  => $validationErrors
            ];
        }

        $allowanceId = $allowance['id'];

        if (filter_var($allowanceId, FILTER_VALIDATE_INT) !== false) {
            $allowanceId = (int) $allowanceId;
        }

        $allowanceTypeToUpdate = new Allowance(
            id         :         $allowanceId             ,
            name       :         $allowance['name'       ],
            amount     : (float) $allowance['amount'     ],
            frequency  :         $allowance['frequency'  ],
            description:         $allowance['description'],
            status     :         $allowance['status'     ]
        );

        $updateAllowanceTypeResult = $this->allowanceRepository->updateAllowance($allowanceTypeToUpdate);

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

    public function deleteAllowance(mixed $allowanceId): array
    {
        $this->allowanceValidator->setGroup('delete');

        $this->allowanceValidator->setData([
            'id' => $allowanceId
        ]);

        $this->allowanceValidator->validate([
            'id'
        ]);

        $validationErrors = $this->allowanceValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'warning',
                'message' => 'Invalid input detected. Please ensure all fields are valid.',
                'errors'  => $validationErrors
            ];
        }

        if (filter_var($allowanceId, FILTER_VALIDATE_INT) !== false) {
            $allowanceId = (int) $allowanceId;
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