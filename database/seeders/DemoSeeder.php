<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Data contoh untuk mencoba alur PPEPP. Jalankan setelah migrate.
 * Pernyataan, indikator, target, dan penugasan diimpor dari database/data/spmi-polinema-2026.xlsx.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SpmiSeeder::class);
        $this->call(MasterDataSeeder::class);

        // Impor standar -> pernyataan -> indikator -> target -> penugasan otomatis.
        $file = database_path('data/spmi-polinema-2026.xlsx');
        if (is_file($file)) {
            Artisan::call('spmi:import-standards', ['file' => $file, '--edition' => 'SPMI-'.date('Y')]);
            $this->command?->info(trim(Artisan::output()));
        } else {
            $this->command?->warn('File standar tidak ditemukan; jalankan spmi:import-standards secara manual.');
        }

        $this->call(DemoWorkflowSeeder::class);
    }
}
