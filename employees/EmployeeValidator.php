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
                }
            }
        }
    }

    public function isValidRfidUid(mixed $rfidUid, mixed $id): bool
    {
        if ($rfidUid === null) {
            $this->errors['rfid_uid'] = 'The RFID UID cannot be null.';

            return false;
        }

        if ( ! is_string($rfidUid)) {
            $this->errors['rfid_uid'] = 'The RFID UID must be a string.';

            return false;
        }

        $rfidUid = trim($rfidUid);

        if ($rfidUid === '') {
            $this->errors['rfid_uid'] = 'The RFID UID cannot be empty.';

            return false;
        }

        if (mb_strlen($rfidUid) < 8 || mb_strlen($rfidUid) > 32) {
            $this->errors['rfid_uid'] = 'The RFID UID must be between 8 and 32 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Fa-f0-9]+$/', $rfidUid)) {
            $this->errors['rfid_uid'] = 'The RFID UID must contain only hexadecimal characters (0-9, A-F).';

            return false;
        }

        $isUnique = $this->isUnique('rfid_uid', $rfidUid, $id);

        if ($isUnique === null) {
            $this->errors['rfid_uid'] = 'An unexpected error occurred while checking for uniqueness.';

            return false;
        }

        if ($isUnique === false) {
            $this->errors['rfid_uid'] = 'The RFID UID must be unique, another entry already exists with this RFID UID.';

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

        $firstName = trim($firstName);

        if ($firstName === '') {
            $this->errors['first_name'] = 'The first name cannot be empty.';

            return false;
        }

        if (mb_strlen($firstName) < 2 || mb_strlen($firstName) > 30) {
            $this->errors['first_name'] = 'The first name must be between 2 and 30 characters long.';

            return false;
        }

        if ( ! preg_match('/^[\p{L}._\'\-, ]+$/u', $firstName)) {
            $this->errors['first_name'] = 'The first name can only contain letters, periods, hyphens, underscores, apostrophes, commas, and spaces.';

            return false;
        }

        return true;
    }

    public function isValidMiddleName(mixed $middleName): bool
    {
        if ($middleName !== null && ! is_string($middleName)) {
            $this->errors['middle_name'] = '';

            return false;
        }

        if (is_string($middleName)) {
            $middleName = trim($middleName);

            if ($middleName !== '') {
                if (mb_strlen($middleName) < 2 || mb_strlen($middleName) > 30) {
                    $this->errors['middle_name'] = 'The middle name must be between 2 and 30 characters long.';

                    return false;
                }

                if ( ! preg_match('/^(?!.*[._\'\-,]{2})[\p{L}._\'\-, ]+$/u', $middleName)) {
                    $this->errors['middle_name'] = 'The middle name can only contain letters, periods, hyphens, underscores, apostrophes, commas, and spaces, but consecutive special characters are not allowed.';

                    return false;
                }
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

        $lastName = trim($lastName);

        if ($lastName === '') {
            $this->errors['last_name'] = 'The last name cannot be empty.';

            return false;
        }

        if (mb_strlen($lastName) < 2 || mb_strlen($lastName) > 30) {
            $this->errors['last_name'] = 'The last name must be between 2 and 30 characters long.';

            return false;
        }

        if ( ! preg_match('/^[\p{L}._\'\-, ]+$/u', $lastName)) {
            $this->errors['last_name'] = 'The last name can only contain letters, periods, hyphens, underscores, apostrophes, commas, and spaces.';

            return false;
        }

        if ($lastName !== htmlspecialchars(strip_tags($lastName), ENT_QUOTES, 'UTF-8')) {
            $this->errors['last_name'] = 'The last name contains invalid characters.';

            return false;
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

        $dateOfBirth = trim($dateOfBirth);

        if ($dateOfBirth === '') {
            $this->errors['date_of_birth'] = 'The date of birth cannot be empty.';

            return false;
        }

        $date = DateTime::createFromFormat('Y-m-d', $dateOfBirth);

        if ($date === false || $date->format('Y-m-d') !== $dateOfBirth) {
            $this->errors['date_of_birth'] = 'The date of birth must be in the Y-m-d format and be a valid date.';

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

        $gender = trim($gender);

        if ($gender === '') {
            $this->errors['gender'] = 'The gender cannot be empty.';

            return false;
        }

        if (mb_strlen($gender) < 3 || mb_strlen($gender) > 50) {
            $this->errors['gender'] = 'The gender must be between 3 and 50 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Za-z ]+$/', $gender)) {
            $this->errors['gender'] = 'The gender can only contain letters.';

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

        $maritalStatus = trim($maritalStatus);

        if ($maritalStatus === '') {
            $this->errors['marital_status'] = 'The marital status cannot be empty.';

            return false;
        }

        if ( ! in_array(strtolower($maritalStatus), ['single', 'married', 'divorced', 'widowed', 'separated'])) {
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

        $nationality = trim($nationality);

        if ($nationality === '') {
            $this->errors['nationality'] = 'The nationality cannot be empty.';

            return false;
        }

        if (mb_strlen($nationality) < 3 || mb_strlen($nationality) > 50) {
            $this->errors['nationality'] = 'The nationality must be between 3 and 50 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Za-z ]+$/', $nationality)) {
            $this->errors['nationality'] = 'The nationality can only contain letters and spaces.';

            return false;
        }

        return true;
    }

    public function isValidReligion(mixed $religion): bool
    {
        if ($religion !== null && ! is_string($religion)) {
            $this->errors['religion'] = '';

            return false;
        }

        if ($religion !== null) {
            if ( ! is_string($religion)) {
                $this->errors['religion'] = 'The religion must be a string.';

                return false;
            }

            $religion = trim($religion);

            if (mb_strlen($religion) < 3 || mb_strlen($religion) > 50) {
                $this->errors['religion'] = 'The religion must be between 3 and 50 characters long.';

                return false;
            }

            if ( ! preg_match('/^[A-Za-z ]+$/', $religion)) {
                $this->errors['religion'] = 'The religion can only contain letters and spaces.';

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

        $phoneNumber = trim($phoneNumber);

        if ($phoneNumber === '') {
            $this->errors['phone_number'] = 'The phone number cannot be empty.';

            return false;
        }

        if ( ! preg_match('/^\+?(\d{1,3}[-. ]?)?(\(\d{3}\)|\d{3})[-. ]?(\d{3})[-. ]?(\d{4})$/', $phoneNumber)) {
            $this->errors['phone_number'] = '';

            return false;
        }

        $digits = preg_replace('/[^0-9]/', '', $phoneNumber);

        if (strlen($digits) < 7 || strlen($digits) > 15) {
            $this->errors['phone_number'] = '';

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

        $emailAddress = trim($emailAddress);

        if ($emailAddress === '') {
            $this->errors['email_address'] = 'The email address cannot be empty.';

            return false;
        }

        if ( ! filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email_address'] = 'The email address must be a valid email address.';

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

        $address = trim($address);

        if ($address === '') {
            $this->errors['address'] = 'The address cannot be empty.';

            return false;
        }

        if (mb_strlen($address) < 10 || mb_strlen($address) > 255) {
            $this->errors['address'] = 'The address must be between 10 and 255 characters long.';

            return false;
        }

        if ( ! preg_match('/^\d+\s+([a-zA-Z]+\s*)+,\s*([a-zA-Z]+\s*)+,\s*[A-Z]{2}\s*\d{5}(-\d{4})?$/', $address)) {
            $this->errors['address'] = '';

            return false;
        }

        if ($address !== htmlspecialchars(strip_tags($address), ENT_QUOTES, 'UTF-8')) {
            $this->errors['address'] = 'The address contains invalid characters.';

            return false;
        }

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

        $emergencyContactName = trim($emergencyContactName);

        if ($emergencyContactName === '') {
            $this->errors['emergency_contact_name'] = 'The emergency contact name cannot be empty.';

            return false;
        }

        if (mb_strlen($emergencyContactName) < 6 || mb_strlen($emergencyContactName) > 90) {
            $this->errors['emergency_contact_name'] = 'The emergency contact name must be between 6 and 90 characters long.';

            return false;
        }

        if ( ! preg_match('/^[\p{L}._\'\-, ]+$/u', $emergencyContactName)) {
            $this->errors['emergency_contact_name'] = 'The emergency contact name can only contain letters, periods, hyphens, underscores, apostrophes, commas, and spaces.';

            return false;
        }

        if ($emergencyContactName !== htmlspecialchars(strip_tags($emergencyContactName), ENT_QUOTES, 'UTF-8')) {
            $this->errors['emergency_contact_name'] = 'The emergency contact name contains invalid characters.';

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

        $emergencyContactRelationship = trim($emergencyContactRelationship);

        if ($emergencyContactRelationship === '') {
            $this->errors['emergency_contact_relationship'] = 'The emergency contact relationship cannot be empty.';

            return false;
        }

        if (strlen($emergencyContactRelationship) < 2 || strlen($emergencyContactRelationship) > 30) {
            $this->errors['emergency_contact_relationship'] = 'The emergency contact relationship must be between 2 and 30 characters long.';

            return false;
        }

        if ( ! preg_match('/^[A-Za-z ]+$/', $emergencyContactRelationship)) {
            $this->errors['emergency_contact_relationship'] = 'The emergency contact relationship can only contain letters and spaces.';

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

        $emergencyContactPhoneNumber = trim($emergencyContactPhoneNumber);

        if ($emergencyContactPhoneNumber === '') {
            $this->errors['emergency_contact_phone_number'] = 'The emergency contact phone number cannot be empty.';

            return false;
        }

        if ( ! preg_match('/^\+?(\d{1,3}[-. ]?)?(\(\d{3}\)|\d{3})[-. ]?(\d{3})[-. ]?(\d{4})$/', $emergencyContactPhoneNumber)) {
            $this->errors['emergency_contact_phone_number'] = '';

            return false;
        }

        $digits = preg_replace('/[^0-9]/', '', $emergencyContactPhoneNumber);

        if (strlen($digits) < 7 || strlen($digits) > 15) {
            $this->errors['emergency_contact_phone_number'] = '';

            return false;
        }

        return true;
    }

    public function isValidEmergencyContactEmailAddress(mixed $emergencyContactEmailAddress): bool
    {
        if ($emergencyContactEmailAddress !== null && ! is_string($emergencyContactEmailAddress)) {
            $this->errors['emergency_contact_email_address'] = '';

            return false;
        }

        if ( ! is_string($emergencyContactEmailAddress)) {
            $this->errors['emergency_contact_email_address'] = 'The emergency contact email address must be a string.';

            return false;
        }

        $emergencyContactEmailAddress = trim($emergencyContactEmailAddress);

        if ( ! filter_var($emergencyContactEmailAddress, FILTER_VALIDATE_EMAIL)) {
            $this->errors['emergency_contact_email_address'] = 'The emergency contact email address must be a valid email address.';

            return false;
        }

        return true;
    }

    public function isValidEmergencyContactAddress(mixed $emergencyContactAddress): bool
    {
        if ( ! is_string($emergencyContactAddress)) {
            $this->errors['emergency_contact_address'] = 'The emergency contact address must be a string.';

            return false;
        }

        $emergencyContactAddress = trim($emergencyContactAddress);

        if ( ! $emergencyContactAddress !== '') {
            if (mb_strlen($emergencyContactAddress) < 10 || mb_strlen($emergencyContactAddress) > 255) {
                $this->errors['emergency_contact_address'] = 'The emergency contact address must be between 10 and 255 characters long.';

                return false;
            }

            if ( ! preg_match('/^\d+\s+([a-zA-Z]+\s*)+,\s*([a-zA-Z]+\s*)+,\s*[A-Z]{2}\s*\d{5}(-\d{4})?$/', $emergencyContactAddress)) {
                $this->errors['emergency_contact_address'] = 'The emergency contact address format is invalid.';

                return false;
            }

            if ($emergencyContactAddress !== htmlspecialchars(strip_tags($emergencyContactAddress), ENT_QUOTES, 'UTF-8')) {
                $this->errors['emergency_contact_address'] = 'The emergency contact address contains invalid characters.';

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

        $employeeCode = trim($employeeCode);

        if ($employeeCode === '') {
            $this->errors['employee_code'] = 'The employee code cannot be empty.';

            return false;
        }

        if (mb_strlen($employeeCode) < 3 || mb_strlen($employeeCode) > 100) {
            $this->errors['employee_code'] = 'The employee code must be between 3 and 100 characters long.';

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

        $employmentType = trim($employmentType);

        if ($employmentType === '') {
            $this->errors['employment_type'] = 'The employment type cannot be empty.';

            return false;
        }

        $validEmploymentTypes = [
            'regular / permanent',
            'casual'             ,
            'contractual'        ,
            'project-based'      ,
            'seasonal'           ,
            'fixed-term'         ,
            'probationary'       ,
            'part-time'          ,
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

        $dateOfHire = trim($dateOfHire);

        if ($dateOfHire === '') {
            $this->errors['date_of_hire'] = 'The date of hire cannot be empty.';

            return false;
        }

        $date = DateTime::createFromFormat('Y-m-d', $dateOfHire);

        if ($date === false || $date->format('Y-m-d') !== $dateOfHire) {
            $this->errors['date_of_hire'] = 'The date of hire must be in the Y-m-d format and be a valid date.';

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

    public function isValidManagerId(mixed $id): bool
    {
        if (is_int($id) || (is_string($id) && preg_match('/^[1-9]\d*$/', $id))) {
            if ($id < 1) {
                $this->errors['manager_id'] = 'The manager ID must be greater than 0.';

                return false;
            }

            if ($id > PHP_INT_MAX) {
                $this->errors['manager_id'] = 'The manager ID exceeds the maximum allowable integer size.';

                return false;
            }

            $id = (int) $id;
        }

        if (is_string($id) && ! $this->isValidHash($id)) {
            $this->errors['manager_id'] = 'The manager ID is an invalid type.';

            return false;
        }

        if ($id !== null && ! is_int($id) && ! is_string($id)) {
            $this->errors['manager_id'] = 'The manager ID is an invalid type.';

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

        $accessRole = trim($accessRole);

        if ($accessRole === '') {
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

        if ($basicSalary <= 0) {
            $this->errors['basic_salary'] = 'The basic salary must be greater than 0.';

            return false;
        }

        if ($basicSalary > 1_000_000) {
            $this->errors['basic_salary'] = 'The basic salary cannot exceed PHP 1,000,000.';

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

        $tinNumber = trim($tinNumber);

        if ($tinNumber === '') {
            $this->errors['tin_number'] = 'The TIN number cannot be empty.';

            return false;
        }

        if ( ! preg_match('/^\d{3}-\d{3}-\d{3}-\d{3}$/', $tinNumber)) {
            $this->errors['tin_number'] = 'The TIN number you entered is not valid. Please ensure it follows the correct "3-3-3-3" format: XXX-XXX-XXX-XXX.';

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

        $sssNumber = trim($sssNumber);

        if ($sssNumber === '') {
            $this->errors['sss_number'] = 'The SSS number cannot be empty.';

            return false;
        }

        if ( ! preg_match('/^\d{4}-\d{7}-\d{1}$/', $sssNumber)) {
            $this->errors['sss_number'] = 'The SSS number you entered is not valid. Please ensure it follows the correct "4-7-1" format: XXXX-XXXXXXX-X.';

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

        $philhealthNumber = trim($philhealthNumber);

        if ($philhealthNumber === '') {
            $this->errors['philhealth_number'] = 'The PhilHealth number cannot be empty.';

            return false;
        }

        if ( ! preg_match('/^\d{2}-\d{9}-\d{1}$/', $philhealthNumber)) {
            $this->errors['philhealth_number'] = 'The PhilHealth number you entered is not valid. Please ensure it follows the correct "2-9-1" format: XX-XXXXXXXXX-X.';

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

        $pagibigFundNumber = trim($pagibigFundNumber);

        if ($pagibigFundNumber === '') {
            $this->errors['pagibig_fund_number'] = 'The Pag-IBIG Fund number cannot be empty.';

            return false;
        }

        if ( ! preg_match('/^\d{4}-\d{4}-\d{4}$/', $pagibigFundNumber)) {
            $this->errors['pagibig_fund_number'] = 'The Pag-IBIG Fund number you entered is not valid. Please ensure it follows the correct "4-4-4" format: XXXX-XXXXX-XXXX.';

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

        $bankName = trim($bankName);

        if ($bankName === '') {
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

        $bankBranchName = trim($bankBranchName);

        if ($bankBranchName === '') {
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

        $bankAccountNumber = trim($bankAccountNumber);

        if ($bankAccountNumber === '') {
            $this->errors['bank_account_number'] = 'The bank account number cannot be empty.';

            return false;
        }

        if ( ! preg_match('/^\d{10,12}$/', $bankAccountNumber)) {
            $this->errors['bank_account_number'] = 'The bank account number contains invalid characters. It must be between 10 and 12 digits long and contain only numbers.';

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

        $bankAccountType = trim($bankAccountType);

        if ($bankAccountType === '') {
            $this->errors['bank_account_type'] = 'The bank account type cannot be empty.';

            return false;
        }

        $validBankAccountTypes = [
            'payroll Account' ,
            'current Account' ,
            'checking Account',
            'savings account'
        ];

        if ( ! in_array(strtolower($bankAccountType), $validBankAccountTypes)) {
            $this->errors['bank_account_type'] = 'The bank account type must be one of the following: Payroll Account, Current Account, Checking Account, and Savings Account.';

            return false;
        }

        return true;
    }

    private function isUnique(string $field, mixed $value, mixed $id): ?bool
    {
        if ( ! isset($this->errors['id'])) {
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
                    'operator' => '='                   ,
                    'value'    => $value
                ]
            ];

            if (is_int($id) || (is_string($id) && preg_match('/^[1-9]\d*$/', $id))) {
                $filterCriteria[] = [
                    'column'   => 'employee.id',
                    'operator' => '!='           ,
                    'value'    => $id
                ];

            } elseif (is_string($id) && ! $this->isValidHash($id)) {
                $filterCriteria[] = [
                    'column'   => 'SHA2(employee.id, 256)',
                    'operator' => '!='                      ,
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
