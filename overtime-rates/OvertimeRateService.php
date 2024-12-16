<?php

require_once __DIR__ . '/OvertimeRateRepository.php';

class OvertimeRateService
{
    private readonly OvertimeRateRepository $overtimeRateRepository;

    public function __construct(OvertimeRateRepository $overtimeRateRepository)
    {
        $this->overtimeRateRepository = $overtimeRateRepository;
    }

    public function createOvertimeRate(OvertimeRate $overtimeRate): ActionResult
    {
        return $this->overtimeRateRepository->create($overtimeRate);
    }

    public function fetchOvertimeRates(int $overtimeRateAssignmentId, bool $isHashedId = false): ActionResult|array
    {
        return $this->overtimeRateRepository->fetchOvertimeRates($overtimeRateAssignmentId, $isHashedId);
    }

    public function updateOvertimeRate(OvertimeRate $overtimeRate, bool $isHashedId = false): ActionResult
    {
        return $this->overtimeRateRepository->update($overtimeRate, $isHashedId);
    }
}
