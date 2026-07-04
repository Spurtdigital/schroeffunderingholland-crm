<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    public function __construct(
        public Product $product
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
        $available = $this->product->available_stock;
        $minLevel  = $this->product->min_stock_level;

        $subject = $available <= 0
            ? "Voorraad op: {$this->product->name}"
            : "Lage voorraad: {$this->product->name}";

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Voorraadmelding')
            ->line("Het voorraadniveau van **{$this->product->name}** is onder het minimumniveau gekomen.")
            ->line('')
            ->line('**Artikel:** '.$this->product->name)
            ->lineIf($this->product->sku, '**Artikelnummer:** '.$this->product->sku)
            ->line('**Huidige voorraad:** '.$this->product->stock_quantity)
            ->line('**Gereserveerd:** '.$this->product->reserved_quantity)
            ->line('**Beschikbaar:** '.$available)
            ->line('**Minimumniveau:** '.$minLevel)
            ->line('')
            ->action('Bekijk product', url('/admin/products/'.$this->product->id));
    }
}
