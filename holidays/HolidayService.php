<?php

require_once __DIR__ . '/HolidayRepository.php';

class HolidayService
{
    private readonly HolidayRepository $holidayRepository;

    public function __construct(HolidayRepository $holidayRepository)
    {
        $this->holidayRepository = $holidayRepository;
    }

    public function createHoliday(Holiday $holiday): ActionResult
    {
        return $this->holidayRepository->createHoliday($holiday);
    }

    public function fetchAllHolidays(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->holidayRepository->fetchAllHolidays($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateHoliday(Holiday $holiday): ActionResult
    {
        return $this->holidayRepository->updateHoliday($holiday);
    }

    public function deleteHoliday(int $holidayId): ActionResult
    {
        return $this->holidayRepository->deleteHoliday($holidayId);
    }
}
