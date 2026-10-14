<?php

namespace Tests\Feature;

use App\Enums\Jabatan;
use App\Enums\Jenjang;
use App\Enums\UnitType;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_assigns_roles_and_units_from_the_source_files(): void
    {
        $this->seed(MasterDataSeeder::class);

        $auditee = User::where('email', 'fauzi@polinema.ac.id')->firstOrFail();
        $auditor = User::where('email', 'yunhasnawa@polinema.ac.id')->firstOrFail();
        $sharedAuditee = User::where('email', 'yusna@polinema.ac.id')->firstOrFail();

        $this->assertSame(131, User::count());
        $this->assertTrue($auditee->hasRole('auditee'));
        $this->assertSame('D3 Administrasi Bisnis', $auditee->unit->nama);
        $this->assertSame('JURUSAN ADMINISTRASI NIAGA', $auditee->unit->parent->nama);
        $this->assertSame(UnitType::Prodi, $auditee->unit->tipe);
        $this->assertSame(Jenjang::D3, $auditee->unit->jenjang);
        $this->assertSame(Jabatan::KoordinatorProdi, $auditee->jabatan);
        $this->assertTrue($auditor->hasRole('auditor'));
        $this->assertNull($auditor->unit_id);
        $this->assertFalse(Hash::check('password', $auditor->password));
        $this->assertSame('D4 Akuntansi Manajemen', $sharedAuditee->unit->nama);
        $this->assertDatabaseHas('units', ['nama' => 'PUSAT', 'tipe' => UnitType::UnitKerja->value]);
    }

    public function test_reseeding_keeps_master_data_and_existing_passwords_unchanged(): void
    {
        $this->seed(MasterDataSeeder::class);

        $auditor = User::where('email', 'yunhasnawa@polinema.ac.id')->firstOrFail();
        $auditor->update(['password' => Hash::make('individual-password')]);
        $userCount = User::count();
        $unitCount = Unit::count();

        $this->seed(MasterDataSeeder::class);

        $this->assertSame($userCount, User::count());
        $this->assertSame($unitCount, Unit::count());
        $this->assertTrue(Hash::check('individual-password', $auditor->fresh()->password));
    }
}
