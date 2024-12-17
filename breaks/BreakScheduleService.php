<?php

require_once __DIR__ . "/BreakScheduleDao.php";

class BreakScheduleService
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

    public function fetchAllBreakSchedules(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->breakScheduleDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateBreakSchedule(BreakSchedule $breakSchedule, bool $isHashedId = false): ActionResult
    {
        return $this->breakScheduleDao->update($breakSchedule, $isHashedId);
    }

    public function fetchOrderedBreakSchedules(int|string $workScheduleId, bool $isHashedId = false): ActionResult|array
    {
        return $this->breakScheduleDao->fetchOrderedBreakSchedules($workScheduleId, $isHashedId);
    }

    public function deleteBreakSchedule(int|string $breakScheduleId, bool $isHashedId = false): ActionResult
    {
        return $this->breakScheduleDao->delete($breakScheduleId, $isHashedId);
    }

    public function deleteBreakScheduleByWorkScheduleId(int|string $workScheduleId, bool $isHashedId = false): ActionResult
    {
        return $this->breakScheduleDao->deleteByWorkScheduleId($workScheduleId, $isHashedId);
    }
}
