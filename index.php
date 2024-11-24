<?php

require 'vendor/autoload.php';

use RRule\RRule;

class RecurrenceExtractor
{
    public function getRecurrenceDatesInRange(string $recurrencePattern, string $startDate, string $endDate): array
    {
        $rules = $this->parseRecurrencePattern($recurrencePattern);
        if (!$rules) {
            throw new InvalidArgumentException("Invalid recurrence pattern.");
        }

        $rule = new RRule($rules);
        $dates = [];

        foreach ($rule as $occurrence) {
            $date = $occurrence->format('Y-m-d');
            if ($date > $endDate) {
                break;
            }
            if ($date >= $startDate) {
                $dates[] = $date;
            }
        }
        return $dates;
    }

    private function parseRecurrencePattern(string $pattern): ?array
    {
        $parts = explode(';', $pattern);
        $rules = [];
        foreach ($parts as $part) {
            $pair = explode('=', $part);
            if (count($pair) == 2) {
                $rules[$pair[0]] = $pair[1];
            }
        }

        return $rules;
    }
}

// Example Work Schedules (mock data with recurrence rules)
$workSchedulesFromDB = [
    [
        'id' => 1,
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
        'is_flextime' => false,
        'flextime_start_time' => null,
        'flextime_end_time' => null,
        'recurrence_rule' => "FREQ=WEEKLY;INTERVAL=1;DTSTART=2024-11-04;BYDAY=MO,TU,WE,TH,FR,SA,SU;"
    ],
    [
        'id' => 2,
        'start_time' => '12:00:00',
        'end_time' => '15:00:00',
        'is_flextime' => false,
        'flextime_start_time' => null,
        'flextime_end_time' => null,
        'recurrence_rule' => "FREQ=WEEKLY;INTERVAL=1;DTSTART=2024-11-04;BYDAY=TU,WE;"
    ],
    [
        'id' => 3,
        'start_time' => '15:00:00',
        'end_time' => '18:00:00',
        'is_flextime' => false,
        'flextime_start_time' => null,
        'flextime_end_time' => null,
        'recurrence_rule' => "FREQ=WEEKLY;INTERVAL=1;DTSTART=2024-11-04;BYDAY=MO,WE;"
    ],
    [
        'id' => 4,
        'start_time' => '18:00:00',
        'end_time' => '21:00:00',
        'is_flextime' => false,
        'flextime_start_time' => null,
        'flextime_end_time' => null,
        'recurrence_rule' => "FREQ=WEEKLY;INTERVAL=1;DTSTART=2024-11-04;BYDAY=TU,FR;"
    ]
];

// Define the date range to extract schedules
$startDate = '2024-11-13';
$endDate = '2024-11-15';

// Initialize the recurrence extractor
$recurrenceExtractor = new RecurrenceExtractor();

$workSchedules = [];

// Initialize all dates in the range as empty arrays (or null, as per your requirement)
$start = new DateTime($startDate);
$end = (new DateTime($endDate))->modify('+1 day');

$interval = new DateInterval('P1D');
$dateRange = new DatePeriod($start, $interval, $end);

foreach ($dateRange as $date) {
    $workSchedules[$date->format('Y-m-d')] = []; // Initialize with an empty array or null
}
// Output the work schedules grouped by date inside the array
echo '<pre>';
print_r($workSchedules);
echo '</pre>';
// Loop through each work schedule and extract the recurrence dates
foreach ($workSchedulesFromDB as $workSchedule) {
    $recurrenceRule = $workSchedule['recurrence_rule'];
    $recurrenceDates = $recurrenceExtractor->getRecurrenceDatesInRange($recurrenceRule, $startDate, $endDate);

    foreach ($recurrenceDates as $recurrenceDate) {
        // Populate the array with the schedules for the corresponding date
        $workSchedules[$recurrenceDate][] = $workSchedule;
    }
}

// Output the work schedules grouped by date inside the array
echo '<pre>';
print_r($workSchedules);
echo '</pre>';








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

