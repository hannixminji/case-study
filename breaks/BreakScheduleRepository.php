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

    public function createBreakScheduleSnapshot(BreakScheduleSnapshot $breakScheduleSnapshot): int|ActionResult
    {
        return $this->breakScheduleDao->createSnapshot($breakScheduleSnapshot);
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

    public function fetchLatestBreakScheduleSnapshotById(int $breakScheduleId): array|ActionResult
    {
        return $this->breakScheduleDao->fetchLatestSnapshotById($breakScheduleId);
    }

    public function updateBreakSchedule(BreakSchedule $breakSchedule): ActionResult
    {
        return $this->breakScheduleDao->update($breakSchedule);
    }

    public function deleteBreakSchedule(int|string $breakScheduleId): ActionResult
    {
        return $this->breakScheduleDao->delete($breakScheduleId);
    }
}
