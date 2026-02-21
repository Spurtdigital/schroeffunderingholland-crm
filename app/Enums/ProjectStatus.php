<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProjectStatus: string implements HasLabel, HasColor
{
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match($this) {
            self::Active => 'Actief',
            self::OnHold => 'In de wacht',
            self::Completed => 'Afgerond',
            self::Cancelled => 'Geannuleerd',
        };
    }

    public function getColor(): string | array | null
    {
        return match($this) {
            self::Active => 'success',
            self::OnHold => 'warning',
            self::Completed => 'info',
            self::Cancelled => 'danger',
        };
    }
}
