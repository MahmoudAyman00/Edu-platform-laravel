<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case FAWRY_CODE = 'FAWRY_CODE';
    case FAWRY_CARD = 'FAWRY_CARD';
}
