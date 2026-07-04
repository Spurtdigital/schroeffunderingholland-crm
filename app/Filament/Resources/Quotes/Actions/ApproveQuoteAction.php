<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\QuoteApprovedNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ApproveQuoteAction extends Action
{
    public static function make(?string $name = 'approve'): static
    {
        return parent::make($name)
            ->label('Goedkeuren')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Offerte goedkeuren')
            ->modalDescription('Weet je zeker dat je deze offerte wilt goedkeuren?')
            ->modalSubmitActionLabel('Ja, goedkeuren')
            ->visible(fn (Quote $record) => $record->status === QuoteStatus::Sent)
            ->action(function (Quote $record) {
                // Update quote status to Approved (stock reservations are created by QuoteObserver)
                $record->update([
                    'status' => QuoteStatus::Approved,
                    'approved_at' => now(),
                ]);

                // Show success notification
                Notification::make()
                    ->success()
                    ->title('Offerte goedgekeurd')
                    ->body("Offerte ".($record->displayNumber() ?? 'Concept')." is goedgekeurd en kan nu omgezet worden naar een project.")
                    ->send();

                // Send email notification to all users
                $users = User::all();
                foreach ($users as $user) {
                    $user->notify(new QuoteApprovedNotification($record));
                }
            });
    }
}
