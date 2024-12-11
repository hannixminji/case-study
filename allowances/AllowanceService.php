<?php

require_once __DIR__ . '/AllowanceDao.php';

class AllowanceService
{
    private readonly AllowanceDao $allowanceDao;

    public function __construct(AllowanceDao $allowanceDao)
    {
        $this->allowanceDao = $allowanceDao;
    }

    public function createAllowance(Allowance $allowance): ActionResult
    {
        return $this->allowanceDao->create($allowance);
    }

    public function fetchAllAllowances(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->allowanceDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateAllowance(Allowance $allowance): ActionResult
    {
        return $this->allowanceDao->update($allowance);
    }

    public function deleteAllowance(int $allowanceId): ActionResult
    {
        return $this->allowanceDao->delete($allowanceId);
    }
}
