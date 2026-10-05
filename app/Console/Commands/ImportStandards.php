<?php

namespace App\Console\Commands;

use App\Enums\EvaluationPeriod;
use App\Enums\IndicatorType;
use App\Enums\StandardCategory;
use App\Enums\StandardGroup;
use App\Enums\StatementStatus;
use App\Models\Indicator;
use App\Models\IndicatorTarget;
use App\Models\Standard;
use App\Models\Unit;
use App\Models\Statement;
use App\Services\AssignmentService;
use App\Support\PicNormalizer;
use App\Support\StatementTextParser;
use App\Support\TargetParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Impor dokumen standar ("SPMI Polinema 2026 - Usulan Perubahan.xlsx") ke tabel
 * standards, statements, indicators, dan indicator_targets. Aman dijalankan berulang (upsert).
 *
 * Kolom: A No | B No Standar | C Nama Standar | D PIC | E Pernyataan | F Catatan |
 *        G Indikator | H Catatan | I Periode Evaluasi | J Referensi | K Baseline | L-P Target | Q catatan tambahan
 */
class ImportStandards extends Command
{
    protected $signature = 'spmi:import-standards {file? : Path file .xlsx} {--dry-run : Hitung & tampilkan ringkasan tanpa menyimpan}';

    protected $description = 'Impor standar, pernyataan, indikator, dan target dari dokumen Excel SPMI';

    private array $warnings = [];

    /** @var array<string, string> teks PIC asli => catatan dugaan */
    private array $uncertain = [];

