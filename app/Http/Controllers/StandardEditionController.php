<?php

namespace App\Http\Controllers;

use App\Models\StandardEdition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StandardEditionController extends CrudController
{
    protected string $model = StandardEdition::class;

    protected string $routeName = 'standard-editions';

    protected string $title = 'Edisi Standar';

    protected function columns(): array
    {
        return [
            ['label' => 'Kode', 'value' => fn ($row) => $row->kode],
            ['label' => 'Nama Edisi', 'value' => fn ($row) => $row->nama],
            ['label' => 'Tahun Berlaku', 'value' => fn ($row) => $row->tahun_mulai.'–'.($row->tahun_selesai ?? 'seterusnya')],
            ['label' => 'Standar', 'value' => fn ($row) => $row->standards_count],
            ['label' => 'Siklus', 'value' => fn ($row) => $row->cycles_count],
        ];
    }

    protected function query(): Builder
    {
        return StandardEdition::query()->withCount(['standards', 'cycles'])->orderByDesc('tahun_mulai');
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'kode' => ['label' => 'Kode Edisi', 'rules' => ['required', 'string', 'max:50', Rule::unique('standard_editions', 'kode')->ignore($record?->id)]],
            'nama' => ['label' => 'Nama Edisi', 'rules' => 'required|string|max:255'],
            'tahun_mulai' => ['label' => 'Tahun Mulai Berlaku', 'type' => 'number', 'rules' => 'required|integer|between:1900,2100'],
            'tahun_selesai' => ['label' => 'Tahun Akhir Berlaku (kosongkan jika masih berlaku)', 'type' => 'number', 'rules' => 'nullable|integer|between:1900,2100'],
            'deskripsi' => ['label' => 'Keterangan', 'type' => 'textarea', 'rules' => 'nullable|string'],
        ];
    }

    protected function beforeSave(array $data, Request $request, ?Model $record): array
    {
        if ($data['tahun_selesai'] !== null && (int) $data['tahun_selesai'] < (int) $data['tahun_mulai']) {
            throw ValidationException::withMessages([
                'tahun_selesai' => 'Tahun akhir harus sama dengan atau setelah tahun mulai.',
            ]);
        }

        if ($record && $record->cycles()->exists()
            && ($record->kode !== $data['kode']
                || $record->tahun_mulai !== (int) $data['tahun_mulai']
                || $record->tahun_selesai !== ($data['tahun_selesai'] !== null ? (int) $data['tahun_selesai'] : null))) {
            throw ValidationException::withMessages([
                'kode' => 'Kode dan masa berlaku edisi tidak dapat diubah setelah digunakan oleh siklus.',
            ]);
        }

        return $data;
    }
}
