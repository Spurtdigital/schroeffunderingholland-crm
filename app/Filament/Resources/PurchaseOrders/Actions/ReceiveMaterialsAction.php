<?php

namespace App\Filament\Resources\PurchaseOrders\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ReceiveMaterialsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'receiveMaterials';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Materialen inboeken');
        $this->icon('heroicon-o-inbox-arrow-down');
        $this->color('success');
        $this->modalHeading('Materialen inboeken');
        $this->modalDescription('Vul de ontvangen aantallen in. Laat 0 staan als een artikel niet is ontvangen.');
        $this->modalWidth('2xl');

        $this->visible(fn (PurchaseOrder $record) => ! in_array($record->status, [
            PurchaseOrderStatus::Delivered,
            PurchaseOrderStatus::Cancelled,
        ]));

        $this->fillForm(function (PurchaseOrder $record): array {
            return [
                'items' => $record->items->map(fn ($item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'received_quantity' => (float) $item->received_quantity,
                ])->toArray(),
            ];
        });

        $this->form([
            Repeater::make('items')
                ->label('Regels')
                ->schema([
                    TextInput::make('description')
                        ->label('Artikel')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(3),

                    TextInput::make('quantity')
                        ->label('Besteld')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(1),

                    TextInput::make('received_quantity')
                        ->label('Ontvangen')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->required()
                        ->columnSpan(1),

                    Hidden::make('id'),
                ])
                ->columns(3)
                ->addable(false)
                ->deletable(false)
                ->reorderable(false),
        ]);

        $this->action(function (array $data, PurchaseOrder $record): void {
            try {
                $oldQuantities = $record->items->keyBy('id')->map(fn ($i) => (float) $i->received_quantity);

                foreach ($data['items'] as $row) {
                    $itemId = $row['id'] ?? null;

                    if (! $itemId) {
                        Log::warning('ReceiveMaterials: item id ontbreekt in form data', ['row' => $row]);

                        continue;
                    }

                    $record->items()->where('id', $itemId)->update([
                        'received_quantity' => (float) $row['received_quantity'],
                    ]);
                }

                $record->load('items.product');

                foreach ($record->items as $item) {
                    if (! $item->product_id || ! $item->product) {
                        continue;
                    }

                    $delta = (float) $item->received_quantity - ($oldQuantities[$item->id] ?? 0.0);

                    if ($delta !== 0.0) {
                        $roundedDelta = (int) round($delta);

                        if ($roundedDelta === 0) {
                            continue;
                        }

                        $item->product->addStock(
                            $roundedDelta,
                            "Inkoop: {$record->order_number}",
                            null,
                            $record
                        );
                    }

                    if ($delta !== 0.0 && (float) $item->received_quantity !== round((float) $item->received_quantity)) {
                        Log::warning('ReceiveMaterials: ontvangen hoeveelheid is niet geheel, voorraadmutatie is afgerond', [
                            'item_id' => $item->id,
                            'delta' => $delta,
                            'rounded_delta' => (int) round($delta),
                            'received_quantity' => (float) $item->received_quantity,
                        ]);
                    }
                }

                $totalOrdered = $record->items->sum('quantity');
                $totalReceived = $record->items->sum('received_quantity');

                if ($totalReceived <= 0) {
                    $record->update(['status' => PurchaseOrderStatus::Draft]);
                } elseif ($totalReceived >= $totalOrdered) {
                    $record->update(['status' => PurchaseOrderStatus::Delivered]);
                } else {
                    $record->update(['status' => PurchaseOrderStatus::PartiallyReceived]);
                }

                $record->refresh()->recalculateTotals();

                Notification::make()
                    ->success()
                    ->title('Ontvangst ingeboekt')
                    ->body('De ontvangen aantallen zijn bijgewerkt en de voorraad is aangepast.')
                    ->send();
            } catch (\Throwable $e) {
                Log::error('ReceiveMaterials: fout bij inboeken', [
                    'purchase_order_id' => $record->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                Notification::make()
                    ->danger()
                    ->title('Fout bij inboeken')
                    ->body('Er is een fout opgetreden: '.$e->getMessage())
                    ->send();

                throw $e;
            }
        });
    }
}
