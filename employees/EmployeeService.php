<?php

require_once __DIR__ . '/EmployeeRepository.php';

require_once __DIR__ . '/EmployeeValidator.php' ;

class EmployeeService
{
    private readonly EmployeeRepository $employeeRepository;

    private readonly EmployeeValidator $employeeValidator;

    public function __construct(EmployeeRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;

        $this->employeeValidator = new EmployeeValidator($employeeRepository);
    }

    public function createEmployee(array $employee): array
    {
        $this->employeeValidator->setGroup('create');

        $this->employeeValidator->setData($employee);

        $this->employeeValidator->validate([
            'rfid_uid'                       ,
            'first_name'                     ,
            'middle_name'                    ,
            'last_name'                      ,
            'date_of_birth'                  ,
            'gender'                         ,
            'marital_status'                 ,
            'nationality'                    ,
            'religion'                       ,
            'phone_number'                   ,
            'email_address'                  ,
            'address'                        ,
            'profile_picture'                ,
            'emergency_contact_name'         ,
            'emergency_contact_relationship' ,
            'emergency_contact_phone_number' ,
            'emergency_contact_email_address',
            'emergency_contact_address'      ,
            'job_title_id'                   ,
            'department_id'                  ,
            'employment_type'                ,
            'date_of_hire'                   ,
            'supervisor_id'                  ,
            'access_role'                    ,
            'payroll_group_id'               ,
            'basic_salary'                   ,
            'tin_number'                     ,
            'sss_number'                     ,
            'philhealth_number'              ,
            'pagibig_fund_number'            ,
            'bank_name'                      ,
            'bank_branch_name'               ,
            'bank_account_number'            ,
            'bank_account_type'              ,
            'username'                       ,
            'password'
        ]);

        $validationErrors = $this->employeeValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $employeeCode = $this->generateEmployeeCode();

        $jobTitleId     = $employee['job_title_id'    ];
        $departmentId   = $employee['department_id'   ];
        $supervisorId   = $employee['supervisor_id'   ];
        $payrollGroupId = $employee['payroll_group_id'];

        if (filter_var($jobTitleId, FILTER_VALIDATE_INT) !== false) {
            $jobTitleId = (int) $jobTitleId;
        }

        if (filter_var($departmentId, FILTER_VALIDATE_INT) !== false) {
            $departmentId = (int) $departmentId;
        }

        if (filter_var($supervisorId, FILTER_VALIDATE_INT) !== false) {
            $supervisorId = (int) $supervisorId;
        } elseif (is_string($supervisorId) && trim($supervisorId) === '') {
            $supervisorId = null;
        }

        if (filter_var($payrollGroupId, FILTER_VALIDATE_INT) !== false) {
            $payrollGroupId = (int) $payrollGroupId;
        }

        $middleName                   = is_string($employee['middle_name'                    ]) && trim($employee['middle_name'                    ]) === '' ? null : (is_string($employee['middle_name'                    ]) ? trim($employee['middle_name'                    ]) : $employee['middle_name'                    ]);
        $religion                     = is_string($employee['religion'                       ]) && trim($employee['religion'                       ]) === '' ? null : (is_string($employee['religion'                       ]) ? trim($employee['religion'                       ]) : $employee['religion'                       ]);
        $profilePicture               = is_string($employee['profile_picture'                ]) && trim($employee['profile_picture'                ]) === '' ? null : (is_string($employee['profile_picture'                ]) ? trim($employee['profile_picture'                ]) : $employee['profile_picture'                ]);
        $emergencyContactEmailAddress = is_string($employee['emergency_contact_email_address']) && trim($employee['emergency_contact_email_address']) === '' ? null : (is_string($employee['emergency_contact_email_address']) ? trim($employee['emergency_contact_email_address']) : $employee['emergency_contact_email_address']);
        $emergencyContactAddress      = is_string($employee['emergency_contact_address'      ]) && trim($employee['emergency_contact_address'      ]) === '' ? null : (is_string($employee['emergency_contact_address'      ]) ? trim($employee['emergency_contact_address'      ]) : $employee['emergency_contact_address'      ]);

        $newEmployee = new Employee(
            id                          : null                                       ,
            rfidUid                     : $employee['rfid_uid'                      ],
            firstName                   : $employee['first_name'                    ],
            middleName                  : $middleName                                ,
            lastName                    : $employee['last_name'                     ],
            dateOfBirth                 : $employee['date_of_birth'                 ],
            gender                      : $employee['gender'                        ],
            maritalStatus               : $employee['marital_status'                ],
            nationality                 : $employee['nationality'                   ],
            religion                    : $religion                                  ,
            phoneNumber                 : $employee['phone_number'                  ],
            emailAddress                : $employee['email_address'                 ],
            address                     : $employee['address'                       ],
            profilePicture              : $profilePicture                            ,
            emergencyContactName        : $employee['emergency_contact_name'        ],
            emergencyContactRelationship: $employee['emergency_contact_relationship'],
            emergencyContactPhoneNumber : $employee['emergency_contact_phone_number'],
            emergencyContactEmailAddress: $emergencyContactEmailAddress              ,
            emergencyContactAddress     : $emergencyContactAddress                   ,
            employeeCode                : $employeeCode                              ,
            jobTitleId                  : $jobTitleId                                ,
            departmentId                : $departmentId                              ,
            employmentType              : $employee['employment_type'               ],
            dateOfHire                  : $employee['date_of_hire'                  ],
            supervisorId                : $supervisorId                              ,
            accessRole                  : $employee['access_role'                   ],
            payrollGroupId              : $payrollGroupId                            ,
            basicSalary                 : $employee['basic_salary'                  ],
            tinNumber                   : $employee['tin_number'                    ],
            sssNumber                   : $employee['sss_number'                    ],
            philhealthNumber            : $employee['philhealth_number'             ],
            pagibigFundNumber           : $employee['pagibig_number'                ],
            bankName                    : $employee['bank_name'                     ],
            bankBranchName              : $employee['bank_branch_name'              ],
            bankAccountNumber           : $employee['bank_account_number'           ],
            bankAccountType             : $employee['bank_account_type'             ],
            username                    : $employee['username'                      ],
            password                    : $employee['password'                      ],
            notes                       : null
        );

        $createEmployeeResult = $this->employeeRepository->createEmployee($newEmployee);

        if ($createEmployeeResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while creating the employee. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Employee created successfully.'
        ];
    }

