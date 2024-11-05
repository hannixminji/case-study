<?php

require_once __DIR__ . '/../includes/Helper.php'            ;
require_once __DIR__ . '/../includes/enums/ActionResult.php';
require_once __DIR__ . '/../includes/enums/ErrorCode.php'   ;

class LeaveEntitlementDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(LeaveEntitlement $leaveEntitlement): ActionResult
    {
        $query = '
            INSERT INTO leave_entitlements (
                employee_id            ,
                leave_type_id          ,
                number_of_entitled_days,
                number_of_days_taken   ,
                remaining_days
            )
            VALUES (
                :employee_id            ,
                :leave_type_id          ,
                :number_of_entitled_days,
                :number_of_days_taken   ,
                :remaining_days
            )
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':employee_id'            , $leaveEntitlement->getEmployeeId()          , Helper::getPdoParameterType($leaveEntitlement->getEmployeeId()          ));
            $statement->bindValue(':leave_type_id'          , $leaveEntitlement->getLeaveTypeId()         , Helper::getPdoParameterType($leaveEntitlement->getLeaveTypeId()         ));
            $statement->bindValue(':number_of_entitled_days', $leaveEntitlement->getNumberOfEntitledDays(), Helper::getPdoParameterType($leaveEntitlement->getNumberOfEntitledDays()));
            $statement->bindValue(':number_of_days_taken'   , $leaveEntitlement->getNumberOfDaysTaken()   , Helper::getPdoParameterType($leaveEntitlement->getNumberOfDaysTaken()   ));
            $statement->bindValue(':remaining_days'         , $leaveEntitlement->getRemainingDays()       , Helper::getPdoParameterType($leaveEntitlement->getRemainingDays()       ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while creating the leave entitlement. ' .
                      'Exception: ' . $exception->getMessage());

            return ActionResult::FAILURE;
        }
    }



    public function delete(int $leaveEntitlementId): ActionResult
    {
        return $this->softDelete($leaveEntitlementId);
    }

    private function softDelete(int $leaveEntitlementId): ActionResult
    {
        $query = '
            UPDATE leave_entitlements
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
                id = :leave_entitlement_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':leave_entitlement_id', $leaveEntitlementId, PDO::PARAM_INT);

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while deleting the leave entitlement. ' .
                    'Exception: ' . $exception->getMessage());

            return ActionResult::FAILURE;
        }
    }
}
