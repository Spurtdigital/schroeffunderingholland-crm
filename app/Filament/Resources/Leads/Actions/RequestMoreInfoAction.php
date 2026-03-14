<?php

namespace App\Filament\Resources\Leads\Actions;

use App\Enums\LeadStatus;
use App\Notifications\InfoRequiredNotification;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;

class RequestMoreInfoAction
{
    public static function make(): Action
    {
        return Action::make('requestMoreInfo')
            ->label('Meer info nodig')
            ->icon('heroicon-o-envelope')
            ->color('warning')
            ->form([
                Section::make('Benodigde informatie')
                    ->schema([
                        Forms\Components\CheckboxList::make('required_items')
                            ->label('Wat hebben we nodig?')
                            ->options([
                                'constructierapport' => 'Constructierapport',
                                'bouwtekening' => 'Bouwtekening / technische tekening',
                                'vergunning' => 'Bouwvergunning',
                                'grondinformatie' => 'Grondinformatie / bodemrapport',
                                'belastingberekening' => 'Belastingberekening',
                                'locatiefoto' => "Foto's van de locatie",
                                'bereikbaarheid' => 'Bereikbaarheidsinformatie',
                                'afmetingen' => 'Exacte afmetingen constructie',
                            ])
                            ->columns(2)
                            ->required()
                            ->default([]),

                        Forms\Components\Textarea::make('additional_message')
                            ->label('Aanvullend bericht')
                            ->placeholder('Eventuele extra toelichting...')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('send_email')
                            ->label('Email versturen naar klant')
                            ->default(true)
                            ->inline(false),
                    ]),
            ])
            ->action(function (array $data, $record) {
                // Build message
                $items = collect($data['required_items'])->map(function ($key) {
                    $labels = [
                        'constructierapport' => 'Constructierapport',
                        'bouwtekening' => 'Bouwtekening / technische tekening',
                        'vergunning' => 'Bouwvergunning',
                        'grondinformatie' => 'Grondinformatie / bodemrapport',
                        'belastingberekening' => 'Belastingberekening',
                        'locatiefoto' => "Foto's van de locatie",
                        'bereikbaarheid' => 'Bereikbaarheidsinformatie',
                        'afmetingen' => 'Exacte afmetingen constructie',
                    ];

                    return '• '.($labels[$key] ?? $key);
                })->implode("\n");

                $message = $items;

                if (! empty($data['additional_message'])) {
                    $message .= "\n\n".$data['additional_message'];
                }

                // Build update data
                $updateData = [
                    'internal_notes' => ($record->internal_notes ? $record->internal_notes."\n\n" : '')
                        .'['.now()->format('d-m-Y H:i')."] Meer info gevraagd:\n".$message,
                ];

                // Send email if requested
                if ($data['send_email'] && $record->email) {
                    // Use InfoRequiredNotification with email template
                    try {
                        $record->notify(new InfoRequiredNotification($record, $message));

                        // Zet status op InfoRequested als mail verstuurd
                        if ($record->status === LeadStatus::New || $record->status === LeadStatus::Contacted) {
                            $updateData['status'] = LeadStatus::InfoRequested;
                        }

                        // Update lead in één keer
                        $record->update($updateData);

                        Notification::make()
                            ->success()
                            ->title('Email verstuurd')
                            ->body('Email met benodigde informatie is verstuurd naar '.$record->email)
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Email kon niet worden verstuurd')
                            ->body($e->getMessage())
                            ->send();
                    }
                } else {
                    // Update lead in één keer (geen email, dus geen status-wijziging)
                    $record->update($updateData);

                    Notification::make()
                        ->success()
                        ->title('Opgeslagen')
                        ->body('Benodigde informatie is toegevoegd aan interne notities')
                        ->send();
                }
            });
    }
}
