<?php

require_once __DIR__ . '/PayrollGroupRepository.php';

class PayrollGroupService
{
    private readonly PayrollGroupRepository $payrollGroupRepository;

    public function __construct(PayrollGroupRepository $payrollGroupRepository)
    {
        $this->payrollGroupRepository = $payrollGroupRepository;
    }

    public function createPayrollGroup(PayrollGroup $payrollGroup): ActionResult
    {
        return $this->payrollGroupRepository->createPayrollGroup($payrollGroup);
    }

    public function fetchAllPayrollGroups(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array
    {
        return $this->payrollGroupRepository->fetchAllPayrollGroups($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updatePayrollGroup(PayrollGroup $payrollGroup, bool $isHashedId = false): ActionResult
    {
        return $this->payrollGroupRepository->updatePayrollGroup($payrollGroup, $isHashedId);
    }

    public function deletePayrollGroup(int|string $payrollGroupId, bool $isHashedId = false): ActionResult
    {
        return $this->payrollGroupRepository->deletePayrollGroup($payrollGroupId, $isHashedId);
    }
}
