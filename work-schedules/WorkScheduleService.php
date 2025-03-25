<?php

require_once __DIR__ . '/WorkScheduleRepository.php';

require_once __DIR__ . '/WorkScheduleValidator.php' ;

class WorkScheduleService
{
    private readonly WorkScheduleRepository $workScheduleRepository;

    private readonly WorkScheduleValidator $workScheduleValidator;

    public function __construct(WorkScheduleRepository $workScheduleRepository)
    {
        $this->workScheduleRepository = $workScheduleRepository;

        $this->workScheduleValidator = new WorkScheduleValidator($workScheduleRepository);
    }

    public function createWorkSchedule(array $workSchedule): array
    {
        $this->workScheduleValidator->setGroup('create');

        $this->workScheduleValidator->setData($workSchedule);

        $this->workScheduleValidator->validate([
            'employee_id'         ,
            'start_time'          ,
            'end_time'            ,
            'is_flextime'         ,
            'total_hours_per_week',
            'total_work_hours'    ,
            'start_date'          ,
            'recurrence_rule'
        ]);

        $validationErrors = $this->workScheduleValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $employeeId        = filter_var($workSchedule['employee_id'         ], FILTER_VALIDATE_INT    );
        $isFlextime        = filter_var($workSchedule['is_flextime'         ], FILTER_VALIDATE_BOOLEAN);
        $totalHoursPerWeek = filter_var($workSchedule['total_hours_per_week'], FILTER_VALIDATE_FLOAT  );
        $totalWorkHours    = filter_var($workSchedule['total_work_hours'    ], FILTER_VALIDATE_FLOAT  );

        if ($totalHoursPerWeek === false) {
            $totalHoursPerWeek = null;
        }

        $newWorkSchedule = new WorkSchedule(
            id                : null                            ,
            employeeId        : $employeeId                     ,
            startTime         : $workSchedule['start_time'     ],
            endTime           : $workSchedule['end_time'       ],
            isFlextime        : $isFlextime                     ,
            totalHoursPerWeek : $totalHoursPerWeek              ,
            totalWorkHours    : $totalWorkHours                 ,
            startDate         : $workSchedule['start_date'     ],
            recurrenceRule    : $workSchedule['recurrence_rule']
        );

        $createWorkScheduleResult = $this->workScheduleRepository->createWorkSchedule($newWorkSchedule);

        if ($createWorkScheduleResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while creating the work schedule. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Work schedule created successfully.'
        ];
    }

    public function createWorkScheduleSnapshot(WorkScheduleSnapshot $workScheduleSnapshot): int|ActionResult
    {
        return $this->workScheduleRepository->createWorkScheduleSnapshot($workScheduleSnapshot);
    }

    public function fetchAllWorkSchedules(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->workScheduleRepository->fetchAllWorkSchedules(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function fetchLatestWorkScheduleSnapshotById(int $workScheduleId): array|ActionResult
    {
        return $this->workScheduleRepository->fetchLatestWorkScheduleSnapshotById($workScheduleId);
    }

    public function updateWorkSchedule(array $workSchedule): array
    {
        $this->workScheduleValidator->setGroup('update');

        $this->workScheduleValidator->setData($workSchedule);

        $this->workScheduleValidator->validate([
            'id'                  ,
            'employee_id'         ,
            'start_time'          ,
            'end_time'            ,
            'is_flextime'         ,
            'total_hours_per_week',
            'total_work_hours'    ,
            'start_date'          ,
            'recurrence_rule'
        ]);

        $validationErrors = $this->workScheduleValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $workScheduleId    = filter_var($workSchedule['id'                  ], FILTER_VALIDATE_INT    );
        $employeeId        = filter_var($workSchedule['employee_id'         ], FILTER_VALIDATE_INT    );
        $isFlextime        = filter_var($workSchedule['is_flextime'         ], FILTER_VALIDATE_BOOLEAN);
        $totalHoursPerWeek = filter_var($workSchedule['total_hours_per_week'], FILTER_VALIDATE_FLOAT  );
        $totalWorkHours    = filter_var($workSchedule['total_work_hours'    ], FILTER_VALIDATE_FLOAT  );

        if ($workScheduleId === false) {
            $workScheduleId = $workSchedule['id'];
        }

        if ($totalHoursPerWeek === false) {
            $totalHoursPerWeek = null;
        }

        $newWorkSchedule = new WorkSchedule(
            id                : $workScheduleId                 ,
            employeeId        : $employeeId                     ,
            startTime         : $workSchedule['start_time'     ],
            endTime           : $workSchedule['end_time'       ],
            isFlextime        : $isFlextime                     ,
            totalHoursPerWeek : $totalHoursPerWeek              ,
            totalWorkHours    : $totalWorkHours                 ,
            startDate         : $workSchedule['start_date'     ],
            recurrenceRule    : $workSchedule['recurrence_rule']
        );

        $updateWorkScheduleResult = $this->workScheduleRepository->updateWorkSchedule($newWorkSchedule);

        if ($updateWorkScheduleResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while updating the work schedule. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Work schedule updated successfully.'
        ];
    }

    public function getRecurrenceDates(
        string $recurrenceRule,
        string $startDate     ,
        string $endDate
    ): array|ActionResult {

        return $this->workScheduleRepository->getRecurrenceDates(
            recurrenceRule: $recurrenceRule,
            startDate     : $startDate     ,
            endDate       : $endDate
        );
    }

    public function deleteWorkSchedule(mixed $workScheduleId): array
    {
        $this->workScheduleValidator->setGroup('delete');

        $this->workScheduleValidator->setData([
            'id' => $workScheduleId
        ]);

        $this->workScheduleValidator->validate([
            'id'
        ]);

        $validationErrors = $this->workScheduleValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        if (filter_var($workScheduleId, FILTER_VALIDATE_INT) !== false) {
            $workScheduleId = (int) $workScheduleId;
        }

        $deleteWorkScheduleResult = $this->workScheduleRepository->deleteWorkSchedule($workScheduleId);

        if ($deleteWorkScheduleResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while deleting the work schedule. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Work schedule deleted successfully.'
        ];
    }
}
