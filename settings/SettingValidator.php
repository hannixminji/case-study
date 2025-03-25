<?php

require_once __DIR__ . '/../includes/BaseValidator.php';

class SettingValidator extends BaseValidator
{
    public function __construct()
    {
    }

    public function validate(array $fieldsToValidate): void
    {
        foreach ($fieldsToValidate as $field) {
            if ( ! array_key_exists($field, $this->data)) {
                $this->errors[$field] = 'The ' . $field . ' field is missing.';
            } else {
                switch ($field) {
                    case 'id'           : $this->isValidId          ($this->data['id'           ]); break;
                    case 'setting_key'  : $this->isValidSettingKey  ($this->data['setting_key'  ]); break;
                    case 'setting_value': $this->isValidSettingValue($this->data['setting_value']); break;
                    case 'group_name'   : $this->isValidGroupName   ($this->data['group_name'   ]); break;
                }
            }
        }
    }

    public function isValidSettingKey(mixed $settingKey): bool
    {
        if ($settingKey === null) {
            $this->errors['setting_key'] = 'The setting key cannot be null.';

            return false;
        }

        if ( ! is_string($settingKey)) {
            $this->errors['setting_key'] = 'The setting key must be a string.';

            return false;
        }

        if (trim($settingKey) === '') {
            $this->errors['setting_key'] = 'The setting key cannot be empty.';

            return false;
        }

        $validConfigurations = [
            'minutes_can_check_in_before_shift',
            'grace_period'
        ];

        if ( ! in_array($settingKey, $validConfigurations)) {
            $this->errors['setting_key'] = 'This setting does not exist.';

            return false;
        }

        return true;
    }

    public function isValidSettingValue(mixed $settingValue): bool
    {
        if ($settingValue === null) {
            $this->errors['setting_value'] = 'Please enter a valid value.';

            return false;
        }

        if (is_string($settingValue) && trim($settingValue) === '') {
            $this->errors['setting_value'] = 'Please enter a valid value.';

            return false;
        }

        $settingValue = filter_var($settingValue, FILTER_VALIDATE_INT);

        if ($settingValue === false) {
            $this->errors['setting_value'] = 'Please enter a valid value.';

            return false;
        }

        if (array_key_exists('setting_key', $this->data) && ! isset($this->errors['setting_key'])) {
            if ($this->data['setting_key'] === 'grace_period' && ($settingValue < 0 || $settingValue > 30)) {
                $this->errors['setting_value'] = 'The grace period must be between 0 and 30 minutes.';

                return false;
            }

            if ($this->data['setting_key'] === 'minutes_can_check_in_before_shift' && ($settingValue < 0 || $settingValue > 120)) {
                $this->errors['setting_value'] = 'Please enter a value between 0 and 120 minutes for check-in before shift.';

                return false;
            }
        }

        return true;
    }

    public function isValidGroupName(mixed $groupName): bool
    {
        if ($groupName === null) {
            $this->errors['group_name'] = 'The group name cannot be null.';

            return false;
        }

        if ( ! is_string($groupName)) {
            $this->errors['group_name'] = 'The group name must be a string.';

            return false;
        }

        if (trim($groupName) === '') {
            $this->errors['group_name'] = 'The group name cannot be empty.';

            return false;
        }

        $validGroups = [
            'work_schedule'
        ];

        if ( ! in_array($groupName, $validGroups)) {
            $this->errors['group_name'] = 'This group does not exist.';

            return false;
        }

        return true;
    }
}
