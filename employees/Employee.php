<?php

class Employee
{
    public function __construct(
        private readonly   int|string|null $id                           = null,
        private readonly   string          $rfidUid                            ,

        private readonly   string          $firstName                          ,
        private readonly ? string          $middleName                   = null,
        private readonly   string          $lastName                           ,
        private readonly   string          $dateOfBirth                        ,
        private readonly   string          $gender                             ,
        private readonly   string          $maritalStatus                      ,
        private readonly   string          $nationality                        ,
        private readonly ? string          $religion                     = null,
        private readonly   string          $phoneNumber                        ,
        private readonly   string          $emailAddress                       ,
        private readonly   string          $address                            ,
        private readonly ? string          $profilePicture               = null,

        private readonly   string          $emergencyContactName               ,
        private readonly   string          $emergencyContactRelationship       ,
        private readonly   string          $emergencyContactPhoneNumber        ,
        private readonly ? string          $emergencyContactEmailAddress = null,
        private readonly ? string          $emergencyContactAddress      = null,

        private readonly   string          $employeeCode                       ,
        private readonly   int|string      $jobTitleId                         ,
        private readonly   int|string      $departmentId                       ,
        private readonly   string          $employmentType                     ,
        private readonly   string          $dateOfHire                         ,
        private readonly   int|string|null $supervisorId                 = null,
        private readonly   string          $accessRole                         ,

        private readonly   int|string      $payrollGroupId                     ,
        private readonly   float           $basicSalary                        ,

        private readonly   string          $tinNumber                          ,
        private readonly   string          $sssNumber                          ,
        private readonly   string          $philhealthNumber                   ,
        private readonly   string          $pagibigFundNumber                  ,

        private readonly   string          $bankName                           ,
        private readonly   string          $bankBranchName                     ,
        private readonly   string          $bankAccountNumber                  ,
        private readonly   string          $bankAccountType                    ,

        private readonly   string          $username                           ,
        private readonly   string          $password                           ,

        private readonly ? string          $notes                        = null
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getRfidUid(): string
    {
        return $this->rfidUid;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getDateOfBirth(): string
    {
        return $this->dateOfBirth;
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function getMaritalStatus(): string
    {
        return $this->maritalStatus;
    }

    public function getNationality(): string
    {
        return $this->nationality;
    }

    public function getReligion(): ?string
    {
        return $this->religion;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function getEmergencyContactName(): string
    {
        return $this->emergencyContactName;
    }

    public function getEmergencyContactRelationship(): string
    {
        return $this->emergencyContactRelationship;
    }

    public function getEmergencyContactPhoneNumber(): string
    {
        return $this->emergencyContactPhoneNumber;
    }

    public function getEmergencyContactEmailAddress(): ?string
    {
        return $this->emergencyContactEmailAddress;
    }

    public function getEmergencyContactAddress(): ?string
    {
        return $this->emergencyContactAddress;
    }

    public function getEmployeeCode(): string
    {
        return $this->employeeCode;
    }

    public function getJobTitleId(): int|string
    {
        return $this->jobTitleId;
    }

    public function getDepartmentId(): int|string
    {
        return $this->departmentId;
    }

    public function getEmploymentType(): string
    {
        return $this->employmentType;
    }

    public function getDateOfHire(): string
    {
        return $this->dateOfHire;
    }

    public function getSupervisorId(): int|string|null
    {
        return $this->supervisorId;
    }

    public function getAccessRole(): string
    {
        return $this->accessRole;
    }

    public function getPayrollGroupId(): int|string
    {
        return $this->payrollGroupId;
    }

    public function getBasicSalary(): float
    {
        return $this->basicSalary;
    }

    public function getTinNumber(): string
    {
        return $this->tinNumber;
    }

    public function getSssNumber(): string
    {
        return $this->sssNumber;
    }

    public function getPhilhealthNumber(): string
    {
        return $this->philhealthNumber;
    }

    public function getPagibigFundNumber(): string
    {
        return $this->pagibigFundNumber;
    }

    public function getBankName(): string
    {
        return $this->bankName;
    }

    public function getBankBranchName(): string
    {
        return $this->bankBranchName;
    }

    public function getBankAccountNumber(): string
    {
        return $this->bankAccountNumber;
    }

    public function getBankAccountType(): string
    {
        return $this->bankAccountType;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }
}
