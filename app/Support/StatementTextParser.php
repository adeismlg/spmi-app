<?php

namespace App\Support;

/**
 * Memecah pernyataan standar berformat ABCD, mis.
 * "... Wakil Direktur I [A] wajib menetapkan ... [B] berdasarkan ... [C] sebelum ... [D]."
 * Setiap penanda berada di AKHIR segmennya: Audience, Behaviour, Condition, Degree.
 */
final class StatementTextParser
{
    /** @return array{audience: string, behaviour: string, condition: string, degree: string}|null */
    public static function abcd(?string $text): ?array
    {
        if (! $text || ! str_contains($text, '[A]')) {
            return null;
        }

        $ok = preg_match(
            '/([^.\n\[\]]+?)\s*\[A\]\s*(.+?)\s*\[B\]\s*(.+?)\s*\[C\]\s*(.+?)\s*\[D\]/su',
            $text,
            $m,
        );

        if (! $ok) {
            return null;
        }

        return [
            'audience' => trim($m[1]),
            'behaviour' => trim($m[2]),
            'condition' => trim($m[3]),
            'degree' => trim($m[4]),
        ];
    }
}
