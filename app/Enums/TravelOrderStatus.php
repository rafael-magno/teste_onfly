<?php

namespace App\Enums;

enum TravelOrderStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Solicitado',
            self::Approved => 'Aprovado',
            self::Cancelled => 'Cancelado',
            default => $this->name,
        };
    }
}
