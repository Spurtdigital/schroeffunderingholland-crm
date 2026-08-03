<?php

namespace App\Filament\Resources\Products\Actions;

use App\Models\Product;
use App\Models\SerialNumber;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class AddStockAction extends Action
{
    public static function make(?string $name = 'add_stock'): static
    {
        return parent::make($name)
            ->label('Voorraad toevoegen')
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->modalHeading('Voorraad toevoegen')
            ->modalSubmitActionLabel('Toevoegen')
            ->modalWidth('lg')
            ->form([
                TextInput::make('serial_number')
                    ->label('Serienummer / Batchnummer')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Bijv. EA1235'),
                TextInput::make('quantity')
                    ->label('Aantal')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->default(1)
                    ->placeholder('Hoeveel stuks van dit serienummer'),
                Textarea::make('reason')
                    ->label('Reden / opmerking')
                    ->rows(2)
                    ->placeholder('Bijv. inkooplevering van leverancier'),
            ])
            ->action(function (Product $record, array $data) {
                $serialNumber = trim($data['serial_number']);
                $quantity = (int) $data['quantity'];

                if ($quantity <= 0) {
                    Notification::make()
                        ->danger()
                        ->title('Ongeldig aantal')
                        ->body('Voer een geldig aantal in.')
                        ->send();

                    return;
                }

                // Check if serial number already exists for this product
                $existing = SerialNumber::where('product_id', $record->id)
                    ->where('serial_number', $serialNumber)
                    ->first();

                if ($existing) {
                    // Add to existing serial number quantity
                    $existing->increment('quantity', $quantity);
                    
                    // Add stock mutation
                    $record->addStock(
                        quantity: $quantity,
                        reason: ($data['reason'] ?? '') . " (serienummer: {$serialNumber})",
                    );

                    Notification::make()
                        ->success()
                        ->title('Voorraad bijgewerkt')
                        ->body("{$quantity} stuks toegevoegd aan bestaand serienummer {$serialNumber}. Nieuw totaal: {$existing->fresh()->quantity}")
                        ->send();
                } else {
                    // Add stock mutation first
                    $record->addStock(
                        quantity: $quantity,
                        reason: ($data['reason'] ?? '') . " (serienummer: {$serialNumber})",
                    );

                    // Get the latest mutation for linking
                    $mutation = $record->stockMutations()->latest()->first();

                    // Create new serial number
                    SerialNumber::create([
                        'product_id' => $record->id,
                        'serial_number' => $serialNumber,
                        'quantity' => $quantity,
                        'quantity_reserved' => 0,
                        'stock_mutation_id' => $mutation?->id,
                        'notes' => $data['reason'] ?? null,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Voorraad bijgewerkt')
                        ->body("{$quantity} stuks toegevoegd met serienummer {$serialNumber}.")
                        ->send();
                }
            });
    }
}
