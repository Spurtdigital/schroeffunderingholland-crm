<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Enums\QuoteItemType;
use App\Enums\QuoteStatus;
use App\Models\Product;
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
                // Update quote status to Approved
                $record->update([
                    'status' => QuoteStatus::Approved,
                    'approved_at' => now(),
                ]);

                // Create stock reservations for all product items
                foreach ($record->items as $item) {
                    if ($item->type === QuoteItemType::Product && $item->itemable_type === Product::class && $item->itemable_id) {
                        $product = Product::find($item->itemable_id);
                        if ($product) {
                            // Avoid duplicate reservations
                            $alreadyReserved = $record->stockReservations()
                                ->where('product_id', $product->id)
                                ->where('status', 'active')
                                ->exists();

                            if (! $alreadyReserved) {
                                $product->reserveStock($record, (int) $item->quantity);
                            }
                        }
                    }
                }

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
