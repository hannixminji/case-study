<?php

require_once __DIR__ . '/OvertimeRateAssignmentDao.php';

class OvertimeRateAssignmentRepository
{
    private readonly OvertimeRateAssignmentDao $overtimeRateAssignmentDao;

    public function __construct(OvertimeRateAssignmentDao $overtimeRateAssignmentDao)
    {
        $this->overtimeRateAssignmentDao = $overtimeRateAssignmentDao;
    }

    public function createOvertimeRateAssignment(OvertimeRateAssignment $overtimeRateAssignment): int|ActionResult
    {
        return $this->overtimeRateAssignmentDao->create($overtimeRateAssignment);
    }

    public function assignOvertimeRateAssignment(OvertimeRateAssignment $overtimeRateAssignment, array $overtimeRates): ActionResult
    {
        return $this->overtimeRateAssignmentDao->assign($overtimeRateAssignment, $overtimeRates);
    }

    public function findOvertimeRateAssignmentId(OvertimeRateAssignment $overtimeRateAssignment): int|ActionResult
    {
        return $this->overtimeRateAssignmentDao->findId($overtimeRateAssignment);
    }
}
