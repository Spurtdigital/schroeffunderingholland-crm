<?php

namespace App\Filament\Pages;

use App\Enums\InvoiceStatus;
use App\Enums\LeadStatus;
use App\Enums\ProjectStatus;
use App\Enums\QuoteStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quote;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Dashboard';
    protected static ?int $navigationSort = -1;

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\OffertesChartWidget::class,
            DashboardStatsWidget::class,
            \App\Filament\Widgets\UpcomingProjectsWidget::class,
        ];
    }
}

class DashboardStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = ['default' => 'full'];

    protected function getStats(): array
    {
        $totalLeads        = Lead::count();
        $newLeadsMonth     = Lead::whereMonth('created_at', now()->month)->count();
        $convertedLeads    = Lead::where('status', LeadStatus::Converted)->count();
        $conversionRate    = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;
        $sentQuotes        = Quote::where('status', QuoteStatus::Sent)->count();
        $totalQuotes       = Quote::count();
        $quoteValue        = Quote::whereIn('status', [QuoteStatus::Approved, QuoteStatus::Converted])->sum('total');
        $activeProjects    = Project::where('status', ProjectStatus::Active)->count();
        $completedProjects = Project::where('status', ProjectStatus::Completed)->count();
        $projectValue      = Project::sum('project_value');
        $paidInvoices      = Invoice::where('status', InvoiceStatus::Paid)->count();
        $invoiceValue      = Invoice::sum('total');
        $totalProducts     = Product::where('is_active', true)->count();
        $totalCompanies    = Company::count();

        return [
            Stat::make('Leads', $totalLeads)
                ->description($newLeadsMonth.' nieuw deze maand')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Conversie', $conversionRate.'%')
                ->description($convertedLeads.' van '.$totalLeads.' omgezet')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            Stat::make('Offertes', $totalQuotes)
                ->description('Verstuurd: '.$sentQuotes)
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Offerte waarde', '€ '.number_format($quoteValue, 0, ',', '.'))
                ->description('Goedgekeurd & omgezet')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('success'),

            Stat::make('Actieve projecten', $activeProjects)
                ->description($completedProjects.' afgerond')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('primary'),

            Stat::make('Project waarde', '€ '.number_format($projectValue, 0, ',', '.'))
                ->description('Totaal alle projecten')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Facturen betaald', $paidInvoices)
                ->description('€ '.number_format($invoiceValue, 0, ',', '.').' totaal')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('info'),

            Stat::make('Klanten', $totalCompanies)
                ->description($totalProducts.' actieve producten')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('gray'),
        ];
    }
}
