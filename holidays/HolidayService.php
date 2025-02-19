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
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->holidayRepository->fetchAllHolidays(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateHoliday(Holiday $holiday): ActionResult
    {
        return $this->holidayRepository->updateHoliday($holiday);
    }

    public function getHolidayDatesForPeriod(string $startDate, string $endDate): array|ActionResult
    {
        return $this->holidayRepository->getHolidayDatesForPeriod($startDate, $endDate);
    }

    public function deleteHoliday(int|string $holidayId): ActionResult
    {
        return $this->holidayRepository->deleteHoliday($holidayId);
    }
}
