<?php

require_once __DIR__ . '/OvertimeRateAssignmentDao.php';

class OvertimeRateAssignmentRepository
{
    private readonly OvertimeRateAssignmentDao $overtimeRateAssignmentDao;

    public function __construct(OvertimeRateAssignmentDao $overtimeRateAssignmentDao)
    {
        $this->overtimeRateAssignmentDao = $overtimeRateAssignmentDao;
    }

    public function create(OvertimeRateAssignment $overtimeRateAssignment): ActionResult|int
    {
        return $this->overtimeRateAssignmentDao->create($overtimeRateAssignment);
    }

    public function assign(OvertimeRateAssignment $overtimeRateAssignment, array $overtimeRates, bool $isHashedId = false): ActionResult
    {
        return $this->overtimeRateAssignmentDao->assign($overtimeRateAssignment, $overtimeRates, $isHashedId);
    }

    public function findId(OvertimeRateAssignment $overtimeRateAssignment, bool $isHashedId = false): ActionResult|int
    {
        return $this->overtimeRateAssignmentDao->findId($overtimeRateAssignment, $isHashedId);
    }
}
