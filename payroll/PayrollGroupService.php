<?php

require_once __DIR__ . '/PayrollGroupRepository.php';

require_once __DIR__ . '/PayrollGroupValidator.php' ;

class PayrollGroupService
{
    private readonly PayrollGroupRepository $payrollGroupRepository;

    private readonly PayrollGroupValidator $payrollGroupValidator;

    public function __construct(PayrollGroupRepository $payrollGroupRepository)
    {
        $this->payrollGroupRepository = $payrollGroupRepository;

        $this->payrollGroupValidator = new PayrollGroupValidator($payrollGroupRepository);
    }

    public function createPayrollGroup(array $payrollGroup): array
    {
        $this->payrollGroupValidator->setGroup('create');

        $this->payrollGroupValidator->setData($payrollGroup);

        $this->payrollGroupValidator->validate([
            'name'                      ,
            'payroll_frequency'         ,
            'day_of_weekly_cutoff'      ,
            'day_of_biweekly_cutoff'    ,
            'semi_monthly_first_cutoff' ,
            'semi_monthly_second_cutoff',
            'payday_offset'             ,
            'payday_adjustment'         ,
            'status'
        ]);

        $validationErrors = $this->payrollGroupValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $dayOfWeeklyCutoff       = $payrollGroup['day_of_weekly_cutoff'      ];
        $dayOfBiweeklyCutoff     = $payrollGroup['day_of_biweekly_cutoff'    ];
        $semiMonthlyFirstCutoff  = $payrollGroup['semi_monthly_first_cutoff' ];
        $semiMonthlySecondCutoff = $payrollGroup['semi_monthly_second_cutoff'];
        $paydayOffset            = $payrollGroup['payday_offset'             ];

        $dayOfWeeklyCutoff       = $dayOfWeeklyCutoff       === '' ? null : filter_var($dayOfWeeklyCutoff      , FILTER_VALIDATE_INT);
        $dayOfBiweeklyCutoff     = $dayOfBiweeklyCutoff     === '' ? null : filter_var($dayOfBiweeklyCutoff    , FILTER_VALIDATE_INT);
        $semiMonthlyFirstCutoff  = $semiMonthlyFirstCutoff  === '' ? null : filter_var($semiMonthlyFirstCutoff , FILTER_VALIDATE_INT);
        $semiMonthlySecondCutoff = $semiMonthlySecondCutoff === '' ? null : filter_var($semiMonthlySecondCutoff, FILTER_VALIDATE_INT);
        $paydayOffset            = $paydayOffset            === '' ? null : filter_var($paydayOffset           , FILTER_VALIDATE_INT);

        $newPayrollGroup = new PayrollGroup(
            id                     : null                              ,
            name                   : $payrollGroup['name'             ],
            payrollFrequency       : $payrollGroup['payroll_frequency'],
            dayOfWeeklyCutoff      : $dayOfWeeklyCutoff                ,
            dayOfBiweeklyCutoff    : $dayOfBiweeklyCutoff              ,
            semiMonthlyFirstCutoff : $semiMonthlyFirstCutoff           ,
            semiMonthlySecondCutoff: $semiMonthlySecondCutoff          ,
            paydayOffset           : $paydayOffset                     ,
            paydayAdjustment       : $payrollGroup['payday_adjustment' ],
            status                 : $payrollGroup['status'            ]
        );

        $createPayrollGroupResult = $this->payrollGroupRepository->createPayrollGroup($newPayrollGroup);

        if ($createPayrollGroupResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while creating the payroll group. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Payroll group created successfully.'
        ];
    }

