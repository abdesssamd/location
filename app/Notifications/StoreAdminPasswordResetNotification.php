<?php

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StoreAdminPasswordResetNotification extends Notification
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
            ->subject('Nouveau mot de passe — magasin « '.$this->store->name.' »')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Un nouveau mot de passe a été généré pour votre compte administrateur du magasin « '.$this->store->name.' ».')
            ->line('Email de connexion : '.$notifiable->email)
            ->line('Nouveau mot de passe : '.$this->password)
            ->action('Se connecter', route('login'))
            ->line('Pour votre sécurité, nous vous recommandons de le changer après la première connexion.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'store_admin_password_reset',
            'store_id' => $this->store->id,
            'store_name' => $this->store->name,
            'email' => $notifiable->email,
        ];
    }
}