    public function handle(AssignmentService $assignments): int
    {
        $pics = new PicNormalizer(config('spmi.pic_rules', []), config('spmi.jenjang_rules', []));

        $path = $this->argument('file') ?: database_path('data/spmi-polinema-2026.xlsx');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray(null, true, false, false);

        $years = array_map('intval', array_slice($rows[1] ?? [], 10, 6));
        if (count(array_filter($years)) !== 6) {
            $years = [IndicatorTarget::BASELINE_YEAR, ...IndicatorTarget::TARGET_YEARS];
            $this->warn('Baris tahun (baris 2) tidak terbaca; memakai 2025 (baseline) dan 2026-2030.');
        }

        $data = [];
        foreach (array_slice($rows, 2, null, true) as $i => $r) {
            if (collect($r)->contains(fn ($c) => $c !== null && trim((string) $c) !== '')) {
                $data[] = ['excel_row' => $i + 1, 'cells' => $r];
            }
        }

        $names = $this->standardNames($data);
        $count = ['standard' => 0, 'statement' => 0, 'indicator' => 0, 'target' => 0];
        $types = [];

        DB::beginTransaction();

        try {
            $standards = [];
            foreach ($names as $prefix => $name) {
                $standards[$prefix] = Standard::updateOrCreate(
                    ['nomor' => $prefix],
                    [
                        'kode' => sprintf('S%02d', $prefix),
                        'nama' => $name,
                        'kategori' => StandardCategory::SnDikti,
                        'kelompok' => StandardGroup::fromNomor($prefix),
                        'is_active' => true,
                    ],
                );
                $count['standard']++;
            }
            $prefixByName = array_flip($names);

            foreach ($data as $row) {
                $c = $row['cells'];
                $n = $row['excel_row'];

                $nomor = $this->nomor($c[1] ?? null);
                $prefix = $nomor ? (int) explode('.', $nomor)[0] : ($prefixByName[$this->cleanName($c[2] ?? '')] ?? null);

                if (! $prefix || ! isset($standards[$prefix])) {
                    $this->warnings[] = "Baris {$n}: standar tidak dapat ditentukan, dilewati.";

                    continue;
                }
                if (! $nomor) {
                    $this->warnings[] = "Baris {$n}: tanpa No Standar; diimpor tanpa nomor (isi manual di aplikasi).";
                }

                $standard = $standards[$prefix];
                $pernyataan = $this->text($c[4] ?? null);
                $abcd = StatementTextParser::abcd($pernyataan);
                if ($pernyataan && str_contains($pernyataan, '[A]') && ! $abcd) {
                    $this->warnings[] = "Baris {$n}: penanda ABCD tidak lengkap, bagian A-B-C-D tidak dipecah.";
                }

                $periode = $this->text($c[8] ?? null);
                $periodeEnum = EvaluationPeriod::fromLabel($periode);
                if ($periode && ! $periodeEnum) {
                    $this->warnings[] = "Baris {$n}: periode evaluasi \"{$periode}\" tidak dikenali.";
                }

                [$status, $keterangan] = $this->statusFromName((string) ($c[2] ?? ''));
                $catatan = collect([
                    $this->text($c[5] ?? null),
                    $keterangan ? "Keterangan di dokumen: {$keterangan}" : null,
                    ! empty($c[16]) ? 'Catatan tambahan: '.$this->text($c[16]) : null,
                ])->filter()->implode("\n");

                $picRaw = $this->text($c[3] ?? null);
                $pic = $pics->normalize($picRaw);
                if (! $picRaw) {
                    $this->warnings[] = 'Baris '.$n.($nomor ? " ({$nomor})" : '').': PIC kosong — belum bisa ditugaskan, tetapkan PIC di menu Pernyataan Standar.';
                } elseif (! $pic['jabatan']) {
                    $this->warnings[] = "Baris {$n}: PIC \"{$picRaw}\" tidak dikenali; tambahkan aturan di config/spmi.php atau pilih jabatan manual.";
                }
                if ($pic['tidak_pasti']) {
                    $this->uncertain[$picRaw] = implode('; ', $pic['tidak_pasti']);
                }

                $attrs = [
                    'pernyataan' => $pernyataan,
                    'abcd_audience' => $abcd['audience'] ?? null,
                    'abcd_behaviour' => $abcd['behaviour'] ?? null,
                    'abcd_condition' => $abcd['condition'] ?? null,
                    'abcd_degree' => $abcd['degree'] ?? null,
                    'pic' => $picRaw,
                    'pic_jabatan' => $pic['jabatan'] ?: null,
                    'pic_unit' => $pic['unit'] ?: null,
                    'jenjang' => $pic['jenjang'] ?: null,
                    'periode_evaluasi' => $periodeEnum,
                    'referensi' => $this->text($c[9] ?? null),
                    'status' => $status,
                    'catatan' => $catatan ?: null,
                ];

                $statement = $nomor
                    ? Statement::updateOrCreate(['standard_id' => $standard->id, 'nomor' => $nomor], $attrs)
                    : Statement::updateOrCreate(['standard_id' => $standard->id, 'nomor' => null, 'pernyataan' => $pernyataan], $attrs);
                $count['statement']++;

                $values = array_slice($c, 10, 6);
                $values = array_pad($values, 6, null);
                $tipe = TargetParser::detectType($values);
                $types[$tipe->value] = ($types[$tipe->value] ?? 0) + 1;

                $indicator = Indicator::updateOrCreate(
                    ['standard_id' => $standard->id, 'kode' => $nomor ?? "R{$n}"],
                    [
                        'statement_id' => $statement->id,
                        'nama' => $this->text($c[6] ?? null) ?? '-',
                        'tipe' => $tipe,
                        'satuan' => $tipe->unit(),
                        'bobot' => 1,
                        'catatan' => $this->text($c[7] ?? null),
                    ],
                );
                $count['indicator']++;

                foreach ($values as $k => $raw) {
                    $key = ['indicator_id' => $indicator->id, 'jenis' => $k === 0 ? 'baseline' : 'target', 'tahun' => $years[$k]];
                    $parsed = TargetParser::parse($raw, $tipe, true);

                    if ($parsed === null) {
                        IndicatorTarget::where($key)->delete();

                        continue;
                    }
                    IndicatorTarget::updateOrCreate($key, $parsed);
                    $count['target']++;
                }
            }

            $assigned = $assignments->generate();

            $this->option('dry-run') ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->info(($this->option('dry-run') ? '[DRY-RUN] ' : '')."Standar: {$count['standard']} · Pernyataan: {$count['statement']} · Indikator: {$count['indicator']} · Nilai target: {$count['target']}");
        $this->line('Jenis indikator terdeteksi: '.collect($types)->map(fn ($v, $k) => "{$k}={$v}")->implode(', '));

        $this->line("Penugasan otomatis: {$assigned['penugasan']} (pernyataan × unit). ".
            ($assignments->createdUnits ? 'Unit kerja baru: '.implode(', ', array_unique($assignments->createdUnits)).'.' : ''));
        if (! Unit::where('tipe', 'prodi')->exists()) {
            $this->warn('Belum ada unit bertipe Prodi — pernyataan untuk Koordinator Program Studi belum punya penugasan. Buat prodi lalu jalankan: php artisan spmi:assign-statements');
        }

        if ($this->uncertain) {
            $this->newLine();
            $this->warn(count($this->uncertain).' pemetaan PIC berupa DUGAAN, mohon dikonfirmasi (ubah di config/spmi.php):');
            foreach ($this->uncertain as $raw => $why) {
                $this->line("  - \"{$raw}\" → {$why}");
            }
        }

        if ($this->warnings) {
            $this->newLine();
            $this->warn(count($this->warnings).' catatan untuk ditinjau:');
            foreach ($this->warnings as $w) {
                $this->line("  - {$w}");
            }
        }

        return self::SUCCESS;
    }

    /** Nama standar per nomor standar: dipilih yang paling sering muncul, tanpa keterangan dalam kurung. */
    private function standardNames(array $data): array
    {
        $tally = [];
        foreach ($data as $row) {
            $nomor = $this->nomor($row['cells'][1] ?? null);
            $name = $this->cleanName($row['cells'][2] ?? '');
            if ($nomor && $name !== '') {
                $prefix = (int) explode('.', $nomor)[0];
                $tally[$prefix][$name] = ($tally[$prefix][$name] ?? 0) + 1;
            }
        }

        $names = [];
        foreach ($tally as $prefix => $variants) {
            arsort($variants);
            $names[$prefix] = array_key_first($variants);
        }
        ksort($names);

        return $names;
    }

    /** "Standar X\n(disarankan dihapus)" -> "Standar X". */
    private function cleanName(mixed $raw): string
    {
        $first = trim(explode("\n", (string) $raw)[0]);

        return trim(preg_replace('/\s*(dihapus karena.*|\(.*)$/iu', '', $first));
    }

    /** @return array{0: StatementStatus, 1: ?string} */
    private function statusFromName(string $raw): array
    {
        $note = trim(preg_replace('/^[^\n]*\n?/u', '', $raw)) ?: null;
        if (! $note && preg_match('/(dihapus karena.*)$/iu', $raw, $m)) {
            $note = trim($m[1]);
        }

        $status = match (true) {
            (bool) preg_match('/dihapus/iu', $raw) => StatementStatus::UsulanHapus,
            (bool) preg_match('/usulan standar baru/iu', $raw) => StatementStatus::UsulanBaru,
            (bool) preg_match('/gabungan/iu', $raw) => StatementStatus::Gabungan,
            default => StatementStatus::Aktif,
        };

        return [$status, $note];
    }

    /** 4.09000000000002 -> "4.09"; 3.1 -> "3.10"; "1.01" -> "1.01". */
    private function nomor(mixed $v): ?string
    {
        if ($v === null || trim((string) $v) === '') {
            return null;
        }

        $f = (float) str_replace(',', '.', (string) $v);
        $whole = (int) floor($f + 1e-9);

        return sprintf('%d.%02d', $whole, (int) round(($f - $whole) * 100));
    }

    private function text(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }

        $s = trim(preg_replace('/[ \t]{2,}/u', ' ', (string) $v));

        return $s === '' ? null : $s;
    }
}
