<?php

require_once __DIR__ . '/../LeaveRequest.php';
require_once __DIR__ . '/../LeaveRequestDao.php';
require_once __DIR__ . '/../../includes/Helper.php';
require_once __DIR__ . '/../../includes/enums/ErrorCode.php';
require_once __DIR__ . '/../../database/database.php';

try {
    $leaveRequestDao = new LeaveRequestDao($pdo);
    $userId = 2; // employee id, employee lang pwede magapply ng leave kaya yung userid is employee dapat
/*
    $newLeaveRequest = new LeaveRequest(
        id: null,
        employeeId: 5,
        leaveTypeId: 2,
        startDate: '2024-12-01',
        endDate: '2024-11-05',
        reason: 'Vacation',
        status: 'Pending'
    );

    $createResult = $leaveRequestDao->create($newLeaveRequest);
    echo "Create result: " . $createResult->value . "<br>";
*/
    $leaveRequests = $leaveRequestDao->fetchAll([], [], [["column" => "leave_request.start_date", "direction" => "ASC"]]);

    echo "Leave Requests:<br>";

    if (empty($leaveRequests)) {
        echo "No leave requests found.<br>";
    } else {
        foreach ($leaveRequests as $leaveRequest) {
            if (isset($leaveRequest['id'])) {
                echo "ID: " . $leaveRequest['id'] . "<br>";
            }
            if (isset($leaveRequest['employee_id'])) {
                echo "Employee ID: " . $leaveRequest['employee_id'] . "<br>";
            }
            if (isset($leaveRequest['employee_first_name'])) {
                echo "Employee First Name: " . $leaveRequest['employee_first_name'] . "<br>";
            }
            if (isset($leaveRequest['leave_type_name'])) {
                echo "Leave Type: " . $leaveRequest['leave_type_name'] . "<br>";
            }
            if (isset($leaveRequest['start_date'])) {
                echo "Start Date: " . $leaveRequest['start_date'] . "<br>";
            }
            if (isset($leaveRequest['end_date'])) {
                echo "End Date: " . $leaveRequest['end_date'] . "<br>";
            }
            if (isset($leaveRequest['reason'])) {
                echo "Reason: " . $leaveRequest['reason'] . "<br>";
            }
            if (isset($leaveRequest['status'])) {
                echo "Status: " . $leaveRequest['status'] . "<br>";
            }
            if (isset($leaveRequest['approved_at'])) {
                echo "Approved At: " . $leaveRequest['approved_at'] . "<br>";
            }
            if (isset($leaveRequest['approved_by_admin'])) {
                echo "Approved By Admin: " . $leaveRequest['approved_by_admin'] . "<br>";
            }
            if (isset($leaveRequest['approved_by_employee'])) {
                echo "Approved By Employee: " . $leaveRequest['approved_by_employee'] . "<br>";
            }
            echo "<hr>";
        }
    }

    $updatedLeaveRequest = new LeaveRequest(
        id: 1,
        employeeId: 1,
        leaveTypeId: 2,
        startDate: '2024-11-01',
        endDate: '2024-11-10',
        reason: 'Updated vacation request',
        status: 'Pending'
    );

    $updateResult = $leaveRequestDao->update($updatedLeaveRequest, $userId, true);
    echo "Update result: " . $updateResult->value . "<br>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
