<?php

require_once __DIR__ . '/SettingDao.php';

class SettingRepository
{
    private readonly SettingDao $settingDao;

    public function __construct(SettingDao $settingDao)
    {
        $this->settingDao = $settingDao;
    }

    public function fetchAllSettings(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->settingDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function fetchSettingValue(string $settingKey, string $groupName): ActionResult|string
    {
        return $this->settingDao->fetchSettingValue($settingKey, $groupName);
    }

    public function updateSetting(Setting $setting): ActionResult
    {
        return $this->settingDao->update($setting);
    }
}
