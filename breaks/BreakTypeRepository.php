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
        ? array $columns        = null,
        ? array $filterCriteria = null,
        ? array $sortCriteria   = null,
        ? int   $limit          = null,
        ? int   $offset         = null
    ): ActionResult|array {
        return $this->breakTypeDao->fetchAll($columns, $filterCriteria, $sortCriteria, $limit, $offset);
    }

    public function updateBreakType(BreakType $breakType): ActionResult
    {
        return $this->breakTypeDao->update($breakType);
    }

    public function deleteBreakType(int $breakTypeId): ActionResult
    {
        return $this->breakTypeDao->delete($breakTypeId);
    }
}
