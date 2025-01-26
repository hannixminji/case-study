<?php

require_once __DIR__ . '/OvertimeRateAssignmentRepository.php';

class OvertimeRateAssignmentService
{
    private readonly OvertimeRateAssignmentRepository $overtimeRateAssignmentRepository;

    public function __construct(OvertimeRateAssignmentRepository $overtimeRateAssignmentRepository)
    {
        $this->overtimeRateAssignmentRepository = $overtimeRateAssignmentRepository;
    }

    public function createOvertimeRateAssignment(OvertimeRateAssignment $overtimeRateAssignment): ActionResult|int
    {
        return $this->overtimeRateAssignmentRepository->createOvertimeRateAssignment($overtimeRateAssignment);
    }

    public function assignOvertimeRateAssignment(OvertimeRateAssignment $overtimeRateAssignment, array $overtimeRates, bool $isHashedId = false): ActionResult
    {
        return $this->overtimeRateAssignmentRepository->assignOvertimeRateAssignment($overtimeRateAssignment, $overtimeRates, $isHashedId);
    }

    public function findOvertimeRateAssignmentId(OvertimeRateAssignment $overtimeRateAssignment, bool $isHashedId = false): ActionResult|int
    {
        return $this->overtimeRateAssignmentRepository->findOvertimeRateAssignmentId($overtimeRateAssignment, $isHashedId);
    }
}
