<?php

namespace App\Http\Controllers;

use App\Enums\AuditStatus;
use App\Enums\SubmissionStatus;
use App\Models\Audit;
use App\Models\AuditChecklist;
use App\Models\Indicator;
use App\Models\SelfEvaluation;
use App\Services\OpenFindingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuditChecklistController extends Controller
{
    private function authorizeAudit(Request $request, Audit $audit): void
    {
        $user = $request->user();
        abort_unless($user->hasRole('admin_spmi') || $audit->hasAuditor($user), 403);
    }

    /** Buat butir untuk indikator yang jatuh tempo dan temuan terbuka dari audit sebelumnya. */
    public function generate(Request $request, Audit $audit): RedirectResponse
    {
        $this->authorizeAudit($request, $audit);

        // Evaluasi diri untuk semester audit, bukan skor dari semester lain.
        $evals = SelfEvaluation::where('cycle_id', $audit->cycle_id)->where('unit_id', $audit->unit_id)
            ->where('semester', $audit->semester->value)
            ->whereIn('status', [
                SubmissionStatus::Submitted->value,
                SubmissionStatus::Verified->value,
            ])->get()->keyBy('indicator_id');

        // Include indicators currently due as well as unresolved findings carried forward.
        $assigned = Indicator::with('statement')
            ->whereHas('statement.assignments', fn ($q) => $q->where('unit_id', $audit->unit_id))
            ->whereHas('standard', fn ($q) => $q->where('is_active', true))
            ->get();
        $dueIds = $assigned->filter(fn ($indicator) => in_array(
            $audit->semester->value,
            $indicator->semesters($audit->cycle->tahun),
            true,
        ))->pluck('id');
        $openIds = app(OpenFindingService::class)->before($audit)->pluck('indicator_id');
        $existingIds = $audit->checklists()->pluck('indicator_id');
        $indicatorIds = $dueIds->merge($openIds)->merge($existingIds)->unique()->values();

        foreach ($indicatorIds as $id) {
            $checklist = AuditChecklist::firstOrNew(['audit_id' => $audit->id, 'indicator_id' => $id]);
            $checklist->self_evaluation_id = $evals->get($id)?->id;
            if ($checklist->isDirty()) {
                $checklist->saveQuietly();
            }
        }

        if ($audit->status === AuditStatus::Terjadwal) {
            $audit->update(['status' => AuditStatus::DeskEvaluation]);
        }

        return back()->with('success', 'Daftar tilik semester '.$audit->semester->label().' dibuat dari indikator terjadwal dan temuan yang belum ditutup ('.count($indicatorIds).' butir).');
    }

    public function update(Request $request, Audit $audit): RedirectResponse
    {
        $this->authorizeAudit($request, $audit);

        $request->validate([
            'items' => 'required|array',
            'items.*.kesesuaian' => 'nullable|in:sesuai,observasi,kts_minor,kts_major',
            'items.*.skor_audit' => 'nullable|numeric|between:0,100',
            'items.*.catatan' => 'nullable|string|max:3000|required_if:items.*.kesesuaian,observasi,kts_minor,kts_major',
        ], [
            'items.*.catatan.required_if' => 'Catatan wajib diisi untuk butir Observasi/KTS.',
        ]);

        $rows = AuditChecklist::where('audit_id', $audit->id)->get()->keyBy('id');

        foreach ($request->input('items') as $id => $item) {
            $row = $rows->get($id);
            if (! $row) {
                continue;
            }

            $row->update([
                'kesesuaian' => $item['kesesuaian'] ?: null,
                'skor_audit' => $item['skor_audit'] !== '' && $item['skor_audit'] !== null ? $item['skor_audit'] : null,
                'catatan' => $item['catatan'] ?? null,
            ]);
        }

        if ($request->has('finish')) {
            $audit->update(['status' => AuditStatus::Selesai]);
        } elseif ($audit->status === AuditStatus::DeskEvaluation && $request->has('to_visitasi')) {
            $audit->update(['status' => AuditStatus::Visitasi]);
        }

        return back()->with('success', $request->has('finish') ? 'Audit diselesaikan.' : 'Daftar tilik disimpan.');
    }
}
