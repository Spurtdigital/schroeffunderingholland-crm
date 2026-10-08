<?php

namespace App\Filament\Resources\SerialNumbers;

use App\Filament\Resources\SerialNumbers\Pages\ListSerialNumbers;
use App\Filament\Resources\SerialNumbers\Tables\SerialNumbersTable;
use App\Models\SerialNumber;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class SerialNumberResource extends Resource
{
    protected static ?string $model = SerialNumber::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-qr-code';

    protected static string|\UnitEnum|null $navigationGroup = 'Administratie';

    protected static ?string $navigationLabel = 'Serienummers';

    protected static ?string $modelLabel = 'serienummer';

    protected static ?string $pluralModelLabel = 'serienummers';

    protected static ?int $navigationSort = 7;

    public static function table(Table $table): Table
    {
        return SerialNumbersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSerialNumbers::route('/'),
        ];
    }
}