    public function fetchAllPayrollGroups(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->payrollGroupRepository->fetchAllPayrollGroups(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updatePayrollGroup(array $payrollGroup): array
    {
        $this->payrollGroupValidator->setGroup('update');

        $this->payrollGroupValidator->setData($payrollGroup);

        $this->payrollGroupValidator->validate([
            'id'                        ,
            'name'                      ,
            'payroll_frequency'         ,
            'day_of_weekly_cutoff'      ,
            'day_of_biweekly_cutoff'    ,
            'semi_monthly_first_cutoff' ,
            'semi_monthly_second_cutoff',
            'payday_offset'             ,
            'payday_adjustment'         ,
            'status'
        ]);

        $validationErrors = $this->payrollGroupValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        $payrollGroupId = $payrollGroup['id'];

        if (filter_var($payrollGroupId, FILTER_VALIDATE_INT) !== false) {
            $payrollGroupId = (int) $payrollGroupId;
        }

        $dayOfWeeklyCutoff       = $payrollGroup['day_of_weekly_cutoff'      ];
        $dayOfBiweeklyCutoff     = $payrollGroup['day_of_biweekly_cutoff'    ];
        $semiMonthlyFirstCutoff  = $payrollGroup['semi_monthly_first_cutoff' ];
        $semiMonthlySecondCutoff = $payrollGroup['semi_monthly_second_cutoff'];
        $paydayOffset            = $payrollGroup['payday_offset'             ];

        $dayOfWeeklyCutoff       = $dayOfWeeklyCutoff       === '' ? null : filter_var($dayOfWeeklyCutoff      , FILTER_VALIDATE_INT);
        $dayOfBiweeklyCutoff     = $dayOfBiweeklyCutoff     === '' ? null : filter_var($dayOfBiweeklyCutoff    , FILTER_VALIDATE_INT);
        $semiMonthlyFirstCutoff  = $semiMonthlyFirstCutoff  === '' ? null : filter_var($semiMonthlyFirstCutoff , FILTER_VALIDATE_INT);
        $semiMonthlySecondCutoff = $semiMonthlySecondCutoff === '' ? null : filter_var($semiMonthlySecondCutoff, FILTER_VALIDATE_INT);
        $paydayOffset            = $paydayOffset            === '' ? null : filter_var($paydayOffset           , FILTER_VALIDATE_INT);

        $updatedPayrollGroup = new PayrollGroup(
            id                     : $payrollGroupId                   ,
            name                   : $payrollGroup['name'             ],
            payrollFrequency       : $payrollGroup['payroll_frequency'],
            dayOfWeeklyCutoff      : $dayOfWeeklyCutoff                ,
            dayOfBiweeklyCutoff    : $dayOfBiweeklyCutoff              ,
            semiMonthlyFirstCutoff : $semiMonthlyFirstCutoff           ,
            semiMonthlySecondCutoff: $semiMonthlySecondCutoff          ,
            paydayOffset           : $paydayOffset                     ,
            paydayAdjustment       : $payrollGroup['payday_adjustment' ],
            status                 : $payrollGroup['status'            ]
        );

        $updatePayrollGroupResult = $this->payrollGroupRepository->updatePayrollGroup($updatedPayrollGroup);

        if ($updatePayrollGroupResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while updating the payroll group. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Payroll group updated successfully.'
        ];
    }

    public function deletePayrollGroup(mixed $payrollGroupId): array
    {
        $this->payrollGroupValidator->setGroup('delete');

        $this->payrollGroupValidator->setData([
            'id' => $payrollGroupId
        ]);

        $this->payrollGroupValidator->validate([
            'id'
        ]);

        $validationErrors = $this->payrollGroupValidator->getErrors();

        if ( ! empty($validationErrors)) {
            return [
                'status'  => 'invalid_input',
                'message' => 'There are validation errors. Please check the input values.',
                'errors'  => $validationErrors
            ];
        }

        if (filter_var($payrollGroupId, FILTER_VALIDATE_INT) !== false) {
            $payrollGroupId = (int) $payrollGroupId;
        }

        $deletePayrollGroupResult = $this->payrollGroupRepository->deletePayrollGroup($payrollGroupId);

        if ($deletePayrollGroupResult === ActionResult::FAILURE) {
            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while deleting the payroll group. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Payroll group deleted successfully.'
        ];
    }
}
