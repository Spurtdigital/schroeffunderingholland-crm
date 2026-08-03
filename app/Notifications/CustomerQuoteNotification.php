<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Quote;
use App\Services\PdfService;
use App\Settings\GeneralSettings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class CustomerQuoteNotification extends Notification
{
    public function __construct(
        public Quote $quote,
        public ?string $contactPerson = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pdfService = app(PdfService::class);
        $displayNumber = $this->quote->displayNumber() ?? 'Concept';
        $greetingName = $this->contactPerson
            ?: $this->quote->company?->contact_person
            ?: 'relatie';

        $settings = app(GeneralSettings::class);
        $signature = $settings->email_signature ?? 'Met vriendelijke groet, Schroeffundering Holland';

        $template = EmailTemplate::getByKey('quote_customer');

        // Generate a signed URL valid for 30 days
        $viewUrl = URL::signedRoute(
            'quotes.public.show',
            ['quote' => $this->quote->id],
            now()->addDays(30)
        );

        $mail = (new MailMessage);

        if ($template) {
            $rendered = $template->renderToHtml([
                'klant_naam' => $greetingName,
                'offerte_nummer' => $displayNumber,
                'totaal' => '€ '.number_format($this->quote->total, 2, ',', '.'),
                'handtekening' => $signature,
                'bekijk_link' => $viewUrl,
            ]);

            $mail->subject($rendered['subject'])
                ->greeting('Dag '.$greetingName.',')
                ->line(new HtmlString($rendered['content']))
                ->action('Offerte bekijken en ondertekenen', $viewUrl)
                ->salutation('');
        } else {
            $mail->subject('Offerte '.$displayNumber)
                ->greeting('Dag '.$greetingName.',')
                ->line('Bijgaand ontvang je onze offerte voor de aangevraagde werkzaamheden.')
                ->line('Je vindt de offerte als PDF in de bijlage van deze e-mail.')
                ->action('Offerte bekijken en ondertekenen', $viewUrl)
                ->line('Via bovenstaande knop kun je de offerte online bekijken en digitaal ondertekenen.')
                ->line('Heb je vragen over de offerte? Neem dan gerust contact met ons op.')
                ->salutation($signature);
        }

        // Attach quote PDF
        $mail->attachData(
            $pdfService->getQuotePdfOutput($this->quote),
            $pdfService->getQuotePdfFilename($this->quote),
            ['mime' => 'application/pdf']
        );

        // Attach algemene voorwaarden if uploaded
        if (! empty($settings->terms_pdf)) {
            $path = Storage::disk('public')->path($settings->terms_pdf);
            if (file_exists($path)) {
                $mail->attach($path, [
                    'as'   => 'algemene-voorwaarden.pdf',
                    'mime' => 'application/pdf',
                ]);
            }
        }

        return $mail;
    }
}
