<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasIcon;

enum QuoteItemType: string implements HasLabel, HasIcon, HasColor
{
    case Product = 'product';
    case FreeLine = 'free_line';
    case Labor = 'labor';
    case Mileage = 'mileage';
    case Discount = 'discount';
    case Machine = 'machine';
    case Adjustment = 'adjustment';

    public function getLabel(): ?string
    {
        return match($this) {
            self::Product    => 'Product',
            self::FreeLine   => 'Vrije regel',
            self::Labor      => 'Arbeid',
            self::Mileage    => 'Reiskosten',
            self::Discount   => 'Korting',
            self::Machine    => 'Machine',
            self::Adjustment => 'Prijsaanpassing',
        };
    }

    public function getIcon(): ?string
    {
        return match($this) {
            self::Product    => 'heroicon-o-cube',
            self::FreeLine   => 'heroicon-o-pencil',
            self::Labor      => 'heroicon-o-wrench-screwdriver',
            self::Mileage    => 'heroicon-o-truck',
            self::Discount   => 'heroicon-o-tag',
            self::Machine    => 'heroicon-o-cog-6-tooth',
            self::Adjustment => 'heroicon-o-arrows-up-down',
        };
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::Discount   => 'danger',
            self::Machine    => 'warning',
            self::Adjustment => 'gray',
            default          => null,
        };
    }
}
