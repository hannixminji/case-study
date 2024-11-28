<?php

class Employee
{
    public function __construct(
        private readonly ? int    $id                              = null,
        private readonly   string $rfid_uid                              ,

        private readonly   string $first_name                            ,
        private readonly ? string $middle_name                     = null,
        private readonly   string $last_name                             ,
        private readonly   string $date_of_birth                         ,
        private readonly   string $gender                                ,
        private readonly   string $marital_status                        ,
        private readonly   string $nationality                           ,
        private readonly ? string $religion                        = null,
        private readonly   string $phone_number                          ,
        private readonly   string $email_address                         ,
        private readonly   string $address                               ,
        private readonly ? string $profile_picture                 = null,

        private readonly   string $emergency_contact_name                ,
        private readonly   string $emergency_contact_relationship        ,
        private readonly   string $emergency_contact_phone_number        ,
        private readonly ? string $emergency_contact_email_address = null,
        private readonly ? string $emergency_contact_address       = null,

        private readonly   string $employee_code                         ,
        private readonly   int    $job_title_id                          ,
        private readonly   int    $department_id                         ,
        private readonly   string $employment_type                       ,
        private readonly   string $date_of_hire                          ,
        private readonly ? int    $supervisor_id                   = null,
        private readonly ? int    $manager_id                      = null,
        private readonly   string $access_role                           ,

        private readonly   int    $payroll_group_id                     ,
        private readonly ? float  $base_salary                     = null,
        private readonly ? float  $hourly_rate                     = null,

        private readonly   string $tin_number                            ,
        private readonly   string $sss_number                            ,
        private readonly   string $philhealth_number                     ,
        private readonly   string $pagibig_fund_number                   ,

        private readonly   string $bank_name                             ,
        private readonly   string $bank_branch_name                      ,
        private readonly   string $bank_account_number                   ,
        private readonly   string $bank_account_type                     ,

        private readonly   string $username                              ,
        private readonly   string $password                              ,

        private readonly ? string $notes                           = null,

        private readonly   string $created_at                            ,
        private readonly   string $updated_at                            ,
        private readonly ? string $deleted_at                      = null,
    ) {
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getRfidUid(): string {
        return $this->rfid_uid;
    }

    public function getFirstName(): string {
        return $this->first_name;
    }

    public function getMiddleName(): ?string {
        return $this->middle_name;
    }

    public function getLastName(): string {
        return $this->last_name;
    }

    public function getDateOfBirth(): string {
        return $this->date_of_birth;
    }

    public function getGender(): string {
        return $this->gender;
    }

    public function getMaritalStatus(): string {
        return $this->marital_status;
    }

    public function getNationality(): string {
        return $this->nationality;
    }

    public function getReligion(): ?string {
        return $this->religion;
    }

    public function getPhoneNumber(): string {
        return $this->phone_number;
    }

    public function getEmailAddress(): string {
        return $this->email_address;
    }

    public function getAddress(): string {
        return $this->address;
    }

    public function getProfilePicture(): ?string {
        return $this->profile_picture;
    }

    public function getEmergencyContactName(): string {
        return $this->emergency_contact_name;
    }

    public function getEmergencyContactRelationship(): string {
        return $this->emergency_contact_relationship;
    }

    public function getEmergencyContactPhoneNumber(): string {
        return $this->emergency_contact_phone_number;
    }

    public function getEmergencyContactEmailAddress(): ?string {
        return $this->emergency_contact_email_address;
    }

    public function getEmergencyContactAddress(): ?string {
        return $this->emergency_contact_address;
    }

    public function getEmployeeCode(): string {
        return $this->employee_code;
    }

    public function getJobTitleId(): int {
        return $this->job_title_id;
    }

    public function getDepartmentId(): int {
        return $this->department_id;
    }

    public function getEmploymentType(): string {
        return $this->employment_type;
    }

    public function getDateOfHire(): string {
        return $this->date_of_hire;
    }

    public function getSupervisorId(): ?int {
        return $this->supervisor_id;
    }

    public function getManagerId(): ?int {
        return $this->manager_id;
    }

    public function getAccessRole(): string {
        return $this->access_role;
    }

    public function getPayrollGroupId(): int {
        return $this->payroll_group_id;
    }

    public function getBaseSalary(): ?float {
        return $this->base_salary;
    }

    public function getHourlyRate(): ?float {
        return $this->hourly_rate;
    }

    public function getTinNumber(): string {
        return $this->tin_number;
    }

    public function getSssNumber(): string {
        return $this->sss_number;
    }

    public function getPhilhealthNumber(): string {
        return $this->philhealth_number;
    }

    public function getPagibigFundNumber(): string {
        return $this->pagibig_fund_number;
    }

    public function getBankName(): string {
        return $this->bank_name;
    }

    public function getBankBranchName(): string {
        return $this->bank_branch_name;
    }

    public function getBankAccountNumber(): string {
        return $this->bank_account_number;
    }

    public function getBankAccountType(): string {
        return $this->bank_account_type;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function getNotes(): ?string {
        return $this->notes;
    }

    public function getCreatedAt(): string {
        return $this->created_at;
    }

    public function getUpdatedAt(): string {
        return $this->updated_at;
    }

    public function getDeletedAt(): ?string {
        return $this->deleted_at;
    }
}
