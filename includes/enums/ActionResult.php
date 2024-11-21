<?php

enum ActionResult: int
{
    case SUCCESS               = 0;
    case FAILURE               = 1;
    case DUPLICATE_ENTRY_ERROR = 2;
    case PASSWORD_INCORRECT    = 3;
    case NO_SCHEDULED_BREAK    = 4;
}
