<?php

require_once __DIR__ . '/EmploymentTypeBenefitRepository.php';

class EmploymentTypeBenefitService
{
    private readonly EmploymentTypeBenefitRepository $employmentTypeBenefitRepository;

    public function __construct(EmploymentTypeBenefitRepository $employmentTypeBenefitRepository)
    {
        $this->employmentTypeBenefitRepository = $employmentTypeBenefitRepository;
    }

    public function createEmploymentTypeBenefit(EmploymentTypeBenefit $employmentTypeBenefit): ActionResult
    {
        return $this->employmentTypeBenefitRepository->createEmploymentTypeBenefit($employmentTypeBenefit);
    }

    public function fetchAllEmploymentTypeBenefits(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): ActionResult|array {

        return $this->employmentTypeBenefitRepository->fetchAllEmploymentTypeBenefits(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function deleteEmploymentTypeBenefit(int|string $employmentTypeBenefitId, bool $isHashedId = false): ActionResult
    {
        return $this->employmentTypeBenefitRepository->deleteEmploymentTypeBenefit($employmentTypeBenefitId, $isHashedId);
    }
}
