<?php

require_once __DIR__ . '/LeaveRequestDao.php';

class LeaveRequestRepository
{
    private readonly LeaveRequestDao $leaveRequestDao;

    public function __construct(LeaveRequestDao $leaveRequestDao)
    {
        $this->leaveRequestDao = $leaveRequestDao;
    }

    public function createLeaveRequest(LeaveRequest $leaveRequest): ActionResult
    {
        return $this->leaveRequestDao->create($leaveRequest);
    }

    public function fetchAllLeaveRequests(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): array|ActionResult {

        return $this->leaveRequestDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function updateLeaveRequest(LeaveRequest $leaveRequest): ActionResult
    {
        return $this->leaveRequestDao->update($leaveRequest);
    }

    public function updateLeaveRequestStatus(int|string $leaveRequestId, string $status): ActionResult
    {
        return $this->leaveRequestDao->updateStatus($leaveRequestId, $status);
    }

    public function updateLeaveRequestStatuses(string $currentDate): ActionResult
    {
        return $this->leaveRequestDao->updateLeaveRequestStatuses($currentDate);
    }

    public function isEmployeeOnLeave(int|string $employeeId): array|null|ActionResult
    {
        return $this->leaveRequestDao->isEmployeeOnLeave($employeeId);
    }

    public function getLeaveDatesForPeriod(int|string $employeeId, string $startDate, string $endDate): array|ActionResult
    {
        $columns = [
            'leave_type_is_paid',
            'start_date'        ,
            'end_date'          ,
            'is_half_day'       ,
            'status'
        ];

        $filterCriteria = [
            [
                'column'   => 'leave_request.employee_id',
                'operator' => '='                        ,
                'value'    => $employeeId
            ],
            [
                'column'   => 'leave_request.start_date',
                'operator' => '<='                      ,
                'value'    => $endDate
            ],
            [
                'column'   => 'leave_request.end_date',
                'operator' => '>='                    ,
                'value'    => $startDate
            ]
        ];

        $leaveRequests = $this->leaveRequestDao->fetchAll($columns, $filterCriteria);

        if ($leaveRequests === ActionResult::FAILURE) {
            return ActionResult::FAILURE;
        }

        $leaveRequests = $leaveRequests['result_set'];

        $approvedLeaveRequests = [];
        foreach ($leaveRequests as $leaveRequest) {
            if (in_array($leaveRequest['status'], ['In Progress', 'Completed', 'Approved'])) {
                $approvedLeaveRequests[] = $leaveRequest;
            }
        }

        $leaveRequests = $approvedLeaveRequests;

        $datesMarkedAsLeave = [];

        $startDate  = new DateTime  ($startDate);
        $endDate    = new DateTime  ($endDate  );
        $datePeriod = new DatePeriod($startDate, new DateInterval('P1D'), $endDate->modify('+1 day'));

        foreach ($datePeriod as $date) {
            $datesMarkedAsLeave[$date->format('Y-m-d')] = [
                'is_leave'    => false,
                'is_paid'     => false,
                'is_half_day' => false
            ];
        }

        foreach ($leaveRequests as $leaveRequest) {
            $leaveStartDate  = new DateTime  ($leaveRequest['start_date']);
            $leaveEndDate    = new DateTime  ($leaveRequest['end_date'  ]);
            $leaveDatePeriod = new DatePeriod($leaveStartDate, new DateInterval('P1D'), $leaveEndDate->modify('+1 day'));

            $isPaid    = $leaveRequest['leave_type_is_paid'];
            $isHalfDay = $leaveRequest['is_half_day'       ];

            foreach ($leaveDatePeriod as $leaveDate) {
                if (isset($datesMarkedAsLeave[$leaveDate->format('Y-m-d')])) {
                    $datesMarkedAsLeave[$leaveDate->format('Y-m-d')] = [
                        'is_leave'    => true                   ,
                        'is_paid'     => $isPaid                ,
                        'is_half_day' => $isHalfDay             ,
                        'status'      => $leaveRequest['status']
                    ];
                }
            }
        }

        return $datesMarkedAsLeave;
    }

    public function deleteLeaveRequest(int|string $leaveRequestId): ActionResult
    {
        return $this->leaveRequestDao->delete($leaveRequestId);
    }
}
