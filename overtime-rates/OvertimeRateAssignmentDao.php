<?php

require_once __DIR__ . "/OvertimeRateDao.php"             ;

require_once __DIR__ . "/../departments/DepartmentDao.php";
require_once __DIR__ . "/../job-titles/JobTitleDao.php"   ;
require_once __DIR__ . "/../employees/EmployeeDao.php"    ;

class OvertimeRateAssignmentDao
{
    private readonly PDO             $pdo            ;
    private readonly OvertimeRateDao $overtimeRateDao;
    private readonly DepartmentDao   $departmentDao  ;
    private readonly JobTitleDao     $jobTitleDao    ;
    private readonly EmployeeDao     $employeeDao    ;

    public function __construct(
        PDO             $pdo            ,
        OvertimeRateDao $overtimeRateDao,
        DepartmentDao   $departmentDao  ,
        JobTitleDao     $jobTitleDao    ,
        EmployeeDao     $employeeDao
    ) {
        $this->pdo             = $pdo            ;
        $this->overtimeRateDao = $overtimeRateDao;
        $this->departmentDao   = $departmentDao  ;
        $this->jobTitleDao     = $jobTitleDao    ;
        $this->employeeDao     = $employeeDao    ;
    }

    public function create(OvertimeRateAssignment $overtimeRateAssignment): int|ActionResult
    {
        $departmentId = $overtimeRateAssignment->getDepartmentId();
        $jobTitleId   = $overtimeRateAssignment->getJobTitleId()  ;
        $employeeId   = $overtimeRateAssignment->getEmployeeId()  ;

        if (is_string($departmentId)) {
            $departmentColumns = [
                "id"
            ];

            $departmentFilterCriteria = [
                [
                    "column"   => "SHA2(department.id, 256)",
                    "operator" => "="                       ,
                    "value"    => $departmentId
                ]
            ];

            $departmentId = $this->departmentDao->fetchAll(
                columns             : $departmentColumns       ,
                filterCriteria      : $departmentFilterCriteria,
                limit               : 1                        ,
                includeTotalRowCount: false
            );

            if ($departmentId === ActionResult::FAILURE || empty($departmentId['result_set'])) {
                return ActionResult::FAILURE;
            }

            $departmentId = $departmentId['result_set'][0]['id'];
        }

        if (is_string($jobTitleId)) {
            $jobTitleColumns = [
                "id"
            ];

            $jobTitleFilterCriteria = [
                [
                    "column"   => "SHA2(job_title.id, 256)",
                    "operator" => "="                      ,
                    "value"    => $jobTitleId
                ]
            ];

            $jobTitleId = $this->jobTitleDao->fetchAll(
                columns             : $jobTitleColumns       ,
                filterCriteria      : $jobTitleFilterCriteria,
                limit               : 1                      ,
                includeTotalRowCount: false
            );

            if ($jobTitleId === ActionResult::FAILURE || empty($jobTitleId['result_set'])) {
                return ActionResult::FAILURE;
            }

            $jobTitleId = $jobTitleId['result_set'][0]['id'];
        }

        if (is_string($employeeId)) {
            $employeeColumns = [
                "id"
            ];

            $employeeFilterCriteria = [
                [
                    "column"   => "SHA2(employee.id, 256)",
                    "operator" => "=",
                    "value"    => $employeeId
                ]
            ];

            $employeeId = $this->employeeDao->fetchAll(
                columns             : $employeeColumns       ,
                filterCriteria      : $employeeFilterCriteria,
                limit               : 1                      ,
                includeTotalRowCount: false
            );

            if ($employeeId === ActionResult::FAILURE || empty($employeeId['result_set'])) {
                return ActionResult::FAILURE;
            }

            $employeeId = $employeeId['result_set'][0]['id'];
        }

        $query = "
            INSERT INTO overtime_rate_assignments (
                department_id,
                job_title_id ,
                employee_id
            )
            VALUES (
                :department_id,
                :job_title_id ,
                :employee_id
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":department_id", $departmentId, Helper::getPdoParameterType($departmentId));
            $statement->bindValue(":job_title_id" , $jobTitleId  , Helper::getPdoParameterType($jobTitleId  ));
            $statement->bindValue(":employee_id"  , $employeeId  , Helper::getPdoParameterType($employeeId  ));

            $statement->execute();

            $lastInsertId = $this->pdo->lastInsertId();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return $lastInsertId;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the overtime rate assignment. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function assign(OvertimeRateAssignment $overtimeRateAssignment, array $overtimeRates): ActionResult
    {
        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $overtimeRateAssignmentId = $this->fetchId($overtimeRateAssignment);

            if ($overtimeRateAssignmentId === ActionResult::FAILURE) {
                if ($isLocalTransaction) {
                    $this->pdo->rollBack();
                }

                return ActionResult::FAILURE;
            }

            if ($overtimeRateAssignmentId === ActionResult::NO_RECORD_FOUND) {
                $overtimeRateAssignmentId = $this->create($overtimeRateAssignment);

                if ($overtimeRateAssignmentId === ActionResult::FAILURE) {
                    if ($isLocalTransaction) {
                        $this->pdo->rollBack();
                    }

                    return ActionResult::FAILURE;
                }

                foreach ($overtimeRates as $overtimeRate) {
                    $overtimeRate->setOvertimeRateAssignmentId($overtimeRateAssignmentId);

                    $result = $this->overtimeRateDao->create($overtimeRate);

                    if ($result === ActionResult::FAILURE) {
                        if ($isLocalTransaction) {
                            $this->pdo->rollBack();
                        }

                        return ActionResult::FAILURE;
                    }
                }

                if ($isLocalTransaction) {
                    $this->pdo->commit();
                }

                return ActionResult::SUCCESS;
            }

            foreach ($overtimeRates as $overtimeRate) {
                $result = $this->overtimeRateDao->update($overtimeRate);

                if ($result === ActionResult::FAILURE) {
                    if ($isLocalTransaction) {
                        $this->pdo->rollBack();
                    }

                    return ActionResult::FAILURE;
                }
            }

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while assigning overtime rates. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function findId(OvertimeRateAssignment $overtimeRateAssignment): int|ActionResult
    {
        $query = "
            SELECT
                id
            FROM
                overtime_rate_assignments
            WHERE
        ";

        if (is_string($overtimeRateAssignment->getEmployeeId())) {
            $query .= "(SHA2(employee_id, 256) = SHA2(:employee_id, 256) ";
        } else {
            $query .= "(employee_id = :employee_id ";
        }

        if (is_string($overtimeRateAssignment->getJobTitleId())) {
            $query .= "AND SHA2(job_title_id, 256) = SHA2(:job_title_id, 256) ";
        } else {
            $query .= "AND job_title_id = :job_title_id ";
        }

        if (is_string($overtimeRateAssignment->getDepartmentId())) {
            $query .= "AND SHA2(department_id, 256) = SHA2(:department_id, 256)) ";
        } else {
            $query .= "AND department_id = :department_id) ";
        }

        $query .= "
        OR
            (employee_id IS NULL ";

        if (is_string($overtimeRateAssignment->getJobTitleId())) {
            $query .= "AND SHA2(job_title_id, 256) = SHA2(:job_title_id, 256) ";
        } else {
            $query .= "AND job_title_id = :job_title_id ";
        }

        if (is_string($overtimeRateAssignment->getDepartmentId())) {
            $query .= "AND SHA2(department_id, 256) = SHA2(:department_id, 256)) ";
        } else {
            $query .= "AND department_id = :department_id) ";
        }

        $query .= "
        OR
            (employee_id IS NULL AND job_title_id IS NULL ";

        if (is_string($overtimeRateAssignment->getDepartmentId())) {
            $query .= "AND SHA2(department_id, 256) = SHA2(:department_id, 256)) ";
        } else {
            $query .= "AND department_id = :department_id) ";
        }

        $query .= "
        OR
            (employee_id IS NULL AND job_title_id IS NULL AND department_id IS NULL)
        ORDER BY
            CASE ";

        if (is_string($overtimeRateAssignment->getEmployeeId())) {
            $query .= "WHEN SHA2(employee_id, 256) = SHA2(:employee_id, 256) ";
        } else {
            $query .= "WHEN employee_id = :employee_id ";
        }

        if (is_string($overtimeRateAssignment->getJobTitleId())) {
            $query .= "AND SHA2(job_title_id, 256) = SHA2(:job_title_id, 256) ";
        } else {
            $query .= "AND job_title_id = :job_title_id ";
        }

        if (is_string($overtimeRateAssignment->getDepartmentId())) {
            $query .= "AND SHA2(department_id, 256) = SHA2(:department_id, 256) THEN 1 ";
        } else {
            $query .= "AND department_id = :department_id THEN 1 ";
        }

        $query .= "
            WHEN employee_id IS NULL ";

        if (is_string($overtimeRateAssignment->getJobTitleId())) {
            $query .= "AND SHA2(job_title_id, 256) = SHA2(:job_title_id, 256) ";
        } else {
            $query .= "AND job_title_id = :job_title_id ";
        }

        if (is_string($overtimeRateAssignment->getDepartmentId())) {
            $query .= "AND SHA2(department_id, 256) = SHA2(:department_id, 256) THEN 2 ";
        } else {
            $query .= "AND department_id = :department_id THEN 2 ";
        }

        $query .= "
            WHEN employee_id IS NULL AND job_title_id IS NULL ";

        if (is_string($overtimeRateAssignment->getDepartmentId())) {
            $query .= "AND SHA2(department_id, 256) = SHA2(:department_id, 256) THEN 3";
        } else {
            $query .= "AND department_id = :department_id THEN 3";
        }

        $query .= "
                    WHEN employee_id IS NULL AND job_title_id IS NULL AND department_id IS NULL THEN 4
                    ELSE 5
                END
            LIMIT 1
        ";

        try {
            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"  , $overtimeRateAssignment->getEmployeeId()  , Helper::getPdoParameterType($overtimeRateAssignment->getEmployeeId()  ));
            $statement->bindValue(":job_title_id" , $overtimeRateAssignment->getJobTitleId()  , Helper::getPdoParameterType($overtimeRateAssignment->getJobTitleId()  ));
            $statement->bindValue(":department_id", $overtimeRateAssignment->getDepartmentId(), Helper::getPdoParameterType($overtimeRateAssignment->getDepartmentId()));

            $statement->execute();

            $overtimeRateAssignmentId = $statement->fetchColumn();

            if ($overtimeRateAssignmentId === false) {
                return ActionResult::NO_RECORD_FOUND;
            }

            return $overtimeRateAssignmentId;

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching the ID. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function fetchId(OvertimeRateAssignment $overtimeRateAssignment): int|ActionResult
    {
        $query = "
            SELECT
                id
            FROM
                overtime_rate_assignments
            WHERE
        ";

        if (is_string($overtimeRateAssignment->getEmployeeId())) {
            $query .= " SHA2(employee_id, 256) = SHA2(:employee_id, 256) ";
        } else {
            $query .= " employee_id = :employee_id ";
        }

        if (is_string($overtimeRateAssignment->getJobTitleId())) {
            $query .= "AND SHA2(job_title_id, 256) = SHA2(:job_title_id, 256) ";
        } else {
            $query .= "AND job_title_id = :job_title_id ";
        }

        if (is_string($overtimeRateAssignment->getDepartmentId())) {
            $query .= "AND SHA2(department_id, 256) = SHA2(:department_id, 256) ";
        } else {
            $query .= "AND department_id = :department_id ";
        }

        $query = "
            LIMIT 1
        ";

        try {
            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"  , $overtimeRateAssignment->getEmployeeId()  , Helper::getPdoParameterType($overtimeRateAssignment->getEmployeeId()  ));
            $statement->bindValue(":job_title_id" , $overtimeRateAssignment->getJobTitleId()  , Helper::getPdoParameterType($overtimeRateAssignment->getJobTitleId()  ));
            $statement->bindValue(":department_id", $overtimeRateAssignment->getDepartmentId(), Helper::getPdoParameterType($overtimeRateAssignment->getDepartmentId()));

            $statement->execute();

            $overtimeRateAssignmentId = $statement->fetchColumn();

            if ($overtimeRateAssignmentId === false) {
                return ActionResult::NO_RECORD_FOUND;
            }

            return $overtimeRateAssignmentId;

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while creating the overtime rate assignment. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
