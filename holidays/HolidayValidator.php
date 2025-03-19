<?php

require_once __DIR__ . '/../includes/BaseValidator.php';

class HolidayValidator extends BaseValidator
{
    private readonly HolidayRepository $holidayRepository;

    public function __construct(HolidayRepository $holidayRepository)
    {
        $this->holidayRepository = $holidayRepository;
    }

    public function validate(array $fieldsToValidate): void
    {
        $this->errors = [];

        foreach ($fieldsToValidate as $field) {
            if ( ! array_key_exists($field, $this->data)) {
                $this->errors[$field] = 'The ' . $field . ' field is missing.';
            } else {
                switch ($field) {
                    case 'id'                : $this->isValidId              ($this->data['id'                ]                           ); break;
                    case 'description'       : $this->isValidDescription     ($this->data['description'       ]                           ); break;
                    case 'status'            : $this->isValidStatus          ($this->data['status'            ]                           ); break;
                }
            }
        }
    }
}
