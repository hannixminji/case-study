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
        return $this->overtimeRateAssignmentRepository->create($overtimeRateAssignment);
    }

    public function assignOvertimeRate(OvertimeRateAssignment $overtimeRateAssignment, array $overtimeRates, bool $isHashedId = false): ActionResult
    {
        return $this->overtimeRateAssignmentRepository->assign($overtimeRateAssignment, $overtimeRates, $isHashedId);
    }

    public function findOvertimeRateAssignmentId(OvertimeRateAssignment $overtimeRateAssignment, bool $isHashedId = false): ActionResult|int
    {
        return $this->overtimeRateAssignmentRepository->findId($overtimeRateAssignment, $isHashedId);
    }
}