    public function fetchAllEmployees(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->employeeRepository->fetchAllEmployees(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function fetchLastEmployeeId(): int
    {
        return $this->employeeRepository->fetchLastEmployeeId();
    }

    public function updateEmployee(array $employee): array
    {
        $this->employeeValidator->setGroup('update');

        $this->employeeValidator->setData($employee);

        $this->employeeValidator->validate([
            'id'                             ,
            'rfid_uid'                       ,
            'first_name'                     ,
            'middle_name'                    ,
            'last_name'                      ,
            'date_of_birth'                  ,
            'gender'                         ,
            'marital_status'                 ,
            'nationality'                    ,
            'religion'                       ,
            'phone_number'                   ,
            'email_address'                  ,
            'address'                        ,
            'profile_picture'                ,
            'emergency_contact_name'         ,
            'emergency_contact_relationship' ,
            'emergency_contact_phone_number' ,
            'emergency_contact_email_address',
            'emergency_contact_address'      ,
            'employee_code'                  ,
            'job_title_id'                   ,
            'department_id'                  ,
            'employment_type'                ,
            'date_of_hire'                   ,
            'supervisor_id'                  ,
            'access_role'                    ,
            'payroll_group_id'               ,
            'basic_salary'                   ,
            'tin_number'                     ,
            'sss_number'                     ,
            'philhealth_number'              ,
            'pagibig_fund_number'            ,
            'bank_name'                      ,
            'bank_branch_name'               ,
            'bank_account_number'            ,
            'bank_account_type'              ,
            'username'                       ,
            'password'
        ]);

        $validationErrors = $this->employeeValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $employeeId     = $employee['id'              ];
        $jobTitleId     = $employee['job_title_id'    ];
        $departmentId   = $employee['department_id'   ];
        $supervisorId   = $employee['supervisor_id'   ];
        $payrollGroupId = $employee['payroll_group_id'];

        if (filter_var($employeeId, FILTER_VALIDATE_INT) !== false) {
            $employeeId = (int) $employeeId;
        }

        if (filter_var($jobTitleId, FILTER_VALIDATE_INT) !== false) {
            $jobTitleId = (int) $jobTitleId;
        }

        if (filter_var($departmentId, FILTER_VALIDATE_INT) !== false) {
            $departmentId = (int) $departmentId;
        }

        if (filter_var($supervisorId, FILTER_VALIDATE_INT) !== false) {
            $supervisorId = (int) $supervisorId;
        } elseif (is_string($supervisorId) && trim($supervisorId) === '') {
            $supervisorId = null;
        }

        if (filter_var($payrollGroupId, FILTER_VALIDATE_INT) !== false) {
            $payrollGroupId = (int) $payrollGroupId;
        }

        $middleName                   = is_string($employee['middle_name'                    ]) && trim($employee['middle_name'                    ]) === '' ? null : (is_string($employee['middle_name'                    ]) ? trim($employee['middle_name'                    ]) : $employee['middle_name'                    ]);
        $religion                     = is_string($employee['religion'                       ]) && trim($employee['religion'                       ]) === '' ? null : (is_string($employee['religion'                       ]) ? trim($employee['religion'                       ]) : $employee['religion'                       ]);
        $profilePicture               = is_string($employee['profile_picture'                ]) && trim($employee['profile_picture'                ]) === '' ? null : (is_string($employee['profile_picture'                ]) ? trim($employee['profile_picture'                ]) : $employee['profile_picture'                ]);
        $emergencyContactEmailAddress = is_string($employee['emergency_contact_email_address']) && trim($employee['emergency_contact_email_address']) === '' ? null : (is_string($employee['emergency_contact_email_address']) ? trim($employee['emergency_contact_email_address']) : $employee['emergency_contact_email_address']);
        $emergencyContactAddress      = is_string($employee['emergency_contact_address'      ]) && trim($employee['emergency_contact_address'      ]) === '' ? null : (is_string($employee['emergency_contact_address'      ]) ? trim($employee['emergency_contact_address'      ]) : $employee['emergency_contact_address'      ]);

        $newEmployee = new Employee(
            id                          : $employeeId                                 ,
            rfidUid                     : $employee['rfid_uid'                      ],
            firstName                   : $employee['first_name'                    ],
            middleName                  : $middleName                                ,
            lastName                    : $employee['last_name'                     ],
            dateOfBirth                 : $employee['date_of_birth'                 ],
            gender                      : $employee['gender'                        ],
            maritalStatus               : $employee['marital_status'                ],
            nationality                 : $employee['nationality'                   ],
            religion                    : $religion                                  ,
            phoneNumber                 : $employee['phone_number'                  ],
            emailAddress                : $employee['email_address'                 ],
            address                     : $employee['address'                       ],
            profilePicture              : $profilePicture                            ,
            emergencyContactName        : $employee['emergency_contact_name'        ],
            emergencyContactRelationship: $employee['emergency_contact_relationship'],
            emergencyContactPhoneNumber : $employee['emergency_contact_phone_number'],
            emergencyContactEmailAddress: $emergencyContactEmailAddress              ,
            emergencyContactAddress     : $emergencyContactAddress                   ,
            employeeCode                : $employee['employee_code'                 ],
            jobTitleId                  : $jobTitleId                                ,
            departmentId                : $departmentId                              ,
            employmentType              : $employee['employment_type'               ],
            dateOfHire                  : $employee['date_of_hire'                  ],
            supervisorId                : $supervisorId                              ,
            accessRole                  : $employee['access_role'                   ],
            payrollGroupId              : $payrollGroupId                            ,
            basicSalary                 : $employee['basic_salary'                  ],
            tinNumber                   : $employee['tin_number'                    ],
            sssNumber                   : $employee['sss_number'                    ],
            philhealthNumber            : $employee['philhealth_number'             ],
            pagibigFundNumber           : $employee['pagibig_number'                ],
            bankName                    : $employee['bank_name'                     ],
            bankBranchName              : $employee['bank_branch_name'              ],
            bankAccountNumber           : $employee['bank_account_number'           ],
            bankAccountType             : $employee['bank_account_type'             ],
            username                    : $employee['username'                      ],
            password                    : $employee['password'                      ],
            notes                       : null
        );

        $updateEmployeeResult = $this->employeeRepository->updateEmployee($newEmployee);

        if ($updateEmployeeResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while updating the employee. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Employee updated successfully.'
        ];
    }

    public function changePassword(mixed $employeeId, mixed $password): array
    {
        $this->employeeValidator->setGroup('change_password');

        $this->employeeValidator->setData([
            'id'       => $employeeId,
            'password' => $password
        ]);

        $this->employeeValidator->validate([
            'id'      ,
            'password'
        ]);

        $validationErrors = $this->employeeValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        if (filter_var($employeeId, FILTER_VALIDATE_INT) !== false) {
            $employeeId = (int) $employeeId;
        }

        $changePasswordResult = $this->employeeRepository->changePassword($employeeId, $password);

        if ($changePasswordResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while changing the password of an employee. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Changed password succesfully.'
        ];
    }

    public function countTotalRecords(): int|ActionResult
    {
        return $this->employeeRepository->countTotalRecords();
    }

    public function deleteEmployee(mixed $employeeId): array
    {
        $this->employeeValidator->setGroup('delete');

        $this->employeeValidator->setData([
            'id' => $employeeId
        ]);

        $this->employeeValidator->validate([
            'id'
        ]);

        $validationErrors = $this->employeeValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        if (filter_var($employeeId, FILTER_VALIDATE_INT) !== false) {
            $employeeId = (int) $employeeId;
        }

        $deleteEmployeeResult = $this->employeeRepository->deleteEmployee($employeeId);

        if ($deleteEmployeeResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while deleting the employee. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Employee deleted successfully.'
        ];
    }

    private function generateEmployeeCode(): string
    {
        return 'EMP-' . str_pad($this->employeeRepository->countTotalRecords() + 1, 4, '0', STR_PAD_LEFT);
    }
}
