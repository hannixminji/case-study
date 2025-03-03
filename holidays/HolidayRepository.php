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
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->holidayDao->fetchAll(
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
        return $this->holidayDao->update($holiday);
    }

    public function getHolidayDatesForPeriod(string $startDate, string $endDate): array|ActionResult
    {
        $columns = [
            'start_date'           ,
            'end_date'             ,
            'is_paid'              ,
            'is_recurring_annually',
            'created_at'
        ];

        $filterCriteria = [
            [
                'column'   => 'holiday.status',
                'operator' => '='             ,
                'value'    => "Active"
            ],
            [
                'column'   => 'holiday.start_date',
                'operator' => '<='                ,
                'value'    => $endDate
            ],
            [
                'column'   => 'holiday.end_date',
                'operator' => '>='              ,
                'value'    => $startDate
            ]
        ];

        $holidays = $this->holidayDao->fetchAll($columns, $filterCriteria);

        if ($holidays === ActionResult::FAILURE) {
            return ActionResult::FAILURE;
        }

        $holidays = $holidays['result_set'];

        $datesMarkedAsHoliday = [];

        $startDate = new DateTime($startDate);
        $endDate   = new DateTime($endDate  );
        $period    = new DatePeriod($startDate, new DateInterval('P1D'), $endDate->modify('+1 day'));

        foreach ($period as $date) {
            $datesMarkedAsHoliday[$date->format('Y-m-d')] = [];
        }

        foreach ($holidays as $holiday) {
            $holidayStartDate = new DateTime($holiday['start_date']);
            $holidayEndDate   = new DateTime($holiday['end_date'  ]);
            $holidayPeriod    = new DatePeriod($holidayStartDate, new DateInterval('P1D'), $holidayEndDate->modify('+1 day'));

            if ($holiday['is_recurring_annually']) {
                foreach ($holidayPeriod as $holidayDate) {
                    foreach ($period as $date) {
                        if ($date->format('m-d') === $holidayDate->format('m-d')) {
                            if (isset($datesMarkedAsHoliday[$date->format('Y-m-d')])) {
                                $datesMarkedAsHoliday[$date->format('Y-m-d')][] = [
                                    'is_paid'    => $holiday['is_paid'   ],
                                    'created_at' => $holiday['created_at']
                                ];
                            }
                        }
                    }
                }

            } else {
                foreach ($holidayPeriod as $holidayDate) {
                    if (isset($datesMarkedAsHoliday[$holidayDate->format('Y-m-d')])) {
                        $datesMarkedAsHoliday[$holidayDate->format('Y-m-d')][] = [
                            'is_paid'    => $holiday['is_paid'   ],
                            'created_at' => $holiday['created_at']
                        ];
                    }
                }
            }
        }

        return $datesMarkedAsHoliday;
    }

    public function deleteHoliday(int|string $holidayId): ActionResult
    {
        return $this->holidayDao->delete($holidayId);
    }
}
