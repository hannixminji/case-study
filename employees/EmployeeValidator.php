<?php

require_once __DIR__ . '/../includes/BaseValidator.php';

class EmployeeValidator extends BaseValidator
{
    private readonly EmployeeRepository $employeeRepository;

    public function __construct(EmployeeRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    public function validate(array $fieldsToValidate): void
    {
        $this->errors = [];

        foreach ($fieldsToValidate as $field) {
            if ( ! array_key_exists($field, $this->data)) {
                $this->errors[$field] = 'The ' . $field . ' field is missing.';
            } else {
                switch ($field) {
                    case 'id'                             : $this->isValidId                          ($this->data['id'                             ]); break;
                    case 'rfid_uid'                       : $this->isValidRfidUid                     ($this->data['rfid_uid'                       ]); break;
                    case 'first_name'                     : $this->isValidFirstName                   ($this->data['first_name'                     ]); break;
                    case 'middle_name'                    : $this->isValidMiddleName                  ($this->data['middle_name'                    ]); break;
                    case 'last_name'                      : $this->isValidLastName                    ($this->data['last_name'                      ]); break;
                    case 'date_of_birth'                  : $this->isValidDateOfBirth                 ($this->data['date_of_birth'                  ]); break;
                    case 'gender'                         : $this->isValidGender                      ($this->data['gender'                         ]); break;
                    case 'marital_status'                 : $this->isValidMaritalStatus               ($this->data['marital_status'                 ]); break;
                    case 'nationality'                    : $this->isValidNationality                 ($this->data['nationality'                    ]); break;
                    case 'religion'                       : $this->isValidReligion                    ($this->data['religion'                       ]); break;
                    case 'phone_number'                   : $this->isValidPhoneNumber                 ($this->data['phone_number'                   ]); break;
                    case 'email_address'                  : $this->isValidEmailAddress                ($this->data['email_address'                  ]); break;
                    case 'address'                        : $this->isValidAddress                     ($this->data['address'                        ]); break;
                    case 'profile_picture'                : $this->isValidProfilePicture              ($this->data['profile_picture'                ]); break;
                    case 'emergency_contact_name'         : $this->isValidEmergencyContactName        ($this->data['emergency_contact_name'         ]); break;
                    case 'emergency_contact_relationship' : $this->isValidEmergencyContactRelationship($this->data['emergency_contact_relationship' ]); break;
                    case 'emergency_contact_phone_number' : $this->isValidEmergencyContactPhoneNumber ($this->data['emergency_contact_phone_number' ]); break;
                    case 'emergency_contact_email_address': $this->isValidEmergencyContactEmailAddress($this->data['emergency_contact_email_address']); break;
                    case 'emergency_contact_address'      : $this->isValidEmergencyContactAddress     ($this->data['emergency_contact_address      ']); break;
                    case 'employee_code'                  : $this->isValidEmployeeCode                ($this->data['employee_code'                  ]); break;
                    case 'job_title_id'                   : $this->isValidJobTitleId                  ($this->data['job_title_id'                   ]); break;
                    case 'department_id'                  : $this->isValidDepartmentId                ($this->data['department_id'                  ]); break;
                    case 'employment_type'                : $this->isValidEmploymentType              ($this->data['employment_type'                ]); break;
                    case 'date_of_hire'                   : $this->isValidDateOfHire                  ($this->data['date_of_hire'                   ]); break;
                    case 'supervisor_id'                  : $this->isValidSupervisorId                ($this->data['supervisor_id'                  ]); break;
                    case 'access_role'                    : $this->isValidAccessRole                  ($this->data['access_role'                    ]); break;
                    case 'payroll_group_id'               : $this->isValidPayrollGroupId              ($this->data['payroll_group_id'               ]); break;
                    case 'basic_salary'                   : $this->isValidBasicSalary                 ($this->data['basic_salary'                   ]); break;
                    case 'tin_number'                     : $this->isValidTinNumber                   ($this->data['tin_number'                     ]); break;
                    case 'sss_number'                     : $this->isValidSssNumber                   ($this->data['sss_number'                     ]); break;
                    case 'philhealth_number'              : $this->isValidPhilhealthNumber            ($this->data['philhealth_number'              ]); break;
                    case 'pagibig_fund_number'            : $this->isValidPagibigFundNumber           ($this->data['pagibig_fund_number'            ]); break;
                    case 'bank_name'                      : $this->isValidBankName                    ($this->data['bank_name'                      ]); break;
                    case 'bank_branch_name'               : $this->isValidBankBranchName              ($this->data['bank_branch_name'               ]); break;
                    case 'bank_account_number'            : $this->isValidBankAccountNumber           ($this->data['bank_account_number'            ]); break;
                    case 'bank_account_type'              : $this->isValidBankAccountType             ($this->data['bank_account_type'              ]); break;
                    case 'username'                       : $this->isValidUsername                    ($this->data['username'                       ]); break;
                    case 'password'                       : $this->isValidPassword                    ($this->data['password'                       ]); break;
                    case 'notes'                          : $this->isValidNotes                       ($this->data['notes'                          ]); break;
                }
            }
        }

        $this->isValidFullName();
    }

    public function isValidRfidUid(mixed $rfidUid): bool
    {
        if ($rfidUid === null) {
            $this->errors['rfid_uid'] = 'The RFID UID cannot be null.';

            return false;
        }

        if ( ! is_string($rfidUid)) {
            $this->errors['rfid_uid'] = 'The RFID UID must be a string.';

            return false;
        }

        if (trim($rfidUid) === '') {
            $this->errors['rfid_uid'] = 'The RFID UID cannot be empty.';

            return false;
        }

        if (mb_strlen($rfidUid) < 8 || mb_strlen($rfidUid) > 32) {
            $this->errors['rfid_uid'] = 'The RFID UID must be between 8 and 32 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Fa-f0-9]+$/', $rfidUid)) {
            $this->errors['rfid_uid'] = 'The RFID UID contains invalid characters. Only hexadecimal characters from 0 to 9 and A to F are allowed.';

            return false;
        }

        $isUnique = $this->isUnique('rfid_uid', $rfidUid);

        if ($isUnique === null) {
            $this->errors['rfid_uid'] = 'Unable to verify the uniqueness of the RFID UID. The provided employee ID may be missing or invalid. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['rfid_uid'] = 'This RFID UID already exists. Please provide a different one.';

            return false;
        }

        return true;
    }

