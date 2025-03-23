<?php

require_once __DIR__ . '/JobTitleRepository.php';

require_once __DIR__ . '/JobTitleValidator.php' ;

class JobTitleService
{
    private readonly JobTitleRepository $jobTitleRepository;

    private readonly JobTitleValidator $jobTitleValidator;

    public function __construct(JobTitleRepository $jobTitleRepository)
    {
        $this->jobTitleRepository = $jobTitleRepository;

        $this->jobTitleValidator = new JobTitleValidator($jobTitleRepository);
    }

    public function createJobTitle(array $jobTitle): array
    {
        $this->jobTitleValidator->setGroup('create');

        $this->jobTitleValidator->setData($jobTitle);

        $this->jobTitleValidator->validate([
            'title'        ,
            'department_id',
            'description'  ,
            'status'
        ]);

        $validationErrors = $this->jobTitleValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $departmentId = $jobTitle['department_id'];

        if (filter_var($departmentId, FILTER_VALIDATE_INT) !== false) {
            $departmentId = (int) $departmentId;
        }

        $newJobTitle = new JobTitle(
            id            : null                    ,
            title         : $jobTitle['title'      ],
            departmentId  : $departmentId           ,
            description   : $jobTitle['description'],
            status        : $jobTitle['status'     ]
        );

        $createJobTitleResult = $this->jobTitleRepository->createJobTitle($newJobTitle);

        if ($createJobTitleResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while creating the job title. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Job title created successfully.'
        ];
    }

    public function fetchAllJobTitles(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->jobTitleRepository->fetchAllJobTitles(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateJobTitle(array $jobTitle): array
    {
        $this->jobTitleValidator->setGroup('update');

        $this->jobTitleValidator->setData($jobTitle);

        $this->jobTitleValidator->validate([
            'id'           ,
            'title'        ,
            'department_id',
            'description'  ,
            'status'
        ]);

        $validationErrors = $this->jobTitleValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $jobTitleId   = $jobTitle['id'           ];
        $departmentId = $jobTitle['department_id'];

        if (filter_var($jobTitleId, FILTER_VALIDATE_INT) !== false) {
            $jobTitleId = (int) $jobTitleId;
        }

        if (filter_var($departmentId, FILTER_VALIDATE_INT) !== false) {
            $departmentId = (int) $departmentId;
        }

        $newJobTitle = new JobTitle(
            id            : $jobTitleId             ,
            title         : $jobTitle['title'      ],
            departmentId  : $departmentId           ,
            description   : $jobTitle['description'],
            status        : $jobTitle['status'     ]
        );

        $updateJobTitleResult = $this->jobTitleRepository->updateJobTitle($newJobTitle);

        if ($updateJobTitleResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while updating the job title. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Job title updated successfully.'
        ];
    }

    public function deleteJobTitle(mixed $jobTitleId): array
    {
        $this->jobTitleValidator->setGroup('delete');

        $this->jobTitleValidator->setData([
            'id' => $jobTitleId
        ]);

        $this->jobTitleValidator->validate([
            'id'
        ]);

        $validationErrors = $this->jobTitleValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        if (filter_var($jobTitleId, FILTER_VALIDATE_INT) !== false) {
            $jobTitleId = (int) $jobTitleId;
        }

        $deleteJobTitleResult = $this->jobTitleRepository->deleteJobTitle($jobTitleId);

        if ($deleteJobTitleResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while deleting the job title. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Job title deleted successfully.'
        ];
    }
}
