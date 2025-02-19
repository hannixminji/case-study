<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

class PayslipDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Payslip $payslip): ActionResult
    {
        $query = "
            INSERT INTO payslips (
                employee_id                      ,
                payroll_group_id                 ,
                payday_date                      ,
                cutoff_start_date                ,
                cutoff_end_date                  ,
                total_regular_hours              ,
                total_overtime_hours             ,
                total_night_differential         ,
                total_night_differential_overtime,
                total_regular_holiday_hours      ,
                total_special_holiday_hours      ,
                total_days_worked                ,
                total_hours_worked               ,
                gross_pay                        ,
                net_pay                          ,
                sss_deduction                    ,
                philhealth_deduction             ,
                pagibig_fund_deduction           ,
                withholding_tax                  ,
                thirteen_month_pay               ,
                leave_salary
            )
            VALUES (
                :employee_id                      ,
                :payroll_group_id                 ,
                :payday_date                      ,
                :cutoff_start_date                ,
                :cutoff_end_date                  ,
                :total_regular_hours              ,
                :total_overtime_hours             ,
                :total_night_differential         ,
                :total_night_differential_overtime,
                :total_regular_holiday_hours      ,
                :total_special_holiday_hours      ,
                :total_days_worked                ,
                :total_hours_worked               ,
                :gross_pay                        ,
                :net_pay                          ,
                :sss_deduction                    ,
                :philhealth_deduction             ,
                :pagibig_fund_deduction           ,
                :withholding_tax                  ,
                :thirteen_month_pay               ,
                :leave_salary
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"                      , $payslip->getEmployeeId()                    , Helper::getPdoParameterType($payslip->getEmployeeId()                    ));
            $statement->bindValue(":payroll_group_id"                 , $payslip->getPayrollGroupId()                , Helper::getPdoParameterType($payslip->getPayrollGroupId()                ));
            $statement->bindValue(":payday_date"                      , $payslip->getpaydayDate()                    , Helper::getPdoParameterType($payslip->getpaydayDate()                    ));
            $statement->bindValue(":cutoff_start_date"                , $payslip->getCutoffStartDate()               , Helper::getPdoParameterType($payslip->getCutoffStartDate()               ));
            $statement->bindValue(":cutoff_end_date"                  , $payslip->getCutoffEndDate()                 , Helper::getPdoParameterType($payslip->getCutoffEndDate()                 ));
            $statement->bindValue(":total_regular_hours"              , $payslip->getTotalRegularHours()             , Helper::getPdoParameterType($payslip->getTotalRegularHours()             ));
            $statement->bindValue(":total_overtime_hours"             , $payslip->getTotalOvertimeHours()            , Helper::getPdoParameterType($payslip->getTotalOvertimeHours()            ));
            $statement->bindValue(":total_night_differential"         , $payslip->getTotalNightDifferential()        , Helper::getPdoParameterType($payslip->getTotalNightDifferential()        ));
            $statement->bindValue(":total_night_differential_overtime", $payslip->getTotalNightDifferentialOvertime(), Helper::getPdoParameterType($payslip->getTotalNightDifferentialOvertime()));
            $statement->bindValue(":total_regular_holiday_hours"      , $payslip->getTotalRegularHolidayHours()      , Helper::getPdoParameterType($payslip->getTotalRegularHolidayHours()      ));
            $statement->bindValue(":total_special_holiday_hours"      , $payslip->getTotalSpecialHolidayHours()      , Helper::getPdoParameterType($payslip->getTotalSpecialHolidayHours()      ));
            $statement->bindValue(":total_days_worked"                , $payslip->getTotalDaysWorked()               , Helper::getPdoParameterType($payslip->getTotalDaysWorked()               ));
            $statement->bindValue(":total_hours_worked"               , $payslip->getTotalHoursWorked()              , Helper::getPdoParameterType($payslip->getTotalHoursWorked()              ));
            $statement->bindValue(":gross_pay"                        , $payslip->getGrossPay()                      , Helper::getPdoParameterType($payslip->getGrossPay()                      ));
            $statement->bindValue(":net_pay"                          , $payslip->getNetPay()                        , Helper::getPdoParameterType($payslip->getNetPay()                        ));
            $statement->bindValue(":sss_deduction"                    , $payslip->getSssDeduction()                  , Helper::getPdoParameterType($payslip->getSssDeduction()                  ));
            $statement->bindValue(":philhealth_deduction"             , $payslip->getPhilhealthDeduction()           , Helper::getPdoParameterType($payslip->getPhilhealthDeduction()           ));
            $statement->bindValue(":pagibig_fund_deduction"           , $payslip->getPagibigFundDeduction()          , Helper::getPdoParameterType($payslip->getPagibigFundDeduction()          ));
            $statement->bindValue(":withholding_tax"                  , $payslip->getWithholdingTax()                , Helper::getPdoParameterType($payslip->getWithholdingTax()                ));
            $statement->bindValue(":thirteen_month_pay"               , $payslip->getThirteenMonthPay()              , Helper::getPdoParameterType($payslip->getThirteenMonthPay()              ));
            $statement->bindValue(":leave_salary"                     , $payslip->getLeaveSalary()                   , Helper::getPdoParameterType($payslip->getLeaveSalary()                   ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the payslip. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function fetchAll(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        $tableColumns = [
            "id"                                => "payslip.id                                AS id"                               ,
            "employee_id"                       => "payslip.employee_id                       AS employee_id"                      ,
            "payroll_group_id"                  => "payslip.payroll_group_id                  AS payroll_group_id"                 ,
            "payday_date"                       => "payslip.payday_date                       AS payday_date"                      ,
            "cutoff_start_date"                 => "payslip.cutoff_start_date                 AS cutoff_start_date"                ,
            "cutoff_end_date"                   => "payslip.cutoff_end_date                   AS cutoff_end_date"                  ,
            "total_regular_hours"               => "payslip.total_regular_hours               AS total_regular_hours"              ,
            "total_overtime_hours"              => "payslip.total_overtime_hours              AS total_overtime_hours"             ,
            "total_night_differential"          => "payslip.total_night_differential          AS total_night_differential"         ,
            "total_night_differential_overtime" => "payslip.total_night_differential_overtime AS total_night_differential_overtime",
            "total_regular_holiday_hours"       => "payslip.total_regular_holiday_hours       AS total_regular_holiday_hours"      ,
            "total_special_holiday_hours"       => "payslip.total_special_holiday_hours       AS total_special_holiday_hours"      ,
            "total_days_worked"                 => "payslip.total_days_worked                 AS total_days_worked"                ,
            "total_hours_worked"                => "payslip.total_hours_worked                AS total_hours_worked"               ,
            "gross_pay"                         => "payslip.gross_pay                         AS gross_pay"                        ,
            "net_pay"                           => "payslip.net_pay                           AS net_pay"                          ,
            "sss_deduction"                     => "payslip.sss_deduction                     AS sss_deduction"                    ,
            "philhealth_deduction"              => "payslip.philhealth_deduction              AS philhealth_deduction"             ,
            "pagibig_fund_deduction"            => "payslip.pagibig_fund_deduction            AS pagibig_fund_deduction"           ,
            "withholding_tax"                   => "payslip.withholding_tax                   AS withholding_tax"                  ,
            "thirteen_month_pay"                => "payslip.thirteen_month_pay                AS thirteen_month_pay"               ,
            "leave_salary"                      => "payslip.leave_salary                      AS leave_salary"                     ,
            "created_at"                        => "payslip.created_at                        AS created_at"                       ,
            "updated_at"                        => "payslip.updated_at                        AS updated_at"                       ,
            "deleted_at"                        => "payslip.deleted_at                        AS deleted_at"                       ,

            "employee_full_name"                => "employee.full_name                        AS full_name"                        ,
            "employee_code"                     => "employee.employee_code                    AS employee_code"                    ,
            "employee_employment_type"          => "employee.employment_type                  AS employment_type"                  ,
            "employee_basic_salary"             => "employee.basic_salary                     AS basic_salary"                     ,
            "employee_bank_name"                => "employee.bank_name                        AS bank_name"                        ,
            "employee_bank_branch_name"         => "employee.bank_branch_name                 AS bank_branch_name"                 ,
            "employee_bank_account_number"      => "employee.bank_account_number              AS bank_account_number"              ,
            "employee_bank_account_type"        => "employee.bank_account_type                AS bank_account_type"                ,

            "employee_job_title"                => "job_title.title                           AS job_title_title"                  ,

            "employee_department_name"          => "department.name                           AS department_name"
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("employee_full_name"          , $selectedColumns) ||
            array_key_exists("employee_code"               , $selectedColumns) ||
            array_key_exists("employee_department_name"    , $selectedColumns) ||
            array_key_exists("employee_employment_type"    , $selectedColumns) ||
            array_key_exists("employee_basic_salary"       , $selectedColumns) ||
            array_key_exists("employee_bank_name"          , $selectedColumns) ||
            array_key_exists("employee_bank_branch_name"   , $selectedColumns) ||
            array_key_exists("employee_bank_account_number", $selectedColumns) ||
            array_key_exists("employee_bank_account_type"  , $selectedColumns) ||

            array_key_exists("employee_job_title"          , $selectedColumns) ||

            array_key_exists("employee_department_name"    , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    employees AS employee
                ON
                    payslip.employee_id = employee.id
            ";
        }

        if (array_key_exists("employee_job_title", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    job_titles AS job_title
                ON
                    employee.job_title_id = job_title.id
            ";
        }

        if (array_key_exists("employee_department_name", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    departments AS department
                ON
                    employee.department_id = department.id
            ";
        }

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "payslip.deleted_at IS NULL";

        } else {
            foreach ($filterCriteria as $filterCriterion) {
                $column   = $filterCriterion["column"  ];
                $operator = $filterCriterion["operator"];

                switch ($operator) {
                    case "="   :
                    case "LIKE":
                        $whereClauses    [] = "{$column} {$operator} ?";
                        $queryParameters [] = $filterCriterion["value"];

                        $filterParameters[] = $filterCriterion["value"];

                        break;

                    case "BETWEEN":
                        $whereClauses    [] = "{$column} {$operator} ? AND ?";
                        $queryParameters [] = $filterCriterion["lower_bound"];
                        $queryParameters [] = $filterCriterion["upper_bound"];

                        $filterParameters[] = $filterCriterion["lower_bound"];
                        $filterParameters[] = $filterCriterion["upper_bound"];

                        break;
                }
            }
        }

        $orderByClauses = [];

        if ( ! empty($sortCriteria)) {
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
            SELECT
                " . implode(", ", $selectedColumns) . "
            FROM
                payslips AS payslip
            {$joinClauses}
            WHERE
                " . implode(" AND ", $whereClauses) . "
            " . ( ! empty($orderByClauses) ? "ORDER BY " . implode(", ", $orderByClauses) : "") . "
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

            $totalRowCount = null;

            if ($includeTotalRowCount) {
                $totalRowCountQuery = "
                    SELECT
                        COUNT(payslip.id)
                    FROM
                        payslips AS payslip
                    {$joinClauses}
                    WHERE
                        " . implode(" AND ", $whereClauses) . "
                ";

                $countStatement = $this->pdo->prepare($totalRowCountQuery);

                foreach ($filterParameters as $index => $parameter) {
                    $countStatement->bindValue($index + 1, $parameter, Helper::getPdoParameterType($parameter));
                }

                $countStatement->execute();

                $totalRowCount = $countStatement->fetchColumn();
            }

            return [
                "result_set"      => $resultSet    ,
                "total_row_count" => $totalRowCount
            ];

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while fetching the payslips. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function update(Payslip $payslip): ActionResult
    {
        $query = "
            UPDATE payslips
            SET
                employee_id                       = :employee_id                      ,
                payroll_group_id                  = :payroll_group_id                 ,
                payday_date                       = :payday_date                      ,
                cutoff_start_date                 = :cutoff_start_date                ,
                cutoff_end_date                   = :cutoff_end_date                  ,
                total_regular_hours               = :total_regular_hours              ,
                total_overtime_hours              = :total_overtime_hours             ,
                total_night_differential          = :total_night_differential         ,
                total_night_differential_overtime = :total_night_differential_overtime,
                total_regular_holiday_hours       = :total_regular_holiday_hours      ,
                total_special_holiday_hours       = :total_special_holiday_hours      ,
                total_days_worked                 = :total_days_worked                ,
                total_hours_worked                = :total_hours_worked               ,
                gross_pay                         = :gross_pay                        ,
                net_pay                           = :net_pay                          ,
                sss_deduction                     = :sss_deduction                    ,
                philhealth_deduction              = :philhealth_deduction             ,
                pagibig_fund_deduction            = :pagibig_fund_deduction           ,
                withholding_tax                   = :withholding_tax                  ,
                thirteen_month_pay                = :thirteen_month_pay               ,
                leave_salary                      = :leave_salary
            WHERE
        ";

        if (is_string($payslip->getId())) {
            $query .= " SHA2(id, 256) = :payslip_id";
        } else {
            $query .= " id = :payslip_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"                      , $payslip->getEmployeeId()                    , Helper::getPdoParameterType($payslip->getEmployeeId()                    ));
            $statement->bindValue(":payroll_group_id"                 , $payslip->getPayrollGroupId()                , Helper::getPdoParameterType($payslip->getPayrollGroupId()                ));
            $statement->bindValue(":payday_date"                      , $payslip->getpaydayDate()                    , Helper::getPdoParameterType($payslip->getpaydayDate()                    ));
            $statement->bindValue(":cutoff_start_date"                , $payslip->getCutoffStartDate()               , Helper::getPdoParameterType($payslip->getCutoffStartDate()               ));
            $statement->bindValue(":cutoff_end_date"                  , $payslip->getCutoffEndDate()                 , Helper::getPdoParameterType($payslip->getCutoffEndDate()                 ));
            $statement->bindValue(":total_regular_hours"              , $payslip->getTotalRegularHours()             , Helper::getPdoParameterType($payslip->getTotalRegularHours()             ));
            $statement->bindValue(":total_overtime_hours"             , $payslip->getTotalOvertimeHours()            , Helper::getPdoParameterType($payslip->getTotalOvertimeHours()            ));
            $statement->bindValue(":total_night_differential"         , $payslip->getTotalNightDifferential()        , Helper::getPdoParameterType($payslip->getTotalNightDifferential()        ));
            $statement->bindValue(":total_night_differential_overtime", $payslip->getTotalNightDifferentialOvertime(), Helper::getPdoParameterType($payslip->getTotalNightDifferentialOvertime()));
            $statement->bindValue(":total_regular_holiday_hours"      , $payslip->getTotalRegularHolidayHours()      , Helper::getPdoParameterType($payslip->getTotalRegularHolidayHours()      ));
            $statement->bindValue(":total_special_holiday_hours"      , $payslip->getTotalSpecialHolidayHours()      , Helper::getPdoParameterType($payslip->getTotalSpecialHolidayHours()      ));
            $statement->bindValue(":total_days_worked"                , $payslip->getTotalDaysWorked()               , Helper::getPdoParameterType($payslip->getTotalDaysWorked()               ));
            $statement->bindValue(":total_hours_worked"               , $payslip->getTotalHoursWorked()              , Helper::getPdoParameterType($payslip->getTotalHoursWorked()              ));
            $statement->bindValue(":gross_pay"                        , $payslip->getGrossPay()                      , Helper::getPdoParameterType($payslip->getGrossPay()                      ));
            $statement->bindValue(":net_pay"                          , $payslip->getNetPay()                        , Helper::getPdoParameterType($payslip->getNetPay()                        ));
            $statement->bindValue(":sss_deduction"                    , $payslip->getSssDeduction()                  , Helper::getPdoParameterType($payslip->getSssDeduction()                  ));
            $statement->bindValue(":philhealth_deduction"             , $payslip->getPhilhealthDeduction()           , Helper::getPdoParameterType($payslip->getPhilhealthDeduction()           ));
            $statement->bindValue(":pagibig_fund_deduction"           , $payslip->getPagibigFundDeduction()          , Helper::getPdoParameterType($payslip->getPagibigFundDeduction()          ));
            $statement->bindValue(":withholding_tax"                  , $payslip->getWithholdingTax()                , Helper::getPdoParameterType($payslip->getWithholdingTax()                ));
            $statement->bindValue(":thirteen_month_pay"               , $payslip->getThirteenMonthPay()              , Helper::getPdoParameterType($payslip->getThirteenMonthPay()              ));
            $statement->bindValue(":leave_salary"                     , $payslip->getLeaveSalary()                   , Helper::getPdoParameterType($payslip->getLeaveSalary()                   ));

            $statement->bindValue(":payslip_id"                       , $payslip->getId()                            , Helper::getPdoParameterType($payslip->getId()                            ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while updating the payslip. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
