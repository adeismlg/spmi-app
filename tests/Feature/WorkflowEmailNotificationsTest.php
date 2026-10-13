<?php

namespace Tests\Feature;

use App\Enums\ActionStatus;
use App\Enums\AuditStatus;
use App\Enums\Conformity;
use App\Enums\CycleStage;
use App\Enums\EvaluationPeriod;
use App\Enums\EvidenceStatus;
use App\Enums\IndicatorType;
use App\Enums\Jabatan;
use App\Enums\Semester;
use App\Enums\SubmissionStatus;
use App\Enums\UnitType;
use App\Models\Activity;
use App\Models\Audit;
use App\Models\CorrectiveAction;
use App\Models\Cycle;
use App\Models\Evidence;
use App\Models\Finding;
use App\Models\Indicator;
use App\Models\SelfEvaluation;
use App\Models\SemesterWindow;
use App\Models\Standard;
use App\Models\Statement;
use App\Models\StatementAssignment;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\AuditScheduleChanged;
use App\Notifications\CorrectiveActionChanged;
use App\Notifications\EvaluationSubmitted;
use App\Notifications\UserAccountCreated;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkflowEmailNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_audit_notifies_its_auditor_and_only_its_auditee_unit(): void
    {
        $this->seed(RoleSeeder::class);
        $unit = $this->createUnit('AUDIT-UNIT', 'Prodi Audit');
        $otherUnit = $this->createUnit('OTHER-UNIT', 'Prodi Lain');
        $cycle = $this->createCycle(CycleStage::Evaluasi);
        $admin = $this->createUser('admin_spmi');
        $auditor = $this->createUser('auditor');
        $auditee = $this->createUser('auditee', $unit, Jabatan::KoordinatorProdi);
        $unrelatedAuditee = $this->createUser('auditee', $otherUnit, Jabatan::KoordinatorProdi);
        Notification::fake();

        $this->actingAs($admin)
            ->post(route('audits.store'), [
                'cycle_id' => $cycle->id,
                'unit_id' => $unit->id,
                'semester' => Semester::Ganjil->value,
                'tanggal_desk_evaluation' => '2026-10-20',
                'tanggal_visitasi' => '2026-10-25',
                'status' => AuditStatus::Terjadwal->value,
                'ketua' => $auditor->id,
                'auditors' => [],
            ])
            ->assertRedirect(route('audits.index'));

        Notification::assertSentTo([$auditor, $auditee], AuditScheduleChanged::class);
        Notification::assertNotSentTo($unrelatedAuditee, AuditScheduleChanged::class);
        $this->assertDatabaseHas('audits', [
            'cycle_id' => $cycle->id,
            'unit_id' => $unit->id,
            'tanggal_desk_evaluation' => '2026-10-20 00:00:00',
        ]);
    }

    public function test_updating_an_audit_schedule_notifies_current_and_previous_auditors_but_not_for_unchanged_data(): void
    {
        $this->seed(RoleSeeder::class);
        $unit = $this->createUnit('UPDATED-AUDIT', 'Prodi Audit Diperbarui');
        $cycle = $this->createCycle(CycleStage::Evaluasi);
        $admin = $this->createUser('admin_spmi');
        $previousAuditor = $this->createUser('auditor');
        $currentAuditor = $this->createUser('auditor');
        $auditee = $this->createUser('auditee', $unit, Jabatan::KoordinatorProdi);
        $audit = Audit::create([
            'cycle_id' => $cycle->id,
            'unit_id' => $unit->id,
            'semester' => Semester::Ganjil,
            'tanggal_desk_evaluation' => '2026-10-20',
            'tanggal_visitasi' => '2026-10-25',
            'status' => AuditStatus::Terjadwal,
        ]);
        $audit->auditors()->attach($previousAuditor->id, ['peran' => 'ketua']);
        Notification::fake();

        $this->actingAs($admin)
            ->put(route('audits.update', $audit->id), [
                'cycle_id' => $cycle->id,
                'unit_id' => $unit->id,
                'semester' => Semester::Ganjil->value,
                'tanggal_desk_evaluation' => '2026-10-21',
                'tanggal_visitasi' => '2026-10-26',
                'status' => AuditStatus::Terjadwal->value,
                'ketua' => $currentAuditor->id,
                'auditors' => [],
            ])
            ->assertRedirect(route('audits.index'));

        Notification::assertSentTo(
            [$previousAuditor, $currentAuditor, $auditee],
            AuditScheduleChanged::class,
            fn (AuditScheduleChanged $notification): bool => ! $notification->created,
        );
        $this->assertDatabaseHas('audits', [
            'id' => $audit->id,
            'tanggal_desk_evaluation' => '2026-10-21 00:00:00',
        ]);

        Notification::fake();

        $this->actingAs($admin)
            ->put(route('audits.update', $audit->id), [
                'cycle_id' => $cycle->id,
                'unit_id' => $unit->id,
                'semester' => Semester::Ganjil->value,
                'tanggal_desk_evaluation' => '2026-10-21',
                'tanggal_visitasi' => '2026-10-26',
                'status' => AuditStatus::Terjadwal->value,
                'ketua' => $currentAuditor->id,
                'auditors' => [],
            ])
            ->assertRedirect(route('audits.index'));

        Notification::assertNothingSent();
    }

    public function test_submitting_self_evaluation_notifies_admin_and_assigned_auditor(): void
    {
        config(['services.dosen_email' => 'dosen.pembimbing@polinema.ac.id']);
        $this->seed(RoleSeeder::class);
        $unit = $this->createUnit('SUBMIT-UNIT', 'Prodi Pengaju');
        $cycle = $this->createCycle(CycleStage::Pelaksanaan);
        $admin = $this->createUser('admin_spmi');
        $auditor = $this->createUser('auditor');
        $auditee = $this->createUser('auditee', $unit, Jabatan::KoordinatorProdi);
        $indicator = $this->createAssignedIndicator($unit);
        SemesterWindow::create([
            'cycle_id' => $cycle->id,
            'semester' => Semester::Ganjil,
            'pengisian_mulai' => now()->subDay(),
            'pengisian_selesai' => now()->addDay(),
            'pemeriksaan_mulai' => now()->subDay(),
            'pemeriksaan_selesai' => now()->addDays(2),
        ]);
        $activity = Activity::create([
            'indicator_id' => $indicator->id,
            'nama' => 'Unggah bukti kegiatan',
            'urutan' => 1,
            'is_active' => true,
        ]);
        $evaluation = SelfEvaluation::create([
            'cycle_id' => $cycle->id,
            'unit_id' => $unit->id,
            'indicator_id' => $indicator->id,
            'semester' => Semester::Ganjil->value,
            'status' => SubmissionStatus::Draft,
        ]);
        Evidence::create([
            'self_evaluation_id' => $evaluation->id,
            'activity_id' => $activity->id,
            'judul' => 'Bukti rapat mutu',
            'uploaded_by' => $auditee->id,
            'status' => EvidenceStatus::Pending,
        ]);
        $audit = Audit::create([
            'cycle_id' => $cycle->id,
            'unit_id' => $unit->id,
            'semester' => Semester::Ganjil,
        ]);
        $audit->auditors()->attach($auditor->id, ['peran' => 'ketua']);
        Notification::fake();

        $this->actingAs($auditee)
            ->put(route('self-evaluations.update'), [
                'semester' => Semester::Ganjil->value,
                'items' => [$indicator->id => [
                    'capaian' => '',
                    'skor' => '80',
                    'uraian' => 'Evaluasi telah dilengkapi.',
                ]],
                'submit' => '1',
            ])
            ->assertRedirect();

        Notification::assertSentTo([$admin, $auditor], EvaluationSubmitted::class);
        Notification::assertSentOnDemand(
            EvaluationSubmitted::class,
            fn (EvaluationSubmitted $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'dosen.pembimbing@polinema.ac.id',
        );
        $this->assertSame(SubmissionStatus::Submitted, $evaluation->fresh()->status);
    }

    public function test_creating_a_user_account_notifies_the_configured_dosen_email(): void
    {
        config(['services.dosen_email' => 'dosen.pembimbing@polinema.ac.id']);
        $this->seed(RoleSeeder::class);
        $unit = $this->createUnit('USER-UNIT', 'Unit Pengguna Baru');
        $admin = $this->createUser('admin_spmi');
        Notification::fake();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Pengguna Baru',
                'email' => 'pengguna.baru@polinema.ac.id',
                'password' => 'password-rahasia',
                'unit_id' => $unit->id,
                'jabatan' => Jabatan::KoordinatorProdi->value,
                'role' => 'auditee',
            ])
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'pengguna.baru@polinema.ac.id')->firstOrFail();
        Notification::assertSentOnDemand(
            UserAccountCreated::class,
            fn (UserAccountCreated $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'dosen.pembimbing@polinema.ac.id'
                && $notification->user->is($user),
        );
        $this->assertSame('auditee', $user->getRoleNames()->first());

        Notification::fake();

        $this->actingAs($admin)
            ->put(route('users.update', $user->id), [
                'name' => 'Pengguna Baru Diperbarui',
                'email' => 'pengguna.baru@polinema.ac.id',
                'unit_id' => $unit->id,
                'jabatan' => Jabatan::KoordinatorProdi->value,
                'role' => 'auditee',
            ])
            ->assertRedirect(route('users.index'));

        Notification::assertNothingSent();
    }

    public function test_creating_updating_and_verifying_an_rtl_notifies_its_pic_and_unit_auditees(): void
    {
        $this->seed(RoleSeeder::class);
        $unit = $this->createUnit('RTL-UNIT', 'Prodi RTL');
        $otherUnit = $this->createUnit('RTL-OTHER', 'Prodi Lain');
        $cycle = $this->createCycle(CycleStage::Pengendalian);
        $admin = $this->createUser('admin_spmi');
        $auditee = $this->createUser('auditee', $unit, Jabatan::KoordinatorProdi);
        $unrelatedAuditee = $this->createUser('auditee', $otherUnit, Jabatan::KoordinatorProdi);
        $pic = $this->createUser('auditor');
        $audit = Audit::create([
            'cycle_id' => $cycle->id,
            'unit_id' => $unit->id,
            'semester' => Semester::Ganjil,
        ]);
        $finding = Finding::create([
            'audit_id' => $audit->id,
            'kategori' => Conformity::KtsMinor,
            'uraian' => 'Bukti pelaksanaan belum lengkap.',
        ]);
        Notification::fake();

        $this->actingAs($admin)
            ->post(route('corrective-actions.store'), [
                'finding_id' => $finding->id,
                'akar_masalah' => 'Dokumentasi belum terstandardisasi.',
                'tindakan' => 'Menyusun dan menerapkan daftar kendali bukti.',
                'pic_user_id' => $pic->id,
                'target_selesai' => '2026-11-01',
                'status' => 'rencana',
            ])
            ->assertRedirect(route('corrective-actions.index'));

        Notification::assertSentTo([$pic, $auditee], CorrectiveActionChanged::class);
        Notification::assertNotSentTo($unrelatedAuditee, CorrectiveActionChanged::class);
        $this->assertDatabaseHas('corrective_actions', [
            'finding_id' => $finding->id,
            'pic_user_id' => $pic->id,
        ]);

        $action = CorrectiveAction::firstOrFail();
        Notification::fake();

        $this->actingAs($admin)
            ->put(route('corrective-actions.update', $action->id), [
                'finding_id' => $finding->id,
                'akar_masalah' => 'Dokumentasi belum konsisten antarperiode.',
                'tindakan' => 'Menerapkan daftar kendali bukti pada seluruh kegiatan.',
                'pic_user_id' => $pic->id,
                'target_selesai' => '2026-11-15',
                'status' => ActionStatus::Selesai->value,
            ])
            ->assertRedirect(route('corrective-actions.index'));

        Notification::assertSentTo(
            [$pic, $auditee],
            CorrectiveActionChanged::class,
            fn (CorrectiveActionChanged $notification): bool => ! $notification->created,
        );
        $this->assertSame(ActionStatus::Selesai, $action->fresh()->status);

        Notification::fake();

        $this->actingAs($admin)
            ->post(route('corrective-actions.verify', $action->id), [
                'catatan' => 'Tindak lanjut telah diperiksa.',
            ])
            ->assertRedirect();

        Notification::assertSentTo(
            [$pic, $auditee],
            CorrectiveActionChanged::class,
            fn (CorrectiveActionChanged $notification): bool => ! $notification->created,
        );
        $this->assertSame(ActionStatus::Terverifikasi, $action->fresh()->status);
    }

    private function createUnit(string $code, string $name): Unit
    {
        return Unit::create([
            'kode' => $code,
            'nama' => $name,
            'tipe' => UnitType::Prodi,
        ]);
    }

    private function createCycle(CycleStage $stage): Cycle
    {
        return Cycle::create([
            'tahun' => 2026,
            'nama' => 'Siklus Notifikasi',
            'tahap_aktif' => $stage,
            'is_active' => true,
        ]);
    }

    private function createUser(string $role, ?Unit $unit = null, ?Jabatan $jabatan = null): User
    {
        $user = User::factory()->create([
            'unit_id' => $unit?->id,
            'jabatan' => $jabatan,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function createAssignedIndicator(Unit $unit): Indicator
    {
        $standard = Standard::create([
            'kode' => 'S01',
            'nomor' => 1,
            'nama' => 'Standar Pelaksanaan',
            'kategori' => 'sn_dikti',
            'is_active' => true,
        ]);
        $statement = Statement::create([
            'standard_id' => $standard->id,
            'pernyataan' => 'Pernyataan pelaksanaan standar',
            'periode_evaluasi' => EvaluationPeriod::SetiapSemester,
            'pic_jabatan' => [Jabatan::KoordinatorProdi->value],
        ]);
        StatementAssignment::create([
            'statement_id' => $statement->id,
            'unit_id' => $unit->id,
            'jabatan' => Jabatan::KoordinatorProdi->value,
        ]);

        return Indicator::create([
            'standard_id' => $standard->id,
            'statement_id' => $statement->id,
            'kode' => '1.01',
            'nama' => 'Indikator Pelaksanaan',
            'tipe' => IndicatorType::Teks,
        ]);
    }
}
