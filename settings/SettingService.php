<?php

require_once __DIR__ . '/SettingRepository.php';

require_once __DIR__ . '/SettingValidator.php' ;

class SettingService
{
    private readonly SettingRepository $settingRepository;

    private readonly SettingValidator $settingValidator;

    public function __construct(SettingRepository $settingRepository)
    {
        $this->settingRepository = $settingRepository;

        $this->settingValidator = new SettingValidator();
    }

    public function fetchAllSettings(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->settingRepository->fetchAllSettings(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function fetchSettingValue(string $settingKey, string $groupName): string|ActionResult
    {
        return $this->settingRepository->fetchSettingValue($settingKey, $groupName);
    }

    public function updateSetting(array $setting): array
    {
        $this->settingValidator->setGroup('update');

        $this->settingValidator->setData($setting);

        $this->settingValidator->validate([
            'id'           ,
            'setting_key'  ,
            'setting_value',
            'group_name'
        ]);

        $validationErrors = $this->settingValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $settingId = $setting['id'];

        if (filter_var($settingId, FILTER_VALIDATE_INT) !== false) {
            $settingId = (int) $settingId;
        }

        $newSetting = new Setting(
            id          :       $settingId               ,
            settingKey  :       $setting['setting_key'  ],
            settingValue: (int) $setting['setting_value'],
            groupName   :       $setting['group_name'   ]
        );

        $updateSettingResult = $this->settingRepository->updateSetting($newSetting);

        if ($updateSettingResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while updating the setting. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Setting updated successfully.'
        ];
    }
}
