<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class QuoteApprovedNotification extends Notification implements ShouldQueue
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
        $template = EmailTemplate::getByKey('quote_approved');

        if (! $template) {
            return $this->fallbackEmail($notifiable);
        }

        $rendered = $template->renderToHtml([
            'team_naam' => $notifiable->name ?? 'team',
            'offerte_nummer' => $this->quote->quote_number,
            'klant_naam' => $this->quote->company->name,
            'contactpersoon' => $this->quote->company->contact_name ?? 'Onbekend',
            'email' => $this->quote->company->email ?? '',
            'telefoon' => $this->quote->company->phone ?? '',
            'totaal' => '€'.number_format($this->quote->total, 2, ',', '.'),
            'goedgekeurd_op' => $this->quote->approved_at?->format('d-m-Y H:i') ?? '',
        ]);

        return (new MailMessage)
            ->subject($rendered['subject'])
            ->greeting('Fantastisch nieuws, '.($notifiable->name ?? 'team').'!')
            ->line(new HtmlString($rendered['content']))
            ->action('Omzetten naar project', url('/admin/quotes/'.$this->quote->id));
    }

    /**
     * Fallback email when template is not found.
     */
    private function fallbackEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Offerte goedgekeurd - '.$this->quote->quote_number)
            ->greeting('Fantastisch nieuws, '.($notifiable->name ?? 'team').'!')
            ->line('Een offerte is zojuist goedgekeurd door de klant!')
            ->line('')
            ->line('**📋 Offerte details**')
            ->line('**Nummer:** '.$this->quote->quote_number)
            ->line('**Klant:** '.$this->quote->company->name)
            ->line('**Contactpersoon:** '.($this->quote->company->contact_name ?? 'Onbekend'))
            ->lineIf($this->quote->company->email, '**Email:** '.$this->quote->company->email)
            ->lineIf($this->quote->company->phone, '**Telefoon:** '.$this->quote->company->phone)
            ->line('')
            ->line('**💰 Orderwaarde**')
            ->line('**Totaalbedrag:** €'.number_format($this->quote->total, 2, ',', '.'))
            ->lineIf($this->quote->approved_at, '**Goedgekeurd op:** '.$this->quote->approved_at->format('d-m-Y H:i'))
            ->line('')
            ->line('**⚡ Volgende stappen**')
            ->line('1️⃣ Converteer de offerte naar een project')
            ->line('2️⃣ Plan de werkzaamheden in')
            ->line('3️⃣ Verstuur opdrachtbevestiging naar klant')
            ->line('4️⃣ Genereer de eerste factuur')
            ->line('')
            ->action('Omzetten naar project', url('/admin/quotes/'.$this->quote->id))
            ->line('')
            ->line('💡 **Tip:** Klik op de knop hierboven en kies "Omzetten naar project" om direct aan de slag te gaan!');
    }
}
