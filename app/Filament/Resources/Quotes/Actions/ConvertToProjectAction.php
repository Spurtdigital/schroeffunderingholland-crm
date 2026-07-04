<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Enums\InvoicingType;
use App\Enums\ProjectStatus;
use App\Enums\QuoteStatus;
use App\Enums\StockReservationStatus;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class ConvertToProjectAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'convertToProject';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Omzetten naar project');

        $this->icon('heroicon-o-wrench-screwdriver');

        $this->color('success');

        $this->modalHeading('Offerte omzetten naar project');

        $this->modalDescription('Kies de facturatiemethode, montagedatum en verwachte opleverdatum voor dit project.');

        $this->modalSubmitActionLabel('Project aanmaken');

        $this->modalWidth('lg');

        $this->visible(fn (Model $record): bool => $record->status === QuoteStatus::Approved
        );

        $this->form([
            Forms\Components\Select::make('invoicing_type')
                ->label('Facturatiemethode')
                ->options(InvoicingType::class)
                ->default(InvoicingType::Split5050)
                ->required()
                ->live()
                ->helperText(fn ($state) => $state instanceof InvoicingType ? $state->getDescription() : null)
                ->columnSpanFull(),

            Forms\Components\DatePicker::make('start_date')
                ->label('Montagedatum')
                ->helperText('Wanneer start de montage?')
                ->native(false)
                ->displayFormat('d-m-Y')
                ->minDate(now())
                ->columnSpanFull(),

            Forms\Components\DatePicker::make('expected_completion_date')
                ->label('Verwachte opleverdatum')
                ->helperText('Wanneer verwacht je dit project op te leveren?')
                ->native(false)
                ->displayFormat('d-m-Y')
                ->default(now()->addWeeks(2))
                ->minDate(now())
                ->columnSpanFull(),

            Forms\Components\Toggle::make('send_order_confirmation')
                ->label('Opdrachtbevestiging versturen naar klant')
                ->helperText('Stuur automatisch een opdrachtbevestiging per e-mail naar de klant')
                ->default(true)
                ->inline(false)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('notes')
                ->label('Opmerkingen')
                ->helperText('Optionele notities voor dit project')
                ->rows(3)
                ->columnSpanFull(),
        ]);

        $this->action(function (Model $record, array $data) {
            // Derive the project number from the quote number so both share the
            // same sequence (e.g. quote "E2026-0012" → project "P2026-0012").
            // Fall back to an auto-generated number when the quote still has a
            // concept placeholder (not yet synced with Moneybird).
            $projectNumber = Project::projectNumberFromQuote($record->quote_number)
                ?? Project::generateProjectNumber();

            // Create project from quote
            $project = Project::create([
                'company_id' => $record->company_id,
                'quote_id' => $record->id,
                'project_number' => $projectNumber,
                'status' => ProjectStatus::Active,
                'invoicing_type' => $data['invoicing_type'],
                'start_date' => $data['start_date'] ?? null,
                'completion_date' => $data['expected_completion_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'project_value' => $record->total,
            ]);

            // Copy quote items to project items FIRST
            // ProjectItemObserver creates fresh reservations with correct qty
            foreach ($record->items as $quoteItem) {
                $project->items()->create([
                    'quote_item_id' => $quoteItem->id,
                    'itemable_type' => $quoteItem->itemable_type,
                    'itemable_id' => $quoteItem->itemable_id,
                    'type' => $quoteItem->type,
                    'description' => $quoteItem->description,
                    'quantity' => $quoteItem->quantity,
                    'unit_price' => $quoteItem->unit_price,
                    'total' => $quoteItem->total,
                    'sort_order' => $quoteItem->sort_order,
                    'is_optional' => $quoteItem->is_optional,
                ]);
            }

            // Release old quote reservations (project items created fresh ones via observer)
            foreach ($record->stockReservations()->where('status', StockReservationStatus::Active)->get() as $reservation) {
                $reservation->release();
            }

            // Update quote status
            $record->update([
                'status' => QuoteStatus::Converted,
                'project_id' => $project->id,
            ]);

            // Update lead if exists
            if ($record->lead) {
                $record->lead->update([
                    'status' => \App\Enums\LeadStatus::Converted,
                ]);
            }

            // Send order confirmation if requested
            if ($data['send_order_confirmation'] ?? false) {
                $project->company->notify(
                    new \App\Notifications\OrderConfirmationNotification($project)
                );

                Notification::make()
                    ->success()
                    ->title('Opdrachtbevestiging verzonden')
                    ->body("Email verzonden naar {$project->company->email}")
                    ->send();
            }

            Notification::make()
                ->success()
                ->title('Offerte geconverteerd')
                ->body("Project {$project->project_number} is aangemaakt met {$data['invoicing_type']->getLabel()}. {$record->items->count()} items gekopieerd.")
                ->send();

            // Redirect to project edit page
            return redirect()->route('filament.admin.resources.projects.view', ['record' => $project]);
        });
    }
}
