<?php

require_once __DIR__ . '/LeaveRequestAttachmentDao.php';

class LeaveRequestAttachmentRepository
{
    private LeaveRequestAttachmentDao $leaveRequestAttachmentDao;

    public function __construct(LeaveRequestAttachmentDao $leaveRequestAttachmentDao)
    {
        $this->leaveRequestAttachmentDao = $leaveRequestAttachmentDao;
    }

    public function createLeaveRequestAttachment(LeaveRequestAttachment $leaveRequestAttachment): ActionResult
    {
        return $this->leaveRequestAttachmentDao->create($leaveRequestAttachment);
    }

    public function fetchAllLeaveRequestAttachments(
        ? array $columns              = null,
        ? array $filterCriteria       = null,
        ? array $sortCriteria         = null,
        ? int   $limit                = null,
        ? int   $offset               = null,
          bool  $includeTotalRowCount = true
    ): ActionResult|array {

        return $this->leaveRequestAttachmentDao->fetchAll(
            columns             : $columns             ,
            filterCriteria      : $filterCriteria      ,
            sortCriteria        : $sortCriteria        ,
            limit               : $limit               ,
            offset              : $offset              ,
            includeTotalRowCount: $includeTotalRowCount
        );
    }

    public function deleteLeaveRequestAttachment(int|string $leaveRequestAttachmentId, bool $isHashedId = false): ActionResult
    {
        return $this->leaveRequestAttachmentDao->delete($leaveRequestAttachmentId, $isHashedId);
    }
}
