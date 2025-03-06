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

    public function updateWorkSchedule(WorkSchedule $workSchedule): ActionResult
    {
        return $this->workScheduleRepository->updateWorkSchedule($workSchedule);
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

    public function deleteWorkSchedule(int|string $workScheduleId): ActionResult
    {
        return $this->workScheduleRepository->deleteWorkSchedule($workScheduleId);
    }
}
