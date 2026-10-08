<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InvoicingType: string implements HasLabel
{
    case Single = 'single';
    case Split5050 = 'split_50_50';
    case Split3070 = 'split_30_70';
    case Split8020 = 'split_80_20';
    case SingleUpfront = 'single_upfront';
    case SingleOnsite = 'single_onsite';

    public function getLabel(): ?string
    {
        return match($this) {
            self::Single => 'Eén totaal factuur',
            self::Split5050 => '50/50 factuur (50% vooraf, 50% bij oplevering)',
            self::Split3070 => '30/70 factuur (30% vooraf, 70% bij oplevering)',
            self::Split8020 => '80/20 factuur (80% vooraf, 20% bij oplevering)',
            self::SingleUpfront => 'Volledig vooraf factuur (100% bij start)',
            self::SingleOnsite => 'Pin/contant bij oplevering',
        };
    }
    
    public function getDescription(): ?string
    {
        return match($this) {
            self::Single => 'Volledige factuur na voltooiing van het project',
            self::Split5050 => 'Eerste factuur: 50% bij start, Tweede factuur: 50% bij oplevering',
            self::Split3070 => 'Eerste factuur: 30% bij start, Tweede factuur: 70% bij oplevering',
            self::Split8020 => 'Eerste factuur: 80% bij start, Tweede factuur: 20% bij oplevering',
            self::SingleUpfront => 'Volledige factuur bij start van het project',
            self::SingleOnsite => 'Betaling per pin of contant bij oplevering',
        };
    }
    
    public function getFirstInvoicePercentage(): int
    {
        return match($this) {
            self::Single => 100,
            self::Split5050 => 50,
            self::Split3070 => 30,
            self::Split8020 => 80,
            self::SingleUpfront => 100,
            self::SingleOnsite => 100,
        };
    }
    
    public function getSecondInvoicePercentage(): ?int
    {
        return match($this) {
            self::Single => null,
            self::Split5050 => 50,
            self::Split3070 => 70,
            self::Split8020 => 20,
            self::SingleUpfront => null,
            self::SingleOnsite => null,
        };
    }
}
