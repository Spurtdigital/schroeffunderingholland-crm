<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Confirmed = 'confirmed';
    case PartiallyReceived = 'partially_received';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft             => 'Concept',
            self::Sent              => 'Verstuurd',
            self::Confirmed         => 'Bevestigd',
            self::PartiallyReceived => 'Gedeeltelijk ontvangen',
            self::Delivered         => 'Volledig ontvangen',
            self::Cancelled         => 'Geannuleerd',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft             => 'gray',
            self::Sent              => 'warning',
            self::Confirmed         => 'info',
            self::PartiallyReceived => 'warning',
            self::Delivered         => 'success',
            self::Cancelled         => 'danger',
        };
    }
}
