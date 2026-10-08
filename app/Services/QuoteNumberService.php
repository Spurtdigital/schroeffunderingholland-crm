<?php

namespace App\Services;

use App\Models\Quote;
use App\Settings\GeneralSettings;

class QuoteNumberService
{
    public function __construct(private GeneralSettings $settings) {}

    /**
     * Het eerstvolgende lokale offertenummer volgens het formaat "YYYY-NNNN",
     * zonder rekening te houden met een handmatig ingesteld volgnummer.
     * Wordt gebruikt als placeholder in de instellingen.
     */
    public function peekNextLocalNumber(): string
    {
        $year = now()->year;
        $start = max(1, (int) ($this->settings->quote_number_start ?? 1));

        $last = Quote::whereYear('created_at', $year)
            ->where('quote_number', 'like', $year.'-%')
            ->orderByDesc('quote_number')
            ->value('quote_number');

        if ($last) {
            $current = (int) substr($last, strpos($last, '-') + 1);
            $next = max($start, $current + 1);
        } else {
            $next = $start;
        }

        return $year.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Genereer het volgende lokale offertenummer. Een handmatig ingesteld
     * "volgend nummer" wordt exact één keer gebruikt en daarna gewist, zodat
     * de nummering daarna automatisch doorloopt.
     */
    public function generateLocalNumber(): string
    {
        $override = trim((string) ($this->settings->next_quote_number ?? ''));

        if ($override !== '') {
            $this->settings->next_quote_number = null;
            $this->settings->save();

            return $override;
        }

        return $this->peekNextLocalNumber();
    }
}
