<?php

namespace App\Filament\Resources\Leads\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class LeadActivityWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.resources.leads.widgets.lead-activity';

    protected int|string|array $columnSpan = 1;

    public ?Model $record = null;
}
