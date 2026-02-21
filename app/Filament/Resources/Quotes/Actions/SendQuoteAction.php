<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Enums\QuoteStatus;
use App\Enums\StockReservationStatus;
use App\Models\Product;
use App\Models\StockReservation;
use App\Models\User;
use App\Notifications\QuoteSentNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class SendQuoteAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'sendQuote';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Verstuur offerte');

        $this->icon('heroicon-o-paper-airplane');

        $this->color('success');

        $this->requiresConfirmation();

        $this->modalHeading('Offerte versturen');

        $this->modalDescription('Weet je zeker dat je deze offerte wilt versturen? Voorraad wordt gereserveerd voor producten in deze offerte.');

        $this->modalSubmitActionLabel('Ja, verstuur');

        $this->visible(fn (Model $record): bool => $record->status === QuoteStatus::Draft
        );

        $this->action(function (Model $record) {
            // Check if we have items
            if ($record->items()->count() === 0) {
                Notification::make()
                    ->warning()
                    ->title('Geen items')
                    ->body('Voeg eerst items toe aan de offerte voordat je deze verstuurt.')
                    ->send();

                return;
            }

            // Reserve stock for product items
            foreach ($record->items as $item) {
                if ($item->itemable_type === Product::class && $item->itemable_id) {
                    $product = Product::find($item->itemable_id);

                    if ($product) {
                        // Check if we have enough available stock (considering existing reservations)
                        if ($product->available_stock < $item->quantity) {
                            Notification::make()
                                ->warning()
                                ->title('Onvoldoende voorraad')
                                ->body("Product '{$product->name}' heeft onvoldoende beschikbare voorraad ({$product->available_stock} beschikbaar, {$item->quantity} nodig).")
                                ->send();

                            return;
                        }

                        // Create stock reservation (this automatically updates reserved_quantity via accessor)
                        StockReservation::create([
                            'product_id' => $product->id,
                            'quote_id' => $record->id,
                            'quantity' => $item->quantity,
                            'status' => StockReservationStatus::Active,
                        ]);
                    }
                }
            }

            // Update quote status
            $record->update([
                'status' => QuoteStatus::Sent,
                'sent_at' => now(),
            ]);

            Notification::make()
                ->success()
                ->title('Offerte verstuurd')
                ->body("Offerte {$record->quote_number} is verstuurd en voorraad is gereserveerd.")
                ->send();

            // Send email notification to all users
            $users = User::all();
            foreach ($users as $user) {
                $user->notify(new QuoteSentNotification($record));
            }
        });
    }
}
