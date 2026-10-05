<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait LimitsToUser
{
    /**
     * Batasi query berdasarkan peran:
     * admin/pimpinan = semua; auditor = audit yang ditugaskan; auditee = unit sendiri.
     * $auditRelation = nama relasi menuju Audit, mis. 'audit' atau 'finding.audit'.
     */
    protected function limitToUser(Builder $q, string $auditRelation): Builder
    {
        $user = auth()->user();

        if ($user->seesAllUnits()) {
            return $q;
        }

        if ($user->hasRole('auditor')) {
            return $q->whereHas($auditRelation.'.auditors', fn ($a) => $a->where('users.id', $user->id));
        }

        return $q->whereHas($auditRelation, fn ($a) => $a->where('unit_id', $user->unit_id));
    }
}
