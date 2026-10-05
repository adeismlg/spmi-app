<?php

namespace App\Services;

use App\Enums\Jabatan;
use App\Enums\StatementStatus;
use App\Enums\UnitType;
use App\Models\Statement;
use App\Models\StatementAssignment;
use App\Models\Unit;
use Illuminate\Support\Str;

/**
 * Menentukan unit mana yang menerima pernyataan standar (penugasan) dan jabatan pengisinya.
 *
 *   Koordinator Prodi -> semua prodi (disaring jenjang bila pernyataan berlaku untuk jenjang tertentu)
 *   Ketua Jurusan     -> semua jurusan
 *   Ketua Unit        -> unit kerja yang disebut pada PIC (mis. P3M, UPA TIK); dibuat otomatis bila belum ada
 *   Wadir I-III / Direktur -> unit Institusi
 *
 * Penugasan "otomatis" boleh dihitung ulang kapan saja. Begitu admin mengaturnya manual
 * (sumber = manual), penugasan pernyataan itu tidak lagi ditimpa otomatis.
 */
class AssignmentService
{
    /** @var string[] nama unit yang dibuat otomatis pada proses ini */
    public array $createdUnits = [];

    /** @return array{pernyataan: int, penugasan: int, tanpa_penugasan: array<int, string>} */
    public function generate(bool $fresh = false): array
    {
        if ($fresh) {
            StatementAssignment::where('sumber', 'otomatis')->delete();
        }

        $total = 0;
        $skipped = [];

        Statement::with('standard')->get()->each(function (Statement $s) use (&$total, &$skipped) {
            $n = $this->forStatement($s);
            $total += $n;

            if ($n === 0 && $s->status !== StatementStatus::UsulanHapus && $s->assignments()->doesntExist()) {
                $skipped[] = ($s->nomor ?? "id {$s->id}").' — '.($s->picLabel());
            }
        });

        return ['pernyataan' => Statement::count(), 'penugasan' => $total, 'tanpa_penugasan' => $skipped];
    }

    /** Hitung ulang penugasan otomatis satu pernyataan. Mengembalikan jumlah penugasan hasil hitung. */
    public function forStatement(Statement $s): int
    {
        // Penugasan manual bersifat final; usulan dihapus tidak dievaluasi.
        if ($s->assignments()->where('sumber', 'manual')->exists()) {
            return 0;
        }

        $keep = [];

        if ($s->status !== StatementStatus::UsulanHapus) {
            foreach ($s->pic_jabatan ?? [] as $value) {
                $jabatan = Jabatan::tryFrom($value);
                if (! $jabatan) {
                    continue;
                }

                foreach ($this->unitsFor($jabatan, $s) as $unit) {
                    $row = StatementAssignment::firstOrCreate(
                        ['statement_id' => $s->id, 'unit_id' => $unit->id, 'jabatan' => $jabatan->value],
                        ['sumber' => 'otomatis'],
                    );
                    $keep[] = $row->id;
                }
            }
        }

        StatementAssignment::where('statement_id', $s->id)->where('sumber', 'otomatis')
            ->whereNotIn('id', $keep)->delete();

        return count($keep);
    }

    /** Simpan penugasan manual: daftar unit terpilih. Kosong = hapus semua penugasan. */
    public function setManual(Statement $s, array $unitIds): void
    {
        StatementAssignment::where('statement_id', $s->id)->delete();

        $units = Unit::whereIn('id', $unitIds)->get();
        $picJabatan = collect($s->pic_jabatan ?? [])->map(fn ($j) => Jabatan::tryFrom($j))->filter();

        foreach ($units as $unit) {
            foreach (self::jabatanForUnit($unit, $picJabatan->all()) as $jabatan) {
                StatementAssignment::create([
                    'statement_id' => $s->id, 'unit_id' => $unit->id,
                    'jabatan' => $jabatan->value, 'sumber' => 'manual',
                ]);
            }
        }
    }

    /** Kembalikan ke penugasan otomatis. */
    public function reset(Statement $s): int
    {
        StatementAssignment::where('statement_id', $s->id)->delete();

        return $this->forStatement($s);
    }

    /**
     * Jabatan pengisi untuk sebuah unit yang dipilih manual.
     *
     * @param  Jabatan[]  $picJabatan
     * @return Jabatan[]
     */
    public static function jabatanForUnit(Unit $unit, array $picJabatan): array
    {
        $type = $unit->tipe;

        $match = array_values(array_filter($picJabatan, fn (Jabatan $j) => $j->unitType() === $type));
        if ($match) {
            return $match;
        }

        // Tidak ada jabatan PIC yang cocok dengan tipe unit: pakai jabatan default tipe tersebut.
        return match ($type) {
            UnitType::Prodi => [Jabatan::KoordinatorProdi],
            UnitType::Jurusan => [Jabatan::KetuaJurusan],
            UnitType::UnitKerja => [Jabatan::KetuaUnit],
            default => [Jabatan::Direktur],
        };
    }

    /** @return iterable<Unit> */
    private function unitsFor(Jabatan $jabatan, Statement $s): iterable
    {
        return match ($jabatan) {
            Jabatan::KoordinatorProdi => Unit::where('tipe', UnitType::Prodi->value)
                ->when($s->jenjang, fn ($q, $j) => $q->whereIn('jenjang', $j))->get(),
            Jabatan::KetuaJurusan => Unit::where('tipe', UnitType::Jurusan->value)->get(),
            Jabatan::KetuaUnit => collect($s->pic_unit ?? [])->map(fn ($hint) => $this->unitByName($hint)),
            default => Unit::where('tipe', UnitType::Institusi->value)->limit(1)->get(),
        };
    }

    /** Cari unit kerja berdasar nama/singkatan; buat bila belum ada. */
    public function unitByName(string $name): Unit
    {
        $unit = Unit::where('tipe', UnitType::UnitKerja->value)
            ->where(fn ($q) => $q->where('nama', $name)->orWhere('singkatan', $name))->first();

        if ($unit) {
            return $unit;
        }

        $this->createdUnits[] = $name;

        return Unit::create([
            'kode' => Str::upper(Str::limit(Str::slug($name, '_'), 28, '')),
            'nama' => $name,
            'singkatan' => $name,
            'tipe' => UnitType::UnitKerja,
            'parent_id' => Unit::where('tipe', UnitType::Institusi->value)->value('id'),
        ]);
    }
}
