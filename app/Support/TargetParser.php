<?php

namespace App\Support;

use App\Enums\IndicatorType;

/**
 * Membaca & menampilkan nilai baseline/target yang bentuknya beragam di dokumen standar:
 * 0.8 (=80%), ">70%", "68,34%", "ada", "Rp 9.000.000,-", "100% laporan tertangani".
 * Murni PHP (tanpa Laravel) supaya mudah diuji.
 */
final class TargetParser
{
    /**
     * @param  bool  $fraction  true bila angka <= 1 pada indikator persen berarti pecahan (0.8 => 80)
     * @return array{nilai: ?float, operator: ?string, teks: ?string}|null null bila kosong
     */
    public static function parse(mixed $raw, IndicatorType $tipe, bool $fraction = false): ?array
    {
        if ($raw === null || (is_string($raw) && trim($raw) === '')) {
            return null;
        }

        if (is_int($raw) || is_float($raw)) {
            $v = (float) $raw;
            if ($tipe === IndicatorType::Persen && $fraction && $v <= 1) {
                $v *= 100;
            }
            if ($tipe === IndicatorType::Ada) {
                $v = $v > 0 ? 1.0 : 0.0;
            }

            return ['nilai' => round($v, 4), 'operator' => null, 'teks' => null];
        }

        $s = trim(preg_replace('/\s+/u', ' ', (string) $raw));

        if ($tipe === IndicatorType::Ada) {
            if (preg_match('/^(ada|tersedia|ya|true)$/iu', $s)) {
                return ['nilai' => 1.0, 'operator' => null, 'teks' => null];
            }
            if (preg_match('/^(tidak( ada)?|belum|false)$/iu', $s)) {
                return ['nilai' => 0.0, 'operator' => null, 'teks' => null];
            }
        }

        // Operator pembanding: >70%, >= 5, < 3
        if (preg_match('/^(>=|<=|≥|≤|>|<)\s*([\d.,]+)\s*%?$/u', $s, $m)) {
            $op = strtr($m[1], ['≥' => '>=', '≤' => '<=']);

            return ['nilai' => self::number($m[2]), 'operator' => $op, 'teks' => null];
        }

        // Rupiah: "Rp 9.000.000,-"
        if (preg_match('/^rp\.?\s*([\d.,]+)(,-)?$/iu', $s, $m)) {
            return ['nilai' => (float) preg_replace('/\D/', '', $m[1]), 'operator' => null, 'teks' => null];
        }

        // Angka polos / persen: "68,34%", "80"
        if (preg_match('/^[\d.,]+\s*%?$/u', $s)) {
            return ['nilai' => self::number($s), 'operator' => null, 'teks' => null];
        }

        // "100% laporan tertangani": simpan angka & teks aslinya.
        if (preg_match('/^([\d.,]+)\s*%/u', $s, $m)) {
            return ['nilai' => self::number($m[1]), 'operator' => null, 'teks' => $s];
        }

        return ['nilai' => null, 'operator' => null, 'teks' => $s];
    }

    /** Angka format Indonesia/Inggris: "68,34" -> 68.34, "9.000.000" -> 9000000. */
    public static function number(string $s): float
    {
        $s = trim(str_replace('%', '', $s));

        if (str_contains($s, ',') && str_contains($s, '.')) {
            return (float) str_replace(',', '.', str_replace('.', '', $s));
        }
        if (str_contains($s, ',')) {
            return (float) str_replace(',', '.', $s);
        }
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $s)) {
            return (float) str_replace('.', '', $s);
        }

        return (float) $s;
    }

    /** Menentukan jenis indikator dari sekumpulan nilai mentah (baseline + target). */
    public static function detectType(array $rawValues): IndicatorType
    {
        $vals = array_values(array_filter($rawValues, fn ($v) => $v !== null && trim((string) $v) !== ''));

        if (! $vals) {
            return IndicatorType::Teks;
        }

        $strings = array_map(fn ($v) => trim((string) $v), $vals);

        if (array_filter($strings, fn ($s) => preg_match('/^(ada|tersedia)$/iu', $s))) {
            return IndicatorType::Ada;
        }
        if (array_filter($strings, fn ($s) => preg_match('/^rp/iu', $s))) {
            return IndicatorType::Rupiah;
        }

        $numbers = [];
        $hasPercentString = false;
        foreach ($vals as $v) {
            if (is_int($v) || is_float($v)) {
                $numbers[] = (float) $v;
            } elseif (preg_match('/^(?:>=|<=|>|<)?\s*([\d.,]+)\s*%$/u', trim((string) $v), $m)) {
                $hasPercentString = true;
                $numbers[] = self::number($m[1]) / 100; // dianggap persen
            } else {
                return IndicatorType::Teks;
            }
        }

        if ($hasPercentString || max($numbers) <= 1) {
            return IndicatorType::Persen;
        }

        return IndicatorType::Angka;
    }

    /** Teks untuk kolom isian form: ">70", "80", "ada". */
    public static function toInput(?float $nilai, ?string $operator, ?string $teks, IndicatorType $tipe): string
    {
        if ($nilai === null) {
            return $teks ?? '';
        }
        if ($tipe === IndicatorType::Ada) {
            return $nilai > 0 ? 'ada' : 'tidak';
        }

        return ($operator ? $operator.' ' : '').self::trim($nilai);
    }

    /** Teks untuk tampilan: ">70%", "Rp 9.000.000", "Ada". */
    public static function format(?float $nilai, ?string $operator, ?string $teks, IndicatorType $tipe): string
    {
        if ($nilai === null) {
            return $teks ?? '—';
        }

        $op = $operator ? $operator.' ' : '';

        return match ($tipe) {
            IndicatorType::Ada => $nilai > 0 ? 'Ada' : 'Tidak ada',
            IndicatorType::Persen => $op.self::trim($nilai).'%',
            IndicatorType::Rupiah => $op.'Rp '.number_format($nilai, 0, ',', '.'),
            default => $op.self::trim($nilai),
        };
    }

    private static function trim(float $v): string
    {
        return rtrim(rtrim(number_format($v, 4, '.', ''), '0'), '.');
    }
}
