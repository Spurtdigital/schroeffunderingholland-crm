<?php

namespace App\Filament\Resources\Quotes\Schemas;

use App\Filament\Resources\Quotes\Pages\ViewQuote;
use App\Filament\Resources\Quotes\RelationManagers\ItemsRelationManager;
use App\Models\Machine;
use App\Services\DistanceCalculatorService;
use App\Services\MoneybirdService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuoteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([

                // ── Rij 1: Offerte-informatie – volledige breedte ─────────────
                Section::make('Offerte-informatie')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([

                        // ── Blok 1: Identiteit ─────────────────────────────────
                        Group::make([
                            TextEntry::make('quote_number')
                                ->label('Offertenummer')
                                ->weight('bold')
                                ->size('lg')
                                ->state(fn ($record) => str_starts_with($record->quote_number ?? '', 'CONCEPT-')
                                    ? 'Concept'
                                    : $record->quote_number
                                )
                                ->color(fn ($record) => str_starts_with($record->quote_number ?? '', 'CONCEPT-') ? 'warning' : null)
                                ->hintIcon(fn ($record) => str_starts_with($record->quote_number ?? '', 'CONCEPT-') ? 'heroicon-o-information-circle' : null)
                                ->hintIconTooltip(fn ($record) => str_starts_with($record->quote_number ?? '', 'CONCEPT-') ? 'Nog niet verstuurd naar Moneybird' : null),

                            TextEntry::make('status')
                                ->label('Status')
                                ->badge(),

                            TextEntry::make('valid_until')
                                ->label('Geldig tot')
                                ->date('d-m-Y')
                                ->placeholder('-'),

                            TextEntry::make('sent_at')
                                ->label('Verzonden op')
                                ->dateTime('d-m-Y H:i')
                                ->placeholder('-'),
                        ])->columns(2),

                        // ── Blok 2: Financieel overzicht ───────────────────────
                        Group::make([
                            TextEntry::make('subtotal')
                                ->label('Excl. BTW')
                                ->money('EUR'),

                            TextEntry::make('total')
                                ->label('Incl. BTW')
                                ->money('EUR')
                                ->weight('bold'),

                            TextEntry::make('cost_price_total')
                                ->label('Inkoopprijs')
                                ->state(function ($record) {
                                    $cost = $record->items
                                        ->filter(fn ($item) => $item->itemable_type === \App\Models\Product::class && $item->itemable)
                                        ->sum(fn ($item) => (float) $item->itemable->purchase_price * (float) $item->quantity);

                                    return $cost > 0 ? $cost : null;
                                })
                                ->money('EUR')
                                ->placeholder('-'),

                            TextEntry::make('margin')
                                ->label('Marge')
                                ->state(function ($record) {
                                    $cost = $record->items
                                        ->filter(fn ($item) => $item->itemable_type === \App\Models\Product::class && $item->itemable)
                                        ->sum(fn ($item) => (float) $item->itemable->purchase_price * (float) $item->quantity);

                                    $subtotal = (float) $record->subtotal;
                                    if ($subtotal <= 0) return null;

                                    $margin    = $subtotal - $cost;
                                    $marginPct = round(($margin / $subtotal) * 100, 1);

                                    return '€ '.number_format($margin, 2, ',', '.').' ('.$marginPct.'%)';
                                })
                                ->color(function ($record) {
                                    $cost = $record->items
                                        ->filter(fn ($item) => $item->itemable_type === \App\Models\Product::class && $item->itemable)
                                        ->sum(fn ($item) => (float) $item->itemable->purchase_price * (float) $item->quantity);

                                    $subtotal = (float) $record->subtotal;
                                    if ($subtotal <= 0) return null;

                                    $marginPct = (($subtotal - $cost) / $subtotal) * 100;

                                    return match (true) {
                                        $marginPct >= 20 => 'success',
                                        $marginPct >= 10 => 'warning',
                                        default          => 'danger',
                                    };
                                })
                                ->weight('bold'),
                        ])->columns(2),

                        // ── Blok 3: Instellingen ───────────────────────────────
                        Group::make([
                            TextEntry::make('machine.name')
                                ->label('Montage machine')
                                ->placeholder('-')
                                ->hintAction(
                                    Action::make('editMachine')
                                        ->icon('heroicon-m-pencil-square')
                                        ->label('Bewerken')
                                        ->fillForm(fn ($record) => ['machine_id' => $record->machine_id])
                                        ->form([
                                            FormSelect::make('machine_id')
                                                ->label('Montage machine')
                                                ->options(fn () => Machine::where('active', true)->orderBy('name')->pluck('name', 'id'))
                                                ->searchable()
                                                ->nullable()
                                                ->placeholder('Geen machine geselecteerd'),
                                        ])
                                        ->action(function ($record, array $data, $livewire) {
                                            $record->update(['machine_id' => $data['machine_id']]);
                                            $livewire->record = $record->fresh();
                                        })
                                ),

                            IconEntry::make('show_item_breakdown')
                                ->label('Regels op PDF')
                                ->boolean()
                                ->trueIcon('heroicon-o-eye')
                                ->falseIcon('heroicon-o-eye-slash')
                                ->trueColor('success')
                                ->falseColor('gray'),

                            TextEntry::make('reference')
                                ->label('Kenmerk (Moneybird)')
                                ->placeholder('-')
                                ->columnSpan(2)
                                ->hintAction(
                                    Action::make('editReference')
                                        ->icon('heroicon-m-pencil-square')
                                        ->label('Bewerken')
                                        ->fillForm(fn ($record) => ['reference' => $record->reference])
                                        ->form([
                                            FormTextInput::make('reference')
                                                ->label('Kenmerk (Moneybird)')
                                            ->helperText('Bijv. "Overkapping Markelo" — wordt als kenmerk zichtbaar in Moneybird.')
                                            ->maxLength(255)
                                            ->nullable(),
                                    ])
                                     ->action(function ($record, array $data, $livewire) {
                                         $record->update(['reference' => $data['reference']]);
                                         $livewire->record = $record->fresh();
                                     })
                             ),
                        ])->columns(2),
                    ]),

                // ── Kolom links 3/4: Regels + Offerte tekst ──────────────────
                Group::make()
                    ->columnSpan(3)
                    ->schema([
                        Livewire::make(ItemsRelationManager::class, fn ($record) => [
                            'ownerRecord' => $record,
                            'pageClass' => ViewQuote::class,
                        ]),

                        Section::make('Opmerkingen')
                            ->collapsible()
                            ->collapsed(fn ($record) => blank($record->notes))
                            ->schema([
                                TextEntry::make('notes')
                                    ->label('')
                                    ->placeholder('Geen opmerkingen')
                                    ->prose(),
                            ]),

                        Section::make('Offerte tekst (Moneybird)')
                            ->columns(2)
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                TextEntry::make('pre_text_preview')
                                    ->label('Aanhef + opening + aannames (pre_text)')
                                    ->columnSpanFull()
                                    ->state(function ($record) {
                                        $service = app(MoneybirdService::class);

                                        return $service->buildPreText($record);
                                    })
                                    ->prose()
                                    ->placeholder('(leeg)')
                                    ->formatStateUsing(fn (?string $state) => $state ? nl2br(e($state)) : '<em class="text-gray-400">leeg</em>')
                                    ->html(),

                                TextEntry::make('post_text_preview')
                                    ->label('Betalingsvoorwaarden + slottekst (post_text)')
                                    ->columnSpanFull()
                                    ->state(function ($record) {
                                        $service = app(MoneybirdService::class);

                                        return $service->buildPostText($record);
                                    })
                                    ->placeholder('(leeg)')
                                    ->formatStateUsing(fn (?string $state) => $state ? nl2br(e($state)) : '<em class="text-gray-400">leeg</em>')
                                    ->html(),
                            ]),
                    ]),

                // ── Kolom rechts 1/4: Klant + Lead + Historie ─────────────────
                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Klant')
                            ->schema([
                                TextEntry::make('company.name')
                                    ->label('Bedrijf')
                                    ->weight('bold')
                                    ->url(fn ($record) => $record->company
                                        ? route('filament.admin.resources.companies.edit', ['record' => $record->company_id])
                                        : null
                                    )
                                    ->openUrlInNewTab(),

                                TextEntry::make('company.contact_person')
                                    ->label('Contactpersoon')
                                    ->placeholder('-'),

                                TextEntry::make('company_address')
                                    ->label('Adres')
                                    ->state(fn ($record) => $record->company
                                        ? trim(($record->company->street ?? '').' '.($record->company->house_number ?? ''))
                                        : '-'
                                    )
                                    ->placeholder('-'),

                                TextEntry::make('company_city')
                                    ->label('Plaats')
                                    ->state(fn ($record) => $record->company
                                        ? trim(($record->company->postal_code ?? '').' '.($record->company->city ?? ''))
                                        : '-'
                                    )
                                    ->placeholder('-'),

                                TextEntry::make('company.phone')
                                    ->label('Telefoon')
                                    ->placeholder('-'),

                                TextEntry::make('company.email')
                                    ->label('E-mail')
                                    ->placeholder('-'),
                            ]),

                        Section::make('Lead & Werklocatie')
                            ->schema([
                                TextEntry::make('lead.name')
                                    ->label('Lead')
                                    ->url(fn ($record) => $record->lead
                                        ? route('filament.admin.resources.leads.view', ['record' => $record->lead_id])
                                        : null
                                    )
                                    ->openUrlInNewTab()
                                    ->placeholder('-'),

                                TextEntry::make('work_address_display')
                                    ->label('Werklocatie')
                                    ->state(function ($record) {
                                        $address = $record->workFullAddress() ?: null;

                                        if (! $address) {
                                            return null;
                                        }

                                        $service = app(DistanceCalculatorService::class);
                                        if (! $service->isAvailable()) {
                                            return $address;
                                        }

                                        $result = $service->calculateDistance(str_replace("\n", ', ', $address));
                                        if ($result['success']) {
                                            return $address.'||'.$result['distance_km'].'||'.$result['duration_text'];
                                        }

                                        return $address;
                                    })
                                    ->formatStateUsing(function (?string $state): string {
                                        if (! $state) {
                                            return '-';
                                        }
                                        $parts = explode('||', $state);
                                        if (count($parts) === 3) {
                                            return nl2br(e($parts[0])).' <span class="text-gray-500 text-sm font-medium">('.$parts[1].' km)</span>';
                                        }

                                        return nl2br(e($state));
                                    })
                                    ->html()
                                    ->hintIcon(fn ($state) => $state && str_contains($state, '||') ? 'heroicon-o-information-circle' : null)
                                    ->hintIconTooltip(function (?string $state): ?string {
                                        if (! $state) {
                                            return null;
                                        }
                                        $parts = explode('||', $state);
                                        if (count($parts) === 3) {
                                            return 'Rijafstand vanaf bedrijfslocatie: '.$parts[1].' km ('.$parts[2].')';
                                        }

                                        return null;
                                    })
                                    ->placeholder('-'),

                                TextEntry::make('subtotal')
                                    ->label('Subtotaal excl. BTW')
                                    ->money('EUR'),

                                TextEntry::make('vat_amount')
                                    ->label('BTW (21%)')
                                    ->money('EUR'),
                            ]),

                        Section::make('Offerte-aannames')
                            ->schema([
                                TextEntry::make('assumptions')
                                    ->label('')
                                    ->state(fn ($record) => $record->assumptions()->orderBy('sort_order')->pluck('title')->toArray())
                                    ->listWithLineBreaks()
                                    ->placeholder('Geen aannames geselecteerd'),
                            ])
                            ->collapsible()
                            ->collapsed(fn ($record) => $record->assumptions()->doesntExist()),

                        Section::make('Betalingsvoorwaarden')
                            ->schema([
                                TextEntry::make('paymentTerm.title')
                                    ->label('Betalingsvoorwaarde')
                                    ->placeholder('-'),
                                TextEntry::make('paymentTerm.content')
                                    ->label('Tekst')
                                    ->placeholder('-'),
                            ])
                            ->collapsible()
                            ->collapsed(fn ($record) => $record->payment_term_id === null),

                        Section::make('Reiskosten')
                            ->schema([
                                TextEntry::make('travel_info')
                                    ->hiddenLabel()
                                    ->state(function ($record) {
                                        if (blank($record->travel_distance) && blank($record->travel_total)) {
                                            return null;
                                        }

                                        $parts = [];
                                        if ($record->travel_distance) {
                                            $parts[] = $record->travel_distance.' km';
                                        }
                                        if ($record->travel_cost_per_km) {
                                            $parts[] = '€ '.number_format((float) $record->travel_cost_per_km, 2, ',', '.').' per km';
                                        }
                                        if ($record->travel_total) {
                                            $parts[] = '€ '.number_format((float) $record->travel_total, 2, ',', '.').' totaal';
                                        }

                                        return implode(' | ', $parts);
                                    })
                                    ->placeholder('Geen reiskosten ingesteld'),
                            ])
                            ->collapsible()
                            ->collapsed(fn ($record) => blank($record->travel_distance)),

                        Section::make('Historie')
                            ->schema([
                                ViewEntry::make('activity')
                                    ->label('')
                                    ->view('filament.components.quote-activity'),
                            ]),
                    ]),
            ]);
    }
}
