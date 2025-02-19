<?php

require_once __DIR__ . '/OvertimeRateAssignmentRepository.php';

class OvertimeRateAssignmentService
{
    private readonly OvertimeRateAssignmentRepository $overtimeRateAssignmentRepository;

    public function __construct(OvertimeRateAssignmentRepository $overtimeRateAssignmentRepository)
    {
        $this->overtimeRateAssignmentRepository = $overtimeRateAssignmentRepository;
    }

    public function createOvertimeRateAssignment(OvertimeRateAssignment $overtimeRateAssignment): int|ActionResult
    {
        return $this->overtimeRateAssignmentRepository->createOvertimeRateAssignment($overtimeRateAssignment);
    }

    public function assignOvertimeRateAssignment(OvertimeRateAssignment $overtimeRateAssignment, array $overtimeRates): ActionResult
    {
        return $this->overtimeRateAssignmentRepository->assignOvertimeRateAssignment($overtimeRateAssignment, $overtimeRates);
    }

    public function findOvertimeRateAssignmentId(OvertimeRateAssignment $overtimeRateAssignment): int|ActionResult
    {
        return $this->overtimeRateAssignmentRepository->findOvertimeRateAssignmentId($overtimeRateAssignment);
    }
}
