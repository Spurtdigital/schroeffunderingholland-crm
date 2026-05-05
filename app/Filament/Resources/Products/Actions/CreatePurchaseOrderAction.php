<?php

namespace App\Filament\Resources\Products\Actions;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;

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
                    ->options(function (Product $record) {
                        // Show product's suppliers first, then all active suppliers
                        $productSuppliers = $record->suppliers()->pluck('suppliers.name', 'suppliers.id');

                        if ($productSuppliers->isEmpty()) {
                            return Supplier::where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        }

                        $otherSuppliers = Supplier::where('is_active', true)
                            ->whereNotIn('id', $productSuppliers->keys())
                            ->orderBy('name')
                            ->pluck('name', 'id');

                        return $productSuppliers->union($otherSuppliers);
                    })
                    ->default(fn (Product $record) => $record->preferredSupplier()?->id)
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Product $record) {
                        if ($state) {
                            $supplier = Supplier::find($state);
                            if ($supplier?->language) {
                                $set('language', $supplier->language);
                            }
                            // Update price and SKU from pivot data
                            $set('unit_price', $record->purchasePriceForSupplier((int) $state));
                        }
                    }),

                Select::make('language')
                    ->label('Taal PDF')
                    ->options([
                        'nl' => 'Nederlands',
                        'en' => 'Engels',
                        'de' => 'Duits',
                    ])
                    ->default(fn (Product $record) => $record->preferredSupplier()?->language ?? 'nl')
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
                    ->default(function (Product $record) {
                        $preferred = $record->preferredSupplier();

                        return $preferred
                            ? (float) $preferred->pivot->purchase_price
                            : (float) $record->purchase_price;
                    })
                    ->required()
                    ->minValue(0)
                    ->step(0.01),
            ])
            ->action(function (Product $record, array $data) {
                $supplierId = (int) $data['supplier_id'];

                $order = PurchaseOrder::create([
                    'supplier_id' => $supplierId,
                    'language' => $data['language'],
                    'status' => PurchaseOrderStatus::Draft,
                    'order_date' => $data['order_date'],
                    'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                    'product_id' => $record->id,
                ]);

                $order->items()->create([
                    'product_id' => $record->id,
                    'description' => $record->name,
                    'supplier_article_number' => $record->supplierSkuFor($supplierId),
                    'quantity' => $data['quantity'],
                    'unit_price' => $data['unit_price'],
                    'sort_order' => 1,
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
