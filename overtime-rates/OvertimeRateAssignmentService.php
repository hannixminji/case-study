<?php

require_once __DIR__ . '/OvertimeRateAssignmentRepository.php';

class OvertimeRateAssignmentService
{
    private readonly OvertimeRateAssignmentRepository $overtimeRateAssignmentRepository;
    private readonly OvertimeRateRepository           $overtimeRateRepository          ;

    public function __construct(
        OvertimeRateAssignmentRepository $overtimeRateAssignmentRepository,
        OvertimeRateRepository           $overtimeRateRepository
    ) {
        $this->overtimeRateAssignmentRepository = $overtimeRateAssignmentRepository;
        $this->overtimeRateRepository           = $overtimeRateRepository          ;
    }

    public function createOvertimeRateAssignment(OvertimeRateAssignment $overtimeRateAssignment): array
    {
        $result = $this->overtimeRateAssignmentRepository->create($overtimeRateAssignment);

        if ($result === ActionResult::SUCCESS) {
            return [
                'status'  => 'success',
                'message' => 'Overtime rate assignment created successfully.'
            ];
        }

        return [
            'status'  => 'error',
            'message' => 'Failed to create overtime rate assignment.'
        ];
    }

    public function findAssignmentId(int $employee_id, int $job_title_id, int $department_id): array
    {
        $result = $this->overtimeRateAssignmentRepository->findAssignmentId($employee_id, $job_title_id, $department_id);

        if ($result === ActionResult::SUCCESS) {
            return [
                'status'  => 'success',
                'message' => 'Assignment ID found successfully.',
                'data'    => $result
            ];
        }

        return [
            'status'  => 'error',
            'message' => 'Failed to find the assignment ID.'
        ];
    }
}
