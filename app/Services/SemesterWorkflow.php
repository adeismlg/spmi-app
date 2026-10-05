<?php

namespace App\Services;

use App\Enums\CycleStage;
use App\Enums\ReopenRequestStatus;
use App\Models\Cycle;
use App\Models\SelfEvaluation;
use App\Models\SemesterWindow;
use Illuminate\Support\Carbon;

class SemesterWorkflow
{
    public function window(Cycle $cycle, int $semester): ?SemesterWindow
    {
        return SemesterWindow::where('cycle_id', $cycle->id)->where('semester', $semester)->first();
    }

    public function submissionOpen(Cycle $cycle, int $semester): bool
    {
        $window = $this->window($cycle, $semester);

        return $cycle->isStageOpen(CycleStage::Pelaksanaan)
            && $window !== null
            && $this->contains(now(), $window->pengisian_mulai, $window->pengisian_selesai);
    }

    public function reviewOpen(Cycle $cycle, int $semester): bool
    {
        $window = $this->window($cycle, $semester);

        return $cycle->isStageOpen(CycleStage::Evaluasi)
            && $window !== null
            && $this->contains(now(), $window->pemeriksaan_mulai, $window->pemeriksaan_selesai);
    }

    public function canEdit(Cycle $cycle, int $semester, ?SelfEvaluation $evaluation = null): bool
    {
        if ($evaluation && $evaluation->status->value !== 'draft') {
            return false;
        }

        return $this->submissionOpen($cycle, $semester)
            || ($evaluation?->reopenRequests()->where('status', ReopenRequestStatus::Approved->value)->exists() ?? false);
    }

    private function contains(Carbon $now, Carbon $start, Carbon $end): bool
    {
        return $now->betweenIncluded($start, $end);
    }
}
