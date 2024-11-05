<?php

require_once __DIR__ . '/../DepartmentDao.php';
require_once __DIR__ . '/../Department.php';
require_once __DIR__ . '/../../includes/Helper.php';
require_once __DIR__ . '/../../includes/enums/ErrorCode.php';
require_once __DIR__ . '/../../database/database.php';

try {
    $departmentDao = new DepartmentDao($pdo);
    $userId = 1;

    $departments = $departmentDao->fetchAll([], [], [["column" => "department.name", "custom_order" =>  ["Okay 2", "IT Department"]]]);

    echo "Departments:<br>";

    if (empty($departments)) {
        echo "No departments found.<br>";
    } else {
        foreach ($departments as $department) {
            if (isset($department['id'])) {
                echo "ID: " . $department['id'] . "<br>";
            }
            if (isset($department['name'])) {
                echo "Name: " . $department['name'] . "<br>";
            }
            if (isset($department['department_head_id'])) {
                echo "Department Head ID: " . $department['department_head_id'] . "<br>";
            }
            if (isset($department['department_head_first_name'])) {
                echo "Department Head First Name: " . $department['department_head_first_name'] . "<br>";
            }
            if (isset($department['department_head_middle_name'])) {
                echo "Department Head Middle Name: " . $department['department_head_middle_name'] . "<br>";
            }
            if (isset($department['department_head_last_name'])) {
                echo "Department Head Last Name: " . $department['department_head_last_name'] . "<br>";
            }
            if (isset($department['description'])) {
                echo "Description: " . $department['description'] . "<br>";
            }
            if (isset($department['status'])) {
                echo "Status: " . $department['status'] . "<br>";
            }
            if (isset($department['created_at'])) {
                echo "Created At: " . $department['created_at'] . "<br>";
            }
            if (isset($department['created_by'])) {
                echo "Created By: " . $department['created_by'] . "<br>";
            }
            if (isset($department['updated_at'])) {
                echo "Updated At: " . $department['updated_at'] . "<br>";
            }
            if (isset($department['updated_by'])) {
                echo "Updated By: " . $department['updated_by'] . "<br>";
            }
            if (isset($department['deleted_at'])) {
                echo "Deleted At: " . $department['deleted_at'] . "<br>";
            }
            if (isset($department['deleted_by'])) {
                echo "Deleted By: " . $department['deleted_by'] . "<br>";
            }
            echo "<hr>";
        }
    }

    $updatedDepartment = new Department(
        id: 36,
        name: "IT Department",
        departmentHeadId: 2,
        description: "Updated description for IT Department",
        status: "Active"
    );
    $updateResult = $departmentDao->update($updatedDepartment, $userId);
    echo "Update result: " . $updateResult->value . "<br>";

    $deleteResult = $departmentDao->delete(5, $userId);
    echo "Delete result: " . $deleteResult->value . "<br>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
