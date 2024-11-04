<?php

require_once __DIR__ . '/../includes/Helper.php'            ;
require_once __DIR__ . '/../includes/enums/ActionResult.php';
require_once __DIR__ . '/../includes/enums/ErrorCode.php'   ;

class DepartmentDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(LeaveRequest $leaveRequest, int $userId): ActionResult
    {
        $query = '
            INSERT INTO leave_requests (
                employee_id        ,
                leave_type_id      ,
                start_date         ,
                end_date           ,
                reason             ,
                status             ,
                created_by_employee,
                updated_by_employee
            )
            VALUES (
                :employee_id        ,
                :leave_type_id      ,
                :start_date         ,
                :end_date           ,
                :reason             ,
                :status             ,
                :created_by_employee,
                :updated_by_employee
            )
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':employee_id'        , $leaveRequest->getEmployeeId() , Helper::getPdoParameterType($leaveRequest->getEmployeeId() ));
            $statement->bindValue(':leave_type_id'      , $leaveRequest->getLeaveTypeId(), Helper::getPdoParameterType($leaveRequest->getLeaveTypeId()));
            $statement->bindValue(':start_date'         , $leaveRequest->getStartDate()  , Helper::getPdoParameterType($leaveRequest->getStartDate()  ));
            $statement->bindValue(':end_date'           , $leaveRequest->getEndDate()    , Helper::getPdoParameterType($leaveRequest->getEndDate()    ));
            $statement->bindValue(':reason'             , $leaveRequest->getReason()     , Helper::getPdoParameterType($leaveRequest->getReason()     ));
            $statement->bindValue(':status'             , $leaveRequest->getStatus()     , Helper::getPdoParameterType($leaveRequest->getStatus()     ));
            $statement->bindValue(':created_by_employee', $userId                        , Helper::getPdoParameterType($userId                        ));
            $statement->bindValue(':updated_by_employee', $userId                        , Helper::getPdoParameterType($userId                        ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while creating the leave request. ' .
                      'Exception: ' . $exception->getMessage());

            return ActionResult::FAILURE;
        }
    }

    public function update(LeaveRequest $leaveRequest, int $userId): ActionResult
    {
        $query = '
            UPDATE leave_requests
            SET
                employee_id          = :employee_id         ,
                leave_type_id        = :leave_type_id       ,
                start_date           = :start_date          ,
                end_date             = :end_date            ,
                reason               = :reason              ,
                status               = :status              ,
                approved_at          = :approved_at         ,
                approved_by_admin    = :approved_by_admin   ,
                approved_by_employee = :approved_by_employee
            WHERE
                id = :leave_request_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':employee_id'         , $leaveRequest->getEmployeeId()        , Helper::getPdoParameterType($leaveRequest->getEmployeeId()        ));
            $statement->bindValue(':leave_type_id'       , $leaveRequest->getLeaveTypeId()       , Helper::getPdoParameterType($leaveRequest->getLeaveTypeId()       ));
            $statement->bindValue(':start_date'          , $leaveRequest->getStartDate()         , Helper::getPdoParameterType($leaveRequest->getStartDate()         ));
            $statement->bindValue(':end_date'            , $leaveRequest->getEndDate()           , Helper::getPdoParameterType($leaveRequest->getEndDate()           ));
            $statement->bindValue(':reason'              , $leaveRequest->getReason()            , Helper::getPdoParameterType($leaveRequest->getReason()            ));
            $statement->bindValue(':status'              , $leaveRequest->getStatus()            , Helper::getPdoParameterType($leaveRequest->getStatus()            ));
            $statement->bindValue(':approved_at'         , $leaveRequest->getApprovedAt()        , Helper::getPdoParameterType($leaveRequest->getApprovedAt()        ));
            $statement->bindValue(':approved_by_admin'   , $leaveRequest->getApprovedByAdmin()   , Helper::getPdoParameterType($leaveRequest->getApprovedByAdmin()   ));
            $statement->bindValue(':approved_by_employee', $leaveRequest->getApprovedByEmployee(), Helper::getPdoParameterType($leaveRequest->getApprovedByEmployee()));
            $statement->bindValue(':leave_request_id'    , $leaveRequest->getId()                , Helper::getPdoParameterType($leaveRequest->getId()                ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log('Database Error: An error occurred while updating the leave request. ' .
                      'Exception: ' . $exception->getMessage());

            return ActionResult::FAILURE;
        }
    }
}
