<?php

require_once __DIR__ . "/../includes/Helper.php"            ;
require_once __DIR__ . "/../includes/enums/ActionResult.php";

class EmployeeDao
{
    private readonly PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Employee $employee): ActionResult
    {
        $query = "
            INSERT INTO employees (
                rfid_uid                       ,

                first_name                     ,
                middle_name                    ,
                last_name                      ,
                date_of_birth                  ,
                gender                         ,
                marital_status                 ,
                nationality                    ,
                religion                       ,
                phone_number                   ,
                email_address                  ,
                address                        ,
                profile_picture                ,

                emergency_contact_name         ,
                emergency_contact_relationship ,
                emergency_contact_phone_number ,
                emergency_contact_email_address,
                emergency_contact_address      ,

                employee_code                  ,
                job_title_id                   ,
                department_id                  ,
                employment_type                ,
                date_of_hire                   ,
                supervisor_id                  ,
                access_role                    ,

                payroll_group_id               ,
                basic_salary                   ,

                tin_number                     ,
                sss_number                     ,
                philhealth_number              ,
                pagibig_fund_number            ,

                bank_name                      ,
                bank_branch_name               ,
                bank_account_number            ,
                bank_account_type              ,

                username                       ,
                password                       ,

                notes
            )
            VALUES (
                :rfid_uid                       ,

                :first_name                     ,
                :middle_name                    ,
                :last_name                      ,
                :date_of_birth                  ,
                :gender                         ,
                :marital_status                 ,
                :nationality                    ,
                :religion                       ,
                :phone_number                   ,
                :email_address                  ,
                :address                        ,
                :profile_picture                ,

                :emergency_contact_name         ,
                :emergency_contact_relationship ,
                :emergency_contact_phone_number ,
                :emergency_contact_email_address,
                :emergency_contact_address      ,

                :employee_code                  ,
                :job_title_id                   ,
                :department_id                  ,
                :employment_type                ,
                :date_of_hire                   ,
                :supervisor_id                  ,
                :access_role                    ,

                :payroll_group_id               ,
                :basic_salary                   ,

                :tin_number                     ,
                :sss_number                     ,
                :philhealth_number              ,
                :pagibig_fund_number            ,

                :bank_name                      ,
                :bank_branch_name               ,
                :bank_account_number            ,
                :bank_account_type              ,

                :username                       ,
                :password                       ,

                :notes
            )
        ";

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":rfid_uid"                       , $employee->getRfidUid()                     , Helper::getPdoParameterType($employee->getRfidUid()                     ));

            $statement->bindValue(":first_name"                     , $employee->getFirstName()                   , Helper::getPdoParameterType($employee->getFirstName()                   ));
            $statement->bindValue(":middle_name"                    , $employee->getMiddleName()                  , Helper::getPdoParameterType($employee->getMiddleName()                  ));
            $statement->bindValue(":last_name"                      , $employee->getLastName()                    , Helper::getPdoParameterType($employee->getLastName()                    ));
            $statement->bindValue(":date_of_birth"                  , $employee->getDateOfBirth()                 , Helper::getPdoParameterType($employee->getDateOfBirth()                 ));
            $statement->bindValue(":gender"                         , $employee->getGender()                      , Helper::getPdoParameterType($employee->getGender()                      ));
            $statement->bindValue(":marital_status"                 , $employee->getMaritalStatus()               , Helper::getPdoParameterType($employee->getMaritalStatus()               ));
            $statement->bindValue(":nationality"                    , $employee->getNationality()                 , Helper::getPdoParameterType($employee->getNationality()                 ));
            $statement->bindValue(":religion"                       , $employee->getReligion()                    , Helper::getPdoParameterType($employee->getReligion()                    ));
            $statement->bindValue(":phone_number"                   , $employee->getPhoneNumber()                 , Helper::getPdoParameterType($employee->getPhoneNumber()                 ));
            $statement->bindValue(":email_address"                  , $employee->getEmailAddress()                , Helper::getPdoParameterType($employee->getEmailAddress()                ));
            $statement->bindValue(":address"                        , $employee->getAddress()                     , Helper::getPdoParameterType($employee->getAddress()                     ));
            $statement->bindValue(":profile_picture"                , $employee->getProfilePicture()              , Helper::getPdoParameterType($employee->getProfilePicture()              ));

            $statement->bindValue(":emergency_contact_name"         , $employee->getEmergencyContactName()        , Helper::getPdoParameterType($employee->getEmergencyContactName()        ));
            $statement->bindValue(":emergency_contact_relationship" , $employee->getEmergencyContactRelationship(), Helper::getPdoParameterType($employee->getEmergencyContactRelationship()));
            $statement->bindValue(":emergency_contact_phone_number" , $employee->getEmergencyContactPhoneNumber() , Helper::getPdoParameterType($employee->getEmergencyContactPhoneNumber() ));
            $statement->bindValue(":emergency_contact_email_address", $employee->getEmergencyContactEmailAddress(), Helper::getPdoParameterType($employee->getEmergencyContactEmailAddress()));
            $statement->bindValue(":emergency_contact_address"      , $employee->getEmergencyContactAddress()     , Helper::getPdoParameterType($employee->getEmergencyContactAddress()     ));

            $statement->bindValue(":employee_code"                  , $employee->getEmployeeCode()                , Helper::getPdoParameterType($employee->getEmployeeCode()                ));
            $statement->bindValue(":job_title_id"                   , $employee->getJobTitleId()                  , Helper::getPdoParameterType($employee->getJobTitleId()                  ));
            $statement->bindValue(":department_id"                  , $employee->getDepartmentId()                , Helper::getPdoParameterType($employee->getDepartmentId()                ));
            $statement->bindValue(":employment_type"                , $employee->getEmploymentType()              , Helper::getPdoParameterType($employee->getEmploymentType()              ));
            $statement->bindValue(":date_of_hire"                   , $employee->getDateOfHire()                  , Helper::getPdoParameterType($employee->getDateOfHire()                  ));
            $statement->bindValue(":supervisor_id"                  , $employee->getSupervisorId()                , Helper::getPdoParameterType($employee->getSupervisorId()                ));
            $statement->bindValue(":access_role"                    , $employee->getAccessRole()                  , Helper::getPdoParameterType($employee->getAccessRole()                  ));

            $statement->bindValue(":payroll_group_id"               , $employee->getPayrollGroupId()              , Helper::getPdoParameterType($employee->getPayrollGroupId()              ));
            $statement->bindValue(":basic_salary"                   , $employee->getBasicSalary()                 , Helper::getPdoParameterType($employee->getBasicSalary()                 ));

            $statement->bindValue(":tin_number"                     , $employee->getTinNumber()                   , Helper::getPdoParameterType($employee->getTinNumber()                   ));
            $statement->bindValue(":sss_number"                     , $employee->getSssNumber()                   , Helper::getPdoParameterType($employee->getSssNumber()                   ));
            $statement->bindValue(":philhealth_number"              , $employee->getPhilhealthNumber()            , Helper::getPdoParameterType($employee->getPhilhealthNumber()            ));
            $statement->bindValue(":pagibig_fund_number"            , $employee->getPagibigFundNumber()           , Helper::getPdoParameterType($employee->getPagibigFundNumber()           ));

            $statement->bindValue(":bank_name"                      , $employee->getBankName()                    , Helper::getPdoParameterType($employee->getBankName()                    ));
            $statement->bindValue(":bank_branch_name"               , $employee->getBankBranchName()              , Helper::getPdoParameterType($employee->getBankBranchName()              ));
            $statement->bindValue(":bank_account_number"            , $employee->getBankAccountNumber()           , Helper::getPdoParameterType($employee->getBankAccountNumber()           ));
            $statement->bindValue(":bank_account_type"              , $employee->getBankAccountType()             , Helper::getPdoParameterType($employee->getBankAccountType()             ));

            $statement->bindValue(":username"                       , $employee->getUsername()                    , Helper::getPdoParameterType($employee->getUsername()                    ));
            $statement->bindValue(":password"                       , $employee->getPassword()                    , Helper::getPdoParameterType($employee->getPassword()                    ));

            $statement->bindValue(":notes"                          , $employee->getNotes()                       , Helper::getPdoParameterType($employee->getNotes()                       ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the employee. " .
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
            "id"                              => "employee.id                              AS id"                             ,
            "rfid_uid"                        => "employee.rfid_uid                        AS rfid_uid"                       ,

            "first_name"                      => "employee.first_name                      AS first_name"                     ,
            "middle_name"                     => "employee.middle_name                     AS middle_name"                    ,
            "last_name"                       => "employee.last_name                       AS last_name"                      ,
            "full_name"                       => "employee.full_name                       AS full_name"                      ,
            "date_of_birth"                   => "employee.date_of_birth                   AS date_of_birth"                  ,
            "gender"                          => "employee.gender                          AS gender"                         ,
            "marital_status"                  => "employee.marital_status                  AS marital_status"                 ,
            "nationality"                     => "employee.nationality                     AS nationality"                    ,
            "religion"                        => "employee.religion                        AS religion"                       ,
            "phone_number"                    => "employee.phone_number                    AS phone_number"                   ,
            "email_address"                   => "employee.email_address                   AS email_address"                  ,
            "address"                         => "employee.address                         AS address"                        ,
            "profile_picture"                 => "employee.profile_picture                 AS profile_picture"                ,

            "emergency_contact_name"          => "employee.emergency_contact_name          AS emergency_contact_name"         ,
            "emergency_contact_relationship"  => "employee.emergency_contact_relationship  AS emergency_contact_relationship" ,
            "emergency_contact_phone_number"  => "employee.emergency_contact_phone_number  AS emergency_contact_phone_number" ,
            "emergency_contact_email_address" => "employee.emergency_contact_email_address AS emergency_contact_email_address",
            "emergency_contact_address"       => "employee.emergency_contact_address       AS emergency_contact_address"      ,

            "employee_code"                   => "employee.employee_code                   AS employee_code"                  ,
            "job_title_id"                    => "employee.job_title_id                    AS job_title_id"                   ,
            "department_id"                   => "employee.department_id                   AS department_id"                  ,
            "employment_type"                 => "employee.employment_type                 AS employment_type"                ,
            "date_of_hire"                    => "employee.date_of_hire                    AS date_of_hire"                   ,
            "supervisor_id"                   => "employee.supervisor_id                   AS supervisor_id"                  ,
            "access_role"                     => "employee.access_role                     AS access_role"                    ,

            "payroll_group_id"                => "employee.payroll_group_id                AS payroll_group_id"               ,
            "basic_salary"                    => "employee.basic_salary                    AS basic_salary"                   ,

            "tin_number"                      => "employee.tin_number                      AS tin_number"                     ,
            "sss_number"                      => "employee.sss_number                      AS sss_number"                     ,
            "philhealth_number"               => "employee.philhealth_number               AS philhealth_number"              ,
            "pagibig_fund_number"             => "employee.pagibig_fund_number             AS pagibig_fund_number"            ,

            "bank_name"                       => "employee.bank_name                       AS bank_name"                      ,
            "bank_branch_name"                => "employee.bank_branch_name                AS bank_branch_name"               ,
            "bank_account_number"             => "employee.bank_account_number             AS bank_account_number"            ,
            "bank_account_type"               => "employee.bank_account_type               AS bank_account_type"              ,

            "username"                        => "employee.username                        AS username"                       ,
            "password"                        => "employee.password                        AS password"                       ,

            "notes"                           => "employee.notes                           AS notes"                          ,

            "created_at"                      => "employee.created_at                      AS created_at"                     ,
            "updated_at"                      => "employee.updated_at                      AS updated_at"                     ,
            "deleted_at"                      => "employee.deleted_at                      AS deleted_at"                     ,

            "job_title_title"                 => "job_title.title                          AS job_title_title"                ,

            "department_name"                 => "department.name                          AS department_name"                ,
            "department_head_id"              => "department.department_head_id            AS department_head_id"             ,

            "supervisor_first_name"           => "supervisor.first_name                    AS supervisor_first_name"          ,
            "supervisor_middle_name"          => "supervisor.middle_name                   AS supervisor_middle_name"         ,
            "supervisor_last_name"            => "supervisor.last_name                     AS supervisor_last_name"           ,

            "payroll_group_deleted_at"        => "payroll_group.deleted_at                 AS payroll_group_deleted_at"       ,
        ];

        $selectedColumns =
            empty($columns)
                ? $tableColumns
                : array_intersect_key(
                    $tableColumns,
                    array_flip($columns)
                );

        $joinClauses = "";

        if (array_key_exists("job_title_title", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    job_titles AS job_title
                ON
                    employee.job_title_id = job_title.id
            ";
        }

        if (array_key_exists("department_name"   , $selectedColumns) ||
            array_key_exists("department_head_id", $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    departments AS department
                ON
                    employee.department_id = department.id
            ";
        }

        if (array_key_exists("supervisor_first_name" , $selectedColumns) ||
            array_key_exists("supervisor_middle_name", $selectedColumns) ||
            array_key_exists("supervisor_last_name"  , $selectedColumns)) {

            $joinClauses .= "
                LEFT JOIN
                    employees AS supervisor
                ON
                    employee.supervisor_id = supervisor.id
            ";
        }

        if (array_key_exists("payroll_group_deleted_at", $selectedColumns)) {
            $joinClauses .= "
                LEFT JOIN
                    payroll_groups AS payroll_group
                ON
                    employee.payroll_group_id = payroll_group.id
            ";
        }

        $whereClauses     = [];
        $queryParameters  = [];
        $filterParameters = [];

        if (empty($filterCriteria)) {
            $whereClauses[] = "employee.deleted_at is NULL";

        } else {
            foreach ($filterCriteria as $filterCriterion) {
                $column   = $filterCriterion["column"  ];
                $operator = $filterCriterion["operator"];
                $boolean  = isset($filterCriterion["boolean"])
                    ? strtoupper($filterCriterion["boolean"])
                    : 'AND';

                switch ($operator) {
                    case "="   :
                    case "!="  :
                    case "LIKE":
                        $whereClauses    [] = "{$column} {$operator} ?";
                        $queryParameters [] = $filterCriterion["value"];

                        $filterParameters[] = $filterCriterion["value"];

                        break;

                    case "IS NULL":
                        $whereClauses[] = "{$column} {$operator}";

                        break;

                    case "BETWEEN":
                        $whereClauses    [] = "{$column} {$operator} ? AND ?";
                        $queryParameters [] = $filterCriterion["lower_bound"];
                        $queryParameters [] = $filterCriterion["upper_bound"];

                        $filterParameters[] = $filterCriterion["lower_bound"];
                        $filterParameters[] = $filterCriterion["upper_bound"];

                        break;
                }

                $whereClauses[] = " {$boolean}";
            }
        }

        if (in_array(trim(end($whereClauses)), ['AND', 'OR'], true)) {
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
                employees AS employee
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
                        COUNT(employee.id)
                    FROM
                        employees AS employee
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
            error_log("Database Error: An error occurred while fetching the employees. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function fetchLastInsertedId(): int
    {
        return $this->pdo->lastInsertId();
    }

    public function update(Employee $employee): ActionResult
    {
        $query = "
            UPDATE employees
            SET
                rfid_uid                        = :rfid_uid                       ,

                first_name                      = :first_name                     ,
                middle_name                     = :middle_name                    ,
                last_name                       = :last_name                      ,
                date_of_birth                   = :date_of_birth                  ,
                gender                          = :gender                         ,
                marital_status                  = :marital_status                 ,
                nationality                     = :nationality                    ,
                religion                        = :religion                       ,
                phone_number                    = :phone_number                   ,
                email_address                   = :email_address                  ,
                address                         = :address                        ,
                profile_picture                 = :profile_picture                ,

                emergency_contact_name          = :emergency_contact_name         ,
                emergency_contact_relationship  = :emergency_contact_relationship ,
                emergency_contact_phone_number  = :emergency_contact_phone_number ,
                emergency_contact_email_address = :emergency_contact_email_address,
                emergency_contact_address       = :emergency_contact_address      ,

                employee_code                   = :employee_code                  ,
                job_title_id                    = :job_title_id                   ,
                department_id                   = :department_id                  ,
                employment_type                 = :employment_type                ,
                date_of_hire                    = :date_of_hire                   ,
                supervisor_id                   = :supervisor_id                  ,
                access_role                     = :access_role                    ,

                payroll_group_id                = :payroll_group_id               ,
                basic_salary                    = :basic_salary                   ,

                tin_number                      = :tin_number                     ,
                sss_number                      = :sss_number                     ,
                philhealth_number               = :philhealth_number              ,
                pagibig_fund_number             = :pagibig_fund_number            ,

                bank_name                       = :bank_name                      ,
                bank_branch_name                = :bank_branch_name               ,
                bank_account_number             = :bank_account_number            ,
                bank_account_type               = :bank_account_type              ,

                username                        = :username                       ,
                password                        = :password                       ,

                notes                           = :notes
            WHERE
        ";

        if (is_string($employee->getId())) {
            $query .= " SHA2(id, 256) = :employee_id";
        } else {
            $query .= " id = :employee_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":rfid_uid"                       , $employee->getRfidUid()                     , Helper::getPdoParameterType($employee->getRfidUid()                     ));

            $statement->bindValue(":first_name"                     , $employee->getFirstName()                   , Helper::getPdoParameterType($employee->getFirstName()                   ));
            $statement->bindValue(":middle_name"                    , $employee->getMiddleName()                  , Helper::getPdoParameterType($employee->getMiddleName()                  ));
            $statement->bindValue(":last_name"                      , $employee->getLastName()                    , Helper::getPdoParameterType($employee->getLastName()                    ));
            $statement->bindValue(":date_of_birth"                  , $employee->getDateOfBirth()                 , Helper::getPdoParameterType($employee->getDateOfBirth()                 ));
            $statement->bindValue(":gender"                         , $employee->getGender()                      , Helper::getPdoParameterType($employee->getGender()                      ));
            $statement->bindValue(":marital_status"                 , $employee->getMaritalStatus()               , Helper::getPdoParameterType($employee->getMaritalStatus()               ));
            $statement->bindValue(":nationality"                    , $employee->getNationality()                 , Helper::getPdoParameterType($employee->getNationality()                 ));
            $statement->bindValue(":religion"                       , $employee->getReligion()                    , Helper::getPdoParameterType($employee->getReligion()                    ));
            $statement->bindValue(":phone_number"                   , $employee->getPhoneNumber()                 , Helper::getPdoParameterType($employee->getPhoneNumber()                 ));
            $statement->bindValue(":email_address"                  , $employee->getEmailAddress()                , Helper::getPdoParameterType($employee->getEmailAddress()                ));
            $statement->bindValue(":address"                        , $employee->getAddress()                     , Helper::getPdoParameterType($employee->getAddress()                     ));
            $statement->bindValue(":profile_picture"                , $employee->getProfilePicture()              , Helper::getPdoParameterType($employee->getProfilePicture()              ));

            $statement->bindValue(":emergency_contact_name"         , $employee->getEmergencyContactName()        , Helper::getPdoParameterType($employee->getEmergencyContactName()        ));
            $statement->bindValue(":emergency_contact_relationship" , $employee->getEmergencyContactRelationship(), Helper::getPdoParameterType($employee->getEmergencyContactRelationship()));
            $statement->bindValue(":emergency_contact_phone_number" , $employee->getEmergencyContactPhoneNumber() , Helper::getPdoParameterType($employee->getEmergencyContactPhoneNumber() ));
            $statement->bindValue(":emergency_contact_email_address", $employee->getEmergencyContactEmailAddress(), Helper::getPdoParameterType($employee->getEmergencyContactEmailAddress()));
            $statement->bindValue(":emergency_contact_address"      , $employee->getEmergencyContactAddress()     , Helper::getPdoParameterType($employee->getEmergencyContactAddress()     ));

            $statement->bindValue(":employee_code"                  , $employee->getEmployeeCode()                , Helper::getPdoParameterType($employee->getEmployeeCode()                ));
            $statement->bindValue(":job_title_id"                   , $employee->getJobTitleId()                  , Helper::getPdoParameterType($employee->getJobTitleId()                  ));
            $statement->bindValue(":department_id"                  , $employee->getDepartmentId()                , Helper::getPdoParameterType($employee->getDepartmentId()                ));
            $statement->bindValue(":employment_type"                , $employee->getEmploymentType()              , Helper::getPdoParameterType($employee->getEmploymentType()              ));
            $statement->bindValue(":date_of_hire"                   , $employee->getDateOfHire()                  , Helper::getPdoParameterType($employee->getDateOfHire()                  ));
            $statement->bindValue(":supervisor_id"                  , $employee->getSupervisorId()                , Helper::getPdoParameterType($employee->getSupervisorId()                ));
            $statement->bindValue(":access_role"                    , $employee->getAccessRole()                  , Helper::getPdoParameterType($employee->getAccessRole()                  ));

            $statement->bindValue(":payroll_group_id"               , $employee->getPayrollGroupId()              , Helper::getPdoParameterType($employee->getPayrollGroupId()              ));
            $statement->bindValue(":basic_salary"                   , $employee->getBasicSalary()                 , Helper::getPdoParameterType($employee->getBasicSalary()                 ));

            $statement->bindValue(":tin_number"                     , $employee->getTinNumber()                   , Helper::getPdoParameterType($employee->getTinNumber()                   ));
            $statement->bindValue(":sss_number"                     , $employee->getSssNumber()                   , Helper::getPdoParameterType($employee->getSssNumber()                   ));
            $statement->bindValue(":philhealth_number"              , $employee->getPhilhealthNumber()            , Helper::getPdoParameterType($employee->getPhilhealthNumber()            ));
            $statement->bindValue(":pagibig_fund_number"            , $employee->getPagibigFundNumber()           , Helper::getPdoParameterType($employee->getPagibigFundNumber()           ));

            $statement->bindValue(":bank_name"                      , $employee->getBankName()                    , Helper::getPdoParameterType($employee->getBankName()                    ));
            $statement->bindValue(":bank_branch_name"               , $employee->getBankBranchName()              , Helper::getPdoParameterType($employee->getBankBranchName()              ));
            $statement->bindValue(":bank_account_number"            , $employee->getBankAccountNumber()           , Helper::getPdoParameterType($employee->getBankAccountNumber()           ));
            $statement->bindValue(":bank_account_type"              , $employee->getBankAccountType()             , Helper::getPdoParameterType($employee->getBankAccountType()             ));

            $statement->bindValue(":username"                       , $employee->getUsername()                    , Helper::getPdoParameterType($employee->getUsername()                    ));
            $statement->bindValue(":password"                       , $employee->getPassword()                    , Helper::getPdoParameterType($employee->getPassword()                    ));

            $statement->bindValue(":notes"                          , $employee->getNotes()                       , Helper::getPdoParameterType($employee->getNotes()                       ));

            $statement->bindValue(":employee_id"                    , $employee->getId()                          , Helper::getPdoParameterType($employee->getId()                          ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while creating the employee. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function changePassword(int|string $employeeId, string $newHashedPassword): ActionResult
    {
        $query = "
            UPDATE employees
            SET
                password = :new_hashed_password
            WHERE
        ";

        if (is_string($employeeId)) {
            $query .= " SHA2(id, 256) = :employee_id";
        } else {
            $query .= " id = :employee_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":new_hashed_password", $newHashedPassword, Helper::getPdoParameterType($newHashedPassword));

            $statement->bindValue(":employee_id"        , $employeeId       , Helper::getPdoParameterType($employeeId       ));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while changing the password. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function countTotalRecords(): int|ActionResult
    {
        $query = "
            SELECT
                COUNT(*)
            FROM
                employees
        ";

        try {
            $statement = $this->pdo->prepare($query);

            $statement->execute();

            $result = $statement->fetchColumn();

            return (int) $result;

        } catch (PDOException $exception) {
            error_log("Database Error: An error occurred while counting the records. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }

    public function delete(int|string $employeeId): ActionResult
    {
        return $this->softDelete($employeeId);
    }

    private function softDelete(int|string $employeeId): ActionResult
    {
        $query = "
            UPDATE employees
            SET
                deleted_at = CURRENT_TIMESTAMP
            WHERE
        ";

        if (is_string($employeeId)) {
            $query .= " SHA2(id, 256) = :employee_id";
        } else {
            $query .= " id = :employee_id";
        }

        $isLocalTransaction = ! $this->pdo->inTransaction();

        try {
            if ($isLocalTransaction) {
                $this->pdo->beginTransaction();
            }

            $statement = $this->pdo->prepare($query);

            $statement->bindValue(":employee_id", $employeeId, Helper::getPdoParameterType($employeeId));

            $statement->execute();

            if ($isLocalTransaction) {
                $this->pdo->commit();
            }

            return ActionResult::SUCCESS;

        } catch (PDOException $exception) {
            if ($isLocalTransaction) {
                $this->pdo->rollBack();
            }

            error_log("Database Error: An error occurred while deleting the employee. " .
                      "Exception: {$exception->getMessage()}");

            return ActionResult::FAILURE;
        }
    }
}
