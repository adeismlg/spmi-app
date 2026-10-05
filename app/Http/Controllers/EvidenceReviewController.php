<?php

namespace App\Http\Controllers;

use App\Enums\EvidenceStatus;
use App\Enums\SubmissionStatus;
use App\Models\Audit;
use App\Models\Evidence;
use App\Models\EvidenceReview;
use App\Services\SemesterWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EvidenceReviewController extends Controller
{
    public function update(Request $request, Evidence $evidence, SemesterWorkflow $workflow): RedirectResponse
    {
        $user = $request->user();
        $evaluation = $evidence->selfEvaluation()->with(['cycle', 'unit'])->firstOrFail();

        $isAdmin = $user->hasRole('admin_spmi');
        $isAssignedAuditor = $user->hasRole('auditor') && Audit::where('cycle_id', $evaluation->cycle_id)
            ->where('unit_id', $evaluation->unit_id)
            ->where('semester', $evaluation->semester)
            ->whereHas('auditors', fn ($query) => $query->where('users.id', $user->id))
            ->exists();

        abort_unless($isAdmin || $isAssignedAuditor, 403);
        abort_unless($evaluation->status === SubmissionStatus::Submitted, 422, 'Evaluasi harus diajukan permanen sebelum diperiksa.');
        abort_unless($evidence->status !== EvidenceStatus::Superseded, 422, 'Bukti ini sudah diganti dan tidak dapat diperiksa kembali.');
        abort_unless($isAdmin || $workflow->reviewOpen($evaluation->cycle, $evaluation->semester), 422, 'Periode pemeriksaan sedang ditutup.');

        $data = $request->validate([
            'status' => ['required', Rule::in([EvidenceStatus::Verified->value, EvidenceStatus::Revision->value])],
            'komentar' => ['nullable', 'string', 'max:3000', Rule::requiredIf(
                $request->input('status') === EvidenceStatus::Revision->value,
            )],
        ], [
            'komentar.required' => 'Komentar wajib diberikan jika bukti perlu diperbaiki.',
        ]);

        DB::transaction(function () use ($data, $evidence, $evaluation, $user) {
            EvidenceReview::create([
                'evidence_id' => $evidence->id,
                'reviewer_id' => $user->id,
                'status' => $data['status'],
                'komentar' => $data['komentar'] ?? null,
            ]);
            $evidence->update([
                'status' => $data['status'],
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

            $currentEvidences = $evaluation->evidences()->where('status', '!=', EvidenceStatus::Superseded->value);
            $allVerified = (clone $currentEvidences)->exists()
                && (clone $currentEvidences)->where('status', '!=', EvidenceStatus::Verified->value)->doesntExist();
            if ($allVerified) {
                $evaluation->update(['status' => SubmissionStatus::Verified]);
            }
        });

        return back()->with('success', 'Pemeriksaan bukti berhasil disimpan.');
    }
}
