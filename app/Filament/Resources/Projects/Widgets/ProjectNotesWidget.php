<?php

namespace App\Filament\Resources\Projects\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class ProjectNotesWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.resources.projects.widgets.project-notes';

    protected int|string|array $columnSpan = 3;

    public ?Model $record = null;
}
