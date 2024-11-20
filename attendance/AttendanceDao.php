<?php

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
