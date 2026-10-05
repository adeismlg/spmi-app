<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Ringkasan mingguan RTL terlambat untuk Admin SPMI. */
class OverdueActionsDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param  array<int, string>  $lines */
    public function __construct(public array $lines) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[SPMI] '.count($this->lines).' RTL melewati tenggat')
            ->greeting('Yth. '.$notifiable->name.',')
            ->line('Berikut RTL pada siklus aktif yang belum selesai dan sudah melewati tenggat:');

        foreach ($this->lines as $line) {
            $mail->line('• '.$line);
        }

        return $mail->action('Buka Daftar RTL', route('corrective-actions.index'));
    }
}
