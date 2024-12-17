<?php

require_once __DIR__ . '/JobTitleRepository.php';

class JobTitleService
{
    private readonly JobTitleRepository $jobTitleRepository;

    public function __construct(JobTitleRepository $jobTitleRepository)
    {
        $this->jobTitleRepository = $jobTitleRepository;
    }

    public function createJobTitle(JobTitle $jobTitle): ActionResult
    {
        return $this->jobTitleRepository->createJobTitle($jobTitle);
    }

    public function fetchAllJobTitles(
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->jobTitleRepository->fetchAllJobTitles($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateJobTitle(JobTitle $jobTitle, bool $isHashedId = false): ActionResult
    {
        return $this->jobTitleRepository->updateJobTitle($jobTitle, $isHashedId);
    }

    public function deleteJobTitle(int|string $jobTitleId, bool $isHashedId = false): ActionResult
    {
        return $this->jobTitleRepository->deleteJobTitle($jobTitleId, $isHashedId);
    }
}
