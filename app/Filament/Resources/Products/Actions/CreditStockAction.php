<?php

namespace App\Filament\Resources\Products\Actions;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class CreditStockAction extends Action
{
    public static function make(?string $name = 'credit_stock'): static
    {
        return parent::make($name)
            ->label('Voorraad crediteren')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('danger')
            ->modalHeading('Voorraad crediteren')
            ->modalSubmitActionLabel('Crediteren')
            ->form([
                TextInput::make('quantity')
                    ->label('Aantal te crediteren')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->helperText('Voer een positief getal in dat uit de voorraad wordt verwijderd'),
                Select::make('credit_reason')
                    ->label('Reden')
                    ->options([
                        'retour_leverancier' => 'Retour leverancier',
                        'schade' => 'Schade / defect',
                        'gebroken' => 'Gebroken tijdens montage',
                        'foutieve_levering' => 'Foutieve levering',
                        'overschot' => 'Overschot / te veel ontvangen',
                        'overig' => 'Overig',
                    ])
                    ->required(),
                Textarea::make('notes')
                    ->label('Toelichting')
                    ->rows(2)
                    ->placeholder('Optionele extra toelichting'),
            ])
            ->action(function (Product $record, array $data) {
                $reason = match ($data['credit_reason']) {
                    'retour_leverancier' => 'Retour leverancier',
                    'schade' => 'Schade / defect',
                    'gebroken' => 'Gebroken tijdens montage',
                    'foutieve_levering' => 'Foutieve levering',
                    'overschot' => 'Overschot / te veel ontvangen',
                    default => 'Overig',
                };

                if (! empty($data['notes'])) {
                    $reason .= ' — ' . $data['notes'];
                }

                $record->creditStock(
                    quantity: (int) $data['quantity'],
                    reason: $reason,
                );

                Notification::make()
                    ->success()
                    ->title('Voorraad gecrediteerd')
                    ->body("{$data['quantity']} stuks uit de voorraad verwijderd.")
                    ->send();
            });
    }
}
