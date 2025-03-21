<?php

class Setting
{
    public function __construct(
        private readonly null|int|string $id          ,
        private readonly string          $settingKey  ,
        private readonly string          $settingValue,
        private readonly string          $groupName
    ) {
    }

    public function getId(): null|int|string
    {
        return $this->id;
    }

    public function getSettingKey(): string
    {
        return $this->settingKey;
    }

    public function getSettingValue(): string
    {
        return $this->settingValue;
    }

    public function getGroupName(): string
    {
        return $this->groupName;
    }
}
