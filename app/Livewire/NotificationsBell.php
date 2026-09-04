<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationsBell extends Component
{
    public bool $open = false;

    /** Fond sur lequel la cloche est posée : 'dark' (barre latérale) ou 'light' (header mobile). */
    public string $variant = 'dark';

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->open = false;
    }

    public function markAsRead(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->first()?->markAsRead();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $unread = auth()->user()->unreadNotifications()->limit(8)->get();
        $count = auth()->user()->unreadNotifications()->count();

        return view('livewire.notifications-bell', compact('unread', 'count'));
    }
}