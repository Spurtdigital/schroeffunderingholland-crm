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
            DashboardStatsWidget::class,
            UpcomingProjectsWidget::class,
        ];
    }
}

class DashboardStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        // Lead statistics
        $totalLeads = Lead::count();
        $newLeadsThisMonth = Lead::whereMonth('created_at', now()->month)->count();
        $convertedLeads = Lead::where('status', LeadStatus::Converted)->count();
        $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;

        // Quote statistics
        $totalQuotes = Quote::count();
        $draftQuotes = Quote::where('status', QuoteStatus::Draft)->count();
        $sentQuotes = Quote::where('status', QuoteStatus::Sent)->count();
        $approvedQuotes = Quote::where('status', QuoteStatus::Approved)->count();
        $totalQuoteValue = Quote::whereIn('status', [QuoteStatus::Approved, QuoteStatus::Converted])
            ->sum('total');

        // Project statistics
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', ProjectStatus::Active)->count();
        $completedProjects = Project::where('status', ProjectStatus::Completed)->count();
        $totalProjectValue = Project::sum('project_value');

        // Invoice statistics
        $totalInvoices = Invoice::count();
        $sentInvoices = Invoice::where('status', InvoiceStatus::Sent)->count();
        $paidInvoices = Invoice::where('status', InvoiceStatus::Paid)->count();
        $totalInvoiceValue = Invoice::sum('amount');

        // Product statistics
        $totalProducts = Product::where('is_active', true)->count();
        $lowStockProducts = Product::whereRaw('stock_quantity <= low_stock_threshold')
            ->where('is_active', true)
            ->count();

        // Company statistics
        $totalCompanies = Company::count();

        return [
            // Lead stats
            Stat::make('Leads', $totalLeads)
                ->description($newLeadsThisMonth.' nieuwe deze maand')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($this->getLeadChart()),

            Stat::make('Conversie Ratio', $conversionRate.'%')
                ->description($convertedLeads.' van '.$totalLeads.' leads omgezet')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            // Quote stats
            Stat::make('Offertes', $totalQuotes)
                ->description('Concept: '.$draftQuotes.' | Verstuurd: '.$sentQuotes)
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Offerte Waarde', '€ '.number_format($totalQuoteValue, 2, ',', '.'))
                ->description('Goedgekeurde offertes')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('success'),

            // Project stats
            Stat::make('Projecten', $totalProjects)
                ->description('Actief: '.$activeProjects.' | Afgerond: '.$completedProjects)
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('primary')
                ->chart($this->getProjectChart()),

            Stat::make('Project Waarde', '€ '.number_format($totalProjectValue, 2, ',', '.'))
                ->description('Totale waarde alle projecten')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            // Invoice stats
            Stat::make('Facturen', $totalInvoices)
                ->description('Verstuurd: '.$sentInvoices.' | Betaald: '.$paidInvoices)
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('info'),

            Stat::make('Factuur Waarde', '€ '.number_format($totalInvoiceValue, 2, ',', '.'))
                ->description('Totale waarde alle facturen')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('success'),

            // Product stats
            Stat::make('Producten', $totalProducts)
                ->description($lowStockProducts > 0 ? $lowStockProducts.' producten bijna op' : 'Voorraad OK')
                ->descriptionIcon($lowStockProducts > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($lowStockProducts > 0 ? 'danger' : 'success'),

            // Company stats
            Stat::make('Klanten', $totalCompanies)
                ->description('Totaal aantal klanten')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('gray'),
        ];
    }

    /**
     * Get chart data for leads over the last 7 days
     */
    private function getLeadChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = Lead::whereDate('created_at', $date)->count();
        }

        return $data;
    }

    /**
     * Get chart data for projects over the last 7 days
     */
    private function getProjectChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = Project::whereDate('created_at', $date)->count();
        }

        return $data;
    }
}
