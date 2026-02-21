<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasIcon;

enum QuoteItemType: string implements HasLabel, HasIcon
{
    case Product = 'product';
    case FreeLine = 'free_line';
    case Labor = 'labor';
    case Mileage = 'mileage';

    public function getLabel(): ?string
    {
        return match($this) {
            self::Product => 'Product',
            self::FreeLine => 'Vrije regel',
            self::Labor => 'Arbeid',
            self::Mileage => 'Reiskosten',
        };
    }

    public function getIcon(): ?string
    {
        return match($this) {
            self::Product => 'heroicon-o-cube',
            self::FreeLine => 'heroicon-o-pencil',
            self::Labor => 'heroicon-o-wrench-screwdriver',
            self::Mileage => 'heroicon-o-truck',
        };
    }
}
