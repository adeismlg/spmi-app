<?php

namespace Database\Seeders;

use App\Enums\Jabatan;
use App\Enums\Jenjang;
use App\Enums\UnitType;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

/**
 * Data contoh untuk mencoba alur PPEPP. Jalankan setelah migrate.
 * Pernyataan, indikator, target, dan penugasan diimpor dari database/data/spmi-polinema-2026.xlsx.
 * Prodi dibuat LEBIH DULU agar penugasan Koordinator Program Studi punya sasaran.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SpmiSeeder::class);

        $inst = Unit::where('tipe', UnitType::Institusi->value)->first();

        $jti = Unit::firstOrCreate(
            ['kode' => 'JTI'],
            ['nama' => 'Jurusan Teknologi Informasi', 'tipe' => UnitType::Jurusan, 'parent_id' => $inst?->id],
        );

        $prodi = collect([
            ['TI', 'D4 Teknik Informatika', Jenjang::D4],
            ['SIB', 'D4 Sistem Informasi Bisnis', Jenjang::D4],
            ['MI', 'D3 Manajemen Informatika', Jenjang::D3],
        ])->mapWithKeys(fn ($p) => [$p[0] => Unit::updateOrCreate(
            ['kode' => $p[0]],
            ['nama' => $p[1], 'tipe' => UnitType::Prodi, 'jenjang' => $p[2], 'parent_id' => $jti->id],
        )]);

        // Impor standar -> pernyataan -> indikator -> target -> penugasan otomatis.
        $file = database_path('data/spmi-polinema-2026.xlsx');
        if (is_file($file)) {
            Artisan::call('spmi:import-standards', ['file' => $file]);
            $this->command?->info(trim(Artisan::output()));
        } else {
            $this->command?->warn('File standar tidak ditemukan; jalankan spmi:import-standards secara manual.');
        }

        $p3m = Unit::where('singkatan', 'P3M')->first();

        // [email, nama, peran, unit, jabatan]
        $accounts = [
            ['pimpinan@spmi.test', 'Pimpinan', 'pimpinan', $inst, null],
            ['auditor1@spmi.test', 'Auditor Satu', 'auditor', null, null],
            ['auditor2@spmi.test', 'Auditor Dua', 'auditor', null, null],
            ['kaprodi.ti@spmi.test', 'Koordinator Prodi TI', 'auditee', $prodi['TI'], Jabatan::KoordinatorProdi],
            ['kaprodi.sib@spmi.test', 'Koordinator Prodi SIB', 'auditee', $prodi['SIB'], Jabatan::KoordinatorProdi],
            ['kaprodi.mi@spmi.test', 'Koordinator Prodi MI', 'auditee', $prodi['MI'], Jabatan::KoordinatorProdi],
            ['kajur.jti@spmi.test', 'Ketua Jurusan TI', 'auditee', $jti, Jabatan::KetuaJurusan],
            ['ketua.p3m@spmi.test', 'Ketua P3M', 'auditee', $p3m, Jabatan::KetuaUnit],
            ['wadir1@spmi.test', 'Wakil Direktur I', 'auditee', $inst, Jabatan::Wadir1],
            ['wadir2@spmi.test', 'Wakil Direktur II', 'auditee', $inst, Jabatan::Wadir2],
            ['wadir3@spmi.test', 'Wakil Direktur III', 'auditee', $inst, Jabatan::Wadir3],
            ['direktur@spmi.test', 'Direktur', 'auditee', $inst, Jabatan::Direktur],
        ];

        foreach ($accounts as [$email, $name, $role, $unit, $jabatan]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make('password'), 'unit_id' => $unit?->id, 'jabatan' => $jabatan],
            );
            $user->syncRoles([$role]);
        }
    }
}
