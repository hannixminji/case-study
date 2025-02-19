<?php

require_once __DIR__ . '/EmploymentTypeBenefitDao.php';

class EmploymentTypeBenefitRepository
{
    private readonly EmploymentTypeBenefitDao $employmentTypeBenefitDao;

    public function __construct(EmploymentTypeBenefitDao $employmentTypeBenefitDao)
    {
        $this->employmentTypeBenefitDao = $employmentTypeBenefitDao;
    }

    public function createEmploymentTypeBenefit(EmploymentTypeBenefit $employmentTypeBenefit): ActionResult
    {
        return $this->employmentTypeBenefitDao->create($employmentTypeBenefit);
    }

    public function fetchAllEmploymentTypeBenefits(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->employmentTypeBenefitDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function deleteEmploymentTypeBenefit(int|string $employmentTypeBenefitId): ActionResult
    {
        return $this->employmentTypeBenefitDao->delete($employmentTypeBenefitId);
    }
}
