<?php

namespace App\Http\Controllers;

use App\Enums\ReopenRequestStatus;
use App\Enums\Semester;
use App\Enums\SubmissionStatus;
use App\Models\Cycle;
use App\Models\Indicator;
use App\Models\SelfEvaluation;
use App\Models\Standard;
use App\Models\Unit;
use App\Services\EvaluationAccess;
use App\Services\ScoreService;
use App\Services\SemesterWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Evaluasi diri per semester. Setiap unit hanya melihat indikator yang DITUGASKAN kepadanya
 * (lihat AssignmentService), dan pengguna berjabatan hanya melihat yang PIC-nya sama dengan jabatannya.
 */
class SelfEvaluationController extends Controller
{
    private function unitId(Request $request): int
    {
        $user = $request->user();

        $id = $user->hasRole('admin_spmi')
            ? ($request->integer('unit_id') ?: Unit::orderBy('nama')->value('id'))
            : $user->unit_id;

        abort_unless($id, 403, 'Akun Anda belum terhubung dengan unit/program studi.');

        return (int) $id;
    }

    private function semester(Request $request): int
    {
        $s = $request->integer('semester');

        return in_array($s, [1, 2], true) ? $s : Semester::current()->value;
    }

    /** Admin melihat semua jabatan; pengguna berjabatan hanya jabatannya sendiri. */
    private function jabatan(Request $request): ?string
    {
        $user = $request->user();

        if ($user->hasRole('admin_spmi')) {
            return null;
        }

        abort_unless($user->jabatan, 403, 'Akun belum diberi jabatan PIC.');

        return $user->jabatan->value;
    }

