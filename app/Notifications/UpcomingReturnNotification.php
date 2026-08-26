<?php

namespace App\Notifications;

use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UpcomingReturnNotification extends Notification
{
    use Queueable;

    public function __construct(public Rental $rental)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'upcoming_return',
            'rental_id' => $this->rental->id,
            'rental_reference' => $this->rental->reference,
            'customer_name' => $this->rental->customer?->full_name,
            'end_date' => $this->rental->end_date?->toDateString(),
            'url' => route('rentals.show', $this->rental, false),
        ];
    }
}