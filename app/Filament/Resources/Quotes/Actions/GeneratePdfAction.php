<?php

namespace App\Filament\Resources\Quotes\Actions;

use App\Models\Quote;
use App\Services\PdfService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class GeneratePdfAction extends Action
{
    public static function make(?string $name = 'generatePdf'): static
    {
        return parent::make($name)
            ->label('Genereer PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('info')
            ->action(function (Quote $record, PdfService $pdfService) {
                try {
                    // Generate and download the PDF
                    return $pdfService->generateQuotePdf($record, download: true);
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('PDF genereren mislukt')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
