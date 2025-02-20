<?php

enum ActionResult: int
{
    case SUCCESS                              = 0;
    case FAILURE                              = 1;
    case NO_RECORD_FOUND                      = 2;
}
