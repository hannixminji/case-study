<?php

require_once __DIR__ . '/EmployeeBreakDao.php';

class EmployeeBreakRepository
{
    private readonly EmployeeBreakDao $employeeBreakDao;

    public function __construct(EmployeeBreakDao $employeeBreakDao)
    {
        $this->employeeBreakDao = $employeeBreakDao;
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
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): ActionResult|array {

        return $this->employeeBreakDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateEmployeeBreak(EmployeeBreak $employeeBreak, bool $isHashedId = false): ActionResult
    {
        return $this->employeeBreakDao->update($employeeBreak, $isHashedId);
    }

    public function deleteEmployeeBreak(int|string $employeeBreakId, bool $isHashedId = false): ActionResult
    {
        return $this->employeeBreakDao->delete($employeeBreakId, $isHashedId);
    }
}
