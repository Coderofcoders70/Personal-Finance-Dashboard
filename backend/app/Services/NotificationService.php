<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{

    public function create(User $user, string $event, string $title, string $message, string $type = 'info'): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'event' => $event,
            'title' => $title,
            'message' => $message,
            'type' => $type,
        ]);
    }

    public function all(User $user)
    {
        return Notification::where('user_id', $user->id)
            ->latest()
            ->get();
    }

    public function markAsRead(Notification $notification): Notification
    {
        $notification->update([
            'is_read' => true,
        ]);

        return $notification;
    }

    public function markAllAsRead(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->update([
                'is_read' => true,
            ]);
    }

    public function delete(Notification $notification): void
    {
        $notification->delete();
    }
}
