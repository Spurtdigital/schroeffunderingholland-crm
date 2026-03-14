<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Confirmed = 'confirmed';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Concept',
            self::Sent      => 'Verstuurd',
            self::Confirmed => 'Bevestigd',
            self::Delivered => 'Geleverd',
            self::Cancelled => 'Geannuleerd',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft     => 'gray',
            self::Sent      => 'warning',
            self::Confirmed => 'info',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
        };
    }
}
