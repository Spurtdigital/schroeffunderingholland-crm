<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasLabel, HasColor
{
    case Draft = 'draft';
    case PreparedInMoneybird = 'prepared_in_moneybird';
    case Sent = 'sent';
    case Paid = 'paid';

    public function getLabel(): ?string
    {
        return match($this) {
            self::Draft => 'Concept',
            self::PreparedInMoneybird => 'Klaargezet in Moneybird',
            self::Sent => 'Verstuurd',
            self::Paid => 'Betaald',
        };
    }

    public function getColor(): string | array | null
    {
        return match($this) {
            self::Draft => 'gray',
            self::PreparedInMoneybird => 'warning',
            self::Sent => 'info',
            self::Paid => 'success',
        };
    }
}
