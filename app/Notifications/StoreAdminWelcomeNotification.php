<?php

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StoreAdminWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Store $store,
        public string $password,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre compte magasin « '.$this->store->name.' » est prêt')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Un compte a été créé pour vous sur le magasin « '.$this->store->name.' ».')
            ->line('Email de connexion : '.$notifiable->email)
            ->line('Mot de passe temporaire : '.$this->password)
            ->action('Se connecter', route('login'))
            ->line('Nous vous recommandons de changer votre mot de passe après la première connexion.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'store_admin_welcome',
            'store_id' => $this->store->id,
            'store_name' => $this->store->name,
            'email' => $notifiable->email,
        ];
    }
}
