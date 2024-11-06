<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";
require_once __DIR__ . "/../includes/enums/ErrorCode.php"   ;

class EmployeeAllowanceDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function assignAllowanceToEmployee(EmployeeAllowance $employeeAllowance): ActionResult
    {
        $query = "
            INSERT INTO employee_allowances (
                employee_id ,
                allowance_id,
                amount
            )
            VALUES (
                :employee_id ,
                :allowance_id,
                :amount
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id" , $employeeAllowance->getEmployeeId() , Helper::getPdoParameterType($employeeAllowance->getEmployeeId() ));
            $statement->bindValue(":allowance_id", $employeeAllowance->getAllowanceId(), Helper::getPdoParameterType($employeeAllowance->getAllowanceId()));
            $statement->bindValue(":amount"      , $employeeAllowance->getAmount()     , Helper::getPdoParameterType($employeeAllowance->getAmount()     ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while assigning the allowance to employee. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function fetchAllowancesByEmployee(int $employeeId): ActionResult|array
    {
        $query = "
            SELECT
                allowance.id              AS allowance_id  ,
                allowance.name            AS allowance_name,
                employee_allowance.amount AS amount
            FROM
                employee_allowances AS employee_allowance
            JOIN
                allowances AS allowance
            ON
                employee_allowance.allowance_id = allowance.id
            WHERE
                employee_allowance.employee_id = :employee_id
        ";

        try {
            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id", $employeeId, Helper::getPdoParameterType($employeeId));

            $statement->execute();

            $resultSet = [];
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $resultSet[] = $row;
            }

            return $resultSet;

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching employee allowances. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int $employeeAllowanceId): ActionResult
    {
        return $this->softDelete($employeeAllowanceId);
    }

    private function softDelete(int $employeeAllowanceId): ActionResult
    {
        $query = '
            UPDATE employee_allowances
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
                id = :employee_allowance_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':employee_allowance_id', $employeeAllowanceId, PDO::PARAM_INT);

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while deleting the employee allowance. ' .
                    'Exception: ' . $exception->getMessage());

            return ActionResult::FAILURE;
        }
    }
}
