<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteAssumption;
use App\Notifications\CustomerQuoteNotification;
use App\Settings\GeneralSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification as NotificationFacade;

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

        $this->modalHeading('Offerte versturen');

        $this->modalDescription('Controleer de gegevens en verstuur de offerte.');

        $this->modalSubmitActionLabel('Ja, verstuur');

        $this->form(fn (Model $record) => [
            TextInput::make('contact_person')
                ->label('T.a.v. (contactpersoon)')
                ->default($record->company?->contact_person)
                ->maxLength(255),

            TextInput::make('email')
                ->label('E-mailadres klant')
                ->email()
                ->default($record->company?->email)
                ->required()
                ->maxLength(255),

            Section::make('Offerte-aannames')
                ->description('Selecteer de aannames die van toepassing zijn op deze offerte. Ze worden als bullet-lijst opgenomen in de offerte.')
                ->schema(function () use ($record) {
                    $variableAssumptions = QuoteAssumption::where('active', true)
                        ->where('has_variable_input', true)
                        ->orderBy('sort_order')
                        ->get();

                    $defaultIds = $record->assumptions()->pluck('quote_assumptions.id')->toArray();
                    if (empty($defaultIds)) {
                        // Auto-check by SKU
                        $skus = $record->items()
                            ->with('itemable')
                            ->get()
                            ->map(fn ($item) => strtoupper($item->itemable?->sku ?? ''))
                            ->filter()
                            ->toArray();

                        $defaultIds = QuoteAssumption::where('active', true)
                            ->whereNotNull('trigger_sku')
                            ->get()
                            ->filter(function ($a) use ($skus) {
                                $trigger = strtoupper($a->trigger_sku);
                                foreach ($skus as $sku) {
                                    if (str_contains($sku, $trigger)) return true;
                                }
                                return false;
                            })
                            ->pluck('id')
                            ->toArray();
                    }

                    // Pre-fill pivot values for existing selections
                    $pivotValues = $record->assumptions()
                        ->withPivot('pivot_value')
                        ->get()
                        ->pluck('pivot.pivot_value', 'id')
                        ->toArray();

                    $components = [
                        CheckboxList::make('assumption_ids')
                            ->label('')
                            ->options(fn () => QuoteAssumption::where('active', true)
                                ->orderBy('sort_order')
                                ->pluck('title', 'id')
                                ->toArray())
                            ->default($defaultIds)
                            ->columns(1)
                            ->live(),
                    ];

                    foreach ($variableAssumptions as $assumption) {
                        $components[] = TextInput::make("assumption_value_{$assumption->id}")
                            ->label($assumption->variable_input_label ?? 'Waarde')
                            ->suffix($assumption->variable_input_suffix)
                            ->default($pivotValues[$assumption->id] ?? null)
                            ->placeholder('bijv. 3,5')
                            ->visible(fn (Get $get) => in_array($assumption->id, (array) ($get('assumption_ids') ?? [])))
                            ->required(fn (Get $get) => in_array($assumption->id, (array) ($get('assumption_ids') ?? [])));
                    }

                    return $components;
                }),
        ]);

        $this->visible(fn (Model $record): bool => $record->status === QuoteStatus::Draft);

        $this->action(function (Model $record, array $data) {
            // Check if we have items
            if ($record->items()->count() === 0) {
                Notification::make()
                    ->warning()
                    ->title('Geen items')
                    ->body('Voeg eerst items toe aan de offerte voordat je deze verstuurt.')
                    ->send();

                return;
            }

            // Sync selected assumptions with pivot values
            $syncData = [];
            foreach ($data['assumption_ids'] ?? [] as $assumptionId) {
                $pivotValue = $data["assumption_value_{$assumptionId}"] ?? null;
                $syncData[$assumptionId] = ['pivot_value' => $pivotValue];
            }
            $record->assumptions()->sync($syncData);

            // Genereer lokaal offertenummer als Moneybird uitgeschakeld is en het nog een CONCEPT is
            $settings = app(GeneralSettings::class);
            $quoteNumber = $record->quote_number;
            if (! ($settings->moneybird_enabled ?? true) && str_starts_with($record->quote_number ?? '', 'CONCEPT-')) {
                $quoteNumber = static::generateLocalQuoteNumber();
            }

            // Update quote status
            $record->update([
                'status'       => QuoteStatus::Sent,
                'sent_at'      => now(),
                'quote_number' => $quoteNumber,
            ]);

            NotificationFacade::route('mail', $data['email'])->notify(
                new CustomerQuoteNotification($record->fresh(), $data['contact_person'] ?? null)
            );

            Notification::make()
                ->success()
                ->title('Offerte verstuurd')
                ->body("Offerte ".($record->displayNumber() ?? 'Concept')." is per e-mail met PDF-bijlage verstuurd.")
                ->send();

        });
    }

    /**
     * Genereer een lokaal oplopend offertenummer in het formaat YYYY-NNNN.
     * Wordt gebruikt wanneer Moneybird uitgeschakeld is.
     */
    protected static function generateLocalQuoteNumber(): string
    {
        $year = now()->year;
        $settings = app(GeneralSettings::class);
        $start = max(1, (int) ($settings->quote_number_start ?? 1));

        $last = Quote::whereYear('created_at', $year)
            ->where('quote_number', 'like', $year.'-%')
            ->orderByDesc('quote_number')
            ->value('quote_number');

        if ($last) {
            $current = (int) substr($last, strpos($last, '-') + 1);
            $next = max($start, $current + 1);
        } else {
            $next = $start;
        }

        return $year.'-'.str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
