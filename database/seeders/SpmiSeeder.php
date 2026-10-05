<?php

namespace Database\Seeders;

use App\Enums\StandardCategory;
use App\Enums\StandardGroup;
use App\Enums\UnitType;
use App\Models\Cycle;
use App\Models\Standard;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SpmiSeeder extends Seeder
{
    /** 24 standar sesuai dokumen "SPMI Polinema 2026 (Usulan Perubahan)". */
    private const STANDARDS = [
        1 => 'Standar Kompetensi Lulusan',
        2 => 'Standar Isi Pembelajaran',
        3 => 'Standar Proses Pembelajaran',
        4 => 'Standar Penilaian Pembelajaran',
        5 => 'Standar Pendidik dan Tenaga Kependidikan',
        6 => 'Standar Sarana dan Prasarana Pembelajaran',
        7 => 'Standar Pengelolaan Pembelajaran',
        8 => 'Standar Pendanaan dan Pembiayaan Pembelajaran',
        9 => 'Standar Hasil Penelitian',
        10 => 'Standar Isi Penelitian',
        11 => 'Standar Proses Penelitian',
        12 => 'Standar Penilaian Penelitian',
        13 => 'Standar Peneliti',
        14 => 'Standar Sarana dan Prasarana Penelitian',
        15 => 'Standar Pengelolaan Penelitian',
        16 => 'Standar Pendanaan dan Pembiayaan Penelitian',
        17 => 'Standar Hasil Pengabdian kepada Masyarakat',
        18 => 'Standar Isi Pengabdian kepada Masyarakat',
        19 => 'Standar Proses Pengabdian kepada Masyarakat',
        20 => 'Standar Penilaian Pengabdian kepada Masyarakat',
        21 => 'Standar Pelaksana Pengabdian kepada Masyarakat',
        22 => 'Standar Sarana dan Prasarana Pengabdian kepada Masyarakat',
        23 => 'Standar Pengelolaan Pengabdian Kepada Masyarakat',
        24 => 'Standar Pendanaan dan Pembiayaan Pengabdian Kepada Masyarakat',
    ];

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $institusi = Unit::firstOrCreate(['kode' => 'INST'], ['nama' => 'Institusi', 'tipe' => UnitType::Institusi]);
        $lpm = Unit::firstOrCreate(
            ['kode' => 'LPM'],
            ['nama' => 'Lembaga Penjaminan Mutu', 'tipe' => UnitType::UnitKerja, 'parent_id' => $institusi->id],
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@spmi.test'],
            ['name' => 'Admin SPMI', 'password' => Hash::make('password'), 'unit_id' => $lpm->id],
        );
        $admin->assignRole('admin_spmi');

        foreach (self::STANDARDS as $nomor => $nama) {
            Standard::updateOrCreate(
                ['nomor' => $nomor],
                [
                    'kode' => sprintf('S%02d', $nomor),
                    'nama' => $nama,
                    'kategori' => StandardCategory::SnDikti,
                    'kelompok' => StandardGroup::fromNomor($nomor),
                    'is_active' => true,
                ],
            );
        }

        Cycle::firstOrCreate(
            ['tahun' => (int) date('Y')],
            ['nama' => 'Siklus SPMI '.date('Y'), 'is_active' => true],
        );
    }
}
