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
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): ActionResult|array {

        return $this->settingDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function fetchSettingValue(string $settingKey, string $groupName): ActionResult|string
    {
        $columns = [
            "setting_value"
        ];

        $filterCriteria = [
            [
                "column"   => "setting.setting_key",
                "operator" => "="                  ,
                "value"    => $settingKey
            ],
            [
                "column"   => "setting.group_name",
                "operator" => "="                 ,
                "value"    => $groupName
            ]
        ];

        $result = $this->fetchAllSettings(
            columns       : $columns       ,
            filterCriteria: $filterCriteria,
            limit         : 1
        );

        if ($result === ActionResult::FAILURE) {
            return ActionResult::FAILURE;
        }

        return $result["result_set"][0]["setting_value"];
    }

    public function updateSetting(Setting $setting): ActionResult
    {
        return $this->settingDao->update($setting);
    }
}
