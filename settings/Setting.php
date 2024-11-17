<?php

class Setting
{
    public function __construct(
        private readonly ?int   $id          ,
        private readonly string $settingKey  ,
        private readonly string $settingValue,
        private readonly string $groupName
    ) {
    }

    public function getId(): ?int
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
