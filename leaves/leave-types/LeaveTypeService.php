<?php

require_once __DIR__ . '/LeaveTypeRepository.php';

require_once __DIR__ . '/LeaveTypeValidator.php' ;

class LeaveTypeService
{
    private readonly LeaveTypeRepository $leaveTypeRepository;

    private readonly LeaveTypeValidator $leaveTypeValidator;

    public function __construct(LeaveTypeRepository $leaveTypeRepository)
    {
        $this->leaveTypeRepository = $leaveTypeRepository;

        $this->leaveTypeValidator = new LeaveTypeValidator($leaveTypeRepository);
    }

    public function createLeaveType(array $leaveType): array
    {
        $this->leaveTypeValidator->setGroup('create');

        $this->leaveTypeValidator->setData($leaveType);

        $this->leaveTypeValidator->validate([
            'name'                  ,
            'maximum_number_of_days',
            'is_paid'               ,
            'is_encashable'         ,
            'description'           ,
            'status'
        ]);

        $validationErrors = $this->leaveTypeValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $isPaid       = filter_var($leaveType['is_paid'      ], FILTER_VALIDATE_BOOLEAN);
        $isEncashable = filter_var($leaveType['is_encashable'], FILTER_VALIDATE_BOOLEAN);

        $newLeaveType = new LeaveType(
            id                 :       null                                ,
            name               :       $leaveType['name'                  ],
            maximumNumberOfDays: (int) $leaveType['maximum_number_of_days'],
            isPaid             :       $isPaid                             ,
            isEncashable       :       $isEncashable                       ,
            description        :       $leaveType['description'           ],
            status             :       $leaveType['status'                ]
        );

        $createLeaveTypeResult = $this->leaveTypeRepository->createLeaveType($newLeaveType);

        if ($createLeaveTypeResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while creating the leave type. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Leave type created successfully.'
        ];
    }

    public function fetchAllLeaveTypes(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->leaveTypeRepository->fetchAllLeaveTypes(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateLeaveType(array $leaveType): array
    {
        $this->leaveTypeValidator->setGroup('update');

        $this->leaveTypeValidator->setData($leaveType);

        $this->leaveTypeValidator->validate([
            'id'                    ,
            'name'                  ,
            'maximum_number_of_days',
            'is_paid'               ,
            'is_encashable'         ,
            'description'           ,
            'status'
        ]);

        $validationErrors = $this->leaveTypeValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $leaveTypeId = $leaveType['id'];

        if (filter_var($leaveTypeId, FILTER_VALIDATE_INT) !== false) {
            $leaveTypeId = (int) $leaveTypeId;
        }

        $isPaid       = filter_var($leaveType['is_paid'      ], FILTER_VALIDATE_BOOLEAN);
        $isEncashable = filter_var($leaveType['is_encashable'], FILTER_VALIDATE_BOOLEAN);

        $newLeaveType = new LeaveType(
            id                 :       $leaveTypeId                        ,
            name               :       $leaveType['name'                  ],
            maximumNumberOfDays: (int) $leaveType['maximum_number_of_days'],
            isPaid             :       $isPaid                             ,
            isEncashable       :       $isEncashable                       ,
            description        :       $leaveType['description'           ],
            status             :       $leaveType['status'                ]
        );

        $updateLeaveTypeResult = $this->leaveTypeRepository->updateLeaveType($newLeaveType);

        if ($updateLeaveTypeResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while updating the leave type. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Leave type updated successfully.'
        ];
    }

    public function deleteLeaveType(mixed $leaveTypeId): array
    {
        $this->leaveTypeValidator->setGroup('delete');

        $this->leaveTypeValidator->setData([
            'id' => $leaveTypeId
        ]);

        $this->leaveTypeValidator->validate([
            'id'
        ]);

        $validationErrors = $this->leaveTypeValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        if (filter_var($leaveTypeId, FILTER_VALIDATE_INT) !== false) {
            $leaveTypeId = (int) $leaveTypeId;
        }

        $deleteLeaveTypeResult = $this->leaveTypeRepository->deleteLeaveType($leaveTypeId);

        if ($deleteLeaveTypeResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while deleting the leave type. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Leave type deleted successfully.'
        ];
    }
}
