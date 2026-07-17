<?php

namespace App\Enums;

enum TravelOrderStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Cancelled = 'cancelled';
}
