<?php

require_once __DIR__ . '/OvertimeRateRepository.php';

require_once __DIR__ . '/OvertimeRateValidator.php' ;

class OvertimeRateService
{
    private readonly PDO $pdo;

    private readonly OvertimeRateRepository $overtimeRateRepository;

    private readonly OvertimeRateValidator $overtimeRateValidator;

    public function __construct(PDO $pdo, OvertimeRateRepository $overtimeRateRepository)
    {
        $this->pdo = $pdo;

        $this->overtimeRateRepository = $overtimeRateRepository;

        $this->overtimeRateValidator = new OvertimeRateValidator();
    }

    public function createOvertimeRate(array $overtimeRates): array
    {
        $this->overtimeRateValidator->setGroup('create');

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

        $this->pdo->beginTransaction();

        try {
            foreach ($overtimeRates as $overtimeRate) {
                $overtimeRateAssignmentId = $overtimeRate['overtime_rate_assignment_id'];

                if (filter_var($overtimeRateAssignmentId, FILTER_VALIDATE_INT) !== false) {
                    $overtimeRateAssignmentId = (int) $overtimeRateAssignmentId;
                }

                $newOvertimeRate = new OvertimeRate(
                    id                              :         null                                                 ,
                    overtimeRateAssignmentId        :         $overtimeRateAssignmentId                            ,
                    dayType                         :         $overtimeRate['day_type'                            ],
                    holidayType                     :         $overtimeRate['holiday_type'                        ],
                    regularTimeRate                 : (float) $overtimeRate['regular_time_rate'                   ],
                    overtimeRate                    : (float) $overtimeRate['overtime_rate'                       ],
                    nightDifferentialRate           : (float) $overtimeRate['night_differential_rate'             ],
                    nightDifferentialAndOvertimeRate: (float) $overtimeRate['night_differential_and_overtime_rate']
                );

                $createOvertimeRateResult = $this->overtimeRateRepository->createOvertimeRate($newOvertimeRate);

                if ($createOvertimeRateResult === ActionResult::FAILURE) {
                    $this->pdo->rollBack();

                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred while creating the overtime rate. Please try again later.'
                    ];
                }
            }

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while creating the overtime rate. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' =>
                count($overtimeRates) > 1
                    ? 'Overtime rates created successfully.'
                    : 'Overtime rate created successfully.'
        ];
    }

    public function fetchOvertimeRates(int $overtimeRateAssignmentId): array|ActionResult
    {
        return $this->overtimeRateRepository->fetchOvertimeRates($overtimeRateAssignmentId);
    }

    public function updateOvertimeRate(array $overtimeRates): array
    {
        $this->overtimeRateValidator->setGroup('update');

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

        $this->pdo->beginTransaction();

        try {
            foreach ($overtimeRates as $overtimeRate) {
                $overtimeRateId           = $overtimeRate['id'                         ];
                $overtimeRateAssignmentId = $overtimeRate['overtime_rate_assignment_id'];

                if (filter_var($overtimeRateId, FILTER_VALIDATE_INT) !== false) {
                    $overtimeRateId = (int) $overtimeRateId;
                }

                if (filter_var($overtimeRateAssignmentId, FILTER_VALIDATE_INT) !== false) {
                    $overtimeRateAssignmentId = (int) $overtimeRateAssignmentId;
                }

                $newOvertimeRate = new OvertimeRate(
                    id                              :         $overtimeRateId                                      ,
                    overtimeRateAssignmentId        :         $overtimeRateAssignmentId                            ,
                    dayType                         :         $overtimeRate['day_type'                            ],
                    holidayType                     :         $overtimeRate['holiday_type'                        ],
                    regularTimeRate                 : (float) $overtimeRate['regular_time_rate'                   ],
                    overtimeRate                    : (float) $overtimeRate['overtime_rate'                       ],
                    nightDifferentialRate           : (float) $overtimeRate['night_differential_rate'             ],
                    nightDifferentialAndOvertimeRate: (float) $overtimeRate['night_differential_and_overtime_rate']
                );

                $updateOvertimeRateResult = $this->overtimeRateRepository->updateOvertimeRate($newOvertimeRate);

                if ($updateOvertimeRateResult === ActionResult::FAILURE) {
                    $this->pdo->rollBack();

                    return [
                        'status'  => 'error',
                        'message' => 'An unexpected error occurred while updating the overtime rate. Please try again later.'
                    ];
                }
            }

        } catch (PDOException $exception) {
            $this->pdo->rollBack();

            return [
                'status'  => 'error',
                'message' => 'An unexpected error occurred while updating the overtime rate. Please try again later.'
            ];
        }

        return [
            'status'  => 'success',
            'message' =>
                count($overtimeRates) > 1
                    ? 'Overtime rates updated successfully.'
                    : 'Overtime rate updated successfully.'
        ];
    }
}
