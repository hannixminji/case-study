<?php

require_once __DIR__ . '/OvertimeRateDao.php';

class OvertimeRateRepository
{
    private readonly OvertimeRateDao $overtimeRateDao;

    public function __construct(OvertimeRateDao $overtimeRateDao)
    {
        $this->overtimeRateDao = $overtimeRateDao;
    }

    public function createOvertimeRate(OvertimeRate $overtimeRate): ActionResult
    {
        return $this->overtimeRateDao->create($overtimeRate);
    }

    public function fetchOvertimeRates(int $overtimeRateAssignmentId): array|ActionResult
    {
        return $this->overtimeRateDao->fetchOvertimeRates($overtimeRateAssignmentId);
    }

    public function updateOvertimeRate(OvertimeRate $overtimeRate): ActionResult
    {
        return $this->overtimeRateDao->update($overtimeRate);
    }
}
