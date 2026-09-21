<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TeguranNotification extends Notification
{
    use Queueable;

    public $message;

    public $jadwal;

    /**
     * Create a new notification instance.
     */
    public function __construct($message, $jadwal = null)
    {
        $this->message = $message;
        $this->jadwal = $jadwal;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'jadwal_id' => $this->jadwal?->id,
            'mapel' => $this->jadwal?->mataPelajaran?->nama,
            'kelas' => $this->jadwal?->kelas?->nama,
        ];
    }
}
