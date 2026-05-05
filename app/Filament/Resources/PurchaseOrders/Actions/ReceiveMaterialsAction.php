<?php

namespace App\Filament\Resources\PurchaseOrders\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

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
                    Placeholder::make('description')
                        ->label('Artikel')
                        ->content(fn ($state) => new HtmlString('<strong>'.e($state).'</strong>'))
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

                    // Hidden field to carry the item id
                    TextInput::make('id')
                        ->hidden()
                        ->dehydrated(),
                ])
                ->columns(3)
                ->addable(false)
                ->deletable(false)
                ->reorderable(false),
        ]);

        $this->action(function (array $data, PurchaseOrder $record): void {
            // Laad huidige received_quantity per item vóór de update (voor delta-berekening)
            $oldQuantities = $record->items->keyBy('id')->map(fn ($i) => (float) $i->received_quantity);

            foreach ($data['items'] as $row) {
                $record->items()->where('id', $row['id'])->update([
                    'received_quantity' => (float) $row['received_quantity'],
                ]);
            }

            // Laad items inclusief product in één query (N+1 preventie)
            $record->load('items.product');

            // Pas de voorraad aan met de delta (positief = toevoegen, negatief = verlagen)
            foreach ($record->items as $item) {
                if (! $item->product_id || ! $item->product) {
                    continue;
                }

                $delta = (float) $item->received_quantity - ($oldQuantities[$item->id] ?? 0.0);

                if ($delta !== 0.0) {
                    $item->product->addStock(
                        (int) round($delta),
                        "Inkoop: {$record->order_number}",
                        null,
                        $record
                    );
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

            Notification::make()
                ->success()
                ->title('Ontvangst ingeboekt')
                ->body('De ontvangen aantallen zijn bijgewerkt en de voorraad is aangepast.')
                ->send();
        });
    }
}
