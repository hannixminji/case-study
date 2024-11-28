<?php

require 'vendor/autoload.php';

use RRule\RSet;

function getRecurrenceDates(string $recurrenceRule, string $startDate, string $endDate): ActionResult|array
{
    try {
        $recurrenceSet = new RSet();

        $datesToExclude = "";

        if (strpos($recurrenceRule, "EXDATE=") !== false) {
            list($recurrenceRule, $datesToExclude) = explode("EXDATE=", $recurrenceRule);
            $datesToExclude = rtrim($datesToExclude, ";");
        }

        $parsedRecurrenceRule = parseRecurrenceRule($recurrenceRule);
        $recurrenceSet->addRRule($parsedRecurrenceRule);

        if ( ! empty($datesToExclude)) {
            $excludeDates = explode(",", $datesToExclude);

            foreach ($excludeDates as $excludedDate) {
                $recurrenceSet->addExDate($excludedDate);
            }
        }

        $startDate = (new DateTime($startDate))->format("Y-m-d");
        $endDate   = (new DateTime($endDate  ))->format("Y-m-d");

        $dates = [];

        foreach ($recurrenceSet as $occurence) {
            $date = $occurence->format("Y-m-d");

            if ($date > $endDate) {
                return $dates;
            }

            if ($date >= $startDate && $date <= $endDate) {
                $dates[] = $date;
            }
        }

        return $dates;

    } catch (InvalidArgumentException $exception) {
        error_log("Invalid Argument Error: An error occurred while processing the recurrence rule. " .
                  "Exception: {$exception->getMessage()}");

        return ActionResult::FAILURE;

    } catch (Exception $exception) {
        error_log("General Error: An error occurred while processing the recurrence dates. " .
                  "Exception: {$exception->getMessage()}");

        return ActionResult::FAILURE;
    }
}

function parseRecurrenceRule(string $rule): ActionResult|array
{
    try {
        $rule = rtrim($rule, ";");

        $parts = explode(";", $rule);

        $parsedRule = [];

        foreach ($parts as $part) {
            [$key, $value] = explode("=", $part, 2);

            $parsedRule[$key] = $value;
        }

        return $parsedRule;

    } catch (Exception $exception) {
        error_log("Parsing Error: An error occurred while parsing the recurrence rule. " .
                  "Exception: {$exception->getMessage()}");

        return ActionResult::FAILURE;
    }
}

$recurrenceRule = "FREQ=WEEKLY;INTERVAL=1;DTSTART=2024-11-04;BYDAY=MO,TU,WE,TH,FR,SA,SU;EXDATE=2024-11-04,2024-11-06;";
$startDate = "2024-11-01";
$endDate = "2024-11-30";

$dates = getRecurrenceDates($recurrenceRule, $startDate, $endDate);
echo '<pre>';
print_r($dates);
echo '<pre>';
