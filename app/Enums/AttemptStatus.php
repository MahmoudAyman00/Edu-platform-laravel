<?php

namespace App\Enums;

enum AttemptStatus: string
{
    case IN_PROGRESS = 'IN_PROGRESS';
    case SUBMITTED = 'SUBMITTED';
    case EXPIRED = 'EXPIRED';
}
