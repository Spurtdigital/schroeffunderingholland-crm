<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Systeem';

    protected static ?string $modelLabel = 'gebruiker';

    protected static ?string $pluralModelLabel = 'Gebruikers';

    protected static ?string $navigationLabel = 'Gebruikers';

    protected static ?int $navigationSort = 98;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gebruiker')
                    ->schema([
                        TextInput::make('name')
                            ->label('Naam')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('email')
                            ->label('E-mailadres')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('password')
                            ->label('Wachtwoord')
                            ->password()
                            ->revealable()
                            ->minLength(12)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('Minimaal 12 tekens. Laat leeg om het huidige wachtwoord te behouden.')
                            ->columnSpan(2),

                        Select::make('roles')
                            ->label('Rol')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->required()
                            ->helperText('Alleen super_admin heeft toegang tot het paneel.')
                            ->columnSpan(2),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Naam')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rol')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_approved')
                    ->label('Goedgekeurd')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('mfa_enabled')
                    ->label('2FA')
                    ->getStateUsing(fn (User $record): bool => filled($record->app_authentication_secret))
                    ->boolean(),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Goedgekeurd op')
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aangemaakt')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Goedkeuring')
                    ->placeholder('Alle')
                    ->trueLabel('Goedgekeurd')
                    ->falseLabel('Openstaand'),
            ])
            ->recordActions([
                Actions\Action::make('approve')
                    ->label('Goedkeuren')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (User $record): bool => ! $record->is_approved)
                    ->requiresConfirmation()
                    ->modalHeading('Gebruiker goedkeuren')
                    ->modalDescription(fn (User $record): string => "Weet je zeker dat {$record->email} toegang mag krijgen tot het CRM?")
                    ->action(function (User $record): void {
                        $record->forceFill([
                            'is_approved' => true,
                            'approved_at' => now(),
                            'approved_by' => auth()->id(),
                        ])->save();
                    })
                    ->successNotificationTitle('Gebruiker goedgekeurd'),

                Actions\Action::make('resetMfa')
                    ->label('2FA resetten')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->visible(fn (User $record): bool => filled($record->app_authentication_secret))
                    ->requiresConfirmation()
                    ->modalHeading('2FA resetten')
                    ->modalDescription(fn (User $record): string => "De gebruiker moet na opnieuw inloggen direct een nieuwe authenticator instellen. Doorgaan voor {$record->email}?")
                    ->action(fn (User $record) => $record->saveAppAuthenticationSecret(null))
                    ->successNotificationTitle('2FA gereset'),

                Actions\EditAction::make(),

                Actions\DeleteAction::make()
                    ->visible(fn (User $record): bool => $record->id !== auth()->id()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
