<?php

require_once __DIR__ . '/WorkSchedule.php'                            ;
require_once __DIR__ . '/WorkScheduleRepository.php'                  ;
require_once __DIR__ . '/../includes/enums/WorkScheduleEditOption.php';

class WorkScheduleService
{
    private readonly WorkScheduleRepository $workScheduleRepository;

    public function __construct(WorkScheduleRepository $workScheduleRepository)
    {
        $this->workScheduleRepository = $workScheduleRepository;
    }

    public function createWorkSchedule(WorkSchedule $workSchedule): ActionResult
    {
        return $this->workScheduleRepository->createWorkSchedule($workSchedule);
    }

    public function fetchAllWorkSchedules(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->workScheduleRepository->fetchAllWorkSchedules($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateWorkSchedule(WorkSchedule $workSchedule, WorkScheduleEditOption $editOption): ActionResult
    {
        switch ($editOption) {
            case WorkScheduleEditOption::EDIT_THIS_ONLY    :
                $clonedWorkSchedule = clone $workSchedule;
                $clonedWorkSchedule->setRecurrenceRule("FREQ=DAILY;INTERVAL=1;DSTART={}");

                break;

            case WorkScheduleEditOption::EDIT_ALL_FUTURE   :
                break;

            case WorkScheduleEditOption::EDIT_ALL_SCHEDULES:
                break;

            default:
                // Do nothing
        }

        return $this->workScheduleRepository->updateWorkSchedule($workSchedule);
    }

    public function deleteWorkSchedule(int $workScheduleId): ActionResult
    {
        return $this->workScheduleRepository->deleteWorkSchedule($workScheduleId);
    }

    public function getEmployeeWorkSchedules(
        int    $employeeId,
        string $startDate ,
        string $endDate
    ): ActionResult|array {
        return $this->workScheduleRepository->getEmployeeWorkSchedules($employeeId, $startDate, $endDate);
    }
}
