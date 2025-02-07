<?php

require_once __DIR__ . '/BreakTypeRepository.php';

class BreakTypeService
{
    private readonly BreakTypeRepository $breakTypeRepository;

    public function __construct(BreakTypeRepository $breakTypeRepository)
    {
        $this->breakTypeRepository = $breakTypeRepository;
    }

    public function createBreakType(BreakType $breakType): ActionResult
    {
        return $this->breakTypeRepository->createBreakType($breakType);
    }

    public function fetchAllBreakTypes(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): ActionResult|array {

        return $this->breakTypeRepository->fetchAllBreakTypes(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function fetchLatestBreakTypeHistoryId(int $breakTypeId): int|null|ActionResult
    {
        return $this->breakTypeRepository->fetchLatestBreakTypeHistoryId($breakTypeId);
    }

    public function updateBreakType(BreakType $breakType, bool $isHashedId = false): ActionResult
    {
        return $this->breakTypeRepository->updateBreakType($breakType, $isHashedId);
    }

    public function deleteBreakType(int|string $breakTypeId, bool $isHashedId = false): ActionResult
    {
        return $this->breakTypeRepository->deleteBreakType($breakTypeId, $isHashedId);
    }
}
