<?php

namespace App\Services;

use App\Enums\ActionStatus;
use App\Models\Audit;
use App\Models\AuditChecklist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OpenFindingService
{
    /** Daftar tilik sebelum audit ini yang masih memiliki RTL belum diverifikasi. */
    public function before(Audit $audit): Collection
    {
        return AuditChecklist::with(['finding.correctiveActions', 'audit.cycle'])
            ->whereHas('audit', function (Builder $query) use ($audit) {
                $query->where('unit_id', $audit->unit_id)
                    ->where(function (Builder $query) use ($audit) {
                        $query->whereHas('cycle', fn (Builder $cycles) => $cycles->where('tahun', '<', $audit->cycle->tahun))
                            ->orWhere(fn (Builder $sameCycle) => $sameCycle
                                ->where('cycle_id', $audit->cycle_id)
                                ->where('semester', '<', $audit->semester->value));
                    });
            })
            ->whereHas('finding', function (Builder $findings) {
                $findings->whereDoesntHave('correctiveActions')
                    ->orWhereHas('correctiveActions', fn (Builder $actions) => $actions
                        ->where('status', '!=', ActionStatus::Terverifikasi->value));
            })
            ->get();
    }
}
