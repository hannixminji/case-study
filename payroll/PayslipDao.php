<?php

require_once __DIR__ . "/Payslip.php"                       ;

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
                employee_id           ,
                payroll_group_id      ,
                pay_date              ,
                pay_period_start_date ,
                pay_period_end_date   ,
                basic_salary          ,
                basic_pay             ,
                gross_pay             ,
                net_pay               ,
                sss_deduction         ,
                philhealth_deduction  ,
                pagibig_fund_deduction,
                withholding_tax       ,
                thirteen_month_pay    ,
                leave_salary          ,
                work_hours            ,
                overtime_rates
            )
            VALUES (
                :employee_id           ,
                :payroll_group_id      ,
                :pay_date              ,
                :pay_period_start_date ,
                :pay_period_end_date   ,
                :basic_salary          ,
                :basic_pay             ,
                :gross_pay             ,
                :net_pay               ,
                :sss_deduction         ,
                :philhealth_deduction  ,
                :pagibig_fund_deduction,
                :withholding_tax       ,
                :thirteen_month_pay    ,
                :leave_salary          ,
                :work_hours            ,
                :overtime_rates
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id"           , $payslip->getEmployeeId()          , Helper::getPdoParameterType($payslip->getEmployeeId()          ));
            $statement->bindValue(":payroll_group_id"      , $payslip->getPayrollGroupId()      , Helper::getPdoParameterType($payslip->getPayrollGroupId()      ));
            $statement->bindValue(":pay_date"              , $payslip->getPayDate()             , Helper::getPdoParameterType($payslip->getPayDate()             ));
            $statement->bindValue(":pay_period_start_date" , $payslip->getPayPeriodStartDate()  , Helper::getPdoParameterType($payslip->getPayPeriodStartDate()  ));
            $statement->bindValue(":pay_period_end_date"   , $payslip->getPayPeriodEndDate()    , Helper::getPdoParameterType($payslip->getPayPeriodEndDate()    ));
            $statement->bindValue(":basic_salary"          , $payslip->getBasicSalary()         , Helper::getPdoParameterType($payslip->getBasicSalary()         ));
            $statement->bindValue(":basic_pay"             , $payslip->getBasicPay()            , Helper::getPdoParameterType($payslip->getBasicPay()            ));
            $statement->bindValue(":gross_pay"             , $payslip->getGrossPay()            , Helper::getPdoParameterType($payslip->getGrossPay()            ));
            $statement->bindValue(":net_pay"               , $payslip->getNetPay()              , Helper::getPdoParameterType($payslip->getNetPay()              ));
            $statement->bindValue(":sss_deduction"         , $payslip->getSssDeduction()        , Helper::getPdoParameterType($payslip->getSssDeduction()        ));
            $statement->bindValue(":philhealth_deduction"  , $payslip->getPhilhealthDeduction() , Helper::getPdoParameterType($payslip->getPhilhealthDeduction() ));
            $statement->bindValue(":pagibig_fund_deduction", $payslip->getPagibigFundDeduction(), Helper::getPdoParameterType($payslip->getPagibigFundDeduction()));
            $statement->bindValue(":withholding_tax"       , $payslip->getWithholdingTax()      , Helper::getPdoParameterType($payslip->getWithholdingTax()      ));
            $statement->bindValue(":thirteen_month_pay"    , $payslip->getThirteenMonthPay()    , Helper::getPdoParameterType($payslip->getThirteenMonthPay()    ));
            $statement->bindValue(":leave_salary"          , $payslip->getLeaveSalary()         , Helper::getPdoParameterType($payslip->getLeaveSalary()         ));
            $statement->bindValue(":work_hours"            , $payslip->getWorkHours()           , Helper::getPdoParameterType($payslip->getWorkHours()           ));
            $statement->bindValue(":overtime_rates"        , $payslip->getOvertimeRates()       , Helper::getPdoParameterType($payslip->getOvertimeRates()       ));

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
            "id"                           => "payslip.id                      AS id"                    ,
            "employee_id"                  => "payslip.employee_id             AS employee_id"           ,
            "payroll_group_id"             => "payslip.payroll_group_id        AS payroll_group_id"      ,
            "pay_date"                     => "payslip.pay_date                AS pay_date"              ,
            "pay_period_start_date"        => "payslip.pay_period_start_date   AS pay_period_start_date" ,
            "pay_period_end_date"          => "payslip.pay_period_end_date     AS pay_period_end_date"   ,
            "basic_salary"                 => "payslip.basic_salary            AS basic_salary"          ,
            "basic_pay"                    => "payslip.basic_pay               AS basic_pay"             ,
            "gross_pay"                    => "payslip.gross_pay               AS gross_pay"             ,
            "net_pay"                      => "payslip.net_pay                 AS net_pay"               ,
            "sss_deduction"                => "payslip.sss_deduction           AS sss_deduction"         ,
            "philhealth_deduction"         => "payslip.philhealth_deduction    AS philhealth_deduction"  ,
            "pagibig_fund_deduction"       => "payslip.pagibig_fund_deduction  AS pagibig_fund_deduction",
            "withholding_tax"              => "payslip.withholding_tax         AS withholding_tax"       ,
            "thirteen_month_pay"           => "payslip.thirteen_month_pay      AS thirteen_month_pay"    ,
            "leave_salary"                 => "payslip.leave_salary            AS leave_salary"          ,
            "work_hours"                   => "payslip.work_hours              AS work_hours"            ,
            "overtime_rates"               => "payslip.overtime_rates          AS overtime_rates"        ,
            "created_at"                   => "payslip.created_at              AS created_at"            ,
            "updated_at"                   => "payslip.updated_at              AS updated_at"            ,
            "deleted_at"                   => "payslip.deleted_at              AS deleted_at"            ,

            "employee_full_name"           => "employee.full_name              AS full_name"             ,
            "employee_marital_status"      => "employee.marital_status         AS marital_status"        ,
            "employee_email_address"       => "employee.email_address          AS email_address"         ,
            "employee_profile_picture"     => "employee.profile_picture        AS profile_picture"       ,
            "employee_code"                => "employee.employee_code          AS employee_code"         ,
            "employee_job_title_id"        => "employee.job_title_id           AS job_title_id"          ,
            "employee_department_id"       => "employee.department_id          AS department_id"         ,
            "employee_employment_type"     => "employee.employment_type        AS employment_type"       ,
            "employee_date_of_hire"        => "employee.date_of_hire           AS date_of_hire"          ,
            "employee_supervisor_id"       => "employee.supervisor_id          AS supervisor_id"         ,
            "employee_access_role"         => "employee.access_role            AS access_role"           ,
            "employee_basic_salary"        => "employee.basic_salary           AS basic_salary"          ,
            "tin_number"                   => "employee.tin_number             AS tin_number"            ,
            "sss_number"                   => "employee.sss_number             AS sss_number"            ,
            "philhealth_number"            => "employee.philhealth_number      AS philhealth_number"     ,
            "pagibig_fund_number"          => "employee.pagibig_fund_number    AS pagibig_fund_number"   ,
            "employee_bank_name"           => "employee.bank_name              AS bank_name"             ,
            "employee_bank_branch_name"    => "employee.bank_branch_name       AS bank_branch_name"      ,
            "employee_bank_account_number" => "employee.bank_account_number    AS bank_account_number"   ,
            "employee_bank_account_type"   => "employee.bank_account_type      AS bank_account_type"     ,

            "employee_job_title"           => "job_title.title                 AS job_title_title"       ,

            "employee_department_name"     => "department.name                 AS department_name"       ,

            "payroll_frequency"            => "payroll_group.payroll_frequency AS payroll_frequency"     ,

            "total_basic_pay"              => "SUM(basic_pay)                  AS total_basic_pay"
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
            array_key_exists("employee_marital_status"     , $selectedColumns) ||
            array_key_exists("employee_email_address"      , $selectedColumns) ||
            array_key_exists("employee_profile_picture"    , $selectedColumns) ||
            array_key_exists("employee_code"               , $selectedColumns) ||
            array_key_exists("employee_job_title_id"       , $selectedColumns) ||
            array_key_exists("employee_department_id"      , $selectedColumns) ||
            array_key_exists("employee_employment_type"    , $selectedColumns) ||
            array_key_exists("employee_date_of_hire"       , $selectedColumns) ||
            array_key_exists("employee_supervisor_id"      , $selectedColumns) ||
            array_key_exists("employee_access_role"        , $selectedColumns) ||
            array_key_exists("employee_basic_salary"       , $selectedColumns) ||
            array_key_exists("tin_number"                  , $selectedColumns) ||
            array_key_exists("sss_number"                  , $selectedColumns) ||
            array_key_exists("philhealth_number"           , $selectedColumns) ||
            array_key_exists("pagibig_fund_number"         , $selectedColumns) ||
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

        if (array_key_exists("payroll_frequency", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    payroll_groups AS payroll_group
                ON
                    payslip.payroll_group_id = payroll_group.id
            ";
        }

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "payslip.deleted_at IS NULL";

        } else {
            $whereClauses[] = $this->buildFilterCriteria(
                filterCriteria  : $filterCriteria  ,
                queryParameters : $queryParameters ,
                filterParameters: $filterParameters
            );
        }

        if (in_array(trim(end($whereClauses)), ["AND", "OR"], true)) {
            array_pop($whereClauses);
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
                " . implode(" ", $whereClauses) . "
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
                        " . implode(" ", $whereClauses) . "
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

    private function buildFilterCriteria(
        array  $filterCriteria  ,
        array &$queryParameters ,
        array &$filterParameters
    ): string {

        $totalNumberOfConditions = count($filterCriteria);
        $subConditions           = []                    ;

        foreach ($filterCriteria as $index => $filterCriterion) {
            $isNestedCondition = false;

            foreach ($filterCriterion as $condition) {
                if (is_array($condition)) {
                    $isNestedCondition = true;

                    break;
                }
            }

            if ($isNestedCondition) {
                $nestedConditions = $this->buildFilterCriteria(
                    filterCriteria  : $filterCriterion ,
                    queryParameters : $queryParameters ,
                    filterParameters: $filterParameters
                );

                $nestedConditions = "($nestedConditions)";

                $boolean = $filterCriterion[count($filterCriterion) - 1]["boolean"] ?? "AND";

                if ($index < $totalNumberOfConditions - 1) {
                    $nestedConditions .= " {$boolean}";
                }

                $subConditions[] = $nestedConditions;

            } else {
                $column   = $filterCriterion["column"  ]         ;
                $operator = $filterCriterion["operator"]         ;
                $boolean  = $filterCriterion["boolean" ] ?? "AND";

                switch ($operator) {
                    case "="   :
                    case "!="  :
                    case ">="  :
                    case "<="  :
                    case "LIKE":
                        $subCondition = "{$column} {$operator} ?";

                        $queryParameters [] = $filterCriterion["value"];
                        $filterParameters[] = $filterCriterion["value"];

                        break;

                    case "IS NULL"    :
                    case "IS NOT NULL":
                        $subCondition = "{$column} {$operator}";

                        break;

                    case "BETWEEN":
                        $subCondition = "{$column} {$operator} ? AND ?";

                        $queryParameters [] = $filterCriterion["lower_bound"];
                        $queryParameters [] = $filterCriterion["upper_bound"];

                        $filterParameters[] = $filterCriterion["lower_bound"];
                        $filterParameters[] = $filterCriterion["upper_bound"];

                        break;

                    case "IN":
                        $valueList = $filterCriterion["value_list"];

                        if ( ! empty($valueList)) {
                            $placeholders = implode(", ", array_fill(0, count($valueList), "?"));

                            $subCondition     = "{$column} IN ({$placeholders})"          ;

                            $queryParameters  = array_merge($queryParameters , $valueList);
                            $filterParameters = array_merge($filterParameters, $valueList);
                        }

                        break;
                }

                if ($index < $totalNumberOfConditions - 1) {
                    $subCondition .= " {$boolean}";
                }

                $subConditions[] = $subCondition;
            }
        }

        return implode(" ", $subConditions);
    }
}
