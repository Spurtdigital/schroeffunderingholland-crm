<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum QuoteStatus: string implements HasLabel, HasColor
{
    case Draft = 'draft';
    case Sent = 'sent';
    case MoneybirdPrepared = 'moneybird_prepared';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Converted = 'converted';

    public function getLabel(): ?string
    {
        return match($this) {
            self::Draft => 'Concept',
            self::Sent => 'Verstuurd',
            self::MoneybirdPrepared => 'Klaargezet in Moneybird',
            self::Approved => 'Goedgekeurd',
            self::Rejected => 'Afgewezen',
            self::Converted => 'Omgezet naar project',
        };
    }

    public function getColor(): string | array | null
    {
        return match($this) {
            self::Draft => 'gray',
            self::Sent => 'info',
            self::MoneybirdPrepared => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Converted => 'success',
        };
    }
}
