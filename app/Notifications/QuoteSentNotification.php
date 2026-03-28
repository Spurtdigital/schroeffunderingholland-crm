<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class QuoteSentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Quote $quote
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
        $template = EmailTemplate::getByKey('quote_sent');

        if (! $template) {
            return $this->fallbackEmail($notifiable);
        }

        $rendered = $template->renderToHtml([
            'offerte_nummer' => $this->quote->displayNumber() ?? 'Concept',
            'klant_naam' => $this->quote->company->name,
            'contactpersoon' => $this->quote->company->contact_name ?? 'Onbekend',
            'email' => $this->quote->company->email ?? '',
            'telefoon' => $this->quote->company->phone ?? '',
            'subtotaal' => '€'.number_format($this->quote->subtotal, 2, ',', '.'),
            'btw' => '€'.number_format($this->quote->vat_amount, 2, ',', '.'),
            'totaal' => '€'.number_format($this->quote->total, 2, ',', '.'),
            'geldig_tot' => $this->quote->valid_until?->format('d-m-Y') ?? '',
            'status' => $this->quote->status->getLabel(),
        ]);

        return (new MailMessage)
            ->subject($rendered['subject'])
            ->greeting('Hallo '.($notifiable->name ?? 'team').',')
            ->line(new HtmlString($rendered['content']))
            ->action('Bekijk offerte', url('/admin/quotes/'.$this->quote->id));
    }

    /**
     * Fallback email when template is not found.
     */
    private function fallbackEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Offerte verzonden - '.($this->quote->displayNumber() ?? 'Concept'))
            ->greeting('Hallo '.($notifiable->name ?? 'team').',')
            ->line('Een nieuwe offerte is succesvol verstuurd naar de klant.')
            ->line('')
            ->line('**📋 Offerte details**')
            ->line('**Nummer:** '.($this->quote->displayNumber() ?? 'Concept'))
            ->line('**Klant:** '.$this->quote->company->name)
            ->line('**Contactpersoon:** '.($this->quote->company->contact_name ?? 'Onbekend'))
            ->lineIf($this->quote->company->email, '**Email:** '.$this->quote->company->email)
            ->lineIf($this->quote->company->phone, '**Telefoon:** '.$this->quote->company->phone)
            ->line('')
            ->line('**💰 Financiële gegevens**')
            ->line('**Subtotaal:** €'.number_format($this->quote->subtotal, 2, ',', '.'))
            ->line('**BTW (21%):** €'.number_format($this->quote->vat_amount, 2, ',', '.'))
            ->line('**Totaal:** €'.number_format($this->quote->total, 2, ',', '.'))
            ->line('')
            ->line('**📅 Status & Planning**')
            ->line('**Geldig tot:** '.$this->quote->valid_until?->format('d-m-Y'))
            ->line('**Status:** '.$this->quote->status->getLabel())
            ->lineIf($this->quote->sent_at, '**Verzonden op:** '.$this->quote->sent_at->format('d-m-Y H:i'))
            ->line('')
            ->action('Bekijk offerte', url('/admin/quotes/'.$this->quote->id))
            ->line('')
            ->line('✅ De voorraad voor deze offerte is automatisch gereserveerd.')
            ->line('⏰ Vergeet niet om de klant op te volgen als er binnen een week geen reactie is!');
    }
}
