<?php

require_once __DIR__ . '/HolidayRepository.php';

require_once __DIR__ . '/HolidayValidator.php' ;

class HolidayService
{
    private readonly HolidayRepository $holidayRepository;

    private readonly HolidayValidator $holidayValidator;

    public function __construct(HolidayRepository $holidayRepository)
    {
        $this->holidayRepository = $holidayRepository;

        $this->holidayValidator = new HolidayValidator($holidayRepository);
    }

    public function createHoliday(array $holiday): array
    {
        $this->holidayValidator->setGroup('create');

        $this->holidayValidator->setData($holiday);

        $this->holidayValidator->validate([
            'name'                 ,
            'start_date'           ,
            'end_date'             ,
            'is_paid'              ,
            'is_recurring_annually',
            'description'          ,
            'status'
        ]);

        $validationErrors = $this->holidayValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $newHoliday = new Holiday(
            id                 :        null                             ,
            name               :        $holiday['name'                 ],
            startDate          :        $holiday['start_date'           ],
            endDate            :        $holiday['end_date'             ],
            isPaid             : (bool) $holiday['is_paid'              ],
            isRecurringAnnually: (bool) $holiday['is_recurring_annually'],
            description        :        $holiday['description'          ],
            status             :        $holiday['status'               ]
        );

        $createHolidayResult = $this->holidayRepository->createHoliday($newHoliday);

        if ($createHolidayResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while creating the holiday. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Holiday created successfully.'
        ];
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

    public function updateHoliday(array $holiday): array
    {
        $this->holidayValidator->setGroup('update');

        $this->holidayValidator->setData($holiday);

        $this->holidayValidator->validate([
            'id'                   ,
            'name'                 ,
            'start_date'           ,
            'end_date'             ,
            'is_paid'              ,
            'is_recurring_annually',
            'description'          ,
            'status'
        ]);

        $validationErrors = $this->holidayValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $holidayId = $holiday['id'];

        if (filter_var($holidayId, FILTER_VALIDATE_INT) !== false) {
            $holidayId = (int) $holidayId;
        }

        $newHoliday = new Holiday(
            id                 :        $holidayId                       ,
            name               :        $holiday['name'                 ],
            startDate          :        $holiday['start_date'           ],
            endDate            :        $holiday['end_date'             ],
            isPaid             : (bool) $holiday['is_paid'              ],
            isRecurringAnnually: (bool) $holiday['is_recurring_annually'],
            description        :        $holiday['description'          ],
            status             :        $holiday['status'               ]
        );

        $updateHolidayResult = $this->holidayRepository->updateHoliday($newHoliday);

        if ($updateHolidayResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while updating the holiday. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Holiday updated successfully.'
        ];
    }

    public function getHolidayDatesForPeriod(string $startDate, string $endDate): array|ActionResult
    {
        return $this->holidayRepository->getHolidayDatesForPeriod($startDate, $endDate);
    }

    public function deleteHoliday(mixed $holidayId): array
    {
        $this->holidayValidator->setGroup('delete');

        $this->holidayValidator->setData([
            'id' => $holidayId
        ]);

        $this->holidayValidator->validate([
            'id'
        ]);

        $validationErrors = $this->holidayValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        if (filter_var($holidayId, FILTER_VALIDATE_INT) !== false) {
            $holidayId = (int) $holidayId;
        }

        $deleteHolidayResult = $this->holidayRepository->deleteHoliday($holidayId);

        if ($deleteHolidayResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while deleting the holiday. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Holiday deleted successfully.'
        ];
    }
}
