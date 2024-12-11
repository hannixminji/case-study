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
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->settingRepository->fetchAllSettings($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function fetchSettingValue(string $settingKey, string $groupName): ActionResult|string
    {
        return $this->settingRepository->fetchSettingValue($settingKey, $groupName);
    }

    public function updateSetting(Setting $setting): ActionResult
    {
        return $this->settingRepository->updateSetting($setting);
    }
}
