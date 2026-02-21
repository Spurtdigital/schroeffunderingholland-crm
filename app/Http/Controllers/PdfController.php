<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\PdfService;

class PdfController extends Controller
{
    public function __construct(
        protected PdfService $pdfService
    ) {}

    /**
     * Generate and download packing slip PDF for a project
     */
    public function packingSlip(Project $project)
    {
        return $this->pdfService->generatePackingSlipPdf($project, download: true);
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
}
