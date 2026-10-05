<?php

namespace App\Console\Commands;

use App\Models\Evidence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SecureEvidenceFiles extends Command
{
    protected $signature = 'spmi:secure-evidences {--dry-run : Tampilkan berkas yang akan dipindahkan tanpa mengubahnya}';

    protected $description = 'Pindahkan bukti dukung lama dari penyimpanan publik ke penyimpanan privat';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $moved = 0;
        $missing = 0;

        Evidence::whereNotNull('file_path')->orderBy('id')->chunkById(100, function ($evidences) use ($dryRun, &$moved, &$missing) {
            foreach ($evidences as $evidence) {
                if (Storage::disk('local')->exists($evidence->file_path)) {
                    continue;
                }

                if (! Storage::disk('public')->exists($evidence->file_path)) {
                    $this->error("Berkas bukti #{$evidence->id} tidak ditemukan pada disk publik maupun privat.");
                    $missing++;

                    continue;
                }

                $this->line(($dryRun ? 'Akan dipindahkan: ' : 'Memindahkan: ').$evidence->file_path);
                if ($dryRun) {
                    $moved++;

                    continue;
                }

                $stream = Storage::disk('public')->readStream($evidence->file_path);
                if (! is_resource($stream)) {
                    throw new RuntimeException("Tidak dapat membaca berkas bukti #{$evidence->id}.");
                }

                try {
                    $stored = Storage::disk('local')->writeStream($evidence->file_path, $stream);
                } finally {
                    fclose($stream);
                }

                if (! $stored) {
                    throw new RuntimeException("Tidak dapat menyimpan berkas bukti #{$evidence->id} ke disk privat.");
                }

                if (! Storage::disk('public')->delete($evidence->file_path)) {
                    throw new RuntimeException("Berkas privat #{$evidence->id} tersimpan, tetapi berkas publik lama gagal dihapus.");
                }

                $moved++;
            }
        });

        $this->info(($dryRun ? 'Dry-run: ' : 'Selesai: ').$moved.' berkas diproses; '.$missing.' berkas tidak ditemukan.');

        return $missing === 0 ? self::SUCCESS : self::FAILURE;
    }
}
