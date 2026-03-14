<?php

namespace App\Filament\Resources\PurchaseOrders;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Resources\PurchaseOrders\RelationManagers\PurchaseOrderItemsRelationManager;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|\UnitEnum|null $navigationGroup = 'Administratie';

    protected static ?string $navigationLabel = 'Inkooporders';

    protected static ?string $modelLabel = 'inkooporder';

    protected static ?string $pluralModelLabel = 'inkooporders';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Inkooporder details')
                    ->columns(3)
                    ->schema([
                        TextInput::make('order_number')
                            ->label('Ordernummer')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Automatisch gegenereerd')
                            ->columnSpan(1),

                        Placeholder::make('project_link')
                            ->label('Gekoppeld project')
                            ->content(fn ($record) => $record?->project
                                ? new \Illuminate\Support\HtmlString(
                                    '<a href="' . route('filament.admin.resources.projects.view', ['record' => $record->project_id]) . '" class="text-primary-600 hover:underline font-medium">'
                                    . e($record->project->project_number)
                                    . '</a>'
                                )
                                : '-'
                            )
                            ->hidden(fn ($record) => ! $record?->project_id)
                            ->columnSpan(2),

                        Select::make('supplier_id')
                            ->label('Leverancier')
                            ->options(fn () => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->columnSpan(2),

                        Select::make('status')
                            ->label('Status')
                            ->options(PurchaseOrderStatus::class)
                            ->default(PurchaseOrderStatus::Draft)
                            ->required()
                            ->columnSpan(1),

                        Select::make('language')
                            ->label('Taal PDF')
                            ->options([
                                'nl' => 'Nederlands',
                                'en' => 'Engels',
                                'de' => 'Duits',
                            ])
                            ->default('nl')
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('reference')
                            ->label('Referentie')
                            ->maxLength(255)
                            ->columnSpan(1),

                        DatePicker::make('order_date')
                            ->label('Besteldatum')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d-m-Y')
                            ->columnSpan(1),

                        DatePicker::make('expected_delivery_date')
                            ->label('Verwachte leverdatum')
                            ->native(false)
                            ->displayFormat('d-m-Y')
                            ->columnSpan(1),

                        Textarea::make('notes')
                            ->label('Opmerkingen')
                            ->rows(3)
                            ->columnSpan(3),
                    ]),

                Section::make('Leveringsadres')
                    ->description('Vul in als het leveringsadres afwijkt van het bedrijfsadres')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('delivery_name')
                            ->label('Naam')
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('delivery_street')
                            ->label('Straat + huisnummer')
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('delivery_postal_code')
                            ->label('Postcode')
                            ->maxLength(20)
                            ->columnSpan(1),

                        TextInput::make('delivery_city')
                            ->label('Stad')
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('delivery_country')
                            ->label('Land')
                            ->maxLength(255)
                            ->default('Nederland')
                            ->columnSpan(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Ordernummer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Leverancier')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('language')
                    ->label('Taal')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'nl' => 'NL',
                        'en' => 'EN',
                        'de' => 'DE',
                        default => strtoupper($state),
                    })
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('order_date')
                    ->label('Besteldatum')
                    ->date('d-m-Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label('Verwacht')
                    ->date('d-m-Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Totaal')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aangemaakt')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(PurchaseOrderStatus::class),

                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Leverancier')
                    ->options(fn () => Supplier::orderBy('name')->pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('language')
                    ->label('Taal')
                    ->options([
                        'nl' => 'Nederlands',
                        'en' => 'Engels',
                        'de' => 'Duits',
                    ]),
            ])
            ->recordActions([
                Actions\ViewAction::make()->label('Bekijken'),
                Actions\EditAction::make()->label('Bewerken'),
                Actions\DeleteAction::make()->label('Verwijderen'),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->label('Verwijderen'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PurchaseOrderItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'view'   => ViewPurchaseOrder::route('/{record}'),
            'edit'   => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
