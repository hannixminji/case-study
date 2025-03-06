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

    public function createBreakTypeSnapshot(BreakTypeSnapshot $breakTypeSnapshot): int|ActionResult
    {
        return $this->breakTypeRepository->createBreakTypeSnapshot($breakTypeSnapshot);
    }

    public function fetchAllBreakTypes(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->breakTypeRepository->fetchAllBreakTypes(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateBreakType(BreakType $breakType): ActionResult
    {
        return $this->breakTypeRepository->updateBreakType($breakType);
    }

    public function deleteBreakType(int|string $breakTypeId): ActionResult
    {
        return $this->breakTypeRepository->deleteBreakType($breakTypeId);
    }
}
