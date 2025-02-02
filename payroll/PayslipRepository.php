<?php

require_once __DIR__ . '/PayslipDao.php';

class PayslipRepository
{
    private readonly PayslipDao $payslipDao;

    public function __construct(PayslipDao $payslipDao)
    {
        $this->payslipDao = $payslipDao;
    }

    public function createPayslip(Payslip $payslip): ActionResult
    {
        return $this->payslipDao->create($payslip);
    }

    public function fetchAllPayslips(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array
    {
        return $this->payslipDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updatePayslip(Payslip $payslip, bool $isHashedId = false): ActionResult
    {
        return $this->payslipDao->update($payslip, $isHashedId);
    }
}
