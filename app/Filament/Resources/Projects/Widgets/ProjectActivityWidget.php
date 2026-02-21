<?php

namespace App\Filament\Resources\Projects\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class ProjectActivityWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.resources.projects.widgets.project-activity';

    protected int|string|array $columnSpan = 1;

    public ?Model $record = null;
}
