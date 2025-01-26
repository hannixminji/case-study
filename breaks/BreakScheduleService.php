<?php

require_once __DIR__ . "/BreakScheduleRepository.php";

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
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->breakScheduleRepository->fetchAllBreakSchedules($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateBreakSchedule(BreakSchedule $breakSchedule, bool $isHashedId = false): ActionResult
    {
        return $this->breakScheduleRepository->updateBreakSchedule($breakSchedule, $isHashedId);
    }

    public function fetchOrderedBreakSchedules(int|string $workScheduleId, bool $isHashedId = false): ActionResult|array
    {
        return $this->breakScheduleRepository->fetchOrderedBreakSchedules($workScheduleId, $isHashedId);
    }

    public function deleteBreakSchedule(int|string $breakScheduleId, bool $isHashedId = false): ActionResult
    {
        return $this->breakScheduleRepository->deleteBreakSchedule($breakScheduleId, $isHashedId);
    }

    public function deleteBreakScheduleByWorkScheduleId(int|string $workScheduleId, bool $isHashedId = false): ActionResult
    {
        return $this->breakScheduleRepository->deleteBreakScheduleByWorkScheduleId($workScheduleId, $isHashedId);
    }
}
