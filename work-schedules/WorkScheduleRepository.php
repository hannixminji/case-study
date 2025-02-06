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

    public function createWorkScheduleHistory(WorkSchedule $workSchedule): ActionResult
    {
        return $this->workScheduleDao->createHistory($workSchedule);
    }

    public function fetchAllWorkSchedules(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->workScheduleDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function fetchLatestWorkScheduleHistoryId(int $workScheduleId): int|null|ActionResult
    {
        return $this->workScheduleDao->fetchLatestHistoryId($workScheduleId);
    }

    public function fetchWorkScheduleLastInsertedId(): ActionResult|int
    {
        return $this->workScheduleDao->fetchLastInsertedId();
    }

    public function fetchLatestWorkScheduleHistory(int $workScheduleId): array|ActionResult
    {
        return $this->workScheduleDao->fetchLatestHistory($workScheduleId);
    }

    public function updateWorkSchedule(WorkSchedule $workSchedule, bool $isHashedId = false): ActionResult
    {
        return $this->workScheduleDao->update($workSchedule, $isHashedId);
    }

    public function getRecurrenceDates(string $recurrenceRule, string $startDate, string $endDate): array
    {
        return $this->workScheduleDao->getRecurrenceDates($recurrenceRule, $startDate, $endDate);
    }

    public function deleteWorkSchedule(int|string $workScheduleId, bool $isHashedId = false): ActionResult
    {
        return $this->workScheduleDao->delete($workScheduleId, $isHashedId);
    }
}
