<?php

require_once __DIR__ . '/OvertimeRateDao.php';

class OvertimeRateRepository
{
    private readonly OvertimeRateDao $overtimeRateDao;

    public function __construct(OvertimeRateDao $overtimeRateDao)
    {
        $this->overtimeRateDao = $overtimeRateDao;
    }

    public function create(OvertimeRate $overtimeRate): ActionResult
    {
        return $this->overtimeRateDao->create($overtimeRate);
    }

    public function fetchOvertimeRates(int $overtimeRateAssignmentId, bool $isHashedId = false): ActionResult|array
    {
        return $this->overtimeRateDao->fetchOvertimeRates($overtimeRateAssignmentId, $isHashedId);
    }

    public function update(OvertimeRate $overtimeRate, bool $isHashedId = false): ActionResult
    {
        return $this->overtimeRateDao->update($overtimeRate, $isHashedId);
    }
}
