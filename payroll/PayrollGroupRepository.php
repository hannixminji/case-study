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
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->payrollGroupDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updatePayrollGroup(PayrollGroup $payrollGroup): ActionResult
    {
        return $this->payrollGroupDao->update($payrollGroup);
    }

    public function deletePayrollGroup(int|string $payrollGroupId): ActionResult
    {
        return $this->payrollGroupDao->delete($payrollGroupId);
    }
}
