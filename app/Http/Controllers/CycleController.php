<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\StandardEdition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CycleController extends CrudController
{
    protected string $model = Cycle::class;

    protected string $routeName = 'cycles';

    protected string $title = 'Siklus PPEPP';

    protected array $with = ['standardEdition'];

    protected function columns(): array
    {
        return [
            ['label' => 'Tahun', 'value' => fn ($r) => $r->tahun],
            ['label' => 'Nama', 'value' => fn ($r) => $r->nama],
            ['label' => 'Edisi Standar', 'value' => fn ($r) => $r->standardEdition?->label ?? 'Belum ditetapkan'],
            ['label' => 'Tahap Aktif', 'value' => fn ($r) => $r->tahap_aktif->label()],
            ['label' => 'Status', 'value' => fn ($r) => $r->is_active ? 'Aktif' : 'Nonaktif'],
        ];
    }

    protected function rowActions(): array
    {
        return [[
            'label' => fn ($r) => $r->tahap_aktif->next()
                ? 'Tutup tahap & lanjut ke '.$r->tahap_aktif->next()->label()
                : 'Selesaikan siklus',
            'route' => fn ($r) => route('cycles.advance', $r->id),
            'method' => 'post',
            'visible' => fn ($r) => $r->is_active,
            'class' => 'btn-outline-success',
            'confirm' => 'Tutup tahap aktif sekarang? Tahap yang sudah ditutup tidak bisa dibuka lagi.',
        ]];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'standard_edition_id' => ['label' => 'Edisi Standar yang Digunakan', 'type' => 'select', 'placeholder' => '— pilih edisi —',
                'options' => StandardEdition::orderByDesc('tahun_mulai')->get()->mapWithKeys(fn ($edition) => [$edition->id => $edition->label])->all(),
                'rules' => ['required', 'integer', Rule::exists('standard_editions', 'id')]],
            'tahun' => ['label' => 'Tahun', 'type' => 'number', 'default' => date('Y'),
                'rules' => ['required', 'integer', 'between:2000,2100', Rule::unique('cycles', 'tahun')->ignore($record?->id)]],
            'nama' => ['label' => 'Nama Siklus', 'rules' => 'required|max:255'],
            'is_active' => ['label' => 'Jadikan siklus aktif', 'type' => 'checkbox', 'rules' => 'boolean',
                'help' => 'Hanya satu siklus yang boleh aktif.'],
        ];
    }

    protected function beforeSave(array $data, Request $request, ?Model $record): array
    {
        $edition = StandardEdition::findOrFail($data['standard_edition_id']);
        $year = (int) $data['tahun'];
        if ($year < $edition->tahun_mulai || ($edition->tahun_selesai !== null && $year > $edition->tahun_selesai)) {
            throw ValidationException::withMessages([
                'tahun' => 'Tahun siklus harus berada dalam masa berlaku edisi standar yang dipilih.',
            ]);
        }

        if ($record && $record->standard_edition_id !== (int) $data['standard_edition_id']
            && ($record->selfEvaluations()->exists() || $record->audits()->exists())) {
            throw ValidationException::withMessages([
                'standard_edition_id' => 'Edisi tidak dapat diubah karena siklus ini sudah memiliki evaluasi diri atau audit.',
            ]);
        }

        return $data;
    }

    protected function afterSave(Model $record, Request $request): void
    {
        if ($record->is_active) {
            Cycle::where('id', '!=', $record->id)->update(['is_active' => false]);
        }
    }

    public function advance(Cycle $cycle): RedirectResponse
    {
        abort_unless($cycle->is_active, 422);

        if ($cycle->advanceStage()) {
            return back()->with('success', 'Tahap '.$cycle->fresh()->tahap_aktif->label().' dibuka.');
        }

        $cycle->update(['is_active' => false]);

        return back()->with('success', 'Siklus '.$cycle->tahun.' diselesaikan.');
    }
}
