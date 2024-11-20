<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

class EmployeeBreakDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function breakIn(EmployeeBreak $employeeBreak): ActionResult
    {
        $query = "
            INSERT INTO employee_breaks (
                employee_id  ,
                break_type_id,
                start_time
            )
            VALUES (
                :employee_id  ,
                :break_type_id,
                :start_time
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"  , $employeeBreak->getEmployeeId() , Helper::getPdoParameterType($employeeBreak->getEmployeeId() ));
            $statement->bindValue(":break_type_id", $employeeBreak->getBreakTypeId(), Helper::getPdoParameterType($employeeBreak->getBreakTypeId()));
            $statement->bindValue(":start_time"   , $employeeBreak->getStartTime()  , Helper::getPdoParameterType($employeeBreak->getStartTime()  ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while recording the break in. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
