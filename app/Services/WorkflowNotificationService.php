<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\CorrectiveAction;
use App\Models\Cycle;
use App\Models\Evidence;
use App\Models\SelfEvaluation;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\AuditScheduleChanged;
use App\Notifications\CorrectiveActionChanged;
use App\Notifications\EvaluationSubmitted;
use App\Notifications\EvidenceRevisionRequested;
use App\Notifications\UserAccountCreated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

class WorkflowNotificationService
{
    public function notifyAuditSchedule(Audit $audit, bool $created, array $previousAuditorIds = []): void
    {
        $recipients = $this->auditRecipients($audit, $previousAuditorIds);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new AuditScheduleChanged($audit, $created));
        }
    }

    public function notifyEvaluationSubmitted(Cycle $cycle, Unit $unit, int $semester): void
    {
        $indicatorCount = SelfEvaluation::query()
            ->where('cycle_id', $cycle->id)
            ->where('unit_id', $unit->id)
            ->where('semester', $semester)
            ->where('status', '!=', 'draft')
            ->count();

        $auditors = Audit::query()
            ->with('auditors')
            ->where('cycle_id', $cycle->id)
            ->where('unit_id', $unit->id)
            ->where('semester', $semester)
            ->get()
            ->flatMap(fn (Audit $audit) => $audit->auditors);

        $recipients = $this->withEmail(User::role('admin_spmi')->get()->merge($auditors));

        if ($indicatorCount > 0) {
            $notification = new EvaluationSubmitted($cycle, $unit, $semester, $indicatorCount);
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, $notification);
            }
            $this->notifyDosen($notification, $recipients);
        }
    }

    public function notifyUserAccountCreated(User $user): void
    {
        $notification = new UserAccountCreated($user->loadMissing(['unit', 'roles']));
        $this->notifyDosen($notification);
    }

    public function notifyEvidenceRevision(Evidence $evidence, ?string $comment): void
    {
        $recipient = $evidence->uploader;
        if ($recipient && filled($recipient->email)) {
            $recipient->notify(new EvidenceRevisionRequested($evidence, $comment));
        }
    }

    public function notifyCorrectiveActionChanged(CorrectiveAction $action, bool $created): void
    {
        $recipients = $this->correctiveActionRecipients($action);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new CorrectiveActionChanged($action, $created));
        }
    }

    private function auditRecipients(Audit $audit, array $previousAuditorIds): Collection
    {
        $audit->loadMissing('auditors');
        $previousAuditors = $previousAuditorIds
            ? User::whereKey($previousAuditorIds)->get()
            : collect();
        $unitAuditees = User::role('auditee')->where('unit_id', $audit->unit_id)->get();

        return $this->withEmail($audit->auditors->merge($previousAuditors)->merge($unitAuditees));
    }

    private function correctiveActionRecipients(CorrectiveAction $action): Collection
    {
        $action->loadMissing(['pic', 'finding.audit.unit']);
        $unitAuditees = User::role('auditee')->where('unit_id', $action->finding->audit->unit_id)->get();
        $pic = $action->pic ? collect([$action->pic]) : collect();

        return $this->withEmail($pic->merge($unitAuditees));
    }

    private function withEmail(Collection $recipients): Collection
    {
        return $recipients
            ->filter(fn (User $user) => filled($user->email))
            ->unique('id')
            ->values();
    }

    private function notifyDosen(object $notification, ?Collection $existingRecipients = null): void
    {
        $email = config('services.dosen_email');

        if (blank($email)) {
            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('The DOSEN_EMAIL configuration must contain a valid email address.');
        }

        $alreadyNotified = $existingRecipients?->contains(
            fn (User $user) => mb_strtolower($user->email) === mb_strtolower($email),
        ) ?? false;

        if (! $alreadyNotified) {
            Notification::route('mail', $email)->notify($notification);
        }
    }
}
