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

    public function getLastPayslipId(): ActionResult|int|null
    {
        return $this->payslipDao->getLastPayslipId();
    }
}
