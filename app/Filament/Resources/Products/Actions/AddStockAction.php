<?php

namespace App\Filament\Resources\Products\Actions;

use App\Models\Product;
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
            ->form([
                TextInput::make('quantity')
                    ->label('Aantal toe te voegen')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->helperText('Voer een positief getal in'),
                Textarea::make('reason')
                    ->label('Reden / opmerking')
                    ->rows(2)
                    ->placeholder('Bijv. inkooplevering van leverancier'),
            ])
            ->action(function (Product $record, array $data) {
                $record->addStock(
                    quantity: (int) $data['quantity'],
                    reason: $data['reason'] ?? null,
                );

                Notification::make()
                    ->success()
                    ->title('Voorraad bijgewerkt')
                    ->body("{$data['quantity']} stuks toegevoegd aan de voorraad.")
                    ->send();
            });
    }
}
