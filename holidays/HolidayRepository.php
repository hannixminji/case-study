<?php

require_once __DIR__ . '/HolidayDao.php';

class HolidayRepository
{
    private readonly HolidayDao $holidayDao;

    public function __construct(HolidayDao $holidayDao)
    {
        $this->holidayDao = $holidayDao;
    }

    public function createHoliday(Holiday $holiday): ActionResult
    {
        return $this->holidayDao->create($holiday);
    }

    public function fetchAllHolidays(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->holidayDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateHoliday(Holiday $holiday): ActionResult
    {
        return $this->holidayDao->update($holiday);
    }

    public function deleteHoliday(int $holidayId): ActionResult
    {
        return $this->holidayDao->delete($holidayId);
    }
}