    public function isValidFirstName(mixed $firstName): bool
    {
        if ($firstName === null) {
            $this->errors['first_name'] = 'The first name cannot be null.';

            return false;
        }

        if ( ! is_string($firstName)) {
            $this->errors['first_name'] = 'The first name must be a string.';

            return false;
        }

        if (trim($firstName) === '') {
            $this->errors['first_name'] = 'The first name cannot be empty.';

            return false;
        }

        if (mb_strlen($firstName) < 2 || mb_strlen($firstName) > 30) {
            $this->errors['first_name'] = 'The first name must be between 2 and 30 characters long.';

            return false;
        }

        if ( ! preg_match('/^[\p{L}._\'\-, ]+$/u', $firstName)) {
            $this->errors['first_name'] = 'The first name contains invalid characters. Only letters, spaces, and the following characters are allowed: - . , \' _';

            return false;
        }

        return true;
    }

    public function isValidMiddleName(mixed $middleName): bool
    {
        if ($middleName !== null && ! is_string($middleName)) {
            $this->errors['middle_name'] = 'The middle name must be a string.';

            return false;
        }

        if (is_string($middleName) && trim($middleName) !== '') {
            if (mb_strlen($middleName) < 2 || mb_strlen($middleName) > 30) {
                $this->errors['middle_name'] = 'The middle name must be between 2 and 30 characters long.';

                return false;
            }

            if ( ! preg_match('/^[\p{L}._\'\-, ]+$/u', $middleName)) {
                $this->errors['middle_name'] = 'The middle name contains invalid characters. Only letters, spaces, and the following characters are allowed: - . , \' _';

                return false;
            }
        }

        return true;
    }

    public function isValidLastName(mixed $lastName): bool
    {
        if ($lastName === null) {
            $this->errors['last_name'] = 'The last name cannot be null.';

            return false;
        }

        if ( ! is_string($lastName)) {
            $this->errors['last_name'] = 'The last name must be a string.';

            return false;
        }

        if (trim($lastName) === '') {
            $this->errors['last_name'] = 'The last name cannot be empty.';

            return false;
        }

        if (mb_strlen($lastName) < 2 || mb_strlen($lastName) > 30) {
            $this->errors['last_name'] = 'The last name must be between 2 and 30 characters long.';

            return false;
        }

        if ( ! preg_match('/^[\p{L}._\'\-, ]+$/u', $lastName)) {
            $this->errors['last_name'] = 'The last name contains invalid characters. Only letters, spaces, and the following characters are allowed: - . , \' _';

            return false;
        }

        if ($lastName !== htmlspecialchars(strip_tags($lastName), ENT_QUOTES, 'UTF-8')) {
            $this->errors['last_name'] = 'The last name contains HTML tags or special characters that are not allowed.';

            return false;
        }

        return true;
    }

    public function isValidFullName(): bool
    {
        if (   array_key_exists('first_name', $this->data) &&
               array_key_exists('last_name' , $this->data) &&

             ! isset($this->errors['first_name']) &&
             ! isset($this->errors['last_name' ])) {

            $isUnique = $this->isUnique('full_name', $this->data['first_name'] + ' ' + $this->data['last_name']);

            if ($isUnique === null) {
                $this->errors['full_name'] = 'Unable to verify the uniqueness of the employee name. The provided employee ID may be missing or invalid. Please try again later.';

                return false;
            }

            if ($isUnique === false) {
                $this->errors['full_name'] = 'This employee name already exists. Please provide a different one.';

                return false;
            }
        }

        return true;
    }

    public function isValidDateOfBirth(mixed $dateOfBirth): bool
    {
        if ($dateOfBirth === null) {
            $this->errors['date_of_birth'] = 'The date of birth cannot be null.';

            return false;
        }

        if ( ! is_string($dateOfBirth)) {
            $this->errors['date_of_birth'] = 'The date of birth must be a string.';

            return false;
        }

        if (trim($dateOfBirth) === '') {
            $this->errors['date_of_birth'] = 'The date of birth cannot be empty.';

            return false;
        }

        $date = DateTime::createFromFormat('Y-m-d', $dateOfBirth);

        if ($date === false || $date->format('Y-m-d') !== $dateOfBirth) {
            $this->errors['date_of_birth'] = 'The date of birth must be in the Y-m-d format and be a valid date, e.g., 1990-12-31.';

            return false;
        }

        if ($date > new DateTime()) {
            $this->errors['date_of_birth'] = 'The date of birth cannot be in the future.';

            return false;
        }

        return true;
    }

