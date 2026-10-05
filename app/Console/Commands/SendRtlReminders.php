<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\OverdueActionsDigest;
use App\Services\RtlReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendRtlReminders extends Command
{
    protected $signature = 'spmi:rtl-reminders {--dry-run : Tampilkan daftar tanpa mengirim email}';

    protected $description = 'Kirim email pengingat tenggat Rencana Tindak Lanjut (RTL)';

    public function handle(RtlReminderService $service): int
    {
        $dry = $this->option('dry-run');
        $open = $service->open();
        $sent = 0;
        $rows = [];

        foreach ($open as $action) {
            if (! $service->shouldRemind($action)) {
                continue;
            }

            $days = $service->daysLeft($action);
            $recipients = $service->recipients($action);

            $rows[] = [
                $action->id,
                $action->finding->audit->unit->nama,
                $action->target_selesai->format('d/m/Y'),
                $days >= 0 ? "H-{$days}" : 'telat '.abs($days).' hari',
                $recipients->pluck('email')->implode(', ') ?: '(tidak ada penerima)',
            ];

            if (! $dry) {
                $sent += $service->send($action, $days) > 0 ? 1 : 0;
            }
        }

        $this->table(['RTL', 'Unit', 'Tenggat', 'Sisa', 'Penerima'], $rows);

        // Senin: ringkasan RTL terlambat untuk Admin SPMI.
        if (now()->isMonday()) {
            $lines = $open->filter(fn ($a) => $service->daysLeft($a) < 0)->map(fn ($a) => sprintf(
                '%s — %s — tenggat %s (telat %d hari)',
                $a->finding->audit->unit->nama,
                $a->finding->checklist?->indicator?->kode ?? '-',
                $a->target_selesai->format('d/m/Y'),
                abs($service->daysLeft($a)),
            ))->values()->all();

            if ($lines) {
                $this->info('Ringkasan mingguan: '.count($lines).' RTL terlambat.');
                if (! $dry) {
                    Notification::send(User::role('admin_spmi')->get(), new OverdueActionsDigest($lines));
                }
            }
        }

        $this->info($dry ? 'Dry-run: tidak ada email dikirim.' : "Selesai. {$sent} RTL diingatkan.");

        return self::SUCCESS;
    }
}
