<?php

require_once __DIR__ . '/WorkScheduleRepository.php';

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

    public function fetchWorkScheduleLastInsertedId(): ActionResult|int
    {
        return $this->workScheduleRepository->fetchWorkScheduleLastInsertedId();
    }

    public function updateWorkSchedule(WorkSchedule $workSchedule, bool $isHashedId = false): ActionResult
    {
        return $this->workScheduleRepository->updateWorkSchedule($workSchedule, $isHashedId);
    }

    public function getEmployeeWorkSchedules(
        int    $employeeId,
        string $startDate ,
        string $endDate
    ): ActionResult|array {
        return $this->workScheduleRepository->getEmployeeWorkSchedules($employeeId, $startDate, $endDate);
    }

    public function deleteWorkSchedule(int|string $workScheduleId, bool $isHashedId = false): ActionResult
    {
        return $this->workScheduleRepository->deleteWorkSchedule($workScheduleId, $isHashedId);
    }
}
