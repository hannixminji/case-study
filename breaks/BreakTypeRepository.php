<?php

require_once __DIR__ . '/BreakTypeDao.php';

class BreakTypeRepository
{
    private readonly BreakTypeDao $breakTypeDao;

    public function __construct(BreakTypeDao $breakTypeDao)
    {
        $this->breakTypeDao = $breakTypeDao;
    }

    public function createBreakType(BreakType $breakType): ActionResult
    {
        return $this->breakTypeDao->create($breakType);
    }

    public function fetchAllBreakTypes(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): ActionResult|array {

        return $this->breakTypeDao->fetchAll(
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
        return $this->breakTypeDao->fetchLatestHistoryId($breakTypeId);
    }

    public function updateBreakType(BreakType $breakType, bool $isHashedId = false): ActionResult
    {
        return $this->breakTypeDao->update($breakType, $isHashedId);
    }

    public function deleteBreakType(int|string $breakTypeId, bool $isHashedId = false): ActionResult
    {
        return $this->breakTypeDao->delete($breakTypeId, $isHashedId);
    }
}