/*
require_once __DIR__ . '/../includes/Helper.php'            ;
require_once __DIR__ . '/../includes/enums/ActionResult.php';
require_once __DIR__ . '/../includes/enums/ErrorCode.php'   ;

class AttendanceDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Attendance $attendance): ActionResult
    {
        $query = "
            INSERT INTO attendance (
                employee_id         ,
                date                ,
                shift_type          ,
                check_in_time       ,
                check_out_time      ,
                break_start_time    ,
                break_end_time      ,
                is_overtime_approved,
                attendance_status   ,
                remarks
            )
            VALUES (
                :employee_id         ,
                :date                ,
                :shift_type          ,
                :check_in_time       ,
                :check_out_time      ,
                :break_start_time    ,
                :break_end_time      ,
                :is_overtime_approved,
                :attendance_status   ,
                :remarks
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"         , $attendance->getEmployeeId()      , Helper::getPdoParameterType($attendance->getEmployeeId()      ));
            $statement->bindValue(":date"                , $attendance->getDate()            , Helper::getPdoParameterType($attendance->getDate()            ));
            $statement->bindValue(":shift_type"          , $attendance->getShiftType()       , Helper::getPdoParameterType($attendance->getShiftType()       ));
            $statement->bindValue(":check_in_time"       , $attendance->getCheckInTime()     , Helper::getPdoParameterType($attendance->getCheckInTime()     ));
            $statement->bindValue(":check_out_time"      , $attendance->getCheckOutTime()    , Helper::getPdoParameterType($attendance->getCheckOutTime()    ));
            $statement->bindValue(":break_start_time"    , $attendance->getBreakStartTime()  , Helper::getPdoParameterType($attendance->getBreakStartTime()  ));
            $statement->bindValue(":break_end_time"      , $attendance->getBreakEndTime()    , Helper::getPdoParameterType($attendance->getBreakEndTime()    ));
            $statement->bindValue(":is_overtime_approved", $attendance->isOvertimeApproved() , Helper::getPdoParameterType($attendance->isOvertimeApproved() ));
            $statement->bindValue(":attendance_status"   , $attendance->getAttendanceStatus(), Helper::getPdoParameterType($attendance->getAttendanceStatus()));
            $statement->bindValue(":remarks"             , $attendance->getRemarks()         , Helper::getPdoParameterType($attendance->getRemarks()         ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while creating the attendance record. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function fetchAll(
        ?array $columns        = null,
        ?array $filterCriteria = null,
        ?array $sortCriteria   = null,
        ?int   $limit          = null,
        ?int   $offset         = null
    ): ActionResult|array {
        $tableColumns = [
            "id"                  => "attendance.id                  AS id",
            "employee_id"         => "attendance.employee_id         AS employee_id",
            "employee_code" => "",
            "employee_full_name" => "",
            "employee_department" => "",
            "employee_job_title" => "",
            "date"                => "attendance.date                AS date",
            "day_of_week"         => "DAYNAME(attendance.date)       AS day_of_week",
            "shift_type"          => "attendance.shift_type          AS shift_type",
            "check_in_time"       => "attendance.check_in_time       AS check_in_time",
            "check_out_time"      => "attendance.check_out_time      AS check_out_time",
            "break_start_time"    => "attendance.break_start_time    AS break_start_time",
            "break_end_time"      => "attendance.break_end_time      AS break_end_time",
            "is_overtime_approved"=> "attendance.is_overtime_approved AS is_overtime_approved",
            "attendance_status"   => "attendance.attendance_status   AS attendance_status",
            "remarks"             => "attendance.remarks             AS remarks",
            "created_at"          => "attendance.created_at          AS created_at",
            "updated_at"          => "attendance.updated_at          AS updated_at"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $queryParameters = [];

        $whereClauses = [];

        if ( ! empty($filterCriteria)) {
            foreach ($filterCriteria as $filterCriterion) {
                $column   = $filterCriterion["column"  ];
                $operator = $filterCriterion["operator"];

                switch ($operator) {
                    case "=":
                    case "LIKE":
                        $whereClauses   [] = "{$column} {$operator} ?";
                        $queryParameters[] = $filterCriterion["value"];
                        break;

                    case "BETWEEN":
                        $whereClauses   [] = "{$column} {$operator} ? AND ?";
                        $queryParameters[] = $filterCriterion["lower_bound"];
                        $queryParameters[] = $filterCriterion["upper_bound"];
                        break;

                    default:
                        // Do nothing
                }
            }
        }

        $orderByClauses = [];

        if (!empty($sortCriteria)) {
            foreach ($sortCriteria as $sortCriterion) {
                $column = $sortCriterion["column"];

                if (isset($sortCriterion["direction"])) {
                    $direction = $sortCriterion["direction"];
                    $orderByClauses[] = "{$column} {$direction}";

                } elseif (isset($sortCriterion["custom_order"])) {
                    $customOrder = $sortCriterion["custom_order"];
                    $caseExpressions = ["CASE {$column}"];

                    foreach ($customOrder as $priority => $value) {
                        $caseExpressions[] = "WHEN ? THEN {$priority}";
                        $queryParameters[] = $value;
                    }

                    $caseExpressions[] = "ELSE " . count($caseExpressions) . " END";
                    $orderByClauses[] = implode(" ", $caseExpressions);
                }
            }
        }

        $limitClause = "";
        if ($limit !== null) {
            $limitClause = " LIMIT ?";
            $queryParameters[] = $limit;
        }

        $offsetClause = "";
        if ($offset !== null) {
            $offsetClause = " OFFSET ?";
            $queryParameters[] = $offset;
        }

        $query = "
            SELECT SQL_CALC_FOUND_ROWS
                " . implode(", ", $selectedColumns) . "
            FROM
                attendance
            WHERE
            " . implode(" AND ", $whereClauses) . "
            " . (!empty($orderByClauses) ? "ORDER BY " . implode(", ", $orderByClauses) : "") . "
            {$limitClause}
            {$offsetClause}
        ";

        try {
            $statement = $this->pdo->prepare($query);

            foreach ($queryParameters as $index => $parameter) {
                $statement->bindValue($index + 1, $parameter, Helper::getPdoParameterType($parameter));
            }

            $statement->execute();

            $resultSet = [];
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $resultSet[] = $row;
            }

            $countStatement = $this->pdo->query("SELECT FOUND_ROWS()");
            $totalRowCount = $countStatement->fetchColumn();

            return [
                "result_set"      => $resultSet    ,
                "total_row_count" => $totalRowCount
            ];

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching the attendance records. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(Attendance $attendance): ActionResult
    {
        $query = "
            UPDATE attendance
            SET
                employee_id          = :employee_id         ,
                date                 = :date                ,
                shift_type           = :shift_type          ,
                check_in_time        = :check_in_time       ,
                check_out_time       = :check_out_time      ,
                break_start_time     = :break_start_time    ,
                break_end_time       = :break_end_time      ,
                is_overtime_approved = :is_overtime_approved,
                attendance_status    = :attendance_status   ,
                remarks              = :remarks
            WHERE
                id = :attendance_id
        ";

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"         , $attendance->getEmployeeId()      , Helper::getPdoParameterType($attendance->getEmployeeId()      ));
            $statement->bindValue(":date"                , $attendance->getDate()            , Helper::getPdoParameterType($attendance->getDate()            ));
            $statement->bindValue(":shift_type"          , $attendance->getShiftType()       , Helper::getPdoParameterType($attendance->getShiftType()       ));
            $statement->bindValue(":check_in_time"       , $attendance->getCheckInTime()     , Helper::getPdoParameterType($attendance->getCheckInTime()     ));
            $statement->bindValue(":check_out_time"      , $attendance->getCheckOutTime()    , Helper::getPdoParameterType($attendance->getCheckOutTime()    ));
            $statement->bindValue(":break_start_time"    , $attendance->getBreakStartTime()  , Helper::getPdoParameterType($attendance->getBreakStartTime()  ));
            $statement->bindValue(":break_end_time"      , $attendance->getBreakEndTime()    , Helper::getPdoParameterType($attendance->getBreakEndTime()    ));
            $statement->bindValue(":is_overtime_approved", $attendance->isOvertimeApproved() , Helper::getPdoParameterType($attendance->isOvertimeApproved() ));
            $statement->bindValue(":attendance_status"   , $attendance->getAttendanceStatus(), Helper::getPdoParameterType($attendance->getAttendanceStatus()));
            $statement->bindValue(":remarks"             , $attendance->getRemarks()         , Helper::getPdoParameterType($attendance->getRemarks()         ));
            $statement->bindValue(":attendance_id"       , $attendance->getId()              , Helper::getPdoParameterType($attendance->getId()              ));

            $statement->execute();

            $this->pdo->commit();

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            error_log("Database Error: An error occurred while updating the attendance record. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
*/
