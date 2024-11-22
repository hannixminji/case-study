<?php

require_once __DIR__ . "/OvertimeRateRepository.php";

class OvertimeRateService
{
    private readonly OvertimeRateRepository $overtimeRateRepository;

    public function __construct(OvertimeRateRepository $overtimeRateRepository)
    {
        $this->overtimeRateRepository = $overtimeRateRepository;
    }

    public function createOvertimeRate(OvertimeRate $overtimeRate, int $overtimeRateAssignmentId): array
    {
        $result = $this->overtimeRateRepository->create($overtimeRate, $overtimeRateAssignmentId);

        if ($result === ActionResult::SUCCESS) {
            return [
                'status'  => 'success',
                'message' => 'Overtime rate created successfully.'
            ];
        }

        return [
            'status'  => 'error',
            'message' => 'Failed to create the overtime rate.'
        ];
    }

    public function getOvertimeRates(int $overtimeRateAssignmentId): array
    {
        $result = $this->overtimeRateRepository->fetchOvertimeRates($overtimeRateAssignmentId);

        if ($result === ActionResult::SUCCESS) {
            return [
                'status'  => 'success'                             ,
                'message' => 'Overtime rates fetched successfully.',
                'data'    => $result
            ];
        }

        return [
            'status'  => 'error',
            'message' => 'Failed to fetch overtime rates.'
        ];
    }

    public function updateOvertimeRate(OvertimeRate $overtimeRate): array
    {
        $result = $this->overtimeRateRepository->update($overtimeRate);

        if ($result === ActionResult::SUCCESS) {
            return [
                'status'  => 'success',
                'message' => 'Overtime rate updated successfully.'
            ];
        }

        return [
            'status'  => 'error',
            'message' => 'Failed to update the overtime rate.'
        ];
    }
}
