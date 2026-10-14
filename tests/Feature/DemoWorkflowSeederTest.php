<?php

namespace Tests\Feature;

use App\Models\Cycle;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoWorkflowSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseeding_demo_workflow_keeps_sample_data_visible_without_duplicates(): void
    {
        Storage::fake('public');

        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $cycle = Cycle::current();
        $dashboard = app(DashboardService::class)->build($cycle, null);
        $demoAuditee = User::role('auditee')->orderBy('email')->firstOrFail();

        $this->assertDatabaseCount('self_evaluations', 4);
        $this->assertDatabaseCount('activities', 4);
        $this->assertDatabaseCount('evidences', 3);
        $this->assertDatabaseCount('evidence_reviews', 2);
        $this->assertDatabaseCount('audits', 1);
        $this->assertDatabaseCount('audit_checklists', 4);
        $this->assertDatabaseCount('findings', 3);
        $this->assertDatabaseCount('corrective_actions', 3);
        $this->assertDatabaseCount('management_reviews', 1);
        $this->assertDatabaseCount('documents', 4);
        $this->assertSame(1, $dashboard['stats']['unit_submitted']);
        $this->assertSame(1, $dashboard['stats']['audits_done']);
        $this->assertSame(3, $dashboard['stats']['findings_total']);
        $this->assertSame(1, $dashboard['stats']['actions_overdue']);
        $this->assertTrue($demoAuditee->hasRole('auditee'));
        $this->assertDatabaseMissing('users', ['email' => 'demo.auditee@spmi.test']);
        $this->assertDatabaseMissing('units', ['kode' => 'DEMO-PRODI']);

        Storage::disk('public')->assertExists([
            'demo/documents/kebijakan-mutu.txt',
            'demo/documents/manual-ppepp.txt',
            'demo/documents/standar-evaluasi-pembelajaran.txt',
            'demo/documents/formulir-audit-mutu.txt',
        ]);
        $this->assertStringContainsString(
            'DATA DEMO, BUKAN KEBIJAKAN RESMI',
            Storage::disk('public')->get('demo/documents/kebijakan-mutu.txt'),
        );

        $this->actingAs($demoAuditee)
            ->get(route('dashboard'))
            ->assertSee('Audit selesai')
            ->assertSee('1 / 1')
            ->assertSee('RTL terlambat');

        $this->actingAs($demoAuditee)
            ->get(route('documents.index'))
            ->assertSee('[DATA DEMO] Kebijakan Mutu Internal')
            ->assertSee('[DATA DEMO] Manual Siklus PPEPP')
            ->assertDontSee('[DATA DEMO] Standar Evaluasi Pembelajaran')
            ->assertDontSee('[DATA DEMO] Formulir Audit Mutu Internal');
    }
}
