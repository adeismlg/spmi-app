<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\AuditChecklist;
use App\Models\CorrectiveAction;
use App\Models\Cycle;
use App\Models\SelfEvaluation;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Collection;

class ReportService
{
    public const TYPES = [
        'summary' => 'Ringkasan Siklus (PDF)',
        'self-evaluation' => 'Rekap Evaluasi Diri',
        'audit' => 'Hasil Audit / Daftar Tilik',
        'corrective-action' => 'Rekap Rencana Tindak Lanjut (RTL)',
    ];

    /** Unit yang boleh dilihat pengguna. null = semua unit. */
    public function allowedUnitIds(User $user, Cycle $cycle): ?array
    {
        if ($user->seesAllUnits()) {
            return null;
        }

        if ($user->hasRole('auditor')) {
            return Audit::where('cycle_id', $cycle->id)
                ->whereHas('auditors', fn ($q) => $q->where('users.id', $user->id))
                ->pluck('unit_id')->all();
        }

        return $user->unit_id ? [$user->unit_id] : [];
    }

    /** Gabungkan unit yang diminta dengan hak akses. null = semua unit. */
    public function resolveUnits(User $user, Cycle $cycle, ?int $requested): ?array
    {
        $allowed = $this->allowedUnitIds($user, $cycle);

        if ($allowed === null) {
            return $requested ? [$requested] : null;
        }

        abort_if($requested && ! in_array($requested, $allowed, true), 403, 'Anda tidak berhak atas unit tersebut.');

        return $requested ? [$requested] : $allowed;
    }

    /** Unit untuk pilihan di form laporan. */
    public function selectableUnits(User $user): Collection
    {
        if ($user->seesAllUnits()) {
            return Unit::orderBy('nama')->get();
        }

        if ($user->hasRole('auditor')) {
            return Unit::whereHas('audits.auditors', fn ($q) => $q->where('users.id', $user->id))->orderBy('nama')->get();
        }

        return Unit::where('id', $user->unit_id)->get();
    }

    /** @return array{title: string, headings: array, rows: array} */
    public function dataset(string $type, Cycle $cycle, ?array $unitIds): array
    {
        return match ($type) {
            'self-evaluation' => $this->selfEvaluations($cycle, $unitIds),
            'audit' => $this->audits($cycle, $unitIds),
            'corrective-action' => $this->correctiveActions($cycle, $unitIds),
        };
    }

    private function selfEvaluations(Cycle $cycle, ?array $unitIds): array
    {
        $rows = SelfEvaluation::with(['unit', 'indicator.standard', 'indicator.targets'])->withCount('evidences')
            ->where('cycle_id', $cycle->id)
            ->when($unitIds !== null, fn ($q) => $q->whereIn('unit_id', $unitIds))
            ->get()
            ->sortBy(fn ($e) => [$e->unit->nama, $e->indicator->standard->kode, $e->indicator->kode, $e->semester])
            ->map(fn ($e) => [
                $e->unit->nama,
                $e->semester === 1 ? 'Ganjil' : 'Genap',
                $e->indicator->standard->kode,
                $e->indicator->kode,
                $e->indicator->nama,
                $e->indicator->targetLabel($cycle->tahun),
                $e->capaian !== null ? (float) $e->capaian : '-',
                $e->skor !== null ? (float) $e->skor : '-',
                $e->status->label(),
                $e->evidences_count,
                $e->uraian ?? '',
            ])->values()->all();

        return [
            'title' => 'Rekap Evaluasi Diri',
            'headings' => ['Unit', 'Semester', 'Standar', 'Kode', 'Indikator', 'Target', 'Capaian', 'Skor', 'Status', 'Bukti', 'Uraian'],
            'rows' => $rows,
        ];
    }

    private function audits(Cycle $cycle, ?array $unitIds): array
    {
        $rows = AuditChecklist::with(['audit.unit', 'indicator.standard', 'selfEvaluation'])
            ->whereHas('audit', fn ($q) => $q->where('cycle_id', $cycle->id)
                ->when($unitIds !== null, fn ($q) => $q->whereIn('unit_id', $unitIds)))
            ->get()
            ->sortBy(fn ($c) => [$c->audit->unit->nama, $c->audit->semester->value, $c->indicator->standard->kode, $c->indicator->kode])
            ->map(fn ($c) => [
                $c->audit->unit->nama,
                $c->audit->semester->label(),
                $c->indicator->standard->kode,
                $c->indicator->kode,
                $c->indicator->nama,
                $c->selfEvaluation?->skor !== null ? (float) $c->selfEvaluation->skor : '-',
                $c->skor_audit !== null ? (float) $c->skor_audit : '-',
                $c->kesesuaian?->label() ?? '-',
                $c->catatan ?? '',
            ])->values()->all();

        return [
            'title' => 'Hasil Audit Mutu Internal',
            'headings' => ['Unit', 'Semester Audit', 'Standar', 'Kode', 'Indikator', 'Skor Evaluasi Diri', 'Skor Audit', 'Kesesuaian', 'Catatan Auditor'],
            'rows' => $rows,
        ];
    }

    private function correctiveActions(Cycle $cycle, ?array $unitIds): array
    {
        $rows = CorrectiveAction::with(['pic', 'finding.audit.unit', 'finding.checklist.indicator'])
            ->whereHas('finding.audit', fn ($q) => $q->where('cycle_id', $cycle->id)
                ->when($unitIds !== null, fn ($q) => $q->whereIn('unit_id', $unitIds)))
            ->get()
            ->sortBy(fn ($a) => [$a->finding->audit->unit->nama, $a->target_selesai?->timestamp ?? PHP_INT_MAX])
            ->map(fn ($a) => [
                $a->finding->audit->unit->nama,
                $a->finding->checklist?->indicator?->kode ?? '-',
                $a->finding->kategori->label(),
                $a->finding->uraian,
                $a->akar_masalah ?? '',
                $a->tindakan,
                $a->pic?->name ?? '-',
                $a->target_selesai?->format('d/m/Y') ?? '-',
                $a->status->label().($a->isOverdue() ? ' (terlambat)' : ''),
                $a->verified_at?->format('d/m/Y') ?? '-',
            ])->values()->all();

        return [
            'title' => 'Rekap Rencana Tindak Lanjut (RTL)',
            'headings' => ['Unit', 'Indikator', 'Kategori', 'Temuan', 'Akar Masalah', 'Tindakan', 'PIC', 'Target', 'Status', 'Terverifikasi'],
            'rows' => $rows,
        ];
    }
}
