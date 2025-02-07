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

    public function createWorkScheduleHistory(WorkSchedule $workSchedule): ActionResult
    {
        return $this->workScheduleRepository->createWorkScheduleHistory($workSchedule);
    }

    public function fetchAllWorkSchedules(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): ActionResult|array {

        return $this->workScheduleRepository->fetchAllWorkSchedules(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function fetchLatestWorkScheduleHistoryId(int $workScheduleId): int|null|ActionResult
    {
        return $this->workScheduleRepository->fetchLatestWorkScheduleHistoryId($workScheduleId);
    }

    public function fetchWorkScheduleLastInsertedId(): ActionResult|int
    {
        return $this->workScheduleRepository->fetchWorkScheduleLastInsertedId();
    }

    public function updateWorkSchedule(WorkSchedule $workSchedule, bool $isHashedId = false): ActionResult
    {
        return $this->workScheduleRepository->updateWorkSchedule($workSchedule, $isHashedId);
    }

    public function deleteWorkSchedule(int|string $workScheduleId, bool $isHashedId = false): ActionResult
    {
        return $this->workScheduleRepository->deleteWorkSchedule($workScheduleId, $isHashedId);
    }
}
