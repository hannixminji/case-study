<?php

enum WorkScheduleEditOption: string
{
    case EDIT_THIS_ONLY     = 'Edit this schedule only'                 ;
    case EDIT_ALL_FUTURE    = 'Edit all future schedules in this series';
    case EDIT_ALL_SCHEDULES = 'All schedules in this series'            ;
}
