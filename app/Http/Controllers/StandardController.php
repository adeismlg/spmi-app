<?php

namespace App\Http\Controllers;

use App\Enums\StandardCategory;
use App\Enums\StandardGroup;
use App\Models\Standard;
use App\Models\StandardEdition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StandardController extends CrudController
{
    protected string $model = Standard::class;

    protected string $routeName = 'standards';

    protected string $title = 'Standar Mutu';

    protected function query(): Builder
    {
        return Standard::query()->with(['edition'])->withCount(['statements', 'indicators'])
            ->orderByDesc('standard_edition_id')->orderBy('nomor')->orderBy('kode');
    }

    protected function columns(): array
    {
        return [
            ['label' => 'No', 'value' => fn ($r) => $r->nomor ?? '-'],
            ['label' => 'Edisi', 'value' => fn ($r) => $r->edition?->label ?? 'Belum ditetapkan'],
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
            'standard_edition_id' => ['label' => 'Edisi Standar', 'type' => 'select', 'placeholder' => '— pilih edisi —',
                'options' => StandardEdition::orderByDesc('tahun_mulai')->get()
                    ->mapWithKeys(fn ($edition) => [$edition->id => $edition->label])->all(),
                'rules' => ['required', 'integer', Rule::exists('standard_editions', 'id')]],
            'nomor' => ['label' => 'Nomor Standar (1-24)', 'type' => 'number', 'rules' => 'nullable|integer|between:1,99'],
            'kode' => ['label' => 'Kode', 'rules' => ['required', 'max:30', Rule::unique('standards', 'kode')
                ->where(fn ($query) => $query->where('standard_edition_id', request()->input('standard_edition_id')))
                ->ignore($record?->id)]],
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

    protected function beforeSave(array $data, Request $request, ?Model $record): array
    {
        $targetEdition = StandardEdition::findOrFail($data['standard_edition_id']);
        $currentEditionHasData = $record?->edition?->hasCycleData() ?? false;
        $targetEditionHasData = $targetEdition->hasCycleData()
            && (! $record || $record->standard_edition_id !== $targetEdition->id);

        if ($currentEditionHasData || $targetEditionHasData) {
            throw ValidationException::withMessages([
                'standard_edition_id' => 'Isi edisi yang sudah memiliki hasil evaluasi atau audit tidak dapat diubah. Buat edisi baru untuk revisi.',
            ]);
        }

        if ($record && $record->indicators()->where(function ($query) {
            $query->whereHas('selfEvaluations')->orWhereHas('checklists');
        })->exists()) {
            throw ValidationException::withMessages([
                'kode' => 'Standar ini sudah digunakan dalam evaluasi atau audit. Buat edisi baru untuk mengubahnya.',
            ]);
        }

        return $data;
    }

    public function destroy(string $id): RedirectResponse
    {
        $standard = Standard::findOrFail($id);
        if ($standard->edition?->hasCycleData()) {
            return back()->with('error', 'Standar pada edisi yang sudah memiliki evaluasi atau audit tidak dapat dihapus.');
        }

        $hasHistoricalData = $standard->indicators()->where(function ($query) {
            $query->whereHas('selfEvaluations')->orWhereHas('checklists');
        })->exists();

        if ($hasHistoricalData) {
            return back()->with('error', 'Standar ini sudah digunakan dalam evaluasi atau audit dan tidak dapat dihapus.');
        }

        return parent::destroy($id);
    }
}
