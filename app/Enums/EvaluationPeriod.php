<?php

namespace App\Enums;

/** Kolom "Periode Evaluasi" pada dokumen standar. */
enum EvaluationPeriod: string
{
    case SetiapSemester = 'setiap_semester';
    case SetiapAkhirSemester = 'setiap_akhir_semester';
    case SetiapTahun = 'setiap_tahun';
    case SetiapAwalTahun = 'setiap_awal_tahun';
    case SetiapAkhirTahunAkademik = 'setiap_akhir_tahun_akademik';
    case SetiapAkhirTahunAnggaran = 'setiap_akhir_tahun_anggaran';
    case SetiapLimaTahun = 'setiap_5_tahun';

    public function label(): string
    {
        return match ($this) {
            self::SetiapSemester => 'Setiap semester',
            self::SetiapAkhirSemester => 'Setiap akhir semester',
            self::SetiapTahun => 'Setiap tahun',
            self::SetiapAwalTahun => 'Setiap awal tahun',
            self::SetiapAkhirTahunAkademik => 'Setiap akhir tahun akademik',
            self::SetiapAkhirTahunAnggaran => 'Setiap akhir tahun anggaran',
            self::SetiapLimaTahun => 'Setiap 5 tahun',
        };
    }

    /**
     * Semester tempat indikator ini dievaluasi (1 = ganjil, 2 = genap).
     * Evaluasi lima tahunan dijadwalkan pada tahun kalender kelipatan lima.
     *
     * @return array<int, int>
     */
    public function semesters(int $year): array
    {
        return match ($this) {
            self::SetiapSemester, self::SetiapAkhirSemester => [1, 2],
            self::SetiapAwalTahun => [1],
            self::SetiapLimaTahun => $year % 5 === 0 ? [2] : [],
            default => [2],
        };
    }

    /** Cocokkan teks dari Excel (mis. "Setiap tahun") ke enum. */
    public static function fromLabel(?string $text): ?self
    {
        $needle = mb_strtolower(trim((string) $text));

        foreach (self::cases() as $case) {
            if (mb_strtolower($case->label()) === $needle) {
                return $case;
            }
        }

        return null;
    }
}
