<?php

require_once __DIR__ . '/DeductionRepository.php';

class DeductionService
{
    private readonly DeductionRepository $deductionRepository;

    public function __construct(DeductionRepository $deductionRepository)
    {
        $this->deductionRepository = $deductionRepository;
    }

    public function createDeduction(Deduction $deduction): ActionResult
    {
        return $this->deductionRepository->createDeduction($deduction);
    }

    public function fetchAllDeductions(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->deductionRepository->fetchAllDeductions($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateDeduction(Deduction $deduction, bool $isHashedId = false): ActionResult
    {
        return $this->deductionRepository->updateDeduction($deduction, $isHashedId);
    }

    public function deleteDeduction(int $deductionId, bool $isHashedId = false): ActionResult
    {
        return $this->deductionRepository->deleteDeduction($deductionId, $isHashedId);
    }
}
