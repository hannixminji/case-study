<?php

require_once __DIR__ . '/SettingRepository.php';

class SettingService
{
    private readonly SettingRepository $settingRepository;

    public function __construct(SettingRepository $settingRepository)
    {
        $this->settingRepository = $settingRepository;
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

    public function updateSetting(Setting $setting): ActionResult
    {
        return $this->settingRepository->updateSetting($setting);
    }
}
