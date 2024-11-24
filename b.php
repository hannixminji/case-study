<?php
// Define check-in and check-out times
$checkInTime = new DateTime('2024-11-24 22:00:00'); // 10:00 PM
$checkOutTime = new DateTime('2024-11-25 03:00:00'); // 3:00 AM next day

// Example: Check if check-in is before or equal to check-out
if ($checkInTime <= $checkOutTime) {
    echo "Check-in time is before or equal to check-out time.\n";
} else {
    echo "Check-in time is after check-out time.\n";
}

// Example: Check if check-out is after or equal to check-in
if ($checkOutTime >= $checkInTime) {
    echo "Check-out time is after or equal to check-in time.\n";
} else {
    echo "Check-out time is before check-in time.\n";
}

// Calculate the difference between check-in and check-out times
$interval = $checkInTime->diff($checkOutTime);

// Display the total duration worked
echo "Total duration worked: " . $interval->h . " hours " . $interval->i . " minutes.\n";
?>
