<?php

namespace App\Filament\Resources\Products\Actions;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;

class CreatePurchaseOrderAction extends Action
{
    public static function make(?string $name = 'createPurchaseOrder'): static
    {
        return parent::make($name)
            ->label('Inkooporder')
            ->icon('heroicon-o-shopping-cart')
            ->color('warning')
            ->form([
                Select::make('supplier_id')
                    ->label('Leverancier')
                    ->options(fn (Product $record) => Supplier::where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->default(fn (Product $record) => $record->supplier_id)
                    ->searchable()
                    ->required(),

                Select::make('language')
                    ->label('Taal PDF')
                    ->options([
                        'nl' => 'Nederlands',
                        'en' => 'Engels',
                        'de' => 'Duits',
                    ])
                    ->default(fn (Product $record) => $record->supplier?->language ?? 'nl')
                    ->required(),

                DatePicker::make('order_date')
                    ->label('Besteldatum')
                    ->required()
                    ->default(now())
                    ->native(false)
                    ->displayFormat('d-m-Y'),

                DatePicker::make('expected_delivery_date')
                    ->label('Verwachte leverdatum')
                    ->native(false)
                    ->displayFormat('d-m-Y'),

                TextInput::make('quantity')
                    ->label('Aantal')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(1)
                    ->step(1),

                TextInput::make('unit_price')
                    ->label('Inkoopprijs p/st')
                    ->numeric()
                    ->prefix('€')
                    ->default(fn (Product $record) => (float) $record->purchase_price)
                    ->required()
                    ->minValue(0)
                    ->step(0.01),
            ])
            ->action(function (Product $record, array $data) {
                $order = PurchaseOrder::create([
                    'supplier_id'             => $data['supplier_id'],
                    'language'                => $data['language'],
                    'status'                  => 'draft',
                    'order_date'              => $data['order_date'],
                    'expected_delivery_date'  => $data['expected_delivery_date'] ?? null,
                    'product_id'              => $record->id,
                ]);

                $order->items()->create([
                    'product_id'              => $record->id,
                    'description'             => $record->name,
                    'supplier_article_number' => $record->supplier_article_number,
                    'quantity'                => $data['quantity'],
                    'unit_price'              => $data['unit_price'],
                    'sort_order'              => 1,
                ]);

                $order->recalculateTotals();

                Notification::make()
                    ->success()
                    ->title('Inkooporder aangemaakt')
                    ->body("Order {$order->order_number} is aangemaakt als concept.")
                    ->send();
            });
    }
}
