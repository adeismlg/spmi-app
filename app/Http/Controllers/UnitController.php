<?php

namespace App\Http\Controllers;

use App\Enums\Jenjang;
use App\Enums\UnitType;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class UnitController extends CrudController
{
    protected string $model = Unit::class;

    protected string $routeName = 'units';

    protected string $title = 'Unit / Program Studi';

    protected array $with = ['parent'];

    protected function columns(): array
    {
        return [
            ['label' => 'Kode', 'value' => fn ($r) => $r->kode],
            ['label' => 'Nama', 'value' => fn ($r) => $r->nama],
            ['label' => 'Tipe', 'value' => fn ($r) => $r->tipe->label()],
            ['label' => 'Jenjang', 'value' => fn ($r) => $r->jenjang?->label() ?? '-'],
            ['label' => 'Induk', 'value' => fn ($r) => $r->parent?->nama ?? '-'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'kode' => ['label' => 'Kode', 'rules' => ['required', 'max:30', Rule::unique('units', 'kode')->ignore($record?->id)]],
            'nama' => ['label' => 'Nama', 'rules' => 'required|max:255'],
            'singkatan' => ['label' => 'Singkatan', 'rules' => 'nullable|max:40', 'help' => 'Dipakai mencocokkan PIC "Ketua Unit" pada dokumen standar, mis. P3M.'],
            'tipe' => ['label' => 'Tipe', 'type' => 'select', 'options' => $this->enumOptions(UnitType::class),
                'rules' => ['required', Rule::enum(UnitType::class)]],
            'jenjang' => ['label' => 'Jenjang (khusus Prodi)', 'type' => 'select', 'placeholder' => '— bukan prodi —',
                'options' => $this->enumOptions(Jenjang::class), 'rules' => ['nullable', Rule::enum(Jenjang::class)],
                'help' => 'Menentukan standar jenjang tertentu (D3, S2, dst.) yang berlaku untuk prodi ini.'],
            'parent_id' => ['label' => 'Induk', 'type' => 'select', 'placeholder' => '— tidak ada —',
                'options' => Unit::where('id', '!=', $record?->id)->orderBy('nama')->pluck('nama', 'id')->all(),
                'rules' => ['nullable', 'exists:units,id']],
        ];
    }
}
