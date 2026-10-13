<?php

namespace Tests\Feature;

use App\Enums\CycleStage;
use App\Enums\IndicatorType;
use App\Enums\Semester;
use App\Enums\SubmissionStatus;
use App\Enums\UnitType;
use App\Models\Cycle;
use App\Models\Indicator;
use App\Models\SelfEvaluation;
use App\Models\Standard;
use App\Models\Unit;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_metrics_exclude_drafts_and_respect_the_selected_unit(): void
    {
        $cycle = Cycle::create([
            'tahun' => 2026,
            'nama' => 'Siklus Dashboard',
            'tahap_aktif' => CycleStage::Pelaksanaan,
            'is_active' => true,
        ]);
        $standard = Standard::create([
            'kode' => 'DASH-01',
            'nomor' => 1,
            'nama' => 'Standar Dashboard',
            'kategori' => 'sn_dikti',
            'is_active' => true,
        ]);
        $firstUnit = Unit::create([
            'kode' => 'DASH-A',
            'nama' => 'Unit A',
            'tipe' => UnitType::Prodi,
        ]);
        $secondUnit = Unit::create([
            'kode' => 'DASH-B',
            'nama' => 'Unit B',
            'tipe' => UnitType::Prodi,
        ]);

        foreach ([
            [$firstUnit, 'A-01', 80, SubmissionStatus::Submitted],
            [$firstUnit, 'A-02', 10, SubmissionStatus::Draft],
            [$secondUnit, 'B-01', 60, SubmissionStatus::Verified],
            [$secondUnit, 'B-02', 0, SubmissionStatus::Draft],
        ] as [$unit, $code, $score, $status]) {
            $indicator = Indicator::create([
                'standard_id' => $standard->id,
                'kode' => $code,
                'nama' => 'Indikator '.$code,
                'tipe' => IndicatorType::Persen,
            ]);
            SelfEvaluation::create([
                'cycle_id' => $cycle->id,
                'unit_id' => $unit->id,
                'indicator_id' => $indicator->id,
                'semester' => Semester::Ganjil->value,
                'skor' => $score,
                'status' => $status,
            ]);
        }

        $service = app(DashboardService::class);
        $institutionData = $service->build($cycle, null);
        $unitData = $service->build($cycle, $firstUnit->id);

        $this->assertSame(2, $institutionData['stats']['unit_submitted']);
        $this->assertSame(1, $unitData['stats']['unit_submitted']);
        $this->assertSame([80.0], $unitData['radar']['self']);
        $this->assertSame([70.0], $institutionData['trend']['self']);
    }
}
