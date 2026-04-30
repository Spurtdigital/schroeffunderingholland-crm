<?php

namespace App\Filament\Resources\PurchaseOrders\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
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
                    'id'                => $item->id,
                    'description'       => $item->description,
                    'quantity'          => (float) $item->quantity,
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
                        ->content(fn ($state) => new HtmlString('<strong>' . e($state) . '</strong>'))
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
            foreach ($data['items'] as $row) {
                $record->items()->where('id', $row['id'])->update([
                    'received_quantity' => (float) $row['received_quantity'],
                ]);
            }

            $record->refresh();

            $totalOrdered  = $record->items->sum('quantity');
            $totalReceived = $record->items->sum('received_quantity');

            if ($totalReceived >= $totalOrdered) {
                $record->update(['status' => PurchaseOrderStatus::Delivered]);
            } elseif ($totalReceived > 0) {
                $record->update(['status' => PurchaseOrderStatus::PartiallyReceived]);
            }

            Notification::make()
                ->success()
                ->title('Ontvangst ingeboekt')
                ->body('De ontvangen aantallen zijn bijgewerkt.')
                ->send();
        });
    }
}
