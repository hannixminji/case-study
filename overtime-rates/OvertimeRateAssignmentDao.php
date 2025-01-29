<?php

require_once __DIR__ . "/OvertimeRateDao.php";

class OvertimeRateAssignmentDao
{
    private readonly PDO $pdo;
    private readonly OvertimeRateDao $overtimeRateDao;

    public function __construct(PDO $pdo, OvertimeRateDao $overtimeRateDao)
    {
        $this->pdo             = $pdo            ;
        $this->overtimeRateDao = $overtimeRateDao;
    }

    public function create(OvertimeRateAssignment $overtimeRateAssignment): ActionResult|int
    {
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

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":department_id", $overtimeRateAssignment->getDepartmentId(), Helper::getPdoParameterType($overtimeRateAssignment->getDepartmentId()));
            $statement->bindValue(":job_title_id" , $overtimeRateAssignment->getJobTitleId()  , Helper::getPdoParameterType($overtimeRateAssignment->getJobTitleId()  ));
            $statement->bindValue(":employee_id"  , $overtimeRateAssignment->getEmployeeId()  , Helper::getPdoParameterType($overtimeRateAssignment->getEmployeeId()  ));

            $statement->execute();

            $this->pdo->commit();

            return (int) $this->pdo->lastInsertId();

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the overtime rate assignment. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function assign(OvertimeRateAssignment $overtimeRateAssignment, array $overtimeRates, bool $isHashedId = false): ActionResult
    {
        try {
            $this->pdo->beginTransaction();

            $overtimeRateAssignmentId = $this->create($overtimeRateAssignment);

            if ($overtimeRateAssignmentId === ActionResult::FAILURE) {
                return ActionResult::FAILURE;
            }

            if ($overtimeRateAssignmentId === ActionResult::DUPLICATE_ENTRY_ERROR) {
                foreach ($overtimeRates as $overtimeRate) {
                    $result = $this->overtimeRateDao->update($overtimeRate, $isHashedId);

                    if ($result === ActionResult::FAILURE) {
                        $this->pdo->rollBack();

                        return ActionResult::FAILURE;
                    }
                }

                $this->pdo->commit();

                return ActionResult::SUCCESS;
            }

            foreach ($overtimeRates as $overtimeRate) {
                $overtimeRate->setOvertimeRateAssignmentId($overtimeRateAssignmentId);

                $result = $this->overtimeRateDao->create($overtimeRate);

                if ($result === ActionResult::FAILURE) {
                    $this->pdo->rollBack();

                    return ActionResult::FAILURE;
                }
            }

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while assigning overtime rates. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function findId(OvertimeRateAssignment $overtimeRateAssignment, bool $isHashedId = false): ActionResult|int
    {
        $query = "
            SELECT
                id
            FROM
                overtime_rate_assignments
            WHERE
        ";

        if ($isHashedId) {
            $query .= "
                (SHA2(employee_id, 256) = SHA2(:employee_id, 256) AND SHA2(job_title_id, 256) = SHA2(:job_title_id, 256) AND SHA2(department_id, 256) = SHA2(:department_id, 256))
            OR
                (employee_id IS NULL                              AND SHA2(job_title_id, 256) = SHA2(:job_title_id, 256) AND SHA2(department_id, 256) = SHA2(:department_id, 256))
            OR
                (employee_id IS NULL                              AND job_title_id IS NULL                               AND SHA2(department_id, 256) = SHA2(:department_id, 256))
            OR
                (employee_id IS NULL                              AND job_title_id IS NULL                               AND department_id IS NULL                               )
            ORDER BY
                CASE
                    WHEN SHA2(employee_id, 256) = SHA2(:employee_id, 256) AND SHA2(job_title_id, 256) = SHA2(:job_title_id, 256) AND SHA2(department_id, 256) = SHA2(:department_id, 256) THEN 1
                    WHEN employee_id IS NULL                              AND SHA2(job_title_id, 256) = SHA2(:job_title_id, 256) AND SHA2(department_id, 256) = SHA2(:department_id, 256) THEN 2
                    WHEN employee_id IS NULL                              AND job_title_id IS NULL                               AND SHA2(department_id, 256) = SHA2(:department_id, 256) THEN 3
            ";
        } else {
            $query .= "
                (employee_id = :employee_id AND job_title_id = :job_title_id AND department_id = :department_id)
            OR
                (employee_id IS NULL        AND job_title_id = :job_title_id AND department_id = :department_id)
            OR
                (employee_id IS NULL        AND job_title_id IS NULL         AND department_id = :department_id)
            OR
                (employee_id IS NULL        AND job_title_id IS NULL         AND department_id IS NULL         )
            ORDER BY
                CASE
                    WHEN employee_id = :employee_id AND job_title_id = :job_title_id AND department_id = :department_id THEN 1
                    WHEN employee_id IS NULL        AND job_title_id = :job_title_id AND department_id = :department_id THEN 2
                    WHEN employee_id IS NULL        AND job_title_id IS NULL         AND department_id = :department_id THEN 3
            ";
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

            $result = $statement->fetchColumn();

            return $result !== false
                ? (int) $result
                : ActionResult::NO_RECORD_FOUND;

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching the ID. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
