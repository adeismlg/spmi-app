<?php

namespace App\Notifications;

use App\Models\CorrectiveAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorrectiveActionChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CorrectiveAction $action, public bool $created)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $action = $this->action->loadMissing([
            'pic',
            'finding.audit.cycle',
            'finding.audit.unit',
            'finding.checklist.indicator',
        ]);
        $audit = $action->finding->audit;

        return (new MailMessage)
            ->subject('[SPMI] RTL '.($this->created ? 'baru ditetapkan' : 'diperbarui').' — '.$audit->unit->nama)
            ->greeting('Yth. '.$notifiable->name.',')
            ->line($this->created
                ? 'Rencana Tindak Lanjut (RTL) baru telah dicatat untuk unit Anda.'
                : 'Rencana Tindak Lanjut (RTL) pada unit Anda telah diperbarui.')
            ->line('**Unit/Program Studi:** '.$audit->unit->nama)
            ->line('**Siklus:** '.$audit->cycle->nama.' ('.$audit->cycle->tahun.')')
            ->line('**Indikator:** '.($action->finding->checklist?->indicator?->kode ?? '-'))
            ->line('**Tindakan:** '.$action->tindakan)
            ->line('**Penanggung jawab:** '.($action->pic?->name ?? 'Belum ditentukan'))
            ->line('**Target selesai:** '.($action->target_selesai?->translatedFormat('d F Y') ?? 'Belum ditentukan'))
            ->line('**Status:** '.$action->status->label())
            ->action('Buka daftar tindak lanjut', route('corrective-actions.index'))
            ->line('Silakan koordinasikan pelaksanaan dan pemantauan tindak lanjut dengan PIC unit.');
    }
}
