<?php

namespace App\Support;

/**
 * Mengubah teks PIC bebas ("Wakil Direktur II & KPS", "Ka. P3M dan Dosen Peneliti") menjadi
 * jabatan baku. Murni PHP: aturan dikirim lewat konstruktor (lihat config/spmi.php).
 */
final class PicNormalizer
{
    public function __construct(private array $picRules, private array $jenjangRules) {}

    /**
     * @return array{jabatan: string[], unit: string[], jenjang: string[], tidak_pasti: string[]}
     */
    public function normalize(?string $raw): array
    {
        $out = ['jabatan' => [], 'unit' => [], 'jenjang' => [], 'tidak_pasti' => []];
        $text = trim(preg_replace('/\s+/u', ' ', (string) $raw));

        if ($text === '') {
            return $out;
        }

        foreach ($this->picRules as $rule) {
            if (! preg_match($rule['pattern'], $text)) {
                continue;
            }

            $out['jabatan'][] = $rule['jabatan'];
            if (! empty($rule['unit'])) {
                $out['unit'][] = $rule['unit'];
            }
            if (($rule['pasti'] ?? true) === false) {
                $out['tidak_pasti'][] = $rule['catatan'] ?? $rule['jabatan'];
            }
        }

        if (in_array('koordinator_prodi', $out['jabatan'], true)) {
            foreach ($this->jenjangRules as $rule) {
                if (preg_match($rule['pattern'], $text)) {
                    $out['jenjang'][] = $rule['jenjang'];
                }
            }
        }

        foreach (['jabatan', 'unit', 'jenjang', 'tidak_pasti'] as $k) {
            $out[$k] = array_values(array_unique($out[$k]));
        }

        return $out;
    }
}
