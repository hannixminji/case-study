<?php

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
    exit('This resource is only accessible via AJAX requests.');
}

require_once __DIR__ . '/../DepartmentDao.php';
require_once __DIR__ . '/../Department.php';
require_once __DIR__ . '/../../includes/Helper.php';
require_once __DIR__ . '/../../includes/enums/ErrorCode.php';
require_once __DIR__ . '/../../database/database.php';

require_once __DIR__ . '/../../job-titles/JobTitle.php';
require_once __DIR__ . '/../../job-titles/JobTitleDao.php';

try {
    $userId = 1;
    $departmentDao = new DepartmentDao($pdo);
    $action = $_POST['action'] ?? '';
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $limit = 5;
    $offset = ($page - 1) * $limit;

    if ($action === 'fetchAll') {
        $data = $departmentDao->fetchAll([], [["column" => "status", "operator" => "=", "value" => "Active"]], [["column" => "department.created_at", "direction" => "DESC"]], $limit, $offset);
        $departments = $data["result_set"];
        $totalDepartments = $data["total_row_count"];
        $totalPages = ceil($totalDepartments / $limit);

        $jobTitleDao = new JobTitleDao($pdo);
        $filterCriteria = [
            [
                "column" => "job_title.status",
                "operator" => "=",
                "value" => "Active"
            ]
        ];
        $data2 = $jobTitleDao->fetchAll(["id", "title"], $filterCriteria);
        $jobTitles = $data2["result_set"];
        print_r($jobTitles);
        include __DIR__ . '/departmentsTable.php';
    } elseif ($action === 'create') {
        $departmentData = $_POST['department'] ?? null;

        if ($departmentData) {
            $name = $departmentData['name'] ?? '';
            $departmentHeadId = $departmentData['departmentHeadId'] ?? null;

            $newDepartment = new Department(
                id: null,
                name: $name,
                departmentHeadId: $departmentHeadId,
                description: null,
                status: "Active"
            );

            $result = $departmentDao->create($newDepartment, 1);

            if ($result) {
                echo "Department created successfully!";
            } else {
                echo "Failed to create department. Please try again.";
            }
        } else {
            echo "Invalid department data.";
        }
    } else {
        echo "Invalid action specified.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
