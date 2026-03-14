<?php

namespace App\Filament\Resources\Machines;

use App\Filament\Resources\Machines\Pages\CreateMachine;
use App\Filament\Resources\Machines\Pages\EditMachine;
use App\Filament\Resources\Machines\Pages\ListMachines;
use App\Models\Machine;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MachineResource extends Resource
{
    protected static ?string $model = Machine::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'Administratie';

    protected static ?string $navigationLabel = 'Machines';

    protected static ?string $modelLabel = 'machine';

    protected static ?string $pluralModelLabel = 'machines';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')
                    ->label('Naam')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Omschrijving')
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('active')
                    ->label('Actief')
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naam')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Omschrijving')
                    ->limit(50)
                    ->toggleable(),
                IconColumn::make('active')
                    ->label('Actief')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Aangemaakt')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMachines::route('/'),
            'create' => CreateMachine::route('/create'),
            'edit' => EditMachine::route('/{record}/edit'),
        ];
    }
}
