<?php

require_once __DIR__ . '/../includes/BaseValidator.php';

class AttendanceValidator extends BaseValidator
{
    public function __construct()
    {
    }

    public function validate(array $fieldsToValidate): void
    {
        $this->errors = [];

        foreach ($fieldsToValidate as $field) {
            if ( ! array_key_exists($field, $this->data)) {
                $this->errors[$field] = 'The ' . $field . ' field is missing.';
            } else {
                switch ($field) {
                    case 'id'            : $this->isValidId      ($this->data['id'            ]                                    ); break;
                    case 'rfid_uid'      : $this->isValidRfidUid ($this->data['rfid_uid'      ]                                    ); break;
                    case 'current_time'  : $this->isValidDateTime($this->data['current_time'  ], 'current_time'  , 'current time'  ); break;
                    case 'check_in_time' : $this->isValidDateTime($this->data['check_in_time' ], 'check_in_time' , 'check in time' ); break;
                    case 'check_out_time': $this->isValidDateTime($this->data['check_out_time'], 'check_out_time', 'check out time'); break;
                }
            }
        }
    }

    public function isValidRfidUid(mixed $rfidUid): bool
    {
        if ($rfidUid === null) {
            $this->errors['rfid_uid'] = 'The RFID UID cannot be null.';

            return false;
        }

        if ( ! is_string($rfidUid)) {
            $this->errors['rfid_uid'] = 'The RFID UID must be a string.';

            return false;
        }

        if (trim($rfidUid) === '') {
            $this->errors['rfid_uid'] = 'The RFID UID cannot be empty.';

            return false;
        }

        if (mb_strlen($rfidUid) < 8 || mb_strlen($rfidUid) > 32) {
            $this->errors['rfid_uid'] = 'The RFID UID must be between 8 and 32 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Fa-f0-9]+$/', $rfidUid)) {
            $this->errors['rfid_uid'] = 'The RFID UID contains invalid characters. Only hexadecimal characters from 0 to 9 and A to F are allowed.';

            return false;
        }

        return true;
    }

    public function isValidDateTime(mixed $dateTime, string $keyName, string $fieldName): bool {
        if ($dateTime === null) {
            $this->errors[$keyName] = 'The ' . $fieldName . ' cannot be null.';

            return false;
        }

        if ( ! is_string($dateTime)) {
            $this->errors[$keyName] = 'The ' . $fieldName . ' must be a string.';

            return false;
        }

        if (trim($dateTime) === '') {
            $this->errors[$keyName] = 'The ' . $fieldName . ' cannot be empty.';

            return false;
        }

        $time = DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);

        if ($dateTime === false || $time->format('Y-m-d H:i:s') !== $dateTime) {
            $this->errors[$keyName] = 'The ' . $fieldName . ' must be in the Y-m-d H:i:s format and be a valid date and time, e.g., 2025-01-01 14:30:00.';

            return false;
        }

        return true;
    }
}
