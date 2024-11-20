<?php

require 'vendor/autoload.php';

use RRule\RRule;

class RecurrenceExtractor
{
    public function getRecurrenceDatesInRange(string $recurrencePattern, string $startDate, string $endDate): array
    {
        // Parse the recurrence pattern into an associative array
        $rules = $this->parseRecurrencePattern($recurrencePattern);

        if (!$rules) {
            throw new InvalidArgumentException("Invalid recurrence pattern.");
        }

        // Create an RRule object based on the parsed rules
        $rules['DTSTART'] = $startDate; // Ensure the start date is passed to the RRule
        $rule = new RRule($rules);

        // Collect the specific dates from the recurrence rule
        $dates = [];

        // Loop through the occurrences and stop when we exceed the end date
        foreach ($rule as $occurrence) {
            // Convert the occurrence to Y-m-d format
            $date = $occurrence->format('Y-m-d');

            // Check if the date is within the range
            if ($date > $endDate) {
                break; // Stop if we exceed the end date
            }

            // Add the date if it is within the range
            $dates[] = $date;
        }

        return $dates;
    }

    private function parseRecurrencePattern(string $pattern): ?array
    {
        // Split the recurrence pattern into key-value pairs
        $parts = explode(';', $pattern);
        $rules = [];

        foreach ($parts as $part) {
            // Each part is in the format KEY=VALUE
            $pair = explode('=', $part);
            if (count($pair) == 2) {
                $rules[$pair[0]] = $pair[1];
            }
        }

        // Check if mandatory keys are present (e.g., FREQ and DTSTART)
        if (isset($rules['FREQ']) && isset($rules['DTSTART'])) {
            return $rules;
        }

        return null;
    }
}

// Usage Example
$recurrencePattern = "FREQ=WEEKLY;INTERVAL=1;DTSTART=2024-11-02;BYDAY=MO,FR;"; // Example pattern
$recurrenceExtractor = new RecurrenceExtractor();

try {
    // Get the recurrence dates within the range from 2024-11-01 to 2024-12-31
    $dates = $recurrenceExtractor->getRecurrenceDatesInRange($recurrencePattern, '2024-11-01', '2024-12-31');

    echo "Recurrence Dates from 2024-11-01 to 2024-12-31:\n";
    foreach ($dates as $date) {
        echo $date . "\n";
    }
} catch (InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}


/*
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

    public function getRecurrenceDates(string $recurrencePattern, string $startDate, string $endDate): array
    {
        $rules = $this->parseRecurrencePattern($recurrencePattern);


        $rules["DTSTART"] = $startDate;
        $rule = new RRule($rules);

        $dates = [];

        foreach ($rule as $occurrence) {
            $date = $occurrence->format('Y-m-d');

            if ($date > $endDate) {
                return $dates;
            }

            $dates[] = $date;
        }

        return $dates;
    }

    public function getRecurrenceDates(string $recurrenceRule, string $startDate, string $endDate): array
    {
        $parsedRecurrenceRule = $this->parseRecurrenceRule($recurrenceRule);

        $recurrence = new RRule($parsedRecurrenceRule);

        $dates = [];

        foreach ($recurrence as $occurence) {
            $date = $occurence->format("Y-m-d");

            if ($date > $endDate) {
                return $dates;
            }

            $dates[] = $date;
        }

        return $dates;
    }

    public function getRecurrenceDates(string $recurrenceRule, string $startDate, string $endDate): array
    {
        $parsedRecurrenceRule = $this->parseRecurrenceRule($recurrenceRule);

        $recurrence = new RRule($parsedRecurrenceRule);

        $dates = [];

        foreach ($recurrence as $occurence) {
            $date = $occurence->format("Y-m-d");

            if ($date > $endDate) {
                return $dates;
            }

            $dates[] = $date;
        }

        return $dates;
    }

    private function parseRecurrenceRule(string $rule): array
    {
        $parts = explode(";", $rule);

        $parsedRule = [];

        foreach ($parts as $part) {
            [$key, $value] = explode("=", $part, 2);

            $parsedRule[$key] = $value;
        }

        return $parsedRule;
    }
*/

