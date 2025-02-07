<?php

require_once __DIR__ . '/AllowanceRepository.php';

class AllowanceService
{
    private readonly AllowanceRepository $allowanceRepository;

    public function __construct(AllowanceRepository $allowanceRepository)
    {
        $this->allowanceRepository = $allowanceRepository;
    }

    public function createAllowance(Allowance $allowance): ActionResult
    {
        return $this->allowanceRepository->createAllowance($allowance);
    }

    public function fetchAllAllowances(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): ActionResult|array {

        return $this->allowanceRepository->fetchAllAllowances(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateAllowance(Allowance $allowance, bool $isHashedId = false): ActionResult
    {
        return $this->allowanceRepository->updateAllowance($allowance, $isHashedId);
    }

    public function deleteAllowance(int|string $allowanceId, bool $isHashedId = false): ActionResult
    {
        return $this->allowanceRepository->deleteAllowance($allowanceId, $isHashedId);
    }
}
