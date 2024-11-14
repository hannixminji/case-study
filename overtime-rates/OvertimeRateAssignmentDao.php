<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

class OvertimeRateAssignmentDao
{
    private readonly PDO $pdo;
    private readonly OvertimeRateDao $overtimeRateDao;

    public function __construct(PDO $pdo, OvertimeRateDao $overtimeRateDao)
    {
        $this->pdo = $pdo;
        $this->overtimeRateDao = $overtimeRateDao;
    }

    public function assign(OvertimeRateAssignment $overtimeRateAssignment, array $overtimeRates): ActionResult
    {
        try {
            $this->pdo->beginTransaction();

            $existingAssignmentId = $this->hasExistingAssignment($overtimeRateAssignment);

            if ($existingAssignmentId === ActionResult::FAILURE) {
                return ActionResult::FAILURE;
            }

            if ($existingAssignmentId === null) {
                $insertOvertimeRateSetQuery = "INSERT INTO overtime_rate_sets () VALUES ()";
                $this->pdo->exec($insertOvertimeRateSetQuery);

                $overtimeRateSetId = $this->pdo->lastInsertId();

                foreach ($overtimeRates as $overtimeRate) {
                    $result = $this->overtimeRateDao->create($overtimeRate, $overtimeRateSetId);

                    if ($result === ActionResult::FAILURE) {
                        $this->pdo->rollBack();

                        return ActionResult::FAILURE;
                    }
                }

                $overtimeRateAssignment = new OvertimeRateAssignment(
                    null,
                    $overtimeRateSetId,
                    $overtimeRateAssignment->getDepartmentId(),
                    $overtimeRateAssignment->getJobTitleId(),
                    $overtimeRateAssignment->getEmployeeId(),
                    $overtimeRateAssignment->getAssignmentLevel()
                );

                $this->create($overtimeRateAssignment);

            } else {
                foreach ($overtimeRates as $overtimeRate) {
                    $result = $this->overtimeRateDao->update($overtimeRate);

                    if ($result === ActionResult::FAILURE) {
                        $this->pdo->rollBack();

                        return ActionResult::FAILURE;
                    }
                }
            }

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while assigning the overtime rates. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    private function hasExistingAssignment(OvertimeRateAssignment $overtimeRateAssignment): ActionResult|bool
    {
        $query = "
            SELECT
                id
            FROM
                overtime_rate_assignments
            WHERE
                department_id    = :department_id
            AND job_title_id     = :job_title_id
            AND employee_id      = :employee_id
            AND assignment_level = :assignment_level
        ";

        try {
            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':department_id'   , $overtimeRateAssignment->getDepartmentId()   , Helper::getPdoParameterType($overtimeRateAssignment->getDepartmentId()   ));
            $statement->bindValue(':job_title_id'    , $overtimeRateAssignment->getJobTitleId()     , Helper::getPdoParameterType($overtimeRateAssignment->getJobTitleId()     ));
            $statement->bindValue(':employee_id'     , $overtimeRateAssignment->getEmployeeId()     , Helper::getPdoParameterType($overtimeRateAssignment->getEmployeeId()     ));
            $statement->bindValue(':assignment_level', $overtimeRateAssignment->getAssignmentLevel(), Helper::getPdoParameterType($overtimeRateAssignment->getAssignmentLevel()));

            $statement->execute();

            return (bool) $statement->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while checking for an existing assignment. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function create(OvertimeRateAssignment $overtimeRateAssignment): ActionResult
    {
        $query = "
            INSERT INTO overtime_rate_assignments (
                overtime_rate_set_id,
                department_id       ,
                job_title_id        ,
                employee_id         ,
                assignment_level
            ) VALUES (
                :overtime_rate_set_id,
                :department_id       ,
                :job_title_id        ,
                :employee_id         ,
                :assignment_level
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':overtime_rate_set_id', $overtimeRateAssignment->getOvertimeRateSetId(), Helper::getPdoParameterType($overtimeRateAssignment->getOvertimeRateSetId()));
            $statement->bindValue(':department_id'       , $overtimeRateAssignment->getDepartmentId()     , Helper::getPdoParameterType($overtimeRateAssignment->getDepartmentId()     ));
            $statement->bindValue(':job_title_id'        , $overtimeRateAssignment->getJobTitleId()       , Helper::getPdoParameterType($overtimeRateAssignment->getJobTitleId()       ));
            $statement->bindValue(':employee_id'         , $overtimeRateAssignment->getEmployeeId()       , Helper::getPdoParameterType($overtimeRateAssignment->getEmployeeId()       ));
            $statement->bindValue(':assignment_level'    , $overtimeRateAssignment->getAssignmentLevel()  , Helper::getPdoParameterType($overtimeRateAssignment->getAssignmentLevel()  ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the overtime rate assignment. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
