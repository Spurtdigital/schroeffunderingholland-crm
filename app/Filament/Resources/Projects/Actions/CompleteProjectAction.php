<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Enums\ProjectStatus;
use App\Filament\Forms\Components\SignaturePad;
use App\Models\Project;
use App\Notifications\ProjectCompletedNotification;
use App\Services\PdfService;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\HtmlString;

class CompleteProjectAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'completeProject';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Project afronden');
        $this->icon('heroicon-o-check-circle');
        $this->color('success');

        $this->visible(fn (Project $record) => ! $record->isCompleted());

        $this->modalHeading('Project afronden');
        $this->modalSubmitActionLabel('Project afronden & mail versturen');
        $this->modalIcon('heroicon-o-check-circle');
        $this->modalWidth('2xl');

        $this->form(function (Project $record) {
            $company = $record->company;
            $photoCount = $record->getMedia('project-photos')->count();

            // Eager load items
            $record->load('items');
            $items = $record->items->sortBy('sort_order');
            $grandTotal = $items->sum(fn ($i) => (float) $i->total);

            // Info regels
            $lines = [];
            $lines[] = "Project <strong>{$record->project_number}</strong> wordt afgerond.";
            if ($company?->email) {
                $lines[] = "Bevestigingsmail wordt verstuurd naar <strong>{$company->email}</strong>.";
            } else {
                $lines[] = '<em>Geen e-mailadres bekend — er wordt geen mail verstuurd.</em>';
            }
            if ($photoCount > 0) {
                $lines[] = "Er staan al <strong>{$photoCount} foto('s)</strong> bij dit project. Je kunt hieronder extra foto's toevoegen.";
            }

            // Items tabel HTML
            $tableRows = '';
            foreach ($items as $item) {
                $type = $item->type instanceof \App\Enums\QuoteItemType
                    ? $item->type->getLabel()
                    : $item->type;
                $description = e($item->description ?? '—');
                $quantity = rtrim(rtrim(number_format((float) $item->quantity, 2, ',', '.'), '0'), ',');
                $unitPrice = '€ '.number_format((float) $item->unit_price, 2, ',', '.');
                $total = '€ '.number_format((float) $item->total, 2, ',', '.');

                $tableRows .= '<tr class="border-b border-gray-100 dark:border-gray-700">'
                    ."<td class=\"py-1 pr-3 text-gray-500 dark:text-gray-400 whitespace-nowrap\">{$type}</td>"
                    ."<td class=\"py-1 pr-3\">{$description}</td>"
                    ."<td class=\"py-1 pr-3 text-right tabular-nums whitespace-nowrap\">{$quantity}</td>"
                    ."<td class=\"py-1 pr-3 text-right tabular-nums whitespace-nowrap\">{$unitPrice}</td>"
                    ."<td class=\"py-1 text-right tabular-nums whitespace-nowrap font-medium\">{$total}</td>"
                    .'</tr>';
            }

            $totalFormatted = '€ '.number_format($grandTotal, 2, ',', '.');
            $tableRows .= '<tr>'
                .'<td colspan="4" class="pt-2 text-right font-semibold text-gray-700 dark:text-gray-300 pr-3">Totaal excl. btw</td>'
                ."<td class=\"pt-2 text-right tabular-nums font-bold\">{$totalFormatted}</td>"
                .'</tr>';

            $itemsTable = $items->isNotEmpty()
                ? '<div class="mt-4">'
                  .'<p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Projectitems</p>'
                  .'<div class="overflow-x-auto">'
                  .'<table class="w-full text-sm">'
                  .'<thead>'
                  .'<tr class="border-b border-gray-200 dark:border-gray-600 text-left text-xs text-gray-400 uppercase tracking-wide">'
                  .'<th class="pb-1 pr-3 font-medium">Type</th>'
                  .'<th class="pb-1 pr-3 font-medium">Omschrijving</th>'
                  .'<th class="pb-1 pr-3 font-medium text-right">Aantal</th>'
                  .'<th class="pb-1 pr-3 font-medium text-right">Stukprijs</th>'
                  .'<th class="pb-1 font-medium text-right">Totaal</th>'
                  .'</tr>'
                  .'</thead>'
                  ."<tbody>{$tableRows}</tbody>"
                  .'</table>'
                  .'</div>'
                  .'</div>'
                : '';

            return [
                Wizard::make([
                    Step::make("Foto's")
                        ->icon('heroicon-o-camera')
                        ->schema([
                            FileUpload::make('photos')
                                ->label("Foto's van het project")
                                ->helperText('Voeg foto\'s toe die als bijlage worden meegestuurd in de bevestigingsmail.')
                                ->multiple()
                                ->image()
                                ->imagePreviewHeight('120')
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                                ->maxSize(10240)
                                ->disk('local')
                                ->directory('tmp-project-photos')
                                ->visibility('private'),
                        ]),

                    Step::make('Checklist')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->schema([
                            Placeholder::make('checklist_info')
                                ->label('')
                                ->content(new HtmlString(
                                    '<p class="text-sm text-gray-600 dark:text-gray-400">Vink hieronder de van toepassing zijnde punten aan.</p>'
                                )),
                            Checkbox::make('hoogtemaat_klant')
                                ->label('Hoogtemaat klant')
                                ->default(fn (Project $record) => (bool) $record->hoogtemaat_klant),
                            Checkbox::make('uitgezet_door_klant')
                                ->label('Uitgezet door de klant')
                                ->default(fn (Project $record) => (bool) $record->uitgezet_door_klant),
                            Checkbox::make('kans_op_zetting')
                                ->label('Kans op zetting niet uit te sluiten')
                                ->default(fn (Project $record) => (bool) $record->kans_op_zetting),
                        ]),

                    Step::make('Samenvatting')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Placeholder::make('summary')
                                ->label('')
                                ->content(new HtmlString(
                                    '<div class="space-y-1 text-sm">'
                                    .implode('', array_map(fn ($l) => "<p>{$l}</p>", $lines))
                                    .'</div>'
                                    .$itemsTable
                                )),
                        ]),

                    Step::make('Handtekening monteur')
                        ->icon('heroicon-o-pencil')
                        ->schema([
                            Placeholder::make('technician_signature_info')
                                ->label('')
                                ->content(new HtmlString(
                                    '<p class="text-sm text-gray-600 dark:text-gray-400">Zet hieronder uw handtekening als monteur.</p>'
                                )),
                            TextInput::make('technician_name')
                                ->label('Naam monteur')
                                ->placeholder('Volledige naam')
                                ->default(fn () => auth()->user()?->name)
                                ->required(),
                            SignaturePad::make('technician_signature')
                                ->label('Handtekening monteur')
                                ->required(),
                        ]),

                    Step::make('Handtekening klant')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Placeholder::make('client_signature_info')
                                ->label('')
                                ->content(new HtmlString(
                                    '<p class="text-sm text-gray-600 dark:text-gray-400">Laat de klant hieronder tekenen ter bevestiging van de oplevering.</p>'
                                )),
                            TextInput::make('client_name')
                                ->label('Naam klant')
                                ->placeholder('Volledige naam')
                                ->required(),
                            SignaturePad::make('signature')
                                ->label('Handtekening klant')
                                ->required(),
                        ]),
                ])
                    ->submitAction(new HtmlString('')), // submit knop zit buiten wizard
            ];
        });

        $this->action(function (array $data, Project $record) {
            // Sla geüploade foto's op als media
            if (! empty($data['photos'])) {
                foreach ((array) $data['photos'] as $tmpPath) {
                    $fullPath = storage_path('app/'.$tmpPath);
                    if (file_exists($fullPath)) {
                        $record
                            ->addMedia($fullPath)
                            ->toMediaCollection('project-photos');
                    }
                }
            }

            // Sla monteur handtekening op als media
            if (! empty($data['technician_signature'])) {
                $this->saveSignatureToCollection($record, $data['technician_signature'], 'project-technician-signature', 'handtekening-monteur');
            }

            // Sla klant handtekening op als media
            if (! empty($data['signature'])) {
                $this->saveSignatureToCollection($record, $data['signature'], 'project-signature', 'handtekening-klant');
            }

            // Zet status op Completed (observer handelt completion_date af)
            $record->update([
                'status' => ProjectStatus::Completed,
                'hoogtemaat_klant' => (bool) ($data['hoogtemaat_klant'] ?? false),
                'uitgezet_door_klant' => (bool) ($data['uitgezet_door_klant'] ?? false),
                'kans_op_zetting' => (bool) ($data['kans_op_zetting'] ?? false),
            ]);

            // Frish de record zodat media beschikbaar is voor de PDF
            $record = $record->fresh();

            // Genereer opleverrapport PDF en sla op als media
            $pdfService = app(PdfService::class);
            $clientName = $data['client_name'] ?? null;
            $technicianName = $data['technician_name'] ?? null;

            try {
                $pdfOutput = $pdfService->getCompletionReportPdfOutputWithNames($record, $clientName, $technicianName);
                $pdfFilename = $pdfService->getCompletionReportPdfFilename($record);

                $tmpPdf = tempnam(sys_get_temp_dir(), 'report_').'.pdf';
                file_put_contents($tmpPdf, $pdfOutput);

                try {
                    $record
                        ->addMedia($tmpPdf)
                        ->usingFileName($pdfFilename)
                        ->usingName("Opleverrapport {$record->project_number}")
                        ->toMediaCollection('project-completion-report');
                } finally {
                    if (file_exists($tmpPdf)) {
                        unlink($tmpPdf);
                    }
                }
            } catch (\Exception $e) {
                // PDF generatie mislukt — ga toch door
                \Illuminate\Support\Facades\Log::warning("Opleverrapport PDF generatie mislukt: {$e->getMessage()}");
            }

            // Stuur mail naar klant
            $company = $record->fresh()->company;
            if ($company && $company->email) {
                try {
                    $company->notify(new ProjectCompletedNotification($record));
                } catch (\Exception $e) {
                    Notification::make()
                        ->warning()
                        ->title('Project afgerond, mail mislukt')
                        ->body("Project {$record->project_number} is afgerond, maar de bevestigingsmail kon niet worden verstuurd: {$e->getMessage()}")
                        ->send();

                    return;
                }
            }

            $photoCount = $record->getMedia('project-photos')->count();
            $photoText = $photoCount > 0 ? " ({$photoCount} foto's meegestuurd)" : '';

            Notification::make()
                ->success()
                ->title('Project afgerond')
                ->body("Project {$record->project_number} is afgerond en een bevestigingsmail is verstuurd naar {$company?->email}{$photoText}.")
                ->send();
        });
    }

    /**
     * Sla base64 PNG handtekening op in een specifieke media collectie.
     */
    private function saveSignatureToCollection(Project $project, string $base64, string $collection, string $filePrefix): void
    {
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $imageData = base64_decode($imageData);

        if ($imageData === false) {
            return;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'sig_').'.png';
        file_put_contents($tmpFile, $imageData);

        try {
            $project
                ->addMedia($tmpFile)
                ->usingFileName("{$filePrefix}-{$project->project_number}.png")
                ->usingName(ucfirst(str_replace('-', ' ', $filePrefix))." {$project->project_number}")
                ->toMediaCollection($collection);
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }
}
