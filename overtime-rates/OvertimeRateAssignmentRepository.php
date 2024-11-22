<?php

require_once __DIR__ . "/OvertimeRateAssignmentDao.php";

class OvertimeRateAssignmentRepository
{
    private readonly OvertimeRateAssignmentDao $overtimeRateAssignmentDao;

    public function __construct(PDO $pdo)
    {
        $this->overtimeRateAssignmentDao = new OvertimeRateAssignmentDao($pdo);
    }

    public function create(OvertimeRateAssignment $overtimeRateAssignment): ActionResult
    {
        return $this->overtimeRateAssignmentDao->create($overtimeRateAssignment);
    }

    public function findAssignmentId(int $employee_id, int $job_title_id, int $department_id): ActionResult|int
    {
        return $this->overtimeRateAssignmentDao->findAssignmentId($employee_id, $job_title_id, $department_id);
    }
}
