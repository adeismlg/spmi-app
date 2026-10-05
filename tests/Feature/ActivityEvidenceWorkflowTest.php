<?php

namespace Tests\Feature;

use App\Enums\CycleStage;
use App\Enums\EvaluationPeriod;
use App\Enums\EvidenceStatus;
use App\Enums\IndicatorType;
use App\Enums\Jabatan;
use App\Enums\Semester;
use App\Enums\StandardCategory;
use App\Enums\SubmissionStatus;
use App\Enums\UnitType;
use App\Models\Activity;
use App\Models\Audit;
use App\Models\Cycle;
use App\Models\Evidence;
use App\Models\Indicator;
use App\Models\ReopenRequest;
use App\Models\SelfEvaluation;
use App\Models\SemesterWindow;
use App\Models\Standard;
use App\Models\Statement;
use App\Models\StatementAssignment;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityEvidenceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_auditee_and_auditor_complete_the_evidence_review_and_reopen_flow(): void
    {
        Storage::fake('local');
        $this->createRoles();
        [$unit, $indicator, $cycle, $window, $activity] = $this->createEvaluationSetup();
        $auditee = $this->createUser('auditee', $unit, Jabatan::KoordinatorProdi);
        $evaluation = SelfEvaluation::create([
            'cycle_id' => $cycle->id,
            'unit_id' => $unit->id,
            'indicator_id' => $indicator->id,
            'semester' => Semester::Ganjil->value,
            'status' => SubmissionStatus::Draft,
        ]);

        $this->actingAs($auditee)
            ->put(route('self-evaluations.update'), [
                'semester' => Semester::Ganjil->value,
                'items' => [$indicator->id => ['capaian' => '', 'skor' => '80', 'uraian' => 'Pelaksanaan selesai']],
                'submit' => '1',
            ])
            ->assertStatus(422);

        $this->actingAs($auditee)
            ->post(route('activities.evidences.store', [$evaluation->id, $activity->id]), [
                'judul' => 'Notulen kegiatan',
                'berkas' => UploadedFile::fake()->create('notulen.pdf', 30, 'application/pdf'),
            ])
            ->assertRedirect();

        $evidence = Evidence::firstOrFail();
        $this->assertSame($activity->id, $evidence->activity_id);
        $this->assertSame(EvidenceStatus::Pending, $evidence->status);
        Storage::disk('local')->assertExists($evidence->file_path);

        $auditor = $this->createUser('auditor');
        $audit = Audit::create([
            'cycle_id' => $cycle->id,
            'unit_id' => $unit->id,
            'semester' => Semester::Ganjil,
        ]);
        $audit->auditors()->attach($auditor->id, ['peran' => 'anggota']);
        $this->actingAs($auditor)
            ->get(route('self-evaluations.show', $evaluation->id))
            ->assertForbidden();

        $otherUnit = Unit::create([
            'kode' => 'OTHER',
            'nama' => 'Unit Lain',
            'tipe' => UnitType::Prodi,
        ]);
        $otherUser = $this->createUser('auditee', $otherUnit, Jabatan::KoordinatorProdi);
        $this->actingAs($otherUser)
            ->get(route('evidences.download', $evidence->id))
            ->assertForbidden();

        $this->actingAs($auditee)
            ->put(route('self-evaluations.update'), [
                'semester' => Semester::Ganjil->value,
                'items' => [$indicator->id => ['capaian' => '', 'skor' => '80', 'uraian' => 'Pelaksanaan selesai']],
                'submit' => '1',
            ])
            ->assertRedirect();
        $this->assertSame(SubmissionStatus::Submitted, $evaluation->fresh()->status);
        $window->update(['pengisian_selesai' => now()->subMinute()]);
        $this->actingAs($auditee)
            ->put(route('self-evaluations.update'), [
                'semester' => Semester::Ganjil->value,
                'items' => [$indicator->id => ['capaian' => '', 'skor' => '95', 'uraian' => 'Perubahan tanpa izin']],
            ])
            ->assertStatus(422);

        $cycle->update(['tahap_aktif' => CycleStage::Evaluasi]);

        $this->actingAs($auditor)
            ->put(route('evidences.review', $evidence->id), [
                'status' => EvidenceStatus::Revision->value,
                'komentar' => 'Mohon unggah notulen yang sudah ditandatangani.',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('evidence_reviews', [
            'evidence_id' => $evidence->id,
            'reviewer_id' => $auditor->id,
            'status' => EvidenceStatus::Revision->value,
            'komentar' => 'Mohon unggah notulen yang sudah ditandatangani.',
        ]);

        $this->actingAs($auditee)
            ->post(route('reopen-requests.store', $evaluation->id), ['alasan' => 'Perlu mengganti bukti yang salah.'])
            ->assertRedirect();
        $reopen = ReopenRequest::firstOrFail();

        $admin = $this->createUser('admin_spmi');
        $this->actingAs($admin)
            ->post(route('reopen-requests.resolve', $reopen->id), [
                'keputusan' => 'approve',
                'catatan_admin' => 'Silakan perbaiki dan ajukan kembali.',
            ])
            ->assertRedirect();
        $this->assertSame(SubmissionStatus::Draft, $evaluation->fresh()->status);

        $this->actingAs($auditee)
            ->post(route('activities.evidences.store', [$evaluation->id, $activity->id]), [
                'replaces_evidence_id' => $evidence->id,
                'judul' => 'Notulen kegiatan setelah diperbaiki',
                'berkas' => UploadedFile::fake()->create('notulen-revisi.pdf', 30, 'application/pdf'),
            ])
            ->assertRedirect();
        $replacement = Evidence::where('id', '!=', $evidence->id)->firstOrFail();
        $this->assertSame(EvidenceStatus::Superseded, $evidence->fresh()->status);
        $this->assertDatabaseHas('evidence_reviews', ['evidence_id' => $evidence->id]);

        $this->actingAs($auditee)
            ->put(route('self-evaluations.update'), [
                'semester' => Semester::Ganjil->value,
                'items' => [$indicator->id => ['capaian' => '', 'skor' => '85', 'uraian' => 'Diperbaiki']],
                'submit' => '1',
            ])
            ->assertRedirect();
        $this->assertSame(SubmissionStatus::Submitted, $evaluation->fresh()->status);
        $this->assertSame('completed', $reopen->fresh()->status->value);

        $this->actingAs($auditor)
            ->put(route('evidences.review', $replacement->id), [
                'status' => EvidenceStatus::Verified->value,
            ])
            ->assertRedirect();

        $this->assertSame(EvidenceStatus::Verified, $replacement->fresh()->status);
        $this->assertSame(SubmissionStatus::Verified, $evaluation->fresh()->status);
        $this->assertDatabaseCount('evidence_reviews', 2);
        $this->actingAs($auditor)
            ->get(route('self-evaluations.show', $evaluation->id))
            ->assertOk()
            ->assertSee('Mohon unggah notulen yang sudah ditandatangani.')
            ->assertSee('Seluruh bukti terverifikasi');
        $this->actingAs($auditor)
            ->get(route('evidences.download', $replacement->id))
            ->assertOk();
    }

    public function test_admin_can_create_activities_and_semester_windows_but_non_admin_cannot(): void
    {
        $this->createRoles();
        [$unit, $indicator, $cycle] = $this->createEvaluationSetup();
        $admin = $this->createUser('admin_spmi');
        $auditee = $this->createUser('auditee', $unit, Jabatan::KoordinatorProdi);

        $this->actingAs($auditee)
            ->post(route('activities.store'), [
                'indicator_id' => $indicator->id,
                'nama' => 'Kegiatan tidak diizinkan',
                'urutan' => 1,
                'is_active' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('activities.store'), [
                'indicator_id' => $indicator->id,
                'nama' => 'Rapat evaluasi',
                'deskripsi' => 'Unggah notulen dan daftar hadir.',
                'urutan' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect(route('activities.index'));
        $this->assertDatabaseHas('activities', [
            'indicator_id' => $indicator->id,
            'nama' => 'Rapat evaluasi',
        ]);

        $otherCycle = Cycle::create([
            'tahun' => 2027,
            'nama' => 'Siklus Uji Berikutnya',
            'tahap_aktif' => CycleStage::Penetapan,
            'is_active' => false,
        ]);
        $this->actingAs($admin)
            ->post(route('semester-windows.store'), [
                'cycle_id' => $otherCycle->id,
                'semester' => Semester::Genap->value,
                'pengisian_mulai' => now()->addDays(1)->format('Y-m-d\TH:i'),
                'pengisian_selesai' => now()->addDays(5)->format('Y-m-d\TH:i'),
                'pemeriksaan_mulai' => now()->addDays(6)->format('Y-m-d\TH:i'),
                'pemeriksaan_selesai' => now()->addDays(10)->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('semester-windows.index'));

        $this->assertDatabaseHas('semester_windows', [
            'cycle_id' => $otherCycle->id,
            'semester' => Semester::Genap->value,
        ]);

        $this->actingAs($admin)
            ->post(route('semester-windows.store'), [
                'cycle_id' => $cycle->id,
                'semester' => Semester::Genap->value,
                'pengisian_mulai' => now()->addDays(1)->format('Y-m-d\TH:i'),
                'pengisian_selesai' => now()->addDays(5)->format('Y-m-d\TH:i'),
                'pemeriksaan_mulai' => now()->addDays(4)->format('Y-m-d\TH:i'),
                'pemeriksaan_selesai' => now()->addDays(10)->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors('pemeriksaan_mulai');
    }

    private function createRoles(): void
    {
        foreach (['admin_spmi', 'auditee', 'auditor'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    private function createUser(string $role, ?Unit $unit = null, ?Jabatan $jabatan = null): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'email' => uniqid($role, true).'@example.test',
            'password' => 'password',
            'unit_id' => $unit?->id,
            'jabatan' => $jabatan,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function createEvaluationSetup(): array
    {
        $institution = Unit::create([
            'kode' => 'INST',
            'nama' => 'Institusi',
            'tipe' => UnitType::Institusi,
        ]);
        $unit = Unit::create([
            'kode' => 'PRODI',
            'nama' => 'Program Studi',
            'tipe' => UnitType::Prodi,
            'parent_id' => $institution->id,
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
            'tipe' => IndicatorType::Teks,
        ]);
        StatementAssignment::create([
            'statement_id' => $statement->id,
            'unit_id' => $unit->id,
            'jabatan' => Jabatan::KoordinatorProdi,
            'sumber' => 'otomatis',
        ]);
        $cycle = Cycle::create([
            'tahun' => 2026,
            'nama' => 'Siklus Uji',
            'tahap_aktif' => CycleStage::Pelaksanaan,
            'is_active' => true,
        ]);
        $window = SemesterWindow::create([
            'cycle_id' => $cycle->id,
            'semester' => Semester::Ganjil,
            'pengisian_mulai' => now()->subDay(),
            'pengisian_selesai' => now()->addDay(),
            'pemeriksaan_mulai' => now()->subDay(),
            'pemeriksaan_selesai' => now()->addDays(2),
        ]);
        $activity = Activity::create([
            'indicator_id' => $indicator->id,
            'nama' => 'Pelaksanaan kegiatan',
            'urutan' => 1,
            'is_active' => true,
        ]);

        return [$unit, $indicator, $cycle, $window, $activity];
    }
}
