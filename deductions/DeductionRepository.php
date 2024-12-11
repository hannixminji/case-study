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
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->deductionDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateDeduction(Deduction $deduction): ActionResult
    {
        return $this->deductionDao->update($deduction);
    }

    public function deleteDeduction(int $deductionId): ActionResult
    {
        return $this->deductionDao->delete($deductionId);
    }
}
