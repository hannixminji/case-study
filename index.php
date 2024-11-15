<?php

require 'vendor/autoload.php';

use RRule\RRule;

// Set the recurrence rule data for weekly recurrence on Friday (no need for specifying first, second, or last)
$weeklyOnFridayRule = new RRule([
    'FREQ' => 'WEEKLY',        // Weekly recurrence
    'INTERVAL' => 1,           // Every 1 week
    'DTSTART' => '2024-11-02', // Start date (Let's say this is November 1st, 2024, which is a Friday)
    'BYDAY' => ['FR']          // Occurs every Friday
]);

// Print occurrences
echo "Weekly Recurrence (Every Friday):\n";
foreach ($weeklyOnFridayRule as $occurrence) {
    echo $occurrence->format('D d M Y') . ", ";
}
echo "\n";

?>
