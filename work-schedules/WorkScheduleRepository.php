<?php

require_once __DIR__ . '/WorkScheduleDao.php';

class WorkScheduleRepository
{
    private readonly WorkScheduleDao $workScheduleDao;

    public function __construct(WorkScheduleDao $workScheduleDao)
    {
        $this->workScheduleDao = $workScheduleDao;
    }

    public function createWorkSchedule(WorkSchedule $workSchedule): ActionResult
    {
        return $this->workScheduleDao->create($workSchedule);
    }

    public function createWorkScheduleSnapshot(WorkScheduleSnapshot $workScheduleSnapshot): int|ActionResult
    {
        return $this->workScheduleDao->createSnapshot($workScheduleSnapshot);
    }

    public function fetchAllWorkSchedules(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->workScheduleDao->fetchAll(
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
        return $this->workScheduleDao->fetchLatestSnapshotById($workScheduleId);
    }

    public function updateWorkSchedule(WorkSchedule $workSchedule): ActionResult
    {
        return $this->workScheduleDao->update($workSchedule);
    }

    public function getRecurrenceDates(
        string $recurrenceRule,
        string $startDate     ,
        string $endDate
    ): array|ActionResult {

        return $this->workScheduleDao->getRecurrenceDates(
            recurrenceRule: $recurrenceRule,
            startDate     : $startDate     ,
            endDate       : $endDate
        );
    }

    public function deleteWorkSchedule(int|string $workScheduleId): ActionResult
    {
        return $this->workScheduleDao->delete($workScheduleId);
    }
}
