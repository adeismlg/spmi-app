<?php

namespace App\Http\Controllers;

use App\Enums\EvidenceStatus;
use App\Models\Activity;
use App\Models\Evidence;
use App\Models\SelfEvaluation;
use App\Services\EvaluationAccess;
use App\Services\SemesterWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceController extends Controller
{
    public function store(
        Request $request,
        SelfEvaluation $selfEvaluation,
        Activity $activity,
        EvaluationAccess $access,
        SemesterWorkflow $workflow,
    ): RedirectResponse {
        abort_unless($access->canEdit($request->user(), $selfEvaluation), 403);
        abort_unless($activity->indicator_id === $selfEvaluation->indicator_id && $activity->is_active, 404);
        abort_unless(
            $workflow->canEdit($selfEvaluation->cycle, $selfEvaluation->semester, $selfEvaluation),
            422,
            'Pengisian terkunci atau periode pengisian sudah ditutup.',
        );

        $data = $request->validate([
            'judul' => 'required|string|max:255',
            'berkas' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png|max:10240|required_without:url',
            'url' => 'nullable|url|max:2048|required_without:berkas',
            'replaces_evidence_id' => 'nullable|integer|exists:evidences,id',
        ]);

        $replaced = null;
        if (! empty($data['replaces_evidence_id'])) {
            $replaced = $selfEvaluation->evidences()
                ->where('activity_id', $activity->id)
                ->whereKey($data['replaces_evidence_id'])
                ->where('status', EvidenceStatus::Revision->value)
                ->firstOrFail();
        }

        $filePath = null;
        if ($request->hasFile('berkas')) {
            $filePath = $request->file('berkas')->store('evidences', 'local');
            if (! $filePath) {
                throw new RuntimeException('Berkas bukti tidak berhasil disimpan ke penyimpanan privat.');
            }
        }

        $replacement = $selfEvaluation->evidences()->create([
            'activity_id' => $activity->id,
            'judul' => $data['judul'],
            'url' => $data['url'] ?? null,
            'file_path' => $filePath,
            'uploaded_by' => $request->user()->id,
        ]);
        $replaced?->update([
            'status' => EvidenceStatus::Superseded,
            'superseded_by_id' => $replacement->id,
        ]);

        return back()->with('success', $replaced ? 'Bukti perbaikan berhasil diajukan; komentar sebelumnya tetap tersimpan.' : 'Bukti kegiatan berhasil ditambahkan.');
    }

    public function destroy(
        Request $request,
        Evidence $evidence,
        EvaluationAccess $access,
        SemesterWorkflow $workflow,
    ): RedirectResponse {
        $evaluation = $evidence->selfEvaluation;
        abort_unless($access->canEdit($request->user(), $evaluation), 403);
        abort_unless($workflow->canEdit($evaluation->cycle, $evaluation->semester, $evaluation), 422, 'Bukti terkunci.');

        if ($evidence->file_path) {
            if (! Storage::disk('local')->delete($evidence->file_path)) {
                throw new RuntimeException("Tidak dapat menghapus berkas bukti #{$evidence->id} dari penyimpanan privat.");
            }
        }
        $evidence->delete();

        return back()->with('success', 'Bukti dukung dihapus.');
    }

    public function download(Request $request, Evidence $evidence, EvaluationAccess $access): StreamedResponse
    {
        $evaluation = $evidence->selfEvaluation;
        abort_unless($access->canView($request->user(), $evaluation), 403);
        abort_unless($evidence->file_path && Storage::disk('local')->exists($evidence->file_path), 404);

        return Storage::disk('local')->download($evidence->file_path, basename($evidence->file_path));
    }
}
