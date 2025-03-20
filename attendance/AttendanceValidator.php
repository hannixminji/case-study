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
                }
            }
        }
    }

    public function isValidRfidUid(mixed $rfidUid): bool
    {
        if ( ! is_string($rfidUid)) {
            $this->errors['rfid_uid'] = 'The RFID UID must be a string.';

            return false;
        }

        $rfidUid = trim($rfidUid);

        if ($rfidUid === '') {
            $this->errors['rfid_uid'] = 'The RFID UID cannot be empty.';

            return false;
        }

        if (strlen($rfidUid) < 8 || strlen($rfidUid) > 32) {
            $this->errors['rfid_uid'] = 'The RFID UID must be between 8 and 32 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Fa-f0-9]+$/', $rfidUid)) {
            $this->errors['rfid_uid'] = 'The RFID UID must contain only hexadecimal characters (0-9, A-F).';

            return false;
        }

        if ($rfidUid !== htmlspecialchars(strip_tags($rfidUid), ENT_QUOTES, 'UTF-8')) {
            $this->errors['rfid_uid'] = 'The RFID UID contains invalid characters.';

            return false;
        }

        return true;
    }

    public function isValidDateTime(): bool
    {
        return true;
    }
}
