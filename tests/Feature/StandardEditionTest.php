<?php

namespace Tests\Feature;

use App\Enums\CycleStage;
use App\Enums\EvaluationPeriod;
use App\Enums\Semester;
use App\Enums\UnitType;
use App\Models\Audit;
use App\Models\Cycle;
use App\Models\Indicator;
use App\Models\Standard;
use App\Models\StandardEdition;
use App\Models\Statement;
use App\Models\StatementAssignment;
use App\Models\Unit;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StandardEditionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cycle_uses_only_standards_from_its_selected_edition(): void
    {
        $legacyEdition = $this->createEdition('LEGACY-TEST', 2020, 2024);
        $currentEdition = $this->createEdition('SPMI-TEST-2025', 2025, 2029);
        Standard::create([
            'standard_edition_id' => $legacyEdition->id,
            'kode' => 'S01',
            'nomor' => 1,
            'nama' => 'Standar Lama',
            'kategori' => 'sn_dikti',
            'is_active' => true,
        ]);
        Standard::create([
            'standard_edition_id' => $currentEdition->id,
            'kode' => 'S01',
            'nomor' => 1,
            'nama' => 'Standar Baru',
            'kategori' => 'sn_dikti',
            'is_active' => true,
        ]);
        $cycle = Cycle::create([
            'standard_edition_id' => $currentEdition->id,
            'tahun' => 2025,
            'nama' => 'Siklus Uji 2025',
            'tahap_aktif' => CycleStage::Penetapan,
            'is_active' => true,
        ]);

        $dashboard = app(DashboardService::class)->build($cycle, null);

        $this->assertSame(['Standar Baru'], $dashboard['standards']->pluck('nama')->all());
    }

    public function test_self_evaluation_shows_indicators_from_the_cycle_edition_only(): void
    {
        $this->seed(RoleSeeder::class);
        $oldEdition = $this->createEdition('SPMI-OLD', 2020, 2024);
        $cycleEdition = $this->createEdition('SPMI-CURRENT', 2025, 2029);
        $unit = Unit::create(['kode' => 'ED-TEST', 'nama' => 'Unit Uji', 'tipe' => UnitType::Prodi]);
        $admin = User::factory()->create(['unit_id' => $unit->id]);
        $admin->assignRole('admin_spmi');
        $cycle = Cycle::create([
            'standard_edition_id' => $cycleEdition->id,
            'tahun' => 2025,
            'nama' => 'Siklus Uji',
            'tahap_aktif' => CycleStage::Pelaksanaan,
            'is_active' => true,
        ]);

        foreach ([
            [$oldEdition, 'Indikator edisi lama'],
            [$cycleEdition, 'Indikator edisi aktif'],
        ] as [$edition, $indicatorName]) {
            $standard = Standard::create([
                'standard_edition_id' => $edition->id,
                'kode' => 'S01',
                'nomor' => 1,
                'nama' => $indicatorName,
                'kategori' => 'sn_dikti',
                'is_active' => true,
            ]);
            $statement = Statement::create([
                'standard_id' => $standard->id,
                'pernyataan' => $indicatorName,
                'periode_evaluasi' => EvaluationPeriod::SetiapSemester,
            ]);
            StatementAssignment::create([
                'statement_id' => $statement->id,
                'unit_id' => $unit->id,
                'jabatan' => 'koordinator_prodi',
            ]);
            Indicator::create([
                'standard_id' => $standard->id,
                'statement_id' => $statement->id,
                'kode' => '1.01',
                'nama' => $indicatorName,
                'tipe' => 'persen',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('self-evaluations.index', ['unit_id' => $unit->id, 'semester' => Semester::Ganjil->value]))
            ->assertSee('Indikator edisi aktif')
            ->assertDontSee('Indikator edisi lama')
            ->assertSee('evaluation-table-shell', false)
            ->assertSee('data-label="Baseline / Target"', false)
            ->assertSee('grid-template-columns: minmax(0, 1fr)', false);
    }

    public function test_audit_checklist_generation_uses_indicators_from_the_cycle_edition_only(): void
    {
        $this->seed(RoleSeeder::class);
        $oldEdition = $this->createEdition('SPMI-AUDIT-OLD', 2020, 2024);
        $cycleEdition = $this->createEdition('SPMI-AUDIT-CURRENT', 2025, 2029);
        $unit = Unit::create(['kode' => 'AUD-EDITION', 'nama' => 'Unit Audit Edisi', 'tipe' => UnitType::Prodi]);
        $admin = User::factory()->create(['unit_id' => $unit->id]);
        $admin->assignRole('admin_spmi');
        $cycle = Cycle::create([
            'standard_edition_id' => $cycleEdition->id,
            'tahun' => 2025,
            'nama' => 'Siklus Audit Edisi',
            'tahap_aktif' => CycleStage::Evaluasi,
            'is_active' => true,
        ]);
        $audit = Audit::create([
            'cycle_id' => $cycle->id,
            'unit_id' => $unit->id,
            'semester' => Semester::Ganjil,
        ]);
        $indicators = [];

        foreach ([$oldEdition, $cycleEdition] as $edition) {
            $standard = Standard::create([
                'standard_edition_id' => $edition->id,
                'kode' => 'S01',
                'nomor' => 1,
                'nama' => $edition->kode,
                'kategori' => 'sn_dikti',
                'is_active' => true,
            ]);
            $statement = Statement::create([
                'standard_id' => $standard->id,
                'pernyataan' => 'Pernyataan '.$edition->kode,
                'periode_evaluasi' => EvaluationPeriod::SetiapSemester,
            ]);
            StatementAssignment::create([
                'statement_id' => $statement->id,
                'unit_id' => $unit->id,
                'jabatan' => 'koordinator_prodi',
            ]);
            $indicators[$edition->id] = Indicator::create([
                'standard_id' => $standard->id,
                'statement_id' => $statement->id,
                'kode' => '1.01',
                'nama' => 'Indikator '.$edition->kode,
                'tipe' => 'persen',
            ]);
        }

        $this->actingAs($admin)
            ->post(route('audits.checklist.generate', $audit))
            ->assertRedirect();

        $this->assertDatabaseHas('audit_checklists', [
            'audit_id' => $audit->id,
            'indicator_id' => $indicators[$cycleEdition->id]->id,
        ]);
        $this->assertDatabaseMissing('audit_checklists', [
            'audit_id' => $audit->id,
            'indicator_id' => $indicators[$oldEdition->id]->id,
        ]);
    }

    public function test_cycle_year_must_be_within_the_selected_edition_period(): void
    {
        $this->seed(RoleSeeder::class);
        $edition = $this->createEdition('SPMI-LIMITED', 2025, 2029);
        $admin = User::factory()->create();
        $admin->assignRole('admin_spmi');

        $this->actingAs($admin)
            ->post(route('cycles.store'), [
                'standard_edition_id' => $edition->id,
                'tahun' => 2030,
                'nama' => 'Siklus Di Luar Masa Berlaku',
            ])
            ->assertSessionHasErrors([
                'tahun' => 'Tahun siklus harus berada dalam masa berlaku edisi standar yang dipilih.',
            ]);

        $this->assertDatabaseMissing('cycles', ['tahun' => 2030]);
    }

    public function test_admin_can_create_a_standard_edition_with_its_validity_period(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin_spmi');

        $this->actingAs($admin)
            ->post(route('standard-editions.store'), [
                'kode' => 'SPMI-2022',
                'nama' => 'Edisi Standar 2022',
                'tahun_mulai' => 2022,
                'tahun_selesai' => 2026,
                'deskripsi' => 'Standar periode historis',
            ])
            ->assertRedirect(route('standard-editions.index'));

        $this->assertDatabaseHas('standard_editions', [
            'kode' => 'SPMI-2022',
            'tahun_mulai' => 2022,
            'tahun_selesai' => 2026,
        ]);
    }

    public function test_standard_edition_rejects_an_end_year_before_its_start_year(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin_spmi');

        $this->actingAs($admin)
            ->post(route('standard-editions.store'), [
                'kode' => 'SPMI-INVALID',
                'nama' => 'Edisi Tidak Valid',
                'tahun_mulai' => 2026,
                'tahun_selesai' => 2025,
            ])
            ->assertSessionHasErrors([
                'tahun_selesai' => 'Tahun akhir harus sama dengan atau setelah tahun mulai.',
            ]);

        $this->assertDatabaseMissing('standard_editions', ['kode' => 'SPMI-INVALID']);
    }

    public function test_standard_code_can_be_reused_in_another_edition_but_not_the_same_edition(): void
    {
        $this->seed(RoleSeeder::class);
        $firstEdition = $this->createEdition('SPMI-FIRST', 2020, 2024);
        $secondEdition = $this->createEdition('SPMI-SECOND', 2025, 2029);
        $admin = User::factory()->create();
        $admin->assignRole('admin_spmi');
        $payload = [
            'nomor' => 1,
            'kode' => 'S01',
            'nama' => 'Standar Uji',
            'kategori' => 'sn_dikti',
            'is_active' => '1',
        ];

        $this->actingAs($admin)
            ->post(route('standards.store'), ['standard_edition_id' => $firstEdition->id, ...$payload])
            ->assertRedirect(route('standards.index'));
        $this->post(route('standards.store'), ['standard_edition_id' => $secondEdition->id, ...$payload])
            ->assertRedirect(route('standards.index'));
        $this->post(route('standards.store'), ['standard_edition_id' => $firstEdition->id, ...$payload])
            ->assertSessionHasErrors('kode');

        $this->assertDatabaseCount('standards', 2);
    }

    public function test_indicator_form_uses_the_selected_editions_target_years(): void
    {
        $this->seed(RoleSeeder::class);
        $edition = $this->createEdition('SPMI-HISTORIC', 2022, 2026);
        $standard = Standard::create([
            'standard_edition_id' => $edition->id,
            'kode' => 'S01',
            'nomor' => 1,
            'nama' => 'Standar Historis',
            'kategori' => 'sn_dikti',
            'is_active' => true,
        ]);
        $statement = Statement::create([
            'standard_id' => $standard->id,
            'pernyataan' => 'Pernyataan historis',
            'periode_evaluasi' => EvaluationPeriod::SetiapSemester,
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('admin_spmi');

        $this->actingAs($admin)
            ->get(route('indicators.create', ['statement_id' => $statement->id]))
            ->assertSee('Baseline 2021')
            ->assertSee('Target 2022')
            ->assertSee('Target 2026')
            ->assertDontSee('Target 2030');

        $this->post(route('indicators.store'), [
            'statement_id' => $statement->id,
            'kode' => '1.01',
            'nama' => 'Indikator Historis',
            'tipe' => 'persen',
            'bobot' => 1,
            'baseline' => '50',
            'target_2022' => '60',
            'target_2026' => '90',
        ])->assertRedirect(route('indicators.index'));

        $indicator = Indicator::where('kode', '1.01')->firstOrFail();
        $this->assertDatabaseHas('indicator_targets', [
            'indicator_id' => $indicator->id,
            'jenis' => 'baseline',
            'tahun' => 2021,
            'nilai' => 50,
        ]);
        $this->assertDatabaseHas('indicator_targets', [
            'indicator_id' => $indicator->id,
            'jenis' => 'target',
            'tahun' => 2026,
            'nilai' => 90,
        ]);
    }

    public function test_cycle_edition_cannot_change_after_an_audit_exists(): void
    {
        $this->seed(RoleSeeder::class);
        $originalEdition = $this->createEdition('SPMI-ORIGINAL', 2025, 2029);
        $replacementEdition = $this->createEdition('SPMI-REPLACEMENT', 2025, 2029);
        $unit = Unit::create(['kode' => 'AUD-TEST', 'nama' => 'Unit Audit', 'tipe' => UnitType::Prodi]);
        $admin = User::factory()->create();
        $admin->assignRole('admin_spmi');
        $cycle = Cycle::create([
            'standard_edition_id' => $originalEdition->id,
            'tahun' => 2025,
            'nama' => 'Siklus Berjalan',
            'tahap_aktif' => CycleStage::Evaluasi,
            'is_active' => true,
        ]);
        Audit::create([
            'cycle_id' => $cycle->id,
            'unit_id' => $unit->id,
            'semester' => Semester::Ganjil,
        ]);

        $this->actingAs($admin)
            ->put(route('cycles.update', $cycle), [
                'standard_edition_id' => $replacementEdition->id,
                'tahun' => 2025,
                'nama' => 'Siklus Berjalan',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors([
                'standard_edition_id' => 'Edisi tidak dapat diubah karena siklus ini sudah memiliki evaluasi diri atau audit.',
            ]);

        $this->assertSame($originalEdition->id, $cycle->fresh()->standard_edition_id);
    }

    public function test_standard_cannot_be_modified_after_its_edition_has_audit_data(): void
    {
        $this->seed(RoleSeeder::class);
        $edition = $this->createEdition('SPMI-FROZEN', 2025, 2029);
        $unit = Unit::create(['kode' => 'FROZEN-TEST', 'nama' => 'Unit Arsip', 'tipe' => UnitType::Prodi]);
        $admin = User::factory()->create();
        $admin->assignRole('admin_spmi');
        Standard::create([
            'standard_edition_id' => $edition->id,
            'kode' => 'S01',
            'nomor' => 1,
            'nama' => 'Standar Sebelum Audit',
            'kategori' => 'sn_dikti',
            'is_active' => true,
        ]);
        $cycle = Cycle::create([
            'standard_edition_id' => $edition->id,
            'tahun' => 2025,
            'nama' => 'Siklus Arsip',
            'tahap_aktif' => CycleStage::Evaluasi,
            'is_active' => true,
        ]);
        Audit::create([
            'cycle_id' => $cycle->id,
            'unit_id' => $unit->id,
            'semester' => Semester::Ganjil,
        ]);
        $standard = Standard::firstOrFail();

        $this->actingAs($admin)
            ->put(route('standards.update', $standard), [
                'standard_edition_id' => $edition->id,
                'nomor' => 1,
                'kode' => 'S01',
                'nama' => 'Standar Diubah',
                'kategori' => 'sn_dikti',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors([
                'standard_edition_id' => 'Isi edisi yang sudah memiliki hasil evaluasi atau audit tidak dapat diubah. Buat edisi baru untuk revisi.',
            ]);

        $this->assertDatabaseHas('standards', [
            'id' => $standard->id,
            'nama' => 'Standar Sebelum Audit',
        ]);
    }

    private function createEdition(string $code, int $startYear, ?int $endYear): StandardEdition
    {
        return StandardEdition::create([
            'kode' => $code,
            'nama' => $code,
            'tahun_mulai' => $startYear,
            'tahun_selesai' => $endYear,
        ]);
    }
}
