<?php

namespace App\Livewire;

use Illuminate\Support\Collection;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;

        if ($this->open) {
            auth()->user()?->unreadNotifications->markAsRead();
        }
    }

    public function notifications(): Collection
    {
        return auth()->user()?->notifications()->latest()->limit(15)->get() ?? collect();
    }

    public function render()
    {
        return view('livewire.notification-bell', [
            'items' => $this->notifications(),
            'unread' => auth()->user()?->unreadNotifications()->count() ?? 0,
        ]);
    }
}
