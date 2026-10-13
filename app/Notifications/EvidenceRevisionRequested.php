<?php

namespace App\Notifications;

use App\Models\Evidence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EvidenceRevisionRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Evidence $evidence, public ?string $comment)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $evidence = $this->evidence->loadMissing([
            'selfEvaluation.cycle',
            'selfEvaluation.unit',
            'activity',
        ]);
        $evaluation = $evidence->selfEvaluation;

        return (new MailMessage)
            ->subject('[SPMI] Bukti memerlukan perbaikan — '.$evaluation->unit->nama)
            ->greeting('Yth. '.$notifiable->name.',')
            ->line('Auditor meminta perbaikan pada bukti dukung evaluasi diri.')
            ->line('**Unit/Program Studi:** '.$evaluation->unit->nama)
            ->line('**Siklus:** '.$evaluation->cycle->nama.' ('.$evaluation->cycle->tahun.')')
            ->line('**Kegiatan:** '.($evidence->activity?->nama ?? '-'))
            ->line('**Judul bukti:** '.$evidence->judul)
            ->line('**Catatan auditor:** '.$this->comment)
            ->action('Buka evaluasi diri', route('self-evaluations.show', $evaluation))
            ->line('Silakan unggah bukti pengganti setelah melakukan perbaikan.');
    }
}
