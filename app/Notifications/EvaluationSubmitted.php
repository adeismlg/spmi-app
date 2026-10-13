<?php

namespace App\Notifications;

use App\Enums\Semester;
use App\Models\Cycle;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EvaluationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Cycle $cycle,
        public Unit $unit,
        public int $semester,
        public int $indicatorCount,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $greeting = $notifiable instanceof User ? 'Yth. '.$notifiable->name.',' : 'Yth. Bapak/Ibu,';

        return (new MailMessage)
            ->subject('[SPMI] Evaluasi diri diajukan — '.$this->unit->nama)
            ->greeting($greeting)
            ->line('Evaluasi diri unit telah diajukan secara permanen dan siap ditindaklanjuti.')
            ->line('**Siklus:** '.$this->cycle->nama.' ('.$this->cycle->tahun.')')
            ->line('**Unit/Program Studi:** '.$this->unit->nama)
            ->line('**Periode:** '.Semester::from($this->semester)->label())
            ->line('**Jumlah indikator yang diajukan:** '.$this->indicatorCount)
            ->action('Buka Audit Mutu Internal', route('audits.index'))
            ->line('Silakan periksa kelengkapan evaluasi dan bukti dukung pada aplikasi SPMI.');
    }
}
