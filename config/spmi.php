<?php

/**
 * Aturan pemetaan teks PIC lama (bebas ketik) ke jabatan baku.
 * Urutan tidak penting; semua aturan yang cocok akan diterapkan.
 * 'pasti' => false  berarti pemetaan berupa dugaan dan akan ditandai untuk dikonfirmasi saat impor.
 * 'unit'            nama unit kerja untuk jabatan Ketua Unit (dibuat otomatis bila belum ada).
 *
 * Sesuaikan dengan struktur organisasi kampus, lalu jalankan ulang:
 *   php artisan spmi:import-standards && php artisan spmi:assign-statements --fresh
 */
return [

    'pic_rules' => [
        // --- Wakil Direktur ---
        ['pattern' => '/\b(?:wakil direktur|wadir)\s*iii\b/iu', 'jabatan' => 'wadir_3'],
        ['pattern' => '/\b(?:wakil direktur|wadir)\s*ii\b/iu', 'jabatan' => 'wadir_2'],
        ['pattern' => '/\b(?:wakil direktur|wadir)\s*i\b/iu', 'jabatan' => 'wadir_1'],
        ['pattern' => '/\b(?:wakil direktur|wadir)\s*iv\b/iu', 'jabatan' => 'wadir_3', 'pasti' => false, 'catatan' => 'Wadir IV dipetakan ke Wadir III'],
        ['pattern' => '/\b(?:wakil direktur|wadir)\s+(?:sarpras|sarana)/iu', 'jabatan' => 'wadir_2', 'pasti' => false, 'catatan' => 'Wadir Sarpras dipetakan ke Wadir II'],
        ['pattern' => '/\b(?:wakil direktur|wadir)\s+keuangan/iu', 'jabatan' => 'wadir_2', 'pasti' => false, 'catatan' => 'Wadir Keuangan dipetakan ke Wadir II'],
        ['pattern' => '/\b(?:wakil direktur|wadir)\s+(?:kerja\s?sama|pengembangan bisnis)/iu', 'jabatan' => 'wadir_3', 'pasti' => false, 'catatan' => 'Wadir Kerjasama/Bisnis dipetakan ke Wadir III'],
        ['pattern' => '/\bbid(?:ang|\.)?\s*akademik\b/iu', 'jabatan' => 'wadir_1', 'pasti' => false, 'catatan' => 'Bidang Akademik dipetakan ke Wadir I'],

        // --- Direktur ---
        ['pattern' => '/(?<!wakil )(?<!wa)\bdirektur\b/iu', 'jabatan' => 'direktur'],
        ['pattern' => '/\bpimpinan\s+pt\b/iu', 'jabatan' => 'direktur'],

        // --- Koordinator Program Studi ---
        ['pattern' => '/koordinator program studi|\bkps\b|\bkaprodi\b/iu', 'jabatan' => 'koordinator_prodi'],

        // --- Ketua Jurusan ---
        ['pattern' => '/ketua jurusan|\bkajur\b|\bjurusan\b/iu', 'jabatan' => 'ketua_jurusan'],

        // --- Ketua Unit (nama unit ikut dicatat) ---
        ['pattern' => '/\bp3m\b/iu', 'jabatan' => 'ketua_unit', 'unit' => 'P3M'],
        ['pattern' => '/\bp2mpp\b/iu', 'jabatan' => 'ketua_unit', 'unit' => 'P2MPP'],
        ['pattern' => '/\bupa\s*tik\b/iu', 'jabatan' => 'ketua_unit', 'unit' => 'UPA TIK'],
        ['pattern' => '/\bupa\s*perpustakaan\b/iu', 'jabatan' => 'ketua_unit', 'unit' => 'UPA Perpustakaan'],
        ['pattern' => '/\bupa\s*pkk\b/iu', 'jabatan' => 'ketua_unit', 'unit' => 'UPA PKK'],
        ['pattern' => '/\bupa\s*bahasa\b/iu', 'jabatan' => 'ketua_unit', 'unit' => 'UPA Bahasa'],
        ['pattern' => '/\bupa\s*pcp\b/iu', 'jabatan' => 'ketua_unit', 'unit' => 'UPA PCP'],
        ['pattern' => '/\bupa\s*pp\b/iu', 'jabatan' => 'ketua_unit', 'unit' => 'UPA PP'],
        ['pattern' => '/\bupa\s*luk\b/iu', 'jabatan' => 'ketua_unit', 'unit' => 'UPA LUK'],
        ['pattern' => '/\bupa\s*percetakan/iu', 'jabatan' => 'ketua_unit', 'unit' => 'UPA Percetakan dan Penerbitan'],
        ['pattern' => '/\bspi\b|satuan pengawas internal/iu', 'jabatan' => 'ketua_unit', 'unit' => 'SPI'],
        ['pattern' => '/\bpoliklinik\b/iu', 'jabatan' => 'ketua_unit', 'unit' => 'Poliklinik'],
        ['pattern' => '/\bbaak\b/iu', 'jabatan' => 'ketua_unit', 'unit' => 'BAAK'],
        ['pattern' => '/kepala laboratorium|kepala lab\b|ka\.?\s*lab/iu', 'jabatan' => 'ketua_unit', 'unit' => 'Laboratorium', 'pasti' => false, 'catatan' => 'Kepala Laboratorium dipetakan ke Ketua Unit "Laboratorium"'],
    ],

    // Hanya dipakai bila PIC berisi Koordinator Program Studi.
    'jenjang_rules' => [
        ['pattern' => '/\bd\s*2\b|diploma\s*(?:2|dua)\b/iu', 'jenjang' => 'd2'],
        ['pattern' => '/\bd\s*3\b|diploma\s*(?:3|tiga)\b/iu', 'jenjang' => 'd3'],
        ['pattern' => '/sarjana terapan|\bd\s*4\b/iu', 'jenjang' => 'd4'],
        ['pattern' => '/magister|\bs\s*2\b/iu', 'jenjang' => 's2'],
        ['pattern' => '/doktor|\bs\s*3\b/iu', 'jenjang' => 's3'],
    ],
];
