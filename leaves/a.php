<?php

    public function update(LeaveRequest $leaveRequest, int $userId, bool $isAdmin): ActionResult
    {
        $query = '
            UPDATE leave_requests
            SET
                employee_id         = :employee_id,
                leave_type_id       = :leave_type_id,
                start_date          = :start_date,
                end_date            = :end_date,
                reason              = :reason,
                updated_by_admin    = :updated_by_admin,
                updated_by_employee = :updated_by_employee
            WHERE
                id = :leave_request_id
        ';

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(':employee_id', $leaveRequest->getEmployeeId(), Helper::getPdoParameterType($leaveRequest->getEmployeeId()));
            $statement->bindValue(':leave_type_id', $leaveRequest->getLeaveTypeId(), Helper::getPdoParameterType($leaveRequest->getLeaveTypeId()));
            $statement->bindValue(':start_date', $leaveRequest->getStartDate(), Helper::getPdoParameterType($leaveRequest->getStartDate()));
            $statement->bindValue(':end_date', $leaveRequest->getEndDate(), Helper::getPdoParameterType($leaveRequest->getEndDate()));
            $statement->bindValue(':reason', $leaveRequest->getReason(), Helper::getPdoParameterType($leaveRequest->getReason()));

            if ($isAdmin) {
                $statement->bindValue(':updated_by_admin', $userId, Helper::getPdoParameterType($userId));
                $statement->bindValue(':updated_by_employee', null, PDO::PARAM_NULL);
            } else {
                $statement->bindValue(':updated_by_admin', null, PDO::PARAM_NULL);
                $statement->bindValue(':updated_by_employee', $userId, Helper::getPdoParameterType($userId));
            }

            $statement->bindValue(':leave_request_id', $leaveRequest->getId(), Helper::getPdoParameterType($leaveRequest->getId()));

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
