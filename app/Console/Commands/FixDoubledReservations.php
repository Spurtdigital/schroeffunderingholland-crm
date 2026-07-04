<?php

namespace App\Console\Commands;

use App\Enums\QuoteItemType;
use App\Enums\StockMutationType;
use App\Enums\StockReservationStatus;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\StockReservation;
use Illuminate\Console\Command;

class FixDoubledReservations extends Command
{
    protected $signature = 'fix:doubled-reservations {--dry-run : Laat zien wat er gaat gebeuren zonder wijzigingen}';

    protected $description = 'Herstelt voorraadreserveringen die verdubbeld zijn door bug in ConvertToProjectAction';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $affected = [];

        $reservations = StockReservation::whereNotNull('project_id')
            ->whereIn('status', [StockReservationStatus::Active, StockReservationStatus::Consumed])
            ->get();

        foreach ($reservations as $res) {
            $itemTotal = (int) ProjectItem::where('project_id', $res->project_id)
                ->where('itemable_type', Product::class)
                ->where('itemable_id', $res->product_id)
                ->where('type', QuoteItemType::Product)
                ->sum('quantity');

            if ($itemTotal === 0) {
                continue;
            }

            if ((int) $res->quantity === $itemTotal) {
                continue;
            }

            $project = Project::find($res->project_id);
            $product = Product::find($res->product_id);

            $affected[] = [
                'project' => $project?->project_number ?? '#' . $res->project_id,
                'product' => $product?->name ?? '#' . $res->product_id,
                'res_id' => $res->id,
                'was' => $res->quantity,
                'should_be' => $itemTotal,
                'diff' => $res->quantity - $itemTotal,
                'status' => $res->status->value,
            ];

            if ($dryRun) {
                continue;
            }

            if ($res->status === StockReservationStatus::Active) {
                $res->update(['quantity' => $itemTotal]);
                $this->line("  ✅ Res #{$res->id}: {$res->quantity} → {$itemTotal}");
            } elseif ($res->status === StockReservationStatus::Consumed) {
                $overConsumed = $res->quantity - $itemTotal;
                if ($product) {
                    $product->update([
                        'stock_quantity' => $product->stock_quantity + $overConsumed,
                    ]);
                    $product->stockMutations()->create([
                        'user_id' => null,
                        'quantity' => $overConsumed,
                        'quantity_before' => $product->stock_quantity - $overConsumed,
                        'quantity_after' => $product->stock_quantity,
                        'type' => StockMutationType::Correction,
                        'reason' => "Correctie: teveel verbruikt door bug ConvertToProjectAction (res #{$res->id}, {$res->quantity} → {$itemTotal})",
                        'reference_type' => StockReservation::class,
                        'reference_id' => $res->id,
                    ]);
                    $res->update(['quantity' => $itemTotal]);
                    $this->line("  ✅ Res #{$res->id}: stock +{$overConsumed}, qty {$res->quantity} → {$itemTotal}");
                }
            }
        }

        $this->newLine();

        if (empty($affected)) {
            $this->info('Geen verdubbelde reserveringen gevonden.');
            return;
        }

        $this->table(
            ['Project', 'Product', 'Res #', 'Was', 'Moet zijn', 'Verschil', 'Status'],
            array_map(fn ($a) => [$a['project'], $a['product'], $a['res_id'], $a['was'], $a['should_be'], $a['diff'], $a['status']], $affected)
        );

        $totalOverReserved = array_sum(array_column($affected, 'diff'));
        $this->line("Totaal teveel gereserveerd/verbruikt: {$totalOverReserved}");

        if ($dryRun) {
            $this->warn('Dit was een dry-run. Er is niets gewijzigd. Voer uit zonder --dry-run om de correctie door te voeren.');
        } else {
            $this->info('Correctie uitgevoerd.');
        }
    }
}
