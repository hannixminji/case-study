<?php

require_once __DIR__ . '/DeductionDao.php';

class DeductionRepository
{
    private readonly DeductionDao $deductionDao;

    public function __construct(DeductionDao $deductionDao)
    {
        $this->deductionDao = $deductionDao;
    }

    public function createDeduction(Deduction $deduction): ActionResult
    {
        return $this->deductionDao->create($deduction);
    }

    public function fetchAllDeductions(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->deductionDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateDeduction(Deduction $deduction): ActionResult
    {
        return $this->deductionDao->update($deduction);
    }

    public function deleteDeduction(int|string $deductionId): ActionResult
    {
        return $this->deductionDao->delete($deductionId);
    }
}
