<?php

namespace App\Services;

use App\Enums\ActionStatus;
use App\Models\CorrectiveAction;
use App\Models\User;
use App\Notifications\CorrectiveActionDue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class RtlReminderService
{
    /** Pengingat dikirim saat sisa hari = salah satu nilai ini (H-7, H-3, H-1, hari-H). */
    public const MILESTONES = [7, 3, 1, 0];

    /** Setelah lewat tenggat, ulangi pengingat setiap N hari. */
    public const OVERDUE_EVERY_DAYS = 3;

    /** RTL belum selesai pada siklus aktif yang punya tenggat. */
    public function open(): Collection
    {
        return CorrectiveAction::query()
            ->with(['pic', 'finding.audit.unit', 'finding.checklist.indicator'])
            ->whereNotNull('target_selesai')
            ->whereNotIn('status', [ActionStatus::Selesai->value, ActionStatus::Terverifikasi->value])
            ->whereHas('finding.audit.cycle', fn ($q) => $q->where('is_active', true))
            ->get();
    }

    /** Sisa hari ke tenggat (negatif = terlambat). */
    public function daysLeft(CorrectiveAction $action, ?Carbon $today = null): int
    {
        $today = ($today ?? now())->copy()->startOfDay();

        return (int) round($today->diffInDays($action->target_selesai->copy()->startOfDay(), false));
    }

    public function shouldRemind(CorrectiveAction $action, ?Carbon $today = null): bool
    {
        $today ??= now();

        if ($action->last_reminded_at?->isSameDay($today)) {
            return false; // sudah diingatkan hari ini
        }

        $days = $this->daysLeft($action, $today);

        return in_array($days, self::MILESTONES, true)
            || ($days < 0 && abs($days) % self::OVERDUE_EVERY_DAYS === 0);
    }

    /** Penerima: PIC; bila kosong, seluruh pengguna auditee pada unit terkait. */
    public function recipients(CorrectiveAction $action): Collection
    {
        if ($action->pic) {
            return collect([$action->pic]);
        }

        return User::role('auditee')->where('unit_id', $action->finding->audit->unit_id)->get();
    }

    /** Kirim email pengingat. Mengembalikan jumlah penerima. */
    public function send(CorrectiveAction $action, ?int $daysLeft = null): int
    {
        $recipients = $this->recipients($action)->filter(fn ($u) => filled($u->email));

        if ($recipients->isEmpty()) {
            return 0;
        }

        Notification::send($recipients, new CorrectiveActionDue($action, $daysLeft ?? $this->daysLeft($action)));
        $action->forceFill(['last_reminded_at' => now()])->save();

        return $recipients->count();
    }
}
