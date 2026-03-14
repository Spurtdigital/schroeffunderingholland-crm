<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use Illuminate\Console\Command;

class RecalculatePurchaseOrderTotals extends Command
{
    protected $signature = 'recalculate:purchase-order-totals
                            {order_number? : Specifiek ordernummer (bijv. INK2025-0003)}
                            {--all : Alle inkooporders herberekenen}
                            {--foreign : Alleen buitenlandse orders (language != nl) herberekenen}';

    protected $description = 'Herbereken subtotaal, BTW en totaal van inkooporders op basis van de huidige taalinstelling';

    public function handle(): int
    {
        if ($this->option('all')) {
            $orders = PurchaseOrder::with('items')->get();
            $this->info("Herberekenen van {$orders->count()} inkooporders...");
        } elseif ($this->option('foreign')) {
            $orders = PurchaseOrder::with('items')->where('language', '!=', 'nl')->get();
            $this->info("Herberekenen van {$orders->count()} buitenlandse inkooporders (language != nl)...");
        } elseif ($orderNumber = $this->argument('order_number')) {
            $orders = PurchaseOrder::with('items')->where('order_number', $orderNumber)->get();
            if ($orders->isEmpty()) {
                $this->error("Inkooporder '{$orderNumber}' niet gevonden.");
                return 1;
            }
        } else {
            $this->error('Geef een ordernummer op, of gebruik --all of --foreign.');
            $this->line('Voorbeelden:');
            $this->line('  php artisan recalculate:purchase-order-totals INK2025-0003');
            $this->line('  php artisan recalculate:purchase-order-totals --foreign');
            $this->line('  php artisan recalculate:purchase-order-totals --all');
            return 1;
        }

        $headers = ['Order', 'Taal', 'Oud subtotaal', 'Oude BTW', 'Oud totaal', 'Nieuw subtotaal', 'Nieuwe BTW', 'Nieuw totaal'];
        $rows = [];

        foreach ($orders as $order) {
            $oldSubtotal = $order->subtotal;
            $oldVat      = $order->vat_amount;
            $oldTotal    = $order->total;

            $order->recalculateTotals();
            $order->refresh();

            $rows[] = [
                $order->order_number,
                $order->language,
                '€ '.number_format((float) $oldSubtotal, 2, ',', '.'),
                '€ '.number_format((float) $oldVat, 2, ',', '.'),
                '€ '.number_format((float) $oldTotal, 2, ',', '.'),
                '€ '.number_format((float) $order->subtotal, 2, ',', '.'),
                '€ '.number_format((float) $order->vat_amount, 2, ',', '.'),
                '€ '.number_format((float) $order->total, 2, ',', '.'),
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
        $this->info('Klaar!');

        return 0;
    }
}
