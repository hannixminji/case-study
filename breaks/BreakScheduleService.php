<?php

require_once __DIR__ . '/BreakScheduleRepository.php';

class BreakScheduleService
{
    private readonly BreakScheduleRepository $breakScheduleRepository;

    public function __construct(BreakScheduleRepository $breakScheduleRepository)
    {
        $this->breakScheduleRepository = $breakScheduleRepository;
    }

    public function createBreakSchedule(BreakSchedule $breakSchedule): ActionResult
    {
        return $this->breakScheduleRepository->createBreakSchedule($breakSchedule);
    }

    public function fetchAllBreakSchedules(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->breakScheduleRepository->fetchAllBreakSchedules(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateBreakSchedule(BreakSchedule $breakSchedule): ActionResult
    {
        return $this->breakScheduleRepository->updateBreakSchedule($breakSchedule);
    }

    public function deleteBreakSchedule(int|string $breakScheduleId): ActionResult
    {
        return $this->breakScheduleRepository->deleteBreakSchedule($breakScheduleId);
    }
}
