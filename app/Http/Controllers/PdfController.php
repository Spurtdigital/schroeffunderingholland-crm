<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Services\PdfService;

class PdfController extends Controller
{
    public function __construct(
        protected PdfService $pdfService
    ) {}

    /**
     * Generate and download quote PDF
     */
    public function quotePdf(Quote $quote)
    {
        return $this->pdfService->generateQuotePdf($quote, download: true);
    }

    /**
     * Generate and download/stream packing slip PDF for a project
     */
    public function packingSlip(Project $project)
    {
        $download = !request()->query('stream');

        return $this->pdfService->generatePackingSlipPdf($project, download: $download);
    }

    /**
     * Generate and download order confirmation PDF for a project
     */
    public function orderConfirmationPdf(Project $project)
    {
        return $this->pdfService->generateOrderConfirmationPdf($project, download: true);
    }

    /**
     * Generate and download completion report PDF for a project
     */
    public function completionReport(Project $project)
    {
        // Gebruik opgeslagen media als die beschikbaar is
        $reportMedia = $project->getFirstMedia('project-completion-report');
        if ($reportMedia && file_exists($reportMedia->getPath())) {
            return response()->download(
                $reportMedia->getPath(),
                $reportMedia->file_name,
                ['Content-Type' => 'application/pdf']
            );
        }

        // Fallback: genereer on-the-fly
        return $this->pdfService->generateCompletionReportPdf($project, download: true);
    }

    /**
     * Generate and download purchase order PDF
     */
    public function purchaseOrderPdf(PurchaseOrder $purchaseOrder)
    {
        return $this->pdfService->generatePurchaseOrderPdf($purchaseOrder, download: true);
    }
}
