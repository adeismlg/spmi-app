<?php

namespace App\Http\Controllers;

use App\Enums\StandardCategory;
use App\Enums\StandardGroup;
use App\Models\Standard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class StandardController extends CrudController
{
    protected string $model = Standard::class;

    protected string $routeName = 'standards';

    protected string $title = 'Standar Mutu';

    protected function query(): Builder
    {
        return Standard::query()->withCount(['statements', 'indicators'])->orderBy('nomor')->orderBy('kode');
    }

    protected function columns(): array
    {
        return [
            ['label' => 'No', 'value' => fn ($r) => $r->nomor ?? '-'],
            ['label' => 'Nama Standar', 'value' => fn ($r) => $r->nama],
            ['label' => 'Kelompok', 'value' => fn ($r) => $r->kelompok?->label() ?? '-'],
            ['label' => 'Pernyataan', 'value' => fn ($r) => $r->statements_count],
            ['label' => 'Indikator', 'value' => fn ($r) => $r->indicators_count],
            ['label' => 'Aktif', 'value' => fn ($r) => $r->is_active ? 'Ya' : 'Tidak'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'nomor' => ['label' => 'Nomor Standar (1-24)', 'type' => 'number', 'rules' => 'nullable|integer|between:1,99'],
            'kode' => ['label' => 'Kode', 'rules' => ['required', 'max:30', Rule::unique('standards', 'kode')->ignore($record?->id)]],
            'nama' => ['label' => 'Nama Standar', 'rules' => 'required|max:255'],
            'kelompok' => ['label' => 'Kelompok', 'type' => 'select', 'placeholder' => '— pilih —',
                'options' => $this->enumOptions(StandardGroup::class), 'rules' => ['nullable', Rule::enum(StandardGroup::class)],
                'help' => '1-8 Pembelajaran, 9-16 Penelitian, 17-24 Pengabdian kepada Masyarakat.'],
            'kategori' => ['label' => 'Kategori', 'type' => 'select', 'default' => 'sn_dikti',
                'options' => $this->enumOptions(StandardCategory::class), 'rules' => ['required', Rule::enum(StandardCategory::class)]],
            'deskripsi' => ['label' => 'Deskripsi', 'type' => 'textarea', 'rules' => 'nullable|string'],
            'is_active' => ['label' => 'Aktif', 'type' => 'checkbox', 'default' => true, 'rules' => 'boolean'],
        ];
    }
}
