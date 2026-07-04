<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Settings\GeneralSettings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class InfoRequiredNotification extends Notification
{

    public function __construct(
        public Lead $lead,
        public string $message
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
        $template = EmailTemplate::getByKey('info_required');
        
        $settings = app(GeneralSettings::class);
        $signature = $settings->email_signature ?? "Met vriendelijke groet,\n\nSchroeffundering Holland";
        $signatureHtml = nl2br(e($signature));
        
        if (!$template) {
            return $this->fallbackEmail($notifiable, $signatureHtml);
        }
        
        $rendered = $template->renderToHtml([
            'klant_naam' => $this->lead->contact_name,
            'bedrijf_naam' => $this->lead->company_name,
            'bericht' => $this->message,
            'bedrijf_email' => 'info@schroeffunderingholland.nl',
            'bedrijf_telefoon' => 'Zie onze website voor contactgegevens',
            'handtekening' => $signatureHtml,
        ]);
        
        return (new MailMessage)
            ->subject($rendered['subject'])
            ->replyTo('info@schroeffunderingholland.nl', 'Schroeffundering Holland')
            ->greeting('Dag ' . $this->lead->contact_name . ',')
            ->line(new HtmlString($rendered['content']))
            ->salutation('');
    }
    
    /**
     * Fallback email when template is not found.
     */
    private function fallbackEmail(object $notifiable, string $signatureHtml): MailMessage
    {
        return (new MailMessage)
            ->subject('Extra informatie nodig - ' . $this->lead->company_name)
            ->replyTo('info@schroeffunderingholland.nl', 'Schroeffundering Holland')
            ->greeting('Dag ' . $this->lead->contact_name . ',')
            ->line('Bedankt voor je interesse in onze diensten.')
            ->line('Om je een nauwkeurige offerte te kunnen maken, hebben we graag wat aanvullende informatie van je:')
            ->line('')
            ->line(new HtmlString(nl2br(e($this->message))))
            ->line('')
            ->line('Je kunt deze informatie sturen naar **info@schroeffunderingholland.nl**')
            ->line('')
            ->line('We horen graag van je!')
            ->salutation(new HtmlString($signatureHtml));
    }
}
