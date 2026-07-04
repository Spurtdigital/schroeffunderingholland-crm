<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StockMutationType: string implements HasColor, HasIcon, HasLabel
{
    case Purchase = 'purchase';
    case Credit = 'credit';
    case Correction = 'correction';
    case Consumed = 'consumed';
    case Released = 'released';
    case Reserved = 'reserved';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Purchase => 'Inkoop',
            self::Credit => 'Credit',
            self::Correction => 'Correctie',
            self::Consumed => 'Verbruikt',
            self::Released => 'Vrijgegeven',
            self::Reserved => 'Gereserveerd',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Purchase => 'success',
            self::Credit => 'danger',
            self::Correction => 'info',
            self::Consumed => 'danger',
            self::Released => 'gray',
            self::Reserved => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Purchase => 'heroicon-o-arrow-down-tray',
            self::Credit => 'heroicon-o-arrow-up-tray',
            self::Correction => 'heroicon-o-pencil-square',
            self::Consumed => 'heroicon-o-minus-circle',
            self::Released => 'heroicon-o-arrow-uturn-left',
            self::Reserved => 'heroicon-o-lock-closed',
        };
    }
}
