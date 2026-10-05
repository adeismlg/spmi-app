<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Models\Audit;
use App\Models\SelfEvaluation;
use App\Models\User;

class EvaluationAccess
{
    public function canView(User $user, SelfEvaluation $evaluation): bool
    {
        if ($user->seesAllUnits()) {
            return true;
        }

        if ($user->hasRole('auditor')) {
            return in_array($evaluation->status, [SubmissionStatus::Submitted, SubmissionStatus::Verified], true)
                && Audit::where('cycle_id', $evaluation->cycle_id)
                    ->where('unit_id', $evaluation->unit_id)
                    ->where('semester', $evaluation->semester)
                    ->whereHas('auditors', fn ($query) => $query->where('users.id', $user->id))
                    ->exists();
        }

        return $this->isAssignedAuditee($user, $evaluation);
    }

    public function canEdit(User $user, SelfEvaluation $evaluation): bool
    {
        return $user->hasRole('admin_spmi') || $this->isAssignedAuditee($user, $evaluation);
    }

    private function isAssignedAuditee(User $user, SelfEvaluation $evaluation): bool
    {
        $statement = $evaluation->indicator->statement;

        return $statement !== null
            && $user->hasRole('auditee')
            && $user->unit_id === $evaluation->unit_id
            && $user->jabatan !== null
            && $statement->assignments()
                ->where('unit_id', $user->unit_id)
                ->where('jabatan', $user->jabatan->value)
                ->exists();
    }
}
