<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'PENDING';
    case PAID = 'PAID';
    case EXPIRED = 'EXPIRED';
    case FAILED = 'FAILED';
}
