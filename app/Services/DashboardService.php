<?php

namespace App\Services;

use App\Enums\ActionStatus;
use App\Models\Cycle;
use App\Models\Standard;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function build(?Cycle $cycle, ?int $unitId): array
    {
        $standards = Standard::where('is_active', true)->orderBy('kode')->get();

        if (! $cycle) {
            return ['standards' => $standards, 'radar' => null, 'stats' => [], 'trend' => []];
        }

        $self = DB::table('self_evaluations as se')
            ->join('indicators as i', 'i.id', '=', 'se.indicator_id')
            ->where('se.cycle_id', $cycle->id)
            ->when($unitId, fn ($q) => $q->where('se.unit_id', $unitId))
            ->groupBy('i.standard_id')
            ->selectRaw('i.standard_id, AVG(se.skor) as avg_skor')
            ->pluck('avg_skor', 'standard_id');

        $audit = DB::table('audit_checklists as ac')
            ->join('audits as a', 'a.id', '=', 'ac.audit_id')
            ->join('indicators as i', 'i.id', '=', 'ac.indicator_id')
            ->where('a.cycle_id', $cycle->id)
            ->when($unitId, fn ($q) => $q->where('a.unit_id', $unitId))
            ->groupBy('i.standard_id')
            ->selectRaw('i.standard_id, AVG(ac.skor_audit) as avg_skor')
            ->pluck('avg_skor', 'standard_id');

        $radar = [
            'labels' => $standards->pluck('kode')->all(),
            'target' => $standards->map(fn () => 100)->all(),
            'self' => $standards->map(fn ($s) => round((float) ($self[$s->id] ?? 0), 2))->all(),
            'audit' => $standards->map(fn ($s) => round((float) ($audit[$s->id] ?? 0), 2))->all(),
        ];

        $findingsBase = DB::table('findings as f')
            ->join('audits as a', 'a.id', '=', 'f.audit_id')
            ->where('a.cycle_id', $cycle->id)
            ->when($unitId, fn ($q) => $q->where('a.unit_id', $unitId));

        $findingByKategori = (clone $findingsBase)
            ->groupBy('f.kategori')->selectRaw('f.kategori, COUNT(*) as total')->pluck('total', 'kategori');

        $actionsBase = DB::table('corrective_actions as ca')
            ->join('findings as f', 'f.id', '=', 'ca.finding_id')
            ->join('audits as a', 'a.id', '=', 'f.audit_id')
            ->where('a.cycle_id', $cycle->id)
            ->when($unitId, fn ($q) => $q->where('a.unit_id', $unitId));

        $actionByStatus = (clone $actionsBase)
            ->groupBy('ca.status')->selectRaw('ca.status, COUNT(*) as total')->pluck('total', 'status');

        $overdue = (clone $actionsBase)
            ->whereNotIn('ca.status', [ActionStatus::Selesai->value, ActionStatus::Terverifikasi->value])
            ->whereDate('ca.target_selesai', '<', now()->toDateString())
            ->count();

        $totalFindings = (clone $findingsBase)->count();
        $totalActions = (clone $actionsBase)->count();

        $stats = [
            'unit_submitted' => DB::table('self_evaluations')->where('cycle_id', $cycle->id)
                ->where('status', '!=', 'draft')->distinct()->count('unit_id'),
            'audits_done' => DB::table('audits')->where('cycle_id', $cycle->id)->where('status', 'selesai')
                ->when($unitId, fn ($q) => $q->where('unit_id', $unitId))->count(),
            'audits_total' => DB::table('audits')->where('cycle_id', $cycle->id)
                ->when($unitId, fn ($q) => $q->where('unit_id', $unitId))->count(),
            'findings_total' => $totalFindings,
            'findings_by_kategori' => $findingByKategori,
            'actions_total' => $totalActions,
            'actions_by_status' => $actionByStatus,
            'actions_overdue' => $overdue,
            'actions_done_pct' => $totalActions
                ? round((($actionByStatus['selesai'] ?? 0) + ($actionByStatus['terverifikasi'] ?? 0)) / $totalActions * 100)
                : 0,
        ];

        // Tren antar tahun (rata-rata skor evaluasi diri & audit seluruh siklus).
        $trendSelf = DB::table('self_evaluations as se')->join('cycles as c', 'c.id', '=', 'se.cycle_id')
            ->when($unitId, fn ($q) => $q->where('se.unit_id', $unitId))
            ->groupBy('c.tahun')->selectRaw('c.tahun, AVG(se.skor) as v')->pluck('v', 'tahun');
        $trendAudit = DB::table('audit_checklists as ac')->join('audits as a', 'a.id', '=', 'ac.audit_id')
            ->join('cycles as c', 'c.id', '=', 'a.cycle_id')
            ->when($unitId, fn ($q) => $q->where('a.unit_id', $unitId))
            ->groupBy('c.tahun')->selectRaw('c.tahun, AVG(ac.skor_audit) as v')->pluck('v', 'tahun');

        $years = $trendSelf->keys()->merge($trendAudit->keys())->unique()->sort()->values();
        $trend = [
            'labels' => $years->all(),
            'self' => $years->map(fn ($y) => round((float) ($trendSelf[$y] ?? 0), 2))->all(),
            'audit' => $years->map(fn ($y) => round((float) ($trendAudit[$y] ?? 0), 2))->all(),
        ];

        return compact('standards', 'radar', 'stats', 'trend');
    }
}
