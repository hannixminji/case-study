<?php

require_once __DIR__ . '/PayrollGroupDao.php';

class PayrollGroupRepository
{
    private readonly PayrollGroupDao $payrollGroupDao;

    public function __construct(PayrollGroupDao $payrollGroupDao)
    {
        $this->payrollGroupDao = $payrollGroupDao;
    }

    public function createPayrollGroup(PayrollGroup $payrollGroup): ActionResult
    {
        return $this->payrollGroupDao->create($payrollGroup);
    }

    public function fetchAllPayrollGroups(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array
    {
        return $this->payrollGroupDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updatePayrollGroup(PayrollGroup $payrollGroup): ActionResult
    {
        return $this->payrollGroupDao->update($payrollGroup);
    }

    public function deletePayrollGroup(int $payrollGroupId): ActionResult
    {
        return $this->payrollGroupDao->delete($payrollGroupId);
    }
}
