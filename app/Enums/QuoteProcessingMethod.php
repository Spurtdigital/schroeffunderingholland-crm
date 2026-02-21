<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum QuoteProcessingMethod: string implements HasLabel
{
    case System = 'system';
    case MoneybirdDraft = 'moneybird_draft';

    public function getLabel(): ?string
    {
        return match($this) {
            self::System => 'Via systeem',
            self::MoneybirdDraft => 'Als concept in Moneybird',
        };
    }
}
