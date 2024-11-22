<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

class OvertimeRateAssignmentDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(OvertimeRateAssignment $overtimeRateAssignment): ActionResult
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

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the overtime rate assignment. " .
                      "Exception: {$exception->getMessage()}");

            if ( (int) $exception->getCode() === ErrorCode::DUPLICATE_ENTRY->value) {
                return ActionResult::DUPLICATE_ENTRY_ERROR;
            }

            return ActionResult::FAILURE;
        }
    }

    public function findAssignmentId(int $employee_id, int $job_title_id, int $department_id): ActionResult|int
    {
        $query = "
            SELECT
                id
            FROM
                overtime_rate_assignments
            WHERE
                (employee_id = :employee_id AND job_title_id = :job_title_id AND department_id = :department_id)
            OR
                (employee_id IS NULL AND job_title_id = :job_title_id AND department_id = :department_id)
            OR
                (employee_id IS NULL AND job_title_id IS NULL AND department_id = :department_id)
            OR
                (employee_id IS NULL AND job_title_id IS NULL AND department_id IS NULL)
            ORDER BY
                CASE
                    WHEN employee_id = :employee_id AND job_title_id = :job_title_id AND department_id = :department_id THEN 1
                    WHEN employee_id IS NULL AND job_title_id = :job_title_id AND department_id = :department_id        THEN 2
                    WHEN employee_id IS NULL AND job_title_id IS NULL AND department_id = :department_id                THEN 3
                    WHEN employee_id IS NULL AND job_title_id IS NULL AND department_id IS NULL                         THEN 4
                                                                                                                        ELSE 5
                END
            LIMIT 1
        ";

        try {
            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"  , $employee_id  , Helper::getPdoParameterType($employee_id  ));
            $statement->bindValue(":job_title_id" , $job_title_id , Helper::getPdoParameterType($job_title_id ));
            $statement->bindValue(":department_id", $department_id, Helper::getPdoParameterType($department_id));

            $statement->execute();

            $id = $statement->fetchColumn();

            return (int) $id;

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching the ID. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
