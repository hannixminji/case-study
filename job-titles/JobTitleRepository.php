<?php

require_once __DIR__ . '/JobTitleDao.php';

class JobTitleRepository
{
    private readonly JobTitleDao $jobTitleDao;

    public function __construct(JobTitleDao $jobTitleDao)
    {
        $this->jobTitleDao = $jobTitleDao;
    }

    public function createJobTitle(JobTitle $jobTitle): ActionResult
    {
        return $this->jobTitleDao->create($jobTitle);
    }

    public function fetchAllJobTitles(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->jobTitleDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateJobTitle(JobTitle $jobTitle): ActionResult
    {
        return $this->jobTitleDao->update($jobTitle);
    }

    public function deleteJobTitle(int|string $jobTitleId): ActionResult
    {
        return $this->jobTitleDao->delete($jobTitleId);
    }
}
