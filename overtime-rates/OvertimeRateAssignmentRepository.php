<?php

require_once __DIR__ . '/OvertimeRateAssignmentDao.php';

class OvertimeRateAssignmentRepository
{
    private readonly OvertimeRateAssignmentDao $overtimeRateAssignmentDao;

    public function __construct(OvertimeRateAssignmentDao $overtimeRateAssignmentDao)
    {
        $this->overtimeRateAssignmentDao = $overtimeRateAssignmentDao;
    }

    public function createOvertimeRateAssignment(OvertimeRateAssignment $overtimeRateAssignment): ActionResult|int
    {
        return $this->overtimeRateAssignmentDao->create($overtimeRateAssignment);
    }

    public function assignOvertimeRateAssignment(OvertimeRateAssignment $overtimeRateAssignment, array $overtimeRates, bool $isHashedId = false): ActionResult
    {
        return $this->overtimeRateAssignmentDao->assign($overtimeRateAssignment, $overtimeRates, $isHashedId);
    }

    public function findOvertimeRateAssignmentId(OvertimeRateAssignment $overtimeRateAssignment, bool $isHashedId = false): ActionResult|int
    {
        return $this->overtimeRateAssignmentDao->findId($overtimeRateAssignment, $isHashedId);
    }
}
