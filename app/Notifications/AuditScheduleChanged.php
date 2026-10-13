<?php

namespace App\Notifications;

use App\Models\Audit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuditScheduleChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Audit $audit, public bool $created)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $audit = $this->audit->loadMissing(['cycle', 'unit', 'auditors']);
        $subject = $this->created ? 'Audit Mutu Internal dijadwalkan' : 'Jadwal Audit Mutu Internal diperbarui';
        $message = (new MailMessage)
            ->subject('[SPMI] '.$subject.' — '.$audit->unit->nama)
            ->greeting('Yth. '.$notifiable->name.',')
            ->line($this->created
                ? 'Anda menerima pemberitahuan karena terkait dengan jadwal Audit Mutu Internal berikut.'
                : 'Jadwal atau penugasan Audit Mutu Internal yang terkait dengan Anda telah diperbarui.')
            ->line('**Siklus:** '.$audit->cycle->nama.' ('.$audit->cycle->tahun.')')
            ->line('**Unit/Program Studi:** '.$audit->unit->nama)
            ->line('**Periode:** '.$audit->semester->label())
            ->line('**Desk Evaluation:** '.($audit->tanggal_desk_evaluation?->translatedFormat('d F Y') ?? 'Belum ditentukan'))
            ->line('**Visitasi:** '.($audit->tanggal_visitasi?->translatedFormat('d F Y') ?? 'Belum ditentukan'))
            ->line('**Auditor yang ditugaskan:** '.($audit->auditors->pluck('name')->implode(', ') ?: 'Belum ditentukan'));

        return $message
            ->action('Buka informasi audit', route('audits.show', $audit))
            ->line('Silakan periksa jadwal dan koordinasikan persiapan audit dengan unit terkait.');
    }
}
