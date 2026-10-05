<?php

namespace App\Notifications;

use App\Models\CorrectiveAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorrectiveActionDue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CorrectiveAction $action, public int $daysLeft) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->action->loadMissing(['finding.audit.unit', 'finding.checklist.indicator']);
        $unit = $a->finding->audit->unit->nama;
        $indikator = $a->finding->checklist?->indicator;

        [$subject, $headline] = match (true) {
            $this->daysLeft > 0 => ["[SPMI] RTL jatuh tempo {$this->daysLeft} hari lagi", "RTL berikut akan jatuh tempo dalam {$this->daysLeft} hari."],
            $this->daysLeft === 0 => ['[SPMI] RTL jatuh tempo hari ini', 'RTL berikut jatuh tempo hari ini.'],
            default => ['[SPMI] RTL terlambat '.abs($this->daysLeft).' hari', 'RTL berikut sudah melewati tenggat '.abs($this->daysLeft).' hari.'],
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Yth. '.$notifiable->name.',')
            ->line($headline)
            ->line('**Unit:** '.$unit)
            ->line('**Indikator:** '.($indikator ? "{$indikator->kode} — {$indikator->nama}" : '-'))
            ->line('**Tindakan:** '.$a->tindakan)
            ->line('**Target selesai:** '.$a->target_selesai->translatedFormat('d F Y'))
            ->line('**Status saat ini:** '.$a->status->label())
            ->action('Buka Daftar RTL', route('corrective-actions.index'))
            ->line('Perbarui status RTL menjadi "Selesai" setelah tindakan dilaksanakan agar dapat diverifikasi auditor.');

        return $this->daysLeft < 0 ? $mail->error() : $mail;
    }
}
