<?php

require_once __DIR__ . '/EmployeeBreakDao.php';

class EmployeeBreakRepository
{
    private readonly EmployeeBreakDao $employeeBreakDao;

    public function __construct(EmployeeBreakDao $employeeBreakDao)
    {
        $this->employeeBreakDao = $employeeBreakDao;
    }

    public function createEmployeeBreak(EmployeeBreak $employeeBreak): ActionResult
    {
        return $this->employeeBreakDao->create($employeeBreak);
    }

    public function breakIn(EmployeeBreak $employeeBreak): ActionResult
    {
        return $this->employeeBreakDao->breakIn($employeeBreak);
    }

    public function breakOut(EmployeeBreak $employeeBreak): ActionResult
    {
        return $this->employeeBreakDao->breakOut($employeeBreak);
    }

    public function fetchAllEmployeeBreaks(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? array $groupByColumns       = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->employeeBreakDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            groupByColumns      : $groupByColumns      ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateEmployeeBreak(EmployeeBreak $employeeBreak): ActionResult
    {
        return $this->employeeBreakDao->update($employeeBreak);
    }

    public function deleteEmployeeBreak(int|string $employeeBreakId): ActionResult
    {
        return $this->employeeBreakDao->delete($employeeBreakId);
    }
}
