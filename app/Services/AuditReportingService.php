<?php

namespace App\Services;

use App\Enums\UnitType;
use App\Models\Cycle;
use Illuminate\Support\Facades\DB;

class AuditReportingService
{
    /**
     * Ambil ringkasan audit berdasarkan tipe unit (lab vs prodi)
     */
    public function auditSummaryByType(?Cycle $cycle, ?int $unitId): array
    {
        if (! $cycle) {
            return [
                'academic' => [],
                'laboratory' => [],
                'summary' => $this->getEmptySummary(),
            ];
        }

        $auditsAcademic = $this->getAuditsByUnitType($cycle, $unitId, UnitType::Prodi->value);
        $auditsLab = $this->getAuditsByUnitType($cycle, $unitId, UnitType::Laboratorium->value);

        return [
            'academic' => $auditsAcademic,
            'laboratory' => $auditsLab,
            'summary' => $this->calculateSummary($auditsAcademic, $auditsLab),
        ];
    }

    /**
     * Ambil status audit berdasarkan tipe unit
     */
    private function getAuditsByUnitType(Cycle $cycle, ?int $unitId, string $unitType): array
    {
        $query = DB::table('audits as a')
            ->join('units as u', 'u.id', '=', 'a.unit_id')
            ->where('a.cycle_id', $cycle->id)
            ->where('u.tipe', $unitType);

        if ($unitId) {
            $query->where('a.unit_id', $unitId);
        }

        $audits = $query->select(
            'a.id',
            'a.unit_id',
            'u.nama as unit_nama',
            'u.kode as unit_kode',
            'a.status',
            'a.semester',
            'a.tanggal_desk_evaluation',
            'a.tanggal_visitasi',
            DB::raw('COUNT(DISTINCT ac.id) as total_checklist'),
            DB::raw('COUNT(DISTINCT CASE WHEN ac.skor_audit >= 80 THEN ac.id END) as passed_checklist'),
            DB::raw('COUNT(DISTINCT f.id) as total_findings'),
            DB::raw('COUNT(DISTINCT CASE WHEN ca.status = "selesai" THEN ca.id END) as rtl_selesai'),
            DB::raw('COUNT(DISTINCT CASE WHEN ca.status = "terverifikasi" THEN ca.id END) as rtl_terverifikasi'),
            DB::raw('COUNT(DISTINCT ca.id) as total_rtl')
        )
            ->leftJoin('audit_checklists as ac', 'ac.audit_id', '=', 'a.id')
            ->leftJoin('findings as f', 'f.audit_id', '=', 'a.id')
            ->leftJoin('corrective_actions as ca', 'ca.finding_id', '=', 'f.id')
            ->groupBy('a.id', 'a.unit_id', 'u.nama', 'u.kode', 'a.status', 'a.semester', 'a.tanggal_desk_evaluation', 'a.tanggal_visitasi')
            ->orderBy('u.nama')
            ->get()
            ->map(fn ($audit) => [
                'id' => $audit->id,
                'unit_id' => $audit->unit_id,
                'unit_nama' => $audit->unit_nama,
                'unit_kode' => $audit->unit_kode,
                'status' => $audit->status,
                'semester' => $audit->semester,
                'tanggal_desk_evaluation' => $audit->tanggal_desk_evaluation,
                'tanggal_visitasi' => $audit->tanggal_visitasi,
                'total_checklist' => (int) $audit->total_checklist,
                'passed_checklist' => (int) $audit->passed_checklist,
                'compliance_pct' => $audit->total_checklist ? round(($audit->passed_checklist / $audit->total_checklist) * 100, 2) : 0,
                'total_findings' => (int) $audit->total_findings,
                'total_rtl' => (int) $audit->total_rtl,
                'rtl_selesai' => (int) $audit->rtl_selesai,
                'rtl_terverifikasi' => (int) $audit->rtl_terverifikasi,
                'rtl_pct_resolved' => $audit->total_rtl ? round(($audit->rtl_selesai + $audit->rtl_terverifikasi) / $audit->total_rtl * 100, 2) : 0,
            ])
            ->toArray();

        return $audits;
    }

    /**
     * Hitung ringkasan keseluruhan
     */
    private function calculateSummary(array $auditsAcademic, array $auditsLab): array
    {
        $allAudits = array_merge($auditsAcademic, $auditsLab);

        if (empty($allAudits)) {
            return $this->getEmptySummary();
        }

        $totalAudits = count($allAudits);
        $completedAudits = count(array_filter($allAudits, fn ($a) => $a['status'] === 'selesai'));
        $avgCompliance = count($allAudits) > 0 ? round(array_reduce($allAudits, fn ($carry, $a) => $carry + $a['compliance_pct'], 0) / count($allAudits), 2) : 0;
        $totalFindings = array_sum(array_column($allAudits, 'total_findings'));
        $totalRtl = array_sum(array_column($allAudits, 'total_rtl'));
        $rtlSelesai = array_sum(array_column($allAudits, 'rtl_selesai'));
        $rtlTerverifikasi = array_sum(array_column($allAudits, 'rtl_terverifikasi'));

        return [
            'total_audits' => $totalAudits,
            'completed_audits' => $completedAudits,
            'completion_pct' => $totalAudits ? round($completedAudits / $totalAudits * 100, 2) : 0,
            'avg_compliance' => $avgCompliance,
            'total_findings' => $totalFindings,
            'total_rtl' => $totalRtl,
            'rtl_selesai' => $rtlSelesai,
            'rtl_terverifikasi' => $rtlTerverifikasi,
            'rtl_pct_resolved' => $totalRtl ? round(($rtlSelesai + $rtlTerverifikasi) / $totalRtl * 100, 2) : 0,
        ];
    }

    private function getEmptySummary(): array
    {
        return [
            'total_audits' => 0,
            'completed_audits' => 0,
            'completion_pct' => 0,
            'avg_compliance' => 0,
            'total_findings' => 0,
            'total_rtl' => 0,
            'rtl_selesai' => 0,
            'rtl_terverifikasi' => 0,
            'rtl_pct_resolved' => 0,
        ];
    }
}
