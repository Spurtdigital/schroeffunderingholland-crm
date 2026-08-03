<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InvoiceType: string implements HasLabel
{
    case Single = 'single';
    case Deposit = 'deposit';
    case Final = 'final';
    case Cancellation = 'cancellation';

    public function getLabel(): ?string
    {
        return match($this) {
            self::Single => 'Totaal factuur',
            self::Deposit => 'Aanbetaling',
            self::Final => 'Eindfactuur',
            self::Cancellation => 'Annuleringskosten',
        };
    }
}
