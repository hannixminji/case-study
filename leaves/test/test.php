<?php

require_once __DIR__ . '/LeaveType.php';
require_once __DIR__ . '/LeaveTypeDao.php';
require_once __DIR__ . '/../includes/Helper.php';
require_once __DIR__ . '/../includes/enums/ErrorCode.php';
require_once __DIR__ . '/../database/database.php';

try {
    $leaveTypeDao = new LeaveTypeDao($pdo);
    $userId = 1;

    $newLeaveType = new LeaveType(
        id: null,
        name: "Vacation Leave",
        maximumNumberOfDays: 50,
        isPaid: true,
        description: "Sick Leave",
        status: "Active"
    );
    $createResult = $leaveTypeDao->create($newLeaveType, $userId);
    echo "Create result: " . $createResult->value . "<br>";

    $leaveTypes = $leaveTypeDao->fetchAll([], [], [["column" => "name", "direction" => "ASC"]]);

    echo "Leave Types:<br>";

    if (empty($leaveTypes)) {
        echo "No leave types found.<br>";
    } else {
        foreach ($leaveTypes as $leaveType) {
            if (isset($leaveType['id'])) {
                echo "ID: " . $leaveType['id'] . "<br>";
            }
            if (isset($leaveType['name'])) {
                echo "Name: " . $leaveType['name'] . "<br>";
            }
            if (isset($leaveType['description'])) {
                echo "Description: " . $leaveType['description'] . "<br>";
            }
            if (isset($leaveType['maximum_number_of_days'])) {
                echo "Maximum Number of Days: " . $leaveType['maximum_number_of_days'] . "<br>";
            }
            if (isset($leaveType['is_paid'])) {
                echo "Is Paid: " . ($leaveType['is_paid'] ? 'Yes' : 'No') . "<br>";
            }
            if (isset($leaveType['status'])) {
                echo "Status: " . $leaveType['status'] . "<br>";
            }
            if (isset($leaveType['created_at'])) {
                echo "Created At: " . $leaveType['created_at'] . "<br>";
            }
            if (isset($leaveType['created_by'])) {
                echo "Created By: " . $leaveType['created_by'] . "<br>";
            }
            if (isset($leaveType['updated_at'])) {
                echo "Updated At: " . $leaveType['updated_at'] . "<br>";
            }
            if (isset($leaveType['updated_by'])) {
                echo "Updated By: " . $leaveType['updated_by'] . "<br>";
            }
            if (isset($leaveType['deleted_at'])) {
                echo "Deleted At: " . $leaveType['deleted_at'] . "<br>";
            }
            if (isset($leaveType['deleted_by'])) {
                echo "Deleted By: " . $leaveType['deleted_by'] . "<br>";
            }
            echo "<hr>";
        }
    }

    $updatedLeaveType = new LeaveType(
        id: 1,
        name: "Sick Leave",
        maximumNumberOfDays: 14,
        isPaid: false,
        description: "Updated description for Sick Leave",
        status: "Active"
    );
    $updateResult = $leaveTypeDao->update($updatedLeaveType, $userId);
    echo "Update result: " . $updateResult->value . "<br>";

    $deleteResult = $leaveTypeDao->delete(1, $userId);
    echo "Delete result: " . $deleteResult->value . "<br>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
