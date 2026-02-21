<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StockReservationStatus: string implements HasLabel, HasColor
{
    case Active = 'active';
    case Released = 'released';
    case Consumed = 'consumed';

    public function getLabel(): ?string
    {
        return match($this) {
            self::Active => 'Actief',
            self::Released => 'Vrijgegeven',
            self::Consumed => 'Verbruikt',
        };
    }

    public function getColor(): string | array | null
    {
        return match($this) {
            self::Active => 'warning',
            self::Released => 'gray',
            self::Consumed => 'success',
        };
    }
}
