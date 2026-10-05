<?php

namespace App\Console\Commands;

use App\Services\AssignmentService;
use Illuminate\Console\Command;

class AssignStatements extends Command
{
    protected $signature = 'spmi:assign-statements {--fresh : Hapus penugasan otomatis lama sebelum menghitung ulang}';

    protected $description = 'Hitung penugasan pernyataan standar ke unit/prodi berdasarkan PIC baku';

    public function handle(AssignmentService $service): int
    {
        $r = $service->generate((bool) $this->option('fresh'));

        $this->info("Pernyataan: {$r['pernyataan']} · penugasan otomatis dihitung: {$r['penugasan']}");

        if ($service->createdUnits) {
            $this->warn('Unit kerja dibuat otomatis (periksa nama & kode): '.implode(', ', array_unique($service->createdUnits)));
        }
        if ($r['tanpa_penugasan']) {
            $this->warn(count($r['tanpa_penugasan']).' pernyataan belum punya penugasan (PIC kosong / tidak ada unit yang cocok):');
            foreach ($r['tanpa_penugasan'] as $line) {
                $this->line("  - {$line}");
            }
        }

        return self::SUCCESS;
    }
}
