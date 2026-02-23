<?php

namespace App\Filament\Pages;

use App\Services\MoneybirdService;
use App\Settings\GeneralSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.manage-settings';

    protected static ?string $navigationLabel = 'Instellingen';

    protected static string|\UnitEnum|null $navigationGroup = 'Systeem';

    protected static ?int $navigationSort = 99;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public function getTitle(): string
    {
        return 'Instellingen';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(GeneralSettings::class);

        $this->form->fill([
            'company_name' => $settings->company_name,
            'company_address' => $settings->company_address,
            'company_postal_code' => $settings->company_postal_code,
            'company_city' => $settings->company_city,
            'company_phone' => $settings->company_phone,
            'company_email' => $settings->company_email,
            'company_kvk_number' => $settings->company_kvk_number,
            'company_vat_number' => $settings->company_vat_number,
            'company_bank_account' => $settings->company_bank_account,
            'company_logo' => $settings->company_logo,
            'default_vat_percentage' => $settings->default_vat_percentage,
            'default_quote_validity_days' => $settings->default_quote_validity_days,
            'default_travel_cost_per_km' => $settings->default_travel_cost_per_km,
            'moneybird_api_token' => $settings->moneybird_api_token,
            'moneybird_administration_id' => $settings->moneybird_administration_id,
            'google_maps_api_key' => $settings->google_maps_api_key,
            'default_pre_text' => $settings->default_pre_text,
            'default_post_text' => $settings->default_post_text,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Bedrijfsinformatie')
                    ->schema([
                        FileUpload::make('company_logo')
                            ->label('Bedrijfslogo')
                            ->image()
                            ->disk('public')
                            ->directory('logos')
                            ->visibility('public')
                            ->imagePreviewHeight('80')
                            ->helperText('PNG of JPG, max 180×60px aanbevolen. Wordt gebruikt in PDF documenten.')
                            ->columnSpanFull(),
                        TextInput::make('company_name')
                            ->label('Bedrijfsnaam')
                            ->maxLength(255),
                        TextInput::make('company_address')
                            ->label('Adres')
                            ->maxLength(255),
                        TextInput::make('company_postal_code')
                            ->label('Postcode')
                            ->maxLength(20),
                        TextInput::make('company_city')
                            ->label('Plaats')
                            ->maxLength(255),
                        TextInput::make('company_phone')
                            ->label('Telefoonnummer')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('company_email')
                            ->label('E-mailadres')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('company_kvk_number')
                            ->label('KvK-nummer')
                            ->maxLength(50),
                        TextInput::make('company_vat_number')
                            ->label('BTW-nummer')
                            ->maxLength(50),
                        TextInput::make('company_bank_account')
                            ->label('Bankrekeningnummer')
                            ->maxLength(50),
                    ])
                    ->columns(2),

                Section::make('Offerte Instellingen')
                    ->schema([
                        TextInput::make('default_vat_percentage')
                            ->label('Standaard BTW percentage')
                            ->suffix('%')
                            ->numeric()
                            ->default(21)
                            ->required()
                            ->minValue(0)
                            ->maxValue(100),
                        TextInput::make('default_quote_validity_days')
                            ->label('Standaard geldigheid offerte (dagen)')
                            ->numeric()
                            ->default(30)
                            ->required()
                            ->minValue(1)
                            ->maxValue(365),
                        TextInput::make('default_travel_cost_per_km')
                            ->label('Standaard reiskosten per km')
                            ->prefix('€')
                            ->numeric()
                            ->default(0.23)
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->helperText('Wordt gebruikt voor automatische berekening van reiskosten'),
                        Textarea::make('default_pre_text')
                            ->label('Standaard voorwoord (Moneybird)')
                            ->helperText('Wordt automatisch ingevuld bij een nieuwe offerte. Per offerte aan te passen.')
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('default_post_text')
                            ->label('Standaard nawoord (Moneybird)')
                            ->helperText('Wordt automatisch ingevuld bij een nieuwe offerte. Per offerte aan te passen.')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Moneybird API Credentials')
                    ->description('Configureer de Moneybird API om offertes en facturen automatisch te synchroniseren.')
                    ->schema([
                        TextInput::make('moneybird_api_token')
                            ->label('API Token')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->helperText('Vind je API token in Moneybird onder Instellingen > API & Webhooks'),
                        TextInput::make('moneybird_administration_id')
                            ->label('Administratie ID')
                            ->maxLength(50)
                            ->helperText('Je vindt het Administratie ID in de URL van Moneybird: moneybird.com/{ID}/...'),
                    ])
                    ->columns(1),

                Section::make('Google Maps')
                    ->schema([
                        TextInput::make('google_maps_api_key')
                            ->label('Google Maps API Key')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->helperText('Voor locatie features en routeplanning'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Opslaan')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = app(GeneralSettings::class);
        $settings->company_name = $data['company_name'] ?? null;
        $settings->company_address = $data['company_address'] ?? null;
        $settings->company_postal_code = $data['company_postal_code'] ?? null;
        $settings->company_city = $data['company_city'] ?? null;
        $settings->company_phone = $data['company_phone'] ?? null;
        $settings->company_email = $data['company_email'] ?? null;
        $settings->company_kvk_number = $data['company_kvk_number'] ?? null;
        $settings->company_vat_number = $data['company_vat_number'] ?? null;
        $settings->company_bank_account = $data['company_bank_account'] ?? null;
        $settings->company_logo = $data['company_logo'] ?? null;
        $settings->default_vat_percentage = $data['default_vat_percentage'] ?? 21;
        $settings->default_quote_validity_days = $data['default_quote_validity_days'] ?? 30;
        $settings->default_travel_cost_per_km = $data['default_travel_cost_per_km'] ?? 0.23;
        $settings->moneybird_api_token = $data['moneybird_api_token'] ?? null;
        $settings->moneybird_administration_id = $data['moneybird_administration_id'] ?? null;
        $settings->google_maps_api_key = $data['google_maps_api_key'] ?? null;
        $settings->default_pre_text = $data['default_pre_text'] ?? null;
        $settings->default_post_text = $data['default_post_text'] ?? null;
        $settings->save();

        Notification::make()
            ->success()
            ->title('Instellingen opgeslagen')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncMoneybirdContacts')
                ->label('Klanten synchroniseren')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Klanten synchroniseren vanuit Moneybird')
                ->modalDescription('Alle contacten uit Moneybird worden opgehaald en lokaal opgeslagen of bijgewerkt. Dit kan even duren.')
                ->modalSubmitActionLabel('Synchroniseren')
                ->action(function (MoneybirdService $moneybirdService) {
                    try {
                        if (! $moneybirdService->isConfigured()) {
                            Notification::make()
                                ->warning()
                                ->title('Moneybird niet geconfigureerd')
                                ->body('Vul eerst de API Token en Administratie ID in.')
                                ->send();

                            return;
                        }

                        $result = $moneybirdService->syncAllContacts();

                        Notification::make()
                            ->success()
                            ->title('Klanten gesynchroniseerd')
                            ->body("{$result['created']} nieuw aangemaakt, {$result['updated']} bijgewerkt ({$result['total']} totaal).")
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Synchronisatie mislukt')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('testMoneybirdConnection')
                ->label('Test Moneybird Connectie')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action(function (MoneybirdService $moneybirdService) {
                    try {
                        // Check if configured
                        if (! $moneybirdService->isConfigured()) {
                            Notification::make()
                                ->warning()
                                ->title('Moneybird niet geconfigureerd')
                                ->body('Vul eerst de API Token en Administratie ID in.')
                                ->send();

                            return;
                        }

                        // Test the connection by fetching administration info
                        $result = $moneybirdService->testConnection();

                        if ($result['success']) {
                            Notification::make()
                                ->success()
                                ->title('Connectie succesvol!')
                                ->body($result['message'])
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Connectie mislukt')
                                ->body($result['message'])
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Fout bij testen connectie')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
