<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quote;
use App\Settings\GeneralSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    protected GeneralSettings $settings;

    public function __construct(GeneralSettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Generate PDF for a quote
     */
    public function generateQuotePdf(Quote $quote, bool $download = false): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote->load(['company', 'lead', 'items']),
            'settings' => $this->settings,
        ]);

        $filename = "offerte-{$quote->quote_number}.pdf";

        if ($download) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Generate PDF for an invoice
     */
    public function generateInvoicePdf(Invoice $invoice, bool $download = false): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice->load(['project.company', 'project.quote.items']),
            'settings' => $this->settings,
        ]);

        $filename = "factuur-{$invoice->invoice_number}.pdf";

        if ($download) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Save quote PDF to storage
     */
    public function saveQuotePdf(Quote $quote): string
    {
        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote->load(['company', 'lead', 'items']),
            'settings' => $this->settings,
        ]);

        $filename = "quotes/offerte-{$quote->quote_number}.pdf";
        Storage::put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Save invoice PDF to storage
     */
    public function saveInvoicePdf(Invoice $invoice): string
    {
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice->load(['project.company', 'project.quote.items']),
            'settings' => $this->settings,
        ]);

        $filename = "invoices/factuur-{$invoice->invoice_number}.pdf";
        Storage::put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Get quote PDF output for email attachment
     */
    public function getQuotePdfOutput(Quote $quote): string
    {
        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote->load(['company', 'lead', 'items']),
            'settings' => $this->settings,
        ]);

        return $pdf->output();
    }

    /**
     * Get quote PDF filename for email attachment
     */
    public function getQuotePdfFilename(Quote $quote): string
    {
        return "offerte-{$quote->quote_number}.pdf";
    }

    /**
     * Get invoice PDF output for email attachment
     */
    public function getInvoicePdfOutput(Invoice $invoice): string
    {
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice->load(['project.company', 'project.quote.items']),
            'settings' => $this->settings,
        ]);

        return $pdf->output();
    }

    /**
     * Get invoice PDF filename for email attachment
     */
    public function getInvoicePdfFilename(Invoice $invoice): string
    {
        return "factuur-{$invoice->invoice_number}.pdf";
    }

    /**
     * Generate packing slip PDF for a project
     */
    public function generatePackingSlipPdf(Project $project, bool $download = false): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('pdf.packing-slip', [
            'project' => $project->load(['company', 'items.itemable', 'quote.lead']),
            'settings' => $this->settings,
            'signatureBase64' => $this->getSignatureBase64($project),
        ]);

        $filename = $this->getPackingSlipPdfFilename($project);

        if ($download) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Save packing slip PDF to storage
     */
    public function savePackingSlipPdf(Project $project): string
    {
        $pdf = Pdf::loadView('pdf.packing-slip', [
            'project' => $project->load(['company', 'items.itemable', 'quote.lead']),
            'settings' => $this->settings,
            'signatureBase64' => $this->getSignatureBase64($project),
        ]);

        $filename = "packing-slips/pakbon-{$project->project_number}.pdf";
        Storage::put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Get packing slip PDF output for email attachment
     */
    public function getPackingSlipPdfOutput(Project $project): string
    {
        $pdf = Pdf::loadView('pdf.packing-slip', [
            'project' => $project->load(['company', 'items.itemable', 'quote.lead']),
            'settings' => $this->settings,
            'signatureBase64' => $this->getSignatureBase64($project),
        ]);

        return $pdf->output();
    }

    /**
     * Get packing slip PDF filename for email attachment
     */
    public function getPackingSlipPdfFilename(Project $project): string
    {
        return "pakbon-{$project->project_number}.pdf";
    }

    /**
     * Generate completion report PDF for a project (with all items, amounts, photos, both signatures)
     */
    public function generateCompletionReportPdf(Project $project, bool $download = false, ?string $clientName = null, ?string $technicianName = null): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('pdf.completion-report', [
            'project' => $project->load(['company', 'items.itemable', 'quote.lead']),
            'settings' => $this->settings,
            'clientSignatureBase64' => $this->getSignatureBase64($project),
            'technicianSignatureBase64' => $this->getTechnicianSignatureBase64($project),
            'clientName' => $clientName,
            'technicianName' => $technicianName,
        ]);

        $filename = $this->getCompletionReportPdfFilename($project);

        if ($download) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Get completion report PDF output for email attachment
     */
    public function getCompletionReportPdfOutput(Project $project): string
    {
        return $this->getCompletionReportPdfOutputWithNames($project, null, null);
    }

    /**
     * Get completion report PDF output with optional client name
     */
    public function getCompletionReportPdfOutputWithClientName(Project $project, ?string $clientName): string
    {
        return $this->getCompletionReportPdfOutputWithNames($project, $clientName, null);
    }

    /**
     * Get completion report PDF output with optional client name and technician name
     */
    public function getCompletionReportPdfOutputWithNames(Project $project, ?string $clientName, ?string $technicianName): string
    {
        $pdf = Pdf::loadView('pdf.completion-report', [
            'project' => $project->load(['company', 'items.itemable', 'quote.lead']),
            'settings' => $this->settings,
            'clientSignatureBase64' => $this->getSignatureBase64($project),
            'technicianSignatureBase64' => $this->getTechnicianSignatureBase64($project),
            'clientName' => $clientName,
            'technicianName' => $technicianName,
        ]);

        return $pdf->output();
    }

    /**
     * Get completion report PDF filename
     */
    public function getCompletionReportPdfFilename(Project $project): string
    {
        return "opleverrapport-{$project->project_number}.pdf";
    }

    /**
     * Get technician signature as base64 data URL for PDF embedding.
     */
    private function getTechnicianSignatureBase64(Project $project): ?string
    {
        $signature = $project->getFirstMedia('project-technician-signature');

        if (! $signature || ! file_exists($signature->getPath())) {
            return null;
        }

        $imageData = file_get_contents($signature->getPath());

        return 'data:image/png;base64,'.base64_encode($imageData);
    }

    /**
     * Get signature as base64 data URL for PDF embedding.
     */
    private function getSignatureBase64(Project $project): ?string
    {
        $signature = $project->getFirstMedia('project-signature');

        if (! $signature || ! file_exists($signature->getPath())) {
            return null;
        }

        $imageData = file_get_contents($signature->getPath());

        return 'data:image/png;base64,'.base64_encode($imageData);
    }
}
