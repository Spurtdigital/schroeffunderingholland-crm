<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LeadStatus: string implements HasLabel, HasColor
{
    case New = 'new';
    case Contacted = 'contacted';
    case InfoRequested = 'info_requested';
    case Quoted = 'quoted';
    case Rejected = 'rejected';
    case Converted = 'converted';

    public function getLabel(): ?string
    {
        return match($this) {
            self::New => 'Nieuw',
            self::Contacted => 'Contact opgenomen',
            self::InfoRequested => 'Info opgevraagd',
            self::Quoted => 'Offerte gemaakt',
            self::Rejected => 'Afgewezen',
            self::Converted => 'Geconverteerd',
        };
    }

    public function getColor(): string | array | null
    {
        return match($this) {
            self::New => 'info',
            self::Contacted => 'primary',
            self::InfoRequested => 'warning',
            self::Quoted => 'success',
            self::Rejected => 'danger',
            self::Converted => 'success',
        };
    }
}
