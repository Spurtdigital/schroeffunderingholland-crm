<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Project;
use App\Services\PdfService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class OrderConfirmationNotification extends Notification
{

    public function __construct(
        public Project $project,
        public bool $attachQuotePdf = true
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $template = EmailTemplate::getByKey('order_confirmation');
        
        if (!$template) {
            return $this->fallbackEmail($notifiable);
        }
        
        $quote = $this->project->quote;
        $company = $this->project->company;
        
        // Determine invoicing method text
        $facturatiemethode = match($this->project->invoicing_type->value) {
            'split_50_50' => 'Je ontvangt 2 facturen: 50% vooraf en 50% bij oplevering',
            'split_30_70' => 'Je ontvangt 2 facturen: 30% vooraf en 70% bij oplevering',
            'single' => 'Je ontvangt 1 factuur bij oplevering',
            default => 'Facturatie volgens afspraak'
        };
        
        // First invoice info if split
        $eersteFactuurInfo = '';
        if ($this->project->usesSplitInvoicing()) {
            $percentage = $this->project->getFirstInvoicePercentage();
            $eersteFactuurInfo = "De eerste factuur ({$percentage}%) ontvang je separaat per email.";
        }
        
        $rendered = $template->renderToHtml([
            'klant_naam' => $company->contact_name ?? 'relatie',
            'bedrijf_naam' => $company->name,
            'project_nummer' => $this->project->project_number,
            'offerte_nummer' => $quote?->displayNumber() ?? 'N/A',
            'totaal' => $quote ? '€' . number_format($quote->total, 2, ',', '.') : 'N/A',
            'montagedatum' => $this->project->start_date?->format('d-m-Y') ?? 'Nader te bepalen',
            'verwachte_oplevering' => $this->project->completion_date?->format('d-m-Y') ?? 'Nader te bepalen',
            'facturatiemethode' => $facturatiemethode,
            'eerste_factuur_info' => $eersteFactuurInfo,
        ]);
        
        $mail = (new MailMessage)
            ->subject($rendered['subject'])
            ->greeting('Dag ' . ($company->contact_name ?? 'relatie') . ',')
            ->line(new HtmlString($rendered['content']));
        
        // Attach order confirmation PDF if available and requested
        if ($this->attachQuotePdf && $quote) {
            $pdfService = app(PdfService::class);
            $mail->attachData(
                $pdfService->getOrderConfirmationPdfOutput($this->project),
                $pdfService->getOrderConfirmationPdfFilename($this->project),
                ['mime' => 'application/pdf']
            );
        }

        return $mail;
    }

    /**
     * Fallback email when template is not found.
     */
    private function fallbackEmail(object $notifiable): MailMessage
    {
        $quote = $this->project->quote;
        $company = $this->project->company;
        
        $mail = (new MailMessage)
            ->subject('Opdrachtbevestiging - Project ' . $this->project->project_number)
            ->greeting('Dag ' . ($company->contact_name ?? 'relatie') . ',')
            ->line('Hartelijk dank voor je opdracht! We bevestigen hierbij de volgende werkzaamheden:')
            ->line('');
            
        // Project details
        $mail->line('**Projectnummer:** ' . $this->project->project_number)
            ->line('**Bedrijf:** ' . $company->name);
            
        if ($quote && $quote->displayNumber()) {
            $mail->line('**Offerte:** ' . $quote->displayNumber())
                ->line('**Totaalbedrag:** €' . number_format($quote->total, 2, ',', '.'));
        }
        
        if ($this->project->start_date) {
            $mail->line('**Montagedatum:** ' . $this->project->start_date->format('d-m-Y'));
        }
        
        $mail->line('');
        
        // Invoicing method
        $invoicingText = match($this->project->invoicing_type->value) {
            'split_50_50' => 'Je ontvangt 2 facturen: 50% vooraf en 50% bij oplevering',
            'split_30_70' => 'Je ontvangt 2 facturen: 30% vooraf en 70% bij oplevering',
            'single' => 'Je ontvangt 1 factuur bij oplevering',
            default => 'Facturatie volgens afspraak'
        };
        
        $mail->line('**Facturatiemethode:** ' . $invoicingText);
        
        // First invoice notification if split
        if ($this->project->usesSplitInvoicing()) {
            $percentage = $this->project->getFirstInvoicePercentage();
            $mail->line('')
                ->line("De eerste factuur ({$percentage}%) ontvang je separaat per email.");
        }
        
        $mail->line('')
            ->line('**Wat gebeurt er nu?**')
            ->line('• We plannen de werkzaamheden in')
            ->line('• Je ontvangt bericht over de planning')
            ->line('• Bij vragen kun je altijd contact met ons opnemen')
            ->line('')
            ->line('Heb je vragen? Neem gerust contact met ons op!')
            ->salutation('Met vriendelijke groet,')
            ->salutation(config('mail.from.name', 'Schroeffundering Holland'));
        
        // Attach order confirmation PDF if available and requested
        if ($this->attachQuotePdf && $quote) {
            $pdfService = app(PdfService::class);
            $mail->attachData(
                $pdfService->getOrderConfirmationPdfOutput($this->project),
                $pdfService->getOrderConfirmationPdfFilename($this->project),
                ['mime' => 'application/pdf']
            );
        }
            
        return $mail;
    }
}
