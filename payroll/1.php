<?php
// Assuming you have a PDO connection established with your MySQL database
// Replace with your own database connection details
$pdo = new PDO("mysql:host=localhost;dbname=payroll", "root", "");

// Step 1: Fetch the data from MySQL
$query = "
SELECT
    employee_breaks.id,
    employee_breaks.attendance_id,
    employee_breaks.break_schedule_id,
    employee_breaks.start_time,
    employee_breaks.end_time,
    employee_breaks.break_duration_in_minutes,
    employee_breaks.created_at,
    employee_breaks.updated_at,
    employee_breaks.deleted_at,
    break_schedule.start_time AS break_schedule_start_time,
    break_schedule.earliest_start_time AS break_schedule_earliest_start_time
FROM
    employee_breaks
LEFT JOIN break_schedules AS break_schedule
    ON employee_breaks.break_schedule_id = break_schedule.id
";
echo '<pre>';
// Execute the query
$stmt = $pdo->query($query);
$employeeBreaks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Step 2: Define a custom sorting function
usort($employeeBreaks, function($a, $b) {
    // Get the valid start time, prioritizing break_schedule_start_time over break_schedule_earliest_start_time
    $startA = isset($a['break_schedule_start_time']) ? new DateTime($a['break_schedule_start_time']) : new DateTime($a['break_schedule_earliest_start_time']);
    $startB = isset($b['break_schedule_start_time']) ? new DateTime($b['break_schedule_start_time']) : new DateTime($b['break_schedule_earliest_start_time']);

    if ($startA == $startB) {
        // If start times are equal, compare end times
        $endA = isset($a['end_time']) ? new DateTime($a['end_time']) : null;
        $endB = isset($b['end_time']) ? new DateTime($b['end_time']) : null;
        return $endA <=> $endB;
    }
    return $startA <=> $startB;
});

// Step 3: Output the sorted breaks
echo "Sorted Employee Breaks:\n";
foreach ($employeeBreaks as $break) {
    echo "Break ID: " . $break['id'] . "\n";
    echo "Start Time: " . $break['start_time'] . "\n";
    echo "Break Schedule Start Time: " . $break['break_schedule_start_time'] . "\n";
    echo "Break Schedule Earliest Start Time: " . $break['break_schedule_earliest_start_time'] . "\n";
    echo "----------------------\n";
}
?>
