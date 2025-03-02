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
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->payslipDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }
}
