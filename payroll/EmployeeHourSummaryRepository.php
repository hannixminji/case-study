<?php

require_once __DIR__ . '/EmployeeHourSummaryDao.php';

class EmployeeHourSummaryRepository
{
    private readonly EmployeeHourSummaryDao $employeeHourSummaryDao;

    public function __construct(EmployeeHourSummaryDao $employeeHourSummaryDao)
    {
        $this->employeeHourSummaryDao = $employeeHourSummaryDao;
    }

    public function createEmployeeHourSummary(EmployeeHourSummary $employeeHourSummary): ActionResult
    {
        return $this->employeeHourSummaryDao->create($employeeHourSummary);
    }

    public function fetchEmployeeHourSummaries(int $payslipId, bool $isHashedId = false): ActionResult|array
    {
        return $this->employeeHourSummaryDao->fetchEmployeeHourSummaries($payslipId, $isHashedId);
    }

    public function updateEmployeeHourSummary(EmployeeHourSummary $employeeHourSummary, bool $isHashedId = false): ActionResult
    {
        return $this->employeeHourSummaryDao->update($employeeHourSummary, $isHashedId);
    }
}
