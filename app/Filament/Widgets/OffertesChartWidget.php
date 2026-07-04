<?php

namespace App\Filament\Widgets;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OffertesChartWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected static bool $isLazy = false;
    protected int|string|array $columnSpan = ['default' => 'full'];
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $data[] = Quote::whereIn('status', [QuoteStatus::Draft, QuoteStatus::Sent, QuoteStatus::Approved])
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        $dataSent = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $dataSent[] = Quote::where('status', QuoteStatus::Sent)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        $dataAvg = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $avg = Quote::whereIn('status', [QuoteStatus::Approved, QuoteStatus::Converted])
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->avg('total') ?? 0;
            $dataAvg[] = round($avg);
        }

        $avgValue = end($dataAvg);

        return [
            Stat::make('Openstaande offertes', end($data))
                ->description('Concept, verstuurd & goedgekeurd')
                ->descriptionIcon('heroicon-m-document-text')
                ->chart($data)
                ->color('info'),
            Stat::make('Offertes verstuurd', end($dataSent))
                ->description('Verstuurd deze maand')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->chart($dataSent)
                ->color('warning'),
            Stat::make('Gemiddelde offertewaarde', '€ '.number_format($avgValue, 0, ',', '.'))
                ->description('Goedgekeurd & omgezet')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->chart($dataAvg)
                ->color('success'),
        ];
    }
}