    public function isValidGender(mixed $gender): bool
    {
        if ($gender === null) {
            $this->errors['gender'] = 'The gender cannot be null.';

            return false;
        }

        if ( ! is_string($gender)) {
            $this->errors['gender'] = 'The gender must be a string.';

            return false;
        }

        if (trim($gender) === '') {
            $this->errors['gender'] = 'The gender cannot be empty.';

            return false;
        }

        if (mb_strlen($gender) < 3 || mb_strlen($gender) > 50) {
            $this->errors['gender'] = 'The gender must be between 3 and 50 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Za-z ]+$/', $gender)) {
            $this->errors['gender'] = 'The gender contains invalid characters. Only letters and spaces are allowed.';

            return false;
        }

        return true;
    }

    public function isValidMaritalStatus(mixed $maritalStatus): bool
    {
        if ($maritalStatus === null) {
            $this->errors['marital_status'] = 'The marital status cannot be null.';

            return false;
        }

        if ( ! is_string($maritalStatus)) {
            $this->errors['marital_status'] = 'The marital status must be a string.';

            return false;
        }

        if (trim($maritalStatus) === '') {
            $this->errors['marital_status'] = 'The marital status cannot be empty.';

            return false;
        }

        $validMaritalStatuses = [
            'single'   ,
            'married'  ,
            'divorced' ,
            'widowed'  ,
            'separated'
        ];

        if ( ! in_array(strtolower($maritalStatus), $validMaritalStatuses)) {
            $this->errors['marital_status'] = 'The marital status must be single, married, divorced, widowed, or separated.';

            return false;
        }

        return true;
    }

    public function isValidNationality(mixed $nationality): bool
    {
        if ($nationality === null) {
            $this->errors['nationality'] = 'The nationality cannot be null.';

            return false;
        }

        if ( ! is_string($nationality)) {
            $this->errors['nationality'] = 'The nationality must be a string.';

            return false;
        }

        if (trim($nationality) === '') {
            $this->errors['nationality'] = 'The nationality cannot be empty.';

            return false;
        }

        if (mb_strlen($nationality) < 3 || mb_strlen($nationality) > 50) {
            $this->errors['nationality'] = 'The nationality must be between 3 and 50 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Za-z ]+$/', $nationality)) {
            $this->errors['nationality'] = 'The nationality contains invalid characters. Only letters and spaces are allowed.';

            return false;
        }

        return true;
    }

    public function isValidReligion(mixed $religion): bool
    {
        if ($religion !== null && ! is_string($religion)) {
            $this->errors['religion'] = 'The religion must be a string.';

            return false;
        }

        if (is_string($religion) && trim($religion) !== '') {
            if (mb_strlen($religion) < 3 || mb_strlen($religion) > 50) {
                $this->errors['religion'] = 'The religion must be between 3 and 50 characters long.';

                return false;
            }

            if ( ! preg_match('/^[A-Za-z ]+$/', $religion)) {
                $this->errors['religion'] = 'The religion contains invalid characters. Only letters and spaces are allowed.';

                return false;
            }
        }

        return true;
    }

    public function isValidPhoneNumber(mixed $phoneNumber): bool
    {
        if ($phoneNumber === null) {
            $this->errors['phone_number'] = 'The phone number cannot be null.';

            return false;
        }

        if ( ! is_string($phoneNumber)) {
            $this->errors['phone_number'] = 'The phone number must be a string.';

            return false;
        }

        if (trim($phoneNumber) === '') {
            $this->errors['phone_number'] = 'The phone number cannot be empty.';

            return false;
        }

        if ( ! preg_match('/^\+?(\d{1,3}[-. ]?)?(\(\d{3}\)|\d{3})[-. ]?(\d{3})[-. ]?(\d{4})$/', $phoneNumber)) {
            $this->errors['phone_number'] = 'The phone number contains invalid characters or format. Please use a valid phone number format, e.g., +63 912 345 6789 or 0912-345-6789.';

            return false;
        }

        $digits = preg_replace('/[^0-9]/', '', $phoneNumber);

        if (strlen($digits) < 7 || strlen($digits) > 15) {
            $this->errors['phone_number'] = 'The phone number must be between 7 and 15 digits long, excluding non-numeric characters.';

            return false;
        }

        $isUnique = $this->isUnique('phone_number', $phoneNumber);

        if ($isUnique === null) {
            $this->errors['phone_number'] = 'Unable to verify the uniqueness of the phone number. The provided employee ID may be missing or invalid. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['phone_number'] = 'This phone number already exists. Please provide a different one.';

            return false;
        }

        return true;
    }

    public function isValidEmailAddress(mixed $emailAddress): bool
    {
        if ($emailAddress === null) {
            $this->errors['email_address'] = 'The email address cannot be null.';

            return false;
        }

        if ( ! is_string($emailAddress)) {
            $this->errors['email_address'] = 'The email address must be a string.';

            return false;
        }

        if (trim($emailAddress) === '') {
            $this->errors['email_address'] = 'The email address cannot be empty.';

            return false;
        }

        if ( ! filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email_address'] = 'The email address must be a valid email address.';

            return false;
        }

        $isUnique = $this->isUnique('email_address', $emailAddress);

        if ($isUnique === null) {
            $this->errors['email_address'] = 'Unable to verify the uniqueness of the email address. The provided employee ID may be missing or invalid. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['email_address'] = 'This email address already exists. Please provide a different one.';

            return false;
        }

        return true;
    }

    public function isValidAddress(mixed $address): bool
    {
        if ($address === null) {
            $this->errors['address'] = 'The address cannot be null.';

            return false;
        }

        if ( ! is_string($address)) {
            $this->errors['address'] = 'The address must be a string.';

            return false;
        }

        if (trim($address) === '') {
            $this->errors['address'] = 'The address cannot be empty.';

            return false;
        }

        if (mb_strlen($address) < 10 || mb_strlen($address) > 255) {
            $this->errors['address'] = 'The address must be between 10 and 255 characters long.';

            return false;
        }

        if ( ! preg_match('/^[a-zA-Z0-9\s.,\'\-\/#]+$/', $address)) {
            $this->errors['address'] = 'The address contains invalid characters. Only letters, numbers, spaces, and the following characters are allowed: - . , \' / #';

            return false;
        }

        if ($address !== htmlspecialchars(strip_tags($address), ENT_QUOTES, 'UTF-8')) {
            $this->errors['address'] = 'The address contains HTML tags or special characters that are not allowed.';

            return false;
        }

        return true;
    }

    public function isValidProfilePicture(mixed $profilePicture): bool
    {
        return true;
    }

    public function isValidEmergencyContactName(mixed $emergencyContactName): bool
    {
        if ($emergencyContactName === null) {
            $this->errors['emergency_contact_name'] = 'The emergency contact name cannot be null.';

            return false;
        }

        if ( ! is_string($emergencyContactName)) {
            $this->errors['emergency_contact_name'] = 'The emergency contact name must be a string.';

            return false;
        }

        if (trim($emergencyContactName) === '') {
            $this->errors['emergency_contact_name'] = 'The emergency contact name cannot be empty.';

            return false;
        }

        if (mb_strlen($emergencyContactName) < 6 || mb_strlen($emergencyContactName) > 90) {
            $this->errors['emergency_contact_name'] = 'The emergency contact name must be between 6 and 90 characters long.';

            return false;
        }

        if ( ! preg_match('/^[\p{L}._\'\-, ]+$/u', $emergencyContactName)) {
            $this->errors['emergency_contact_name'] = 'The emergency contact name contains invalid characters. Only letters, spaces, and the following characters are allowed: . _ \' - ,';

            return false;
        }

        if ($emergencyContactName !== htmlspecialchars(strip_tags($emergencyContactName), ENT_QUOTES, 'UTF-8')) {
            $this->errors['emergency_contact_name'] = 'The emergency contact name contains HTML tags or special characters that are not allowed.';

            return false;
        }

        return true;
    }

    public function isValidEmergencyContactRelationship(mixed $emergencyContactRelationship): bool
    {
        if ($emergencyContactRelationship === null) {
            $this->errors['emergency_contact_relationship'] = 'The emergency contact relationship cannot be null.';

            return false;
        }

        if ( ! is_string($emergencyContactRelationship)) {
            $this->errors['emergency_contact_relationship'] = 'The emergency contact relationship must be a string.';

            return false;
        }

        if (trim($emergencyContactRelationship) === '') {
            $this->errors['emergency_contact_relationship'] = 'The emergency contact relationship cannot be empty.';

            return false;
        }

        if (strlen($emergencyContactRelationship) < 2 || strlen($emergencyContactRelationship) > 30) {
            $this->errors['emergency_contact_relationship'] = 'The emergency contact relationship must be between 2 and 30 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Za-z ]+$/', $emergencyContactRelationship)) {
            $this->errors['emergency_contact_relationship'] = 'The emergency contact relationship contains invalid characters. Only letters and spaces are allowed.';

            return false;
        }

        return true;
    }

    public function isValidEmergencyContactPhoneNumber(mixed $emergencyContactPhoneNumber): bool
    {
        if ($emergencyContactPhoneNumber === null) {
            $this->errors['emergency_contact_phone_number'] = 'The emergency contact phone number cannot be null.';

            return false;
        }

        if ( ! is_string($emergencyContactPhoneNumber)) {
            $this->errors['emergency_contact_phone_number'] = 'The emergency contact phone number must be a string.';

            return false;
        }

        if (trim($emergencyContactPhoneNumber) === '') {
            $this->errors['emergency_contact_phone_number'] = 'The emergency contact phone number cannot be empty.';

            return false;
        }

        if ( ! preg_match('/^\+?(\d{1,3}[-. ]?)?(\(\d{3}\)|\d{3})[-. ]?(\d{3})[-. ]?(\d{4})$/', $emergencyContactPhoneNumber)) {
            $this->errors['emergency_contact_phone_number'] = 'The emergency contact phone number contains invalid characters or format. Please use a valid phone number format, e.g., +63 912 345 6789 or 0912-345-6789.';

            return false;
        }

        $digits = preg_replace('/[^0-9]/', '', $emergencyContactPhoneNumber);

        if (strlen($digits) < 7 || strlen($digits) > 15) {
            $this->errors['emergency_contact_phone_number'] = 'The emergency contact phone number must be between 7 and 15 digits long, excluding non-numeric characters.';

            return false;
        }

        return true;
    }

    public function isValidEmergencyContactEmailAddress(mixed $emergencyContactEmailAddress): bool
    {
        if ($emergencyContactEmailAddress !== null && ! is_string($emergencyContactEmailAddress)) {
            $this->errors['emergency_contact_email_address'] = 'The emergency contact email address must be a string.';

            return false;
        }

        if (is_string($emergencyContactEmailAddress) && trim($emergencyContactEmailAddress) !== '') {
            if ( ! filter_var($emergencyContactEmailAddress, FILTER_VALIDATE_EMAIL)) {
                $this->errors['emergency_contact_email_address'] = 'The emergency contact email address must be a valid email address.';

                return false;
            }
        }

        return true;
    }

    public function isValidEmergencyContactAddress(mixed $emergencyContactAddress): bool
    {
        if ($emergencyContactAddress !== null && ! is_string($emergencyContactAddress)) {
            $this->errors['emergency_contact_address'] = 'The emergency contact address must be a string.';

            return false;
        }

        if (is_string($emergencyContactAddress) && trim($emergencyContactAddress) !== '') {
            if (mb_strlen($emergencyContactAddress) < 10 || mb_strlen($emergencyContactAddress) > 255) {
                $this->errors['emergency_contact_address'] = 'The emergency contact address must be between 10 and 255 characters long.';

                return false;
            }

            if ( ! preg_match('/^[a-zA-Z0-9\s.,\'\-\/#]+$/', $emergencyContactAddress)) {
                $this->errors['emergency_contact_address'] = "The emergency contact address contains invalid characters. Only letters, numbers, spaces, and the following characters are allowed: - . , ' / #";

                return false;
            }

            if ($emergencyContactAddress !== htmlspecialchars(strip_tags($emergencyContactAddress), ENT_QUOTES, 'UTF-8')) {
                $this->errors['emergency_contact_address'] = 'The emergency contact address contains HTML tags or special characters that are not allowed.';

                return false;
            }
        }

        return true;
    }

    public function isValidEmployeeCode(mixed $employeeCode): bool
    {
        if ($employeeCode === null) {
            $this->errors['employee_code'] = 'The employee code cannot be null.';

            return false;
        }

        if ( ! is_string($employeeCode)) {
            $this->errors['employee_code'] = 'The employee code must be a string.';

            return false;
        }

        if (trim($employeeCode) === '') {
            $this->errors['employee_code'] = 'The employee code cannot be empty.';

            return false;
        }

        if (mb_strlen($employeeCode) < 5 || mb_strlen($employeeCode) > 50) {
            $this->errors['employee_code'] = 'The employee code must be between 5 and 50 characters long.';

            return false;
        }

        if (preg_match('/^[A-Za-z0-9\-_\.]+$/', $employeeCode)) {
            $this->errors['employee_code'] = 'The employee code contains invalid characters. Only letters, numbers, and the following characters are allowed: - _ .';

            return false;
        }

        $isUnique = $this->isUnique('employee_code', $employeeCode);

        if ($isUnique === null) {
            $this->errors['employee_code'] = 'Unable to verify the uniqueness of the employee code. The provided employee ID may be missing or invalid. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['employee_code'] = 'This employee code already exists. Please provide a different one.';

            return false;
        }

        return true;
    }

    public function isValidJobTitleId(mixed $id): bool
    {
        if ($id === null) {
            $this->errors['job_title_id'] = 'The job title ID cannot be null.';

            return false;
        }

        if (is_int($id) || (is_string($id) && preg_match('/^[1-9]\d*$/', $id))) {
            if ($id < 1) {
                $this->errors['job_title_id'] = 'The job title ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['job_title_id'] = 'The job title ID exceeds the maximum allowable integer size.';

                return false;
            }

            $id = (int) $id;
        }

        if (is_string($id) && ! $this->isValidHash($id)) {
            $this->errors['job_title_id'] = 'The job title ID is an invalid type.';

            return false;
        }

        if ( ! is_int($id) && ! is_string($id)) {
            $this->errors['job_title_id'] = 'The job title ID is an invalid type.';

            return false;
        }

        return true;
    }

    public function isValidDepartmentId(mixed $id): bool
    {
        if ($id === null) {
            $this->errors['department_id'] = 'The department ID cannot be null.';

            return false;
        }

        if (is_int($id) || (is_string($id) && preg_match('/^[1-9]\d*$/', $id))) {
            if ($id < 1) {
                $this->errors['department_id'] = 'The department ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['department_id'] = 'The department ID exceeds the maximum allowable integer size.';

                return false;
            }

            $id = (int) $id;
        }

        if (is_string($id) && ! $this->isValidHash($id)) {
            $this->errors['department_id'] = 'The department ID is an invalid type.';

            return false;
        }

        if ( ! is_int($id) && ! is_string($id)) {
            $this->errors['department_id'] = 'The department ID is an invalid type.';

            return false;
        }

        return true;
    }

    public function isValidEmploymentType(mixed $employmentType): bool
    {
        if ($employmentType === null) {
            $this->errors['employment_type'] = 'The employment type cannot be null.';

            return false;
        }

        if ( ! is_string($employmentType)) {
            $this->errors['employment_type'] = 'The employment type must be a string.';

            return false;
        }

        if (trim($employmentType) === '') {
            $this->errors['employment_type'] = 'The employment type cannot be empty.';

            return false;
        }

        $validEmploymentTypes = [
            'regular'            ,
            'regular permanent'  ,
            'casual'             ,
            'contractual'        ,
            'project-based'      ,
            'seasonal'           ,
            'fixed-term'         ,
            'probationary'       ,
            'part-time'          ,
            'regular part-time'  ,
            'part-time permanent',
            'self-employment'    ,
            'freelance'          ,
            'internship'         ,
            'consultancy'        ,
            'apprenticeship'     ,
            'traineeship'        ,
            'gig'
        ];

        if ( ! in_array(strtolower($employmentType), $validEmploymentTypes)) {
            $this->errors['employment_type'] = 'Please select a valid employment type.';

            return false;
        }

        return true;
    }

    public function isValidDateOfHire(mixed $dateOfHire): bool
    {
        if ($dateOfHire === null) {
            $this->errors['date_of_hire'] = 'The date of hire cannot be null';

            return false;
        }

        if ( ! is_string($dateOfHire)) {
            $this->errors['date_of_hire'] = 'The date of hire must be a string.';

            return false;
        }

        if (trim($dateOfHire) === '') {
            $this->errors['date_of_hire'] = 'The date of hire cannot be empty.';

            return false;
        }

        $date = DateTime::createFromFormat('Y-m-d', $dateOfHire);

        if ($date === false || $date->format('Y-m-d') !== $dateOfHire) {
            $this->errors['date_of_hire'] = 'The date of hire must be in the Y-m-d format and be a valid date, e.g., 2025-01-01.';

            return false;
        }

        if ($date > new DateTime()) {
            $this->errors['date_of_hire'] = 'The date of hire cannot be in the future.';

            return false;
        }

        return true;
    }

    public function isValidSupervisorId(mixed $id): bool
    {
        if (is_int($id) || (is_string($id) && preg_match('/^[1-9]\d*$/', $id))) {
            if ($id < 1) {
                $this->errors['supervisor_id'] = 'The supervisor ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['supervisor_id'] = 'The supervisor ID exceeds the maximum allowable integer size.';

                return false;
            }

            $id = (int) $id;
        }

        if (is_string($id) && ! $this->isValidHash($id)) {
            $this->errors['supervisor_id'] = 'The supervisor ID is an invalid type.';

            return false;
        }

        if ($id !== null && ! is_int($id) && ! is_string($id)) {
            $this->errors['supervisor_id'] = 'The supervisor ID is an invalid type.';

            return false;
        }

        return true;
    }

    public function isValidAccessRole(mixed $accessRole): bool
    {
        if ($accessRole === null) {
            $this->errors['access_role'] = 'The access role cannot be null.';

            return false;
        }

        if ( ! is_string($accessRole)) {
            $this->errors['access_role'] = 'The access role must be a string.';

            return false;
        }

        if (trim($accessRole) === '') {
            $this->errors['access_role'] = 'The access role cannot be empty.';

            return false;
        }

        $validAccessRoles = [
            'staff'     ,
            'supervisor',
            'manager'   ,
            'admin'
        ];

        if ( ! in_array(strtolower($accessRole), $validAccessRoles)) {
            $this->errors['access_role'] = 'The access role must be one of the following: Staff, Supervisor, Manager, Admin.';

            return false;
        }

        return true;
    }

    public function isValidPayrollGroupId(mixed $id): bool
    {
        if ($id === null) {
            $this->errors['payroll_group_id'] = 'The payroll group ID cannot be null.';

            return false;
        }

        if (is_int($id) || (is_string($id) && preg_match('/^[1-9]\d*$/', $id))) {
            if ($id < 1) {
                $this->errors['payroll_group_id'] = 'The payroll group ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['payroll_group_id'] = 'The payroll group ID exceeds the maximum allowable integer size.';

                return false;
            }

            $id = (int) $id;
        }

        if (is_string($id) && ! $this->isValidHash($id)) {
            $this->errors['payroll_group_id'] = 'The payroll group ID is an invalid type.';

            return false;
        }

        if ( ! is_int($id) && ! is_string($id)) {
            $this->errors['payroll_group_id'] = 'The payroll group ID is an invalid type.';

            return false;
        }

        return true;
    }

    public function isValidBasicSalary(mixed $basicSalary): bool
    {
        if ($basicSalary === null) {
            $this->errors['basic_salary'] = 'The basic salary cannot be null.';

            return false;
        }

        if ( ! is_numeric($basicSalary)) {
            $this->errors['basic_salary'] = 'The basic salary must be a valid number.';

            return false;
        }

        if ($basicSalary < 0) {
            $this->errors['basic_salary'] = 'The basic salary must be greater than or equal to 0.';

            return false;
        }

        if ($basicSalary > 1_000_000) {
            $this->errors['basic_salary'] = 'The basic salary cannot exceed ₱1,000,000.';

            return false;
        }

        return true;
    }

    public function isValidTinNumber(mixed $tinNumber): bool
    {
        if ($tinNumber === null) {
            $this->errors['tin_number'] = 'The TIN number cannot be null.';

            return false;
        }

        if ( ! is_string($tinNumber)) {
            $this->errors['tin_number'] = 'The TIN number must be a string.';

            return false;
        }

        if (trim($tinNumber) === '') {
            $this->errors['tin_number'] = 'The TIN number cannot be empty.';

            return false;
        }

        if ( ! preg_match('/^\d{3}-\d{3}-\d{3}-\d{3}$/', $tinNumber)) {
            $this->errors['tin_number'] = 'The TIN number you entered is not valid. Please ensure it follows the correct "3-3-3-3" format: XXX-XXX-XXX-XXX.';

            return false;
        }

        $isUnique = $this->isUnique('tin_number', $tinNumber);

        if ($isUnique === null) {
            $this->errors['tin_number'] = 'Unable to verify the uniqueness of the TIN number. The provided employee ID may be missing or invalid. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['tin_number'] = 'This TIN number already exists. Please provide a different one.';

            return false;
        }

        return true;
    }

    public function isValidSssNumber(mixed $sssNumber): bool
    {
        if ($sssNumber === null) {
            $this->errors['sss_number'] = 'The SSS number cannot be null.';

            return false;
        }

        if ( ! is_string($sssNumber)) {
            $this->errors['sss_number'] = 'The SSS number must be a string.';

            return false;
        }

        if (trim($sssNumber) === '') {
            $this->errors['sss_number'] = 'The SSS number cannot be empty.';

            return false;
        }

        if ( ! preg_match('/^\d{4}-\d{7}-\d{1}$/', $sssNumber)) {
            $this->errors['sss_number'] = 'The SSS number you entered is not valid. Please ensure it follows the correct "4-7-1" format: XXXX-XXXXXXX-X.';

            return false;
        }

        $isUnique = $this->isUnique('sss_number', $sssNumber);

        if ($isUnique === null) {
            $this->errors['sss_number'] = 'Unable to verify the uniqueness of the SSS number. The provided employee ID may be missing or invalid. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['sss_number'] = 'This SSS number already exists. Please provide a different one.';

            return false;
        }

        return true;
    }

    public function isValidPhilhealthNumber(mixed $philhealthNumber): bool
    {
        if ($philhealthNumber === null) {
            $this->errors['philhealth_number'] = 'The PhilHealth number cannot be null.';

            return false;
        }

        if ( ! is_string($philhealthNumber)) {
            $this->errors['philhealth_number'] = 'The PhilHealth number must be a string.';

            return false;
        }

        if (trim($philhealthNumber) === '') {
            $this->errors['philhealth_number'] = 'The PhilHealth number cannot be empty.';

            return false;
        }

        if ( ! preg_match('/^\d{2}-\d{9}-\d{1}$/', $philhealthNumber)) {
            $this->errors['philhealth_number'] = 'The PhilHealth number you entered is not valid. Please ensure it follows the correct "2-9-1" format: XX-XXXXXXXXX-X.';

            return false;
        }

        $isUnique = $this->isUnique('philhealth_number', $philhealthNumber);

        if ($isUnique === null) {
            $this->errors['philhealth_number'] = 'Unable to verify the uniqueness of the PhilHealth number. The provided employee ID may be missing or invalid. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['philhealth_number'] = 'This PhilHealth number already exists. Please provide a different one.';

            return false;
        }

        return true;
    }

    public function isValidPagibigFundNumber(mixed $pagibigFundNumber): bool
    {
        if ($pagibigFundNumber === null) {
            $this->errors['pagibig_fund_number'] = 'The Pag-IBIG Fund number cannot be null.';

            return false;
        }

        if ( ! is_string($pagibigFundNumber)) {
            $this->errors['pagibig_fund_number'] = 'The Pag-IBIG Fund number must be a string.';

            return false;
        }

        if (trim($pagibigFundNumber) === '') {
            $this->errors['pagibig_fund_number'] = 'The Pag-IBIG Fund number cannot be empty.';

            return false;
        }

        if ( ! preg_match('/^\d{4}-\d{4}-\d{4}$/', $pagibigFundNumber)) {
            $this->errors['pagibig_fund_number'] = 'The Pag-IBIG Fund number you entered is not valid. Please ensure it follows the correct "4-4-4" format: XXXX-XXXXX-XXXX.';

            return false;
        }

        $isUnique = $this->isUnique('pagibig_fund_number', $pagibigFundNumber);

        if ($isUnique === null) {
            $this->errors['pagibig_fund_number'] = 'Unable to verify the uniqueness of the Pag-IBIG Fund number. The provided employee ID may be missing or invalid. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['pagibig_fund_number'] = 'This Pag-IBIG Fund number already exists. Please provide a different one.';

            return false;
        }

        return true;
    }

    public function isValidBankName(mixed $bankName): bool
    {
        if ($bankName === null) {
            $this->errors['bank_name'] = 'The bank name cannot be null.';

            return false;
        }

        if ( ! is_string($bankName)) {
            $this->errors['bank_name'] = 'The bank name must be a string.';

            return false;
        }

        if (trim($bankName) === '') {
            $this->errors['bank_name'] = 'The bank name cannot be empty.';

            return false;
        }

        if (mb_strlen($bankName) < 3 || mb_strlen($bankName) > 50) {
            $this->errors['bank_name'] = 'The bank name must be between 3 and 50 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Za-z0-9\s\-.,\'()\/&]+$/', $bankName)) {
            $this->errors['bank_name'] = 'The bank name contains invalid characters. Only letters, numbers, spaces, and the following characters are allowed: - . , \' ( ) / &';

            return false;
        }

        if ($bankName !== htmlspecialchars(strip_tags($bankName), ENT_QUOTES, 'UTF-8')) {
            $this->errors['bank_name'] = 'The bank name contains HTML tags or special characters that are not allowed.';

            return false;
        }

        return true;
    }

    public function isValidBankBranchName(mixed $bankBranchName): bool
    {
        if ($bankBranchName === null) {
            $this->errors['bank_branch_name'] = 'The bank branch name cannot be null.';

            return false;
        }

        if ( ! is_string($bankBranchName)) {
            $this->errors['bank_branch_name'] = 'The bank branch name must be a string.';

            return false;
        }

        if (trim($bankBranchName) === '') {
            $this->errors['bank_branch_name'] = 'The bank branch name cannot be empty.';

            return false;
        }

        if (mb_strlen($bankBranchName) < 5 || mb_strlen($bankBranchName) > 100) {
            $this->errors['bank_branch_name'] = 'The bank branch name must be between 5 and 100 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Za-z0-9\s\-.,\'()\/&]+$/', $bankBranchName)) {
            $this->errors['bank_branch_name'] = 'The bank branch name contains invalid characters. Only letters, numbers, spaces, and the following characters are allowed: - . , \' ( ) / &';

            return false;
        }

        if ($bankBranchName !== htmlspecialchars(strip_tags($bankBranchName), ENT_QUOTES, 'UTF-8')) {
            $this->errors['bank_branch_name'] = 'The bank branch name contains HTML tags or special characters that are not allowed.';

            return false;
        }

        return true;
    }

    public function isValidBankAccountNumber(mixed $bankAccountNumber): bool
    {
        if ($bankAccountNumber === null) {
            $this->errors['bank_account_number'] = 'The bank account number cannot be null.';

            return false;
        }

        if ( ! is_string($bankAccountNumber)) {
            $this->errors['bank_account_number'] = 'The bank account number must be a string.';

            return false;
        }

        if (trim($bankAccountNumber) === '') {
            $this->errors['bank_account_number'] = 'The bank account number cannot be empty.';

            return false;
        }

        if ( ! preg_match('/^\d{10,12}$/', $bankAccountNumber)) {
            $this->errors['bank_account_number'] = 'The bank account number contains invalid characters. It must be between 10 and 12 digits long and contain only numbers.';

            return false;
        }

        $isUnique = $this->isUnique('bank_account_number', $bankAccountNumber);

        if ($isUnique === null) {
            $this->errors['bank_account_number'] = 'Unable to verify the uniqueness of the bank account number. The provided employee ID may be missing or invalid. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['bank_account_number'] = 'This bank account number already exists. Please provide a different one.';

            return false;
        }

        return true;
    }

    public function isValidBankAccountType(mixed $bankAccountType): bool
    {
        if ($bankAccountType === null) {
            $this->errors['bank_account_type'] = 'The bank account type cannot be null.';

            return false;
        }

        if ( ! is_string($bankAccountType)) {
            $this->errors['bank_account_type'] = 'The bank account type must be a string.';

            return false;
        }

        if (trim($bankAccountType) === '') {
            $this->errors['bank_account_type'] = 'The bank account type cannot be empty.';

            return false;
        }

        $validBankAccountTypes = [
            'payroll account' ,
            'current account' ,
            'checking account',
            'savings account'
        ];

        if ( ! in_array(strtolower($bankAccountType), $validBankAccountTypes)) {
            $this->errors['bank_account_type'] = 'The bank account type must be one of the following: Payroll Account, Current Account, Checking Account, Savings Account.';

            return false;
        }

        return true;
    }

    public function isValidUsername(mixed $username): bool
    {
        if ($username === null) {
            $this->errors['username'] = 'The username cannot be null.';

            return false;
        }

        if ( ! is_string($username)) {
            $this->errors['username'] = 'The username must be a string.';

            return false;
        }

        if (trim($username) === '') {
            $this->errors['username'] = 'The username cannot be empty.';

            return false;
        }

        if (mb_strlen($username) < 3 || mb_strlen($username) > 25) {
            $this->errors['username'] = 'The username must be between 3 and 25 characters long.';

            return false;
        }

        if ( ! preg_match('/^[a-zA-Z0-9._]+$/', $username)) {
            $this->errors['username'] = 'The username contains invalid characters. Only letters, numbers, and the following characters are allowed: . _';

            return false;
        }

        $isUnique = $this->isUnique('username', $username);

        if ($isUnique === null) {
            $this->errors['username'] = 'Unable to verify the uniqueness of the username. The provided employee ID may be missing or invalid. Please try again later.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['username'] = 'This username already exists. Please provide a different one.';

            return false;
        }

        return true;
    }

    public function isValidPassword(mixed $password): bool
    {
        if ($password === null) {
            $this->errors['password'] = 'The password cannot be null.';

            return false;
        }

        if ( ! is_string($password)) {
            $this->errors['password'] = 'The password must be a string.';

            return false;
        }

        if (trim($password) === '') {
            $this->errors['password'] = 'The password cannot be empty.';

            return false;
        }

        if (mb_strlen($password) < 8 || mb_strlen($password) > 50) {
            $this->errors['password'] = 'The password must be between 8 and 50 characters long.';

            return false;
        }

        if ( ! preg_match('/^[a-zA-Z0-9._!@#$%^&*()\-+=]+$/', $password)) {
            $this->errors['password'] = 'The password contains invalid characters. Only letters, numbers, and the following characters are allowed: . _ ! @ # $ % ^ & * ( ) - + =';

            return false;
        }

        if ( ! preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*()\-+=]).+$/', $password)) {
            $this->errors['password'] = 'The password must contain at least one uppercase letter, one lowercase letter, one digit, and one special character: ! @ # $ % ^ & * ( ) - + =';

            return false;
        }

        return true;
    }

    public function isValidNotes(mixed $notes): bool
    {
        if ($notes !== null && ! is_string($notes)) {
            $this->errors['notes'] = 'The notes must be a string.';

            return false;
        }

        if (is_string($notes) && trim($notes) !== '') {
            if ($notes !== htmlspecialchars(strip_tags($notes), ENT_QUOTES, 'UTF-8')) {
                $this->errors['notes'] = 'The notes contains HTML tags or special characters that are not allowed.';

                return false;
            }
        }

        return true;
    }

    private function isUnique(string $field, mixed $value): ?bool
    {
        if ( ! isset($this->errors['id'])) {
            $id = $this->data['id'] ?? null;

            $columns = [
                'id'
            ];

            $filterCriteria = [
                [
                    'column'   => 'employee.deleted_at',
                    'operator' => 'IS NULL'
                ],
                [
                    'column'   => 'employee.' . $field,
                    'operator' => '='                 ,
                    'value'    => $value
                ]
            ];

            if (is_int($id) || (is_string($id) && preg_match('/^[1-9]\d*$/', $id))) {
                $filterCriteria[] = [
                    'column'   => 'employee.id',
                    'operator' => '!='         ,
                    'value'    => $id
                ];

            } elseif (is_string($id) && $this->isValidHash($id)) {
                $filterCriteria[] = [
                    'column'   => 'SHA2(employee.id, 256)',
                    'operator' => '!='                    ,
                    'value'    => $id
                ];
            }

            $isUnique = $this->employeeRepository->fetchAllEmployees(
                columns             : $columns       ,
                filterCriteria      : $filterCriteria,
                limit               : 1              ,
                includeTotalRowCount: false
            );

            if ($isUnique === ActionResult::FAILURE) {
                return null;
            }

            return empty($isUnique['result_set']);
        }

        return null;
    }
}
