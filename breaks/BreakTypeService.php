<?php

require_once __DIR__ . '/BreakTypeRepository.php';

require_once __DIR__ . '/BreakTypeValidator.php' ;

class BreakTypeService
{
    private readonly BreakTypeRepository $breakTypeRepository;

    private readonly BreakTypeValidator $breakTypeValidator;

    public function __construct(BreakTypeRepository $breakTypeRepository)
    {
        $this->breakTypeRepository = $breakTypeRepository;

        $this->breakTypeValidator = new BreakTypeValidator($breakTypeRepository);
    }

    public function createBreakType(array $breakType): array
    {
        $this->breakTypeValidator->setGroup('create');

        $this->breakTypeValidator->setData($breakType);

        $this->breakTypeValidator->validate([
            'name'                             ,
            'duration_in_minutes'              ,
            'is_paid'                          ,
            'is_require_break_in_and_break_out'
        ]);

        $validationErrors = $this->breakTypeValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $isPaid                    = filter_var($breakType['is_paid'                          ], FILTER_VALIDATE_BOOLEAN);
        $requireBreakInAndBreakOut = filter_var($breakType['is_require_break_in_and_break_out'], FILTER_VALIDATE_BOOLEAN);

        $newBreakType = new BreakType(
            id                       :       null                             ,
            name                     :       $breakType['name'               ],
            durationInMinutes        : (int) $breakType['duration_in_minutes'],
            isPaid                   :       $isPaid                          ,
            requireBreakInAndBreakOut:       $requireBreakInAndBreakOut
        );

        $createBreakTypeResult = $this->breakTypeRepository->createBreakType($newBreakType);

        if ($createBreakTypeResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while creating the break type. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Break type created successfully.'
        ];
    }

    public function createBreakTypeSnapshot(BreakTypeSnapshot $breakTypeSnapshot): int|ActionResult
    {
        return $this->breakTypeRepository->createBreakTypeSnapshot($breakTypeSnapshot);
    }

    public function fetchAllBreakTypes(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->breakTypeRepository->fetchAllBreakTypes(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateBreakType(array $breakType): array
    {
        $this->breakTypeValidator->setGroup('update');

        $this->breakTypeValidator->setData($breakType);

        $this->breakTypeValidator->validate([
            'id'                               ,
            'name'                             ,
            'duration_in_minutes'              ,
            'is_paid'                          ,
            'is_require_break_in_and_break_out'
        ]);

        $validationErrors = $this->breakTypeValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $breakTypeId = $breakType['id'];

        if (filter_var($breakTypeId, FILTER_VALIDATE_INT) !== false) {
            $breakTypeId = (int) $breakTypeId;
        }

        $isPaid                    = filter_var($breakType['is_paid'                          ], FILTER_VALIDATE_BOOLEAN);
        $requireBreakInAndBreakOut = filter_var($breakType['is_require_break_in_and_break_out'], FILTER_VALIDATE_BOOLEAN);

        $newBreakType = new BreakType(
            id                       :       $breakTypeId                     ,
            name                     :       $breakType['name'               ],
            durationInMinutes        : (int) $breakType['duration_in_minutes'],
            isPaid                   :       $isPaid                          ,
            requireBreakInAndBreakOut:       $requireBreakInAndBreakOut
        );

        $updateBreakTypeResult = $this->breakTypeRepository->updateBreakType($newBreakType);

        if ($updateBreakTypeResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while updating the break type. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Break type updated successfully.'
        ];
    }

    public function deleteBreakType(mixed $breakTypeId): array
    {
        $this->breakTypeValidator->setGroup('delete');

        $this->breakTypeValidator->setData([
            'id' => $breakTypeId
        ]);

        $this->breakTypeValidator->validate([
            'id'
        ]);

        $validationErrors = $this->breakTypeValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        if (filter_var($breakTypeId, FILTER_VALIDATE_INT) !== false) {
            $breakTypeId = (int) $breakTypeId;
        }

        $deleteBreakTypeResult = $this->breakTypeRepository->deleteBreakType($breakTypeId);

        if ($deleteBreakTypeResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while deleting the break type. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Break type deleted successfully.'
        ];
    }
}
