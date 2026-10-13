<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserAccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $user = $this->user->loadMissing(['unit', 'roles']);

        return (new MailMessage)
            ->subject('[SPMI] Akun pengguna baru terdaftar')
            ->greeting('Yth. Dosen Pembimbing,')
            ->line('Akun pengguna baru telah dibuat pada aplikasi SPMI.')
            ->line('**Nama:** '.$user->name)
            ->line('**Email akun:** '.$user->email)
            ->line('**Unit/Program Studi:** '.($user->unit?->nama ?? 'Tidak ditentukan'))
            ->line('**Peran:** '.$user->roles->pluck('name')->implode(', '))
            ->line('Pemberitahuan ini tidak memuat kata sandi akun.')
            ->action('Buka aplikasi SPMI', route('login'));
    }
}
