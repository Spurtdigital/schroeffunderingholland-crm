<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    /**
     * Het bedrijfsoverzicht hangt achter de feature flag
     * FEATURE_COMPANY_OVERVIEW. Staat die uit, dan is deze pagina
     * niet bereikbaar en wijzen alle klantlinks naar de bewerkpagina.
     */
    public static function canAccess(array $parameters = []): bool
    {
        return (bool) config('features.company_overview');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Bewerken'),
        ];
    }
}
