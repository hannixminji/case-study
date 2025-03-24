<?php

require_once __DIR__ . '/OvertimeRateAssignmentRepository.php';

require_once __DIR__ . '/OvertimeRateAssignmentValidator.php' ;
require_once __DIR__ . '/OvertimeRateValidator.php'           ;

class OvertimeRateAssignmentService
{
    private readonly OvertimeRateAssignmentRepository $overtimeRateAssignmentRepository;

    private readonly OvertimeRateAssignmentValidator $overtimeRateAssignmentValidator;
    private readonly OvertimeRateValidator           $overtimeRateValidator          ;

    public function __construct(OvertimeRateAssignmentRepository $overtimeRateAssignmentRepository)
    {
        $this->overtimeRateAssignmentRepository = $overtimeRateAssignmentRepository;

        $this->overtimeRateAssignmentValidator = new OvertimeRateAssignmentValidator();
        $this->overtimeRateValidator           = new OvertimeRateValidator          ();
    }

    public function createOvertimeRateAssignment(array $overtimeRateAssignment): array
    {
        $this->overtimeRateAssignmentValidator->setGroup('create');

        $this->overtimeRateAssignmentValidator->setData($overtimeRateAssignment);

        $this->overtimeRateAssignmentValidator->validate([
            'department_id',
            'job_title_id' ,
            'employee_id'
        ]);

        $validationErrors = $this->overtimeRateAssignmentValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $departmentId = $overtimeRateAssignment['department_id'];
        $jobTitleId   = $overtimeRateAssignment['job_title_id' ];
        $employeeId   = $overtimeRateAssignment['employee_id'  ];

        if (filter_var($departmentId, FILTER_VALIDATE_INT) !== false) {
            $departmentId = (int) $departmentId;
        } elseif (is_string($departmentId) && trim($departmentId) === '') {
            $departmentId = null;
        }

        if (filter_var($jobTitleId, FILTER_VALIDATE_INT) !== false) {
            $jobTitleId = (int) $jobTitleId;
        } elseif (is_string($jobTitleId) && trim($jobTitleId) === '') {
            $jobTitleId = null;
        }

        if (filter_var($employeeId, FILTER_VALIDATE_INT) !== false) {
            $employeeId = (int) $employeeId;
        } elseif (is_string($employeeId) && trim($employeeId) === '') {
            $employeeId = null;
        }

        $newOvertimeRateAssignment = new OvertimeRateAssignment(
            id          : null         ,
            departmentId: $departmentId,
            jobTitleId  : $jobTitleId  ,
            employeeId  : $employeeId
        );

        $overtimeRateAssignmentId = $this->overtimeRateAssignmentRepository->createOvertimeRateAssignment($newOvertimeRateAssignment);

        if ($overtimeRateAssignmentId === ActionResult::FAILURE) {
            return [
                'status'                 => 'error',
                'message'                => 'An unexpected error occurred while creating the pay rate assignment. Please try again later.',
                'pay_rate_assignment_id' => null
            ];
        }

        return [
            'status'                 => 'success',
            'message'                => 'Pay rate assignment created successfully.',
            'pay_rate_assignment_id' => $overtimeRateAssignmentId
        ];
    }

    public function assignOvertimeRateAssignment(array $overtimeRateAssignment, array $overtimeRates): array
    {
        $this->overtimeRateAssignmentValidator->setGroup('create');

        $this->overtimeRateAssignmentValidator->setData($overtimeRateAssignment);

        $this->overtimeRateAssignmentValidator->validate([
            'department_id',
            'job_title_id' ,
            'employee_id'
        ]);

        $validationErrors = $this->overtimeRateAssignmentValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        foreach ($overtimeRates as $overtimeRate) {
            $this->overtimeRateValidator->setData($overtimeRate);

            $this->overtimeRateValidator->validate([
                'overtime_rate_assignment_id'         ,
                'day_type'                            ,
                'holiday_type'                        ,
                'regular_time_rate'                   ,
                'overtime_rate'                       ,
                'night_differential_rate'             ,
                'night_differential_and_overtime_rate'
            ]);

            $validationErrors = $this->overtimeRateValidator->getErrors();

            if ( ! empty($validationErrors)) {
                return [
                    'status'  => 'invalid_input',
                    'message' => 'There are validation errors. Please check the input values.',
                    'errors'  => $validationErrors
                ];
            }
        }

        $departmentId = $overtimeRateAssignment['department_id'];
        $jobTitleId   = $overtimeRateAssignment['job_title_id' ];
        $employeeId   = $overtimeRateAssignment['employee_id'  ];

        if (filter_var($departmentId, FILTER_VALIDATE_INT) !== false) {
            $departmentId = (int) $departmentId;
        } elseif (is_string($departmentId) && trim($departmentId) === '') {
            $departmentId = null;
        }

        if (filter_var($jobTitleId, FILTER_VALIDATE_INT) !== false) {
            $jobTitleId = (int) $jobTitleId;
        } elseif (is_string($jobTitleId) && trim($jobTitleId) === '') {
            $jobTitleId = null;
        }

        if (filter_var($employeeId, FILTER_VALIDATE_INT) !== false) {
            $employeeId = (int) $employeeId;
        } elseif (is_string($employeeId) && trim($employeeId) === '') {
            $employeeId = null;
        }

        $newOvertimeRateAssignment = new OvertimeRateAssignment(
            id          : null         ,
            departmentId: $departmentId,
            jobTitleId  : $jobTitleId  ,
            employeeId  : $employeeId
        );

        foreach ($overtimeRates as $key => $overtimeRate) {
            $overtimeRates[$key]['regular_time_rate'                   ] = (float) $overtimeRate['regular_time_rate'                   ];
            $overtimeRates[$key]['overtime_rate'                       ] = (float) $overtimeRate['overtime_rate'                       ];
            $overtimeRates[$key]['night_differential_rate'             ] = (float) $overtimeRate['night_differential_rate'             ];
            $overtimeRates[$key]['night_differential_and_overtime_rate'] = (float) $overtimeRate['night_differential_and_overtime_rate'];
        }

        $overtimeRateAssignmentResult = $this->overtimeRateAssignmentRepository->assignOvertimeRateAssignment($newOvertimeRateAssignment, $overtimeRates);

        if ($overtimeRateAssignmentResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while assigning the pay rates. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Pay rates assignment successfully assigned.'
        ];
    }

    public function findOvertimeRateAssignmentId(OvertimeRateAssignment $overtimeRateAssignment): int|ActionResult
    {
        return $this->overtimeRateAssignmentRepository->findOvertimeRateAssignmentId($overtimeRateAssignment);
    }
}