    /** Semua indikator yang ditugaskan ke unit (semua semester). */
    private function assigned(int $unitId, ?string $jabatan): Collection
    {
        return Indicator::with(['standard', 'statement', 'targets'])
            ->whereHas('statement.assignments', fn ($q) => $q->where('unit_id', $unitId)
                ->when($jabatan, fn ($q, $j) => $q->where('jabatan', $j)))
            ->whereHas('standard', fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy(fn ($i) => [$i->standard->nomor ?? 99, $i->kode])
            ->values();
    }

    public function index(Request $request, SemesterWorkflow $workflow): View|RedirectResponse
    {
        $cycle = Cycle::current();
        if (! $cycle) {
            return redirect()->route('dashboard')->with('error', 'Belum ada siklus aktif.');
        }

        $unitId = $this->unitId($request);
        $semester = $this->semester($request);
        $all = $this->assigned($unitId, $this->jabatan($request));

        $indicators = $all->filter(fn ($i) => in_array($semester, $i->semesters($cycle->tahun), true));
        $evals = SelfEvaluation::where('cycle_id', $cycle->id)->where('unit_id', $unitId)
            ->where('semester', $semester)->withCount('evidences')->get()->keyBy('indicator_id');

        return view('self_evaluations.index', [
            'cycle' => $cycle,
            'unit' => Unit::findOrFail($unitId),
            'units' => $request->user()->hasRole('admin_spmi') ? Unit::orderBy('nama')->get() : collect(),
            'semester' => $semester,
            'counts' => [
                1 => $all->filter(fn ($i) => in_array(1, $i->semesters($cycle->tahun), true))->count(),
                2 => $all->filter(fn ($i) => in_array(2, $i->semesters($cycle->tahun), true))->count(),
            ],
            'standards' => Standard::orderBy('nomor')->get()->keyBy('id'),
            'groups' => $indicators->groupBy('standard_id'),
            'evals' => $evals,
            'evaluationEditable' => $evals->mapWithKeys(fn ($evaluation, $indicatorId) => [
                $indicatorId => $workflow->canEdit($cycle, $semester, $evaluation),
            ]),
            'jabatanLabel' => $request->user()->jabatan?->label(),
            'window' => $workflow->window($cycle, $semester),
            'editable' => $workflow->submissionOpen($cycle, $semester),
        ]);
    }

    public function update(Request $request, SemesterWorkflow $workflow): RedirectResponse
    {
        $cycle = Cycle::current();
        abort_unless($cycle, 404);
        $unitId = $this->unitId($request);
        $semester = $this->semester($request);

        $request->validate([
            'items' => 'required|array',
            'items.*.capaian' => 'nullable|numeric|min:0',
            'items.*.skor' => 'nullable|numeric|between:0,100',
            'items.*.uraian' => 'nullable|string|max:2000',
        ]);

        // Hanya indikator yang memang ditugaskan ke unit/jabatan ini pada semester ini yang boleh disimpan.
        $allowed = $this->assigned($unitId, $this->jabatan($request))
            ->filter(fn ($i) => in_array($semester, $i->semesters($cycle->tahun), true))
            ->keyBy('id');

        $submit = $request->has('submit');
        $submittedItems = $request->input('items', []);
        foreach (array_keys($submittedItems) as $indicatorId) {
            abort_unless($allowed->has((int) $indicatorId), 403, 'Indikator tidak ditugaskan kepada jabatan dan unit Anda.');
        }

        if ($submit) {
            $hasData = collect($submittedItems)->contains(fn ($row) => filled($row['capaian'] ?? null)
                || filled($row['skor'] ?? null)
                || filled($row['uraian'] ?? null));
            abort_if(! $hasData, 422, 'Isi capaian, skor, atau uraian sebelum menyimpan permanen.');
        }

        DB::transaction(function () use ($request, $workflow, $cycle, $unitId, $semester, $allowed, $submit) {
            foreach ($request->input('items', []) as $indicatorId => $row) {
                $indicator = $allowed->get((int) $indicatorId);
                if (! $indicator) {
                    continue;
                }

                $key = [
                    'cycle_id' => $cycle->id,
                    'unit_id' => $unitId,
                    'indicator_id' => $indicator->id,
                    'semester' => $semester,
                ];
                $existing = SelfEvaluation::where($key)->first();
                abort_unless($workflow->canEdit($cycle, $semester, $existing), 422, 'Evaluasi terkunci atau periode pengisian sudah ditutup.');

                $capaian = isset($row['capaian']) && $row['capaian'] !== '' ? (float) $row['capaian'] : null;
                $uraian = $row['uraian'] ?? null;
                $manual = isset($row['skor']) && $row['skor'] !== '' ? (float) $row['skor'] : null;

                if (! $existing && $capaian === null && blank($uraian) && $manual === null) {
                    continue;
                }

                $evaluation = SelfEvaluation::updateOrCreate($key, [
                    'capaian' => $capaian,
                    'uraian' => $uraian,
                    'skor' => ScoreService::score($indicator->tipe, $capaian, $indicator->targetFor($cycle->tahun)) ?? $manual,
                    'status' => $submit ? SubmissionStatus::Submitted : SubmissionStatus::Draft,
                    'submitted_at' => $submit ? now() : null,
                ]);

                if ($submit) {
                    $activityIds = $indicator->activities()->where('is_active', true)->pluck('id');
                    abort_if($activityIds->isEmpty(), 422, 'Admin SPMI belum menetapkan kegiatan untuk indikator ini.');
                    $missingActivity = $activityIds->first(fn ($activityId) => ! $evaluation->evidences()
                        ->where('activity_id', $activityId)->exists());
                    abort_if($missingActivity, 422, 'Tambahkan setidaknya satu bukti untuk setiap kegiatan sebelum Simpan Permanen.');

                    $evaluation->reopenRequests()->where('status', ReopenRequestStatus::Approved->value)
                        ->update(['status' => ReopenRequestStatus::Completed->value]);
                }
            }
        });

        return redirect()->route('self-evaluations.index', ['unit_id' => $unitId, 'semester' => $semester])
            ->with('success', ($submit ? 'Pengajuan permanen evaluasi diri berhasil' : 'Draft evaluasi diri disimpan').' — '.Semester::from($semester)->label().'.');
    }

    public function show(
        Request $request,
        SelfEvaluation $selfEvaluation,
        EvaluationAccess $access,
        SemesterWorkflow $workflow,
    ): View {
        abort_unless($access->canView($request->user(), $selfEvaluation), 403);
        $selfEvaluation->load([
            'indicator.standard',
            'indicator.activities' => fn ($query) => $query->where('is_active', true),
            'unit',
            'evidences.activity',
            'evidences.uploader',
            'evidences.reviewer',
            'evidences.reviews.reviewer',
            'reopenRequests.requester',
            'reopenRequests.resolver',
            'cycle',
        ]);
        $canEdit = $access->canEdit($request->user(), $selfEvaluation)
            && $workflow->canEdit($selfEvaluation->cycle, $selfEvaluation->semester, $selfEvaluation);
        $canReview = $request->user()->hasAnyRole(['admin_spmi', 'auditor'])
            && $selfEvaluation->status === SubmissionStatus::Submitted
            && ($request->user()->hasRole('admin_spmi') || $workflow->reviewOpen($selfEvaluation->cycle, $selfEvaluation->semester));

        return view('self_evaluations.show', [
            'eval' => $selfEvaluation,
            'editable' => $canEdit,
            'canReview' => $canReview,
            'canRequestReopen' => $access->canEdit($request->user(), $selfEvaluation)
                && in_array($selfEvaluation->status, [SubmissionStatus::Submitted, SubmissionStatus::Verified], true)
                && ! $selfEvaluation->reopenRequests()->where('status', ReopenRequestStatus::Pending)->exists(),
            'window' => $workflow->window($selfEvaluation->cycle, $selfEvaluation->semester),
        ]);
    }
}
