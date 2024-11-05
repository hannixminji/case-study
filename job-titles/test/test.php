<?php

require_once __DIR__ . '/../JobTitleDao.php';
require_once __DIR__ . '/../JobTitle.php';
require_once __DIR__ . '/../../includes/Helper.php';
require_once __DIR__ . '/../../includes/enums/ErrorCode.php';
require_once __DIR__ . '/../../database/database.php';

try {
    $jobTitleDao = new JobTitleDao($pdo);
    $userId = 1;

    // Kada refresh baguhin mo yung title, unique siya eh sa database
    $newJobTitle = new JobTitle(
        id: null,
        title: "Junior Developer",
        departmentId: 2,
        description: "Entry-level position for software development.",
        status: "Active"
    );
    $createResult = $jobTitleDao->create($newJobTitle, $userId);
    echo "Create result: " . $createResult->value . "<br>";

    $jobTitles = $jobTitleDao->fetchAll([], [], [["column" => "department_name", "direction" => "DESC"], ["column" => "id", "direction" => "ASC"]]);

    echo "Job Titles:<br>";

    if (empty($jobTitles)) {
        echo "No job titles found.<br>";
    } else {
        foreach ($jobTitles as $jobTitle) {
            if (isset($jobTitle['id'])) {
                echo "ID: " . $jobTitle['id'] . "<br>";
            }
            if (isset($jobTitle['title'])) {
                echo "Title: " . $jobTitle['title'] . "<br>";
            }
            if (isset($jobTitle['department_id'])) {
                echo "Department ID: " . $jobTitle['department_id'] . "<br>";
            }
            if (isset($jobTitle['department_name'])) {
                echo "Department Name: " . $jobTitle['department_name'] . "<br>";
            }
            if (isset($jobTitle['description'])) {
                echo "Description: " . $jobTitle['description'] . "<br>";
            }
            if (isset($jobTitle['status'])) {
                echo "Status: " . $jobTitle['status'] . "<br>";
            }
            if (isset($jobTitle['created_at'])) {
                echo "Created At: " . $jobTitle['created_at'] . "<br>";
            }
            if (isset($jobTitle['created_by'])) {
                echo "Created By: " . $jobTitle['created_by'] . "<br>";
            }
            if (isset($jobTitle['updated_at'])) {
                echo "Updated At: " . $jobTitle['updated_at'] . "<br>";
            }
            if (isset($jobTitle['updated_by'])) {
                echo "Updated By: " . $jobTitle['updated_by'] . "<br>";
            }
            if (isset($jobTitle['deleted_at'])) {
                echo "Deleted At: " . $jobTitle['deleted_at'] . "<br>";
            }
            if (isset($jobTitle['deleted_by'])) {
                echo "Deleted By: " . $jobTitle['deleted_by'] . "<br>";
            }
            echo "<hr>";
        }
    }

    $updatedJobTitle = new JobTitle(
        id: 1,
        title: "Senior Developer",
        departmentId: 2,
        description: "Updated description for Senior Developer",
        status: "Active"
    );
    $updateResult = $jobTitleDao->update($updatedJobTitle, $userId);
    echo "Update result: " . $updateResult->value . "<br>";

    $deleteResult = $jobTitleDao->delete(1, $userId);
    echo "Delete result: " . $deleteResult->value . "<br>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
