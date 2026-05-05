<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Enums\QuoteItemType;
use App\Models\Product;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class CreatePurchaseOrderFromProjectAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'createPurchaseOrder';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Inkooporder');
        $this->icon('heroicon-o-shopping-cart');
        $this->color('warning');
        $this->modalHeading('Nieuwe inkooporder aanmaken');
        $this->modalWidth('2xl');

        $this->fillForm(function (Project $record): array {
            $items = $record->items()
                ->with(['itemable.suppliers'])
                ->where('type', QuoteItemType::Product)
                ->whereNotNull('itemable_id')
                ->get()
                ->filter(fn ($item) => $item->itemable instanceof Product)
                ->values();

            // Determine the most common preferred supplier to pre-select
            $supplierCounts = [];
            foreach ($items as $item) {
                $preferred = $item->itemable->preferredSupplier();
                if ($preferred) {
                    $supplierCounts[$preferred->id] = ($supplierCounts[$preferred->id] ?? 0) + 1;
                }
            }
            arsort($supplierCounts);
            $dominantSupplierId = array_key_first($supplierCounts);
            $dominantSupplier = $dominantSupplierId ? Supplier::find($dominantSupplierId) : null;

            $formItems = $items->map(function ($projectItem) use ($dominantSupplierId) {
                /** @var Product $product */
                $product = $projectItem->itemable;

                return [
                    'product_id'              => $product->id,
                    'description'             => $projectItem->description ?: $product->name,
                    'supplier_article_number' => $product->supplierSkuFor($dominantSupplierId) ?? '',
                    'quantity'                => (float) $projectItem->quantity,
                    'unit_price'              => (float) $product->purchasePriceForSupplier($dominantSupplierId),
                ];
            })->toArray();

            return [
                'supplier_id' => $dominantSupplierId,
                'language'    => $dominantSupplier?->language ?? 'nl',
                'order_date'  => now()->toDateString(),
                'items'       => $formItems,
            ];
        });

        $this->form([
            Select::make('supplier_id')
                ->label('Leverancier')
                ->options(fn () => Supplier::where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    if ($state) {
                        $supplier = Supplier::find($state);
                        if ($supplier?->language) {
                            $set('language', $supplier->language);
                        }

                        // Re-fill supplier-specific data for all items that have a product linked
                        $items = $get('items') ?? [];
                        foreach ($items as $key => $item) {
                            $productId = $item['product_id'] ?? null;
                            if ($productId) {
                                $product = Product::with('suppliers')->find($productId);
                                if ($product) {
                                    $set("items.{$key}.supplier_article_number", $product->supplierSkuFor((int) $state) ?? '');
                                    $set("items.{$key}.unit_price", $product->purchasePriceForSupplier((int) $state));
                                }
                            }
                        }
                    }
                }),

            Select::make('language')
                ->label('Taal PDF')
                ->options([
                    'nl' => 'Nederlands',
                    'en' => 'Engels',
                    'de' => 'Duits',
                ])
                ->default('nl')
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

            Repeater::make('items')
                ->label('Regels')
                ->schema([
                    Select::make('product_id')
                        ->label('Product (optioneel)')
                        ->options(fn () => Product::where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->nullable()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            if ($state) {
                                $product = Product::with('suppliers')->find($state);
                                if ($product) {
                                    $set('description', $product->name);

                                    // Use supplier-specific data from pivot if PO supplier is set
                                    $supplierId = $get('../../supplier_id');
                                    $set('supplier_article_number', $product->supplierSkuFor($supplierId) ?? '');
                                    $set('unit_price', $product->purchasePriceForSupplier($supplierId));
                                }
                            }
                        })
                        ->columnSpan(3),

                    TextInput::make('description')
                        ->label('Omschrijving')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(3),

                    TextInput::make('supplier_article_number')
                        ->label('Art.nr. leverancier')
                        ->maxLength(100)
                        ->columnSpan(1),

                    TextInput::make('quantity')
                        ->label('Aantal')
                        ->numeric()
                        ->required()
                        ->default(1)
                        ->minValue(0.01)
                        ->step(0.01)
                        ->columnSpan(1),

                    TextInput::make('unit_price')
                        ->label('Eenheidsprijs')
                        ->numeric()
                        ->required()
                        ->prefix('€')
                        ->minValue(0)
                        ->step(0.01)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->addActionLabel('Regel toevoegen')
                ->defaultItems(0)
                ->minItems(1)
                ->collapsible()
                ->reorderable(),
        ]);

        $this->action(function (array $data, Project $record) {
            $order = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'project_id' => $record->id,
                'language' => $data['language'],
                'status' => 'draft',
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            ]);

            foreach (($data['items'] ?? []) as $index => $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];

                $order->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'supplier_article_number' => $item['supplier_article_number'] ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => round($quantity * $unitPrice, 2),
                    'sort_order' => $index + 1,
                ]);
            }

            $order->recalculateTotals();

            Notification::make()
                ->success()
                ->title('Inkooporder aangemaakt')
                ->body("Order {$order->order_number} is aangemaakt en gekoppeld aan project {$record->project_number}.")
                ->send();

            return redirect()->route('filament.admin.resources.purchase-orders.edit', ['record' => $order]);
        });
    }
}
