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

        // Support both single and batch uploads
        $data = $request->validate([
            // Single file upload (legacy)
            'judul' => 'nullable|string|max:255',
            'document_type' => 'nullable|string|max:50',
            'keterangan' => 'nullable|string|max:1000',
            'berkas' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar|max:10240',
            'url' => 'nullable|url|max:2048',

            // Batch upload
            'files' => 'nullable|array|max:10',
            'files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip,rar|max:10240',
            'file_metadata' => 'nullable|array',
            'file_metadata.*.judul' => 'required_with:files|string|max:255',
            'file_metadata.*.document_type' => 'required_with:files|string|in:laporan_akhir,struktur_organisasi,proker_kerja,data_statistik,dokumen_pendukung,photo_evidence,video_evidence,catatan_bukti,surat_pernyataan,lainnya',
            'file_metadata.*.keterangan' => 'nullable|string|max:1000',
        ]);

        $uploadedCount = 0;

        // Batch upload
        if (! empty($data['files']) && ! empty($data['file_metadata'])) {
            foreach ($data['files'] as $index => $file) {
                if (! $file) {
                    continue;
                }

                $metadata = $data['file_metadata'][$index] ?? [];
                $filePath = $file->store('evidences', 'local');

                if (! $filePath) {
                    throw new RuntimeException("Berkas bukti #$index tidak berhasil disimpan.");
                }

                $selfEvaluation->evidences()->create([
                    'activity_id' => $activity->id,
                    'judul' => $metadata['judul'] ?? 'Bukti #'.($index + 1),
                    'document_type' => $metadata['document_type'] ?? 'dokumen_pendukung',
                    'keterangan' => $metadata['keterangan'] ?? null,
                    'file_path' => $filePath,
                    'uploaded_by' => $request->user()->id,
                ]);
                $uploadedCount++;
            }
        }
        // Single file upload (legacy)
        elseif (! empty($data['judul']) && (! empty($data['berkas']) || ! empty($data['url']))) {
            $replaced = null;
            if (! empty($data['replaces_evidence_id'] ?? null)) {
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
                'document_type' => $data['document_type'] ?? 'dokumen_pendukung',
                'keterangan' => $data['keterangan'] ?? null,
                'url' => $data['url'] ?? null,
                'file_path' => $filePath,
                'uploaded_by' => $request->user()->id,
            ]);
            $replaced?->update([
                'status' => EvidenceStatus::Superseded,
                'superseded_by_id' => $replacement->id,
            ]);
            $uploadedCount = 1;
        }

        if ($uploadedCount === 0) {
            return back()->withErrors(['files' => 'Silakan upload minimal satu file atau berikan tautan URL.']);
        }

        return back()->with('success', "Berhasil mengunggah $uploadedCount bukti kegiatan.");
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
