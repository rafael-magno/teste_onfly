<?php

namespace App\Notifications;

use App\Models\TravelOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelOrderStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly TravelOrder $travelOrder)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Pedido de viagem #{$this->travelOrder->id} — {$this->travelOrder->status->label()}")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("O status do seu pedido de viagem foi atualizado para: {$this->travelOrder->status->label()}.")
            ->line(
                "Destino: {$this->travelOrder->destination_city}, ".
                "{$this->travelOrder->destination_state} - {$this->travelOrder->destination_country}"
            )
            ->line("Data de ida: {$this->travelOrder->departure_date->format('d/m/Y')}")
            ->line("Data de volta: {$this->travelOrder->return_date->format('d/m/Y')}");
    }
}
