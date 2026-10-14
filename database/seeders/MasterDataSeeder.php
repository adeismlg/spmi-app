<?php

namespace Database\Seeders;

use App\Enums\Jabatan;
use App\Enums\Jenjang;
use App\Enums\UnitType;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $userRows = $this->readCsv('master_users.csv', ['Nama', 'Email']);
        $unitRows = $this->readCsv('master_units.csv', [
            'Unit_Kerja_Prodi',
            'Jurusan',
            'Auditee_Nama',
            'Auditee_Email',
        ]);

        $usersByEmail = [];
        foreach ($userRows as $row) {
            $email = Str::lower($row['Email']);
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new RuntimeException("Alamat email pengguna tidak valid: {$row['Email']}");
            }

            $usersByEmail[$email] ??= ['name' => $row['Nama'], 'email' => $email];
        }

        $auditeesByEmail = [];
        foreach ($unitRows as $row) {
            $email = Str::lower($row['Auditee_Email']);
            if (! isset($usersByEmail[$email])) {
                throw new RuntimeException("Auditee {$email} tidak ditemukan pada master user.");
            }

            $auditeesByEmail[$email] ??= $row;
        }

        DB::transaction(function () use ($usersByEmail, $unitRows, $auditeesByEmail): void {
            $this->call(RoleSeeder::class);

            $institution = Unit::firstOrCreate(
                ['kode' => 'INST'],
                ['nama' => 'Institusi', 'tipe' => UnitType::Institusi],
            );

            $parentsByName = [];
            foreach (array_unique(array_column($unitRows, 'Jurusan')) as $parentName) {
                $parentType = Str::startsWith($parentName, 'JURUSAN ')
                    ? UnitType::Jurusan
                    : UnitType::UnitKerja;

                $parentsByName[$parentName] = Unit::updateOrCreate(
                    ['kode' => $this->unitCode($parentName)],
                    [
                        'nama' => $parentName,
                        'tipe' => $parentType,
                        'parent_id' => $institution->id,
                    ],
                );
            }

            $unitsByName = $parentsByName;
            foreach ($unitRows as $row) {
                $unitName = $row['Unit_Kerja_Prodi'];
                $parentName = $row['Jurusan'];

                if ($unitName === $parentName) {
                    continue;
                }

                preg_match('/^(D2|D3|D4|S2|S3)\b/u', $unitName, $matches);
                $jenjang = isset($matches[1]) ? Jenjang::tryFrom(Str::lower($matches[1])) : null;
                $unitType = $jenjang === null ? UnitType::UnitKerja : UnitType::Prodi;

                $unitsByName[$unitName] = Unit::updateOrCreate(
                    ['kode' => $this->unitCode($unitName)],
                    [
                        'nama' => $unitName,
                        'tipe' => $unitType,
                        'jenjang' => $jenjang,
                        'parent_id' => $parentsByName[$parentName]->id,
                    ],
                );
            }

            foreach ($usersByEmail as $email => $attributes) {
                $auditee = $auditeesByEmail[$email] ?? null;
                $unit = $auditee === null ? null : $unitsByName[$auditee['Unit_Kerja_Prodi']];
                $jabatan = match ($unit?->tipe) {
                    UnitType::Jurusan => Jabatan::KetuaJurusan,
                    UnitType::Prodi => Jabatan::KoordinatorProdi,
                    UnitType::UnitKerja => Jabatan::KetuaUnit,
                    default => null,
                };

                $user = User::query()->firstOrNew(['email' => $email]);
                if (! $user->exists) {
                    $user->password = Str::random(48);
                }

                $user->fill([
                    'name' => $attributes['name'],
                    'unit_id' => $unit?->id,
                    'jabatan' => $jabatan,
                ]);
                $user->save();
                $user->syncRoles([$auditee === null ? 'auditor' : 'auditee']);
            }
        });

        $this->command?->info(sprintf(
            'Master user dan unit berhasil diimpor (%d pengguna unik, %d unit auditee).',
            count($usersByEmail),
            count($unitRows),
        ));
    }

    /**
     * @param  list<string>  $expectedHeaders
     * @return list<array<string, string>>
     */
    private function readCsv(string $filename, array $expectedHeaders): array
    {
        $path = database_path('data/'.$filename);
        $stream = fopen($path, 'rb');

        if ($stream === false) {
            throw new RuntimeException("Berkas master tidak dapat dibaca: {$path}");
        }

        try {
            $headers = fgetcsv($stream, separator: ',', enclosure: '"', escape: '');
            if ($headers === false) {
                throw new RuntimeException("Berkas master kosong: {$path}");
            }

            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            if ($headers !== $expectedHeaders) {
                throw new RuntimeException("Header CSV tidak sesuai pada berkas: {$path}");
            }

            $records = [];
            $line = 1;

            while (($values = fgetcsv($stream, separator: ',', enclosure: '"', escape: '')) !== false) {
                $line++;

                if ($values === [null]) {
                    continue;
                }

                if (count($values) !== count($headers)) {
                    throw new RuntimeException("Jumlah kolom CSV tidak sesuai pada {$path}:{$line}");
                }

                $record = array_combine($headers, $values);
                if ($record === false) {
                    throw new RuntimeException("Baris CSV tidak valid pada {$path}:{$line}");
                }

                foreach ($record as $column => $value) {
                    $record[$column] = trim((string) $value);
                    if ($record[$column] === '') {
                        throw new RuntimeException("Kolom {$column} kosong pada {$path}:{$line}");
                    }
                }

                $records[] = $record;
            }
        } finally {
            fclose($stream);
        }

        if ($records === []) {
            throw new RuntimeException("Tidak ada data master pada berkas: {$path}");
        }

        return $records;
    }

    private function unitCode(string $name): string
    {
        $slug = Str::upper(Str::slug($name));

        return substr($slug !== '' ? $slug : 'UNIT', 0, 17).'-'.Str::upper(substr(hash('sha256', $name), 0, 12));
    }
}
