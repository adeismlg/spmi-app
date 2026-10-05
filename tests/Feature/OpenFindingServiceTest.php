<?php

namespace Tests\Feature;

use App\Enums\ActionStatus;
use App\Enums\CycleStage;
use App\Enums\EvaluationPeriod;
use App\Enums\IndicatorType;
use App\Enums\Semester;
use App\Enums\StandardCategory;
use App\Enums\UnitType;
use App\Models\Audit;
use App\Models\AuditChecklist;
use App\Models\CorrectiveAction;
use App\Models\Cycle;
use App\Models\Finding;
use App\Models\Indicator;
use App\Models\Standard;
use App\Models\Statement;
use App\Models\Unit;
use App\Services\OpenFindingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenFindingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_findings_are_carried_forward_until_their_actions_are_verified(): void
    {
        $unit = Unit::create([
            'kode' => 'PRODI',
            'nama' => 'Program Studi',
            'tipe' => UnitType::Prodi,
        ]);
        $standard = Standard::create([
            'kode' => 'STD-1',
            'nama' => 'Standar Uji',
            'kategori' => StandardCategory::SnDikti,
        ]);
        $statement = Statement::create([
            'standard_id' => $standard->id,
            'periode_evaluasi' => EvaluationPeriod::SetiapSemester,
        ]);
        $indicator = Indicator::create([
            'standard_id' => $standard->id,
            'statement_id' => $statement->id,
            'kode' => 'IND-1',
            'nama' => 'Indikator Uji',
            'tipe' => IndicatorType::Persen,
        ]);
        $cycle = Cycle::create([
            'tahun' => 2026,
            'nama' => 'Siklus 2026',
            'tahap_aktif' => CycleStage::Evaluasi,
            'is_active' => true,
        ]);
        $previousAudit = Audit::create([
            'cycle_id' => $cycle->id,
            'unit_id' => $unit->id,
            'semester' => Semester::Ganjil,
        ]);
        $checklist = AuditChecklist::create([
            'audit_id' => $previousAudit->id,
            'indicator_id' => $indicator->id,
        ]);
        $finding = Finding::create([
            'audit_id' => $previousAudit->id,
            'audit_checklist_id' => $checklist->id,
            'kategori' => 'kts_minor',
            'uraian' => 'Belum memenuhi target',
        ]);
        $action = CorrectiveAction::create([
            'finding_id' => $finding->id,
            'tindakan' => 'Melakukan perbaikan',
            'status' => ActionStatus::Selesai,
        ]);
        $currentAudit = Audit::create([
            'cycle_id' => $cycle->id,
            'unit_id' => $unit->id,
            'semester' => Semester::Genap,
        ]);
        $service = app(OpenFindingService::class);

        $this->assertSame([$checklist->id], $service->before($currentAudit)->modelKeys());

        $action->update(['status' => ActionStatus::Terverifikasi]);

        $this->assertTrue($service->before($currentAudit)->isEmpty());
    }
}
