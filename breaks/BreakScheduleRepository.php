<?php

require_once __DIR__ . "/BreakScheduleDao.php";

class BreakScheduleRepository
{
    private readonly BreakScheduleDao $breakScheduleDao;

    public function __construct(BreakScheduleDao $breakScheduleDao)
    {
        $this->breakScheduleDao = $breakScheduleDao;
    }

    public function createBreakSchedule(BreakSchedule $breakSchedule): ActionResult
    {
        return $this->breakScheduleDao->create($breakSchedule);
    }

    public function createBreakScheduleHistory(BreakSchedule $breakSchedule): ActionResult
    {
        return $this->breakScheduleDao->createHistory($breakSchedule);
    }

    public function fetchAllBreakSchedules(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->breakScheduleDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function fetchLatestBreakScheduleHistoryId(int $breakScheduleId): int|ActionResult
    {
        return $this->breakScheduleDao->fetchLatestHistoryId($breakScheduleId);
    }

    public function fetchLatestBreakScheduleHistory(int $breakScheduleId): array|ActionResult
    {
        return $this->breakScheduleDao->fetchLatestHistory($breakScheduleId);
    }

    public function updateBreakSchedule(BreakSchedule $breakSchedule, bool $isHashedId = false): ActionResult
    {
        return $this->$breakSchedule->update($breakSchedule, $isHashedId);
    }

    public function deleteBreakSchedule(int|string $breakScheduleId, bool $isHashedId = false): ActionResult
    {
        return $this->breakScheduleDao->delete($breakScheduleId, $isHashedId);
    }
}
