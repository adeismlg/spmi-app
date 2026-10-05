<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Indicator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityController extends CrudController
{
    protected string $model = Activity::class;

    protected string $routeName = 'activities';

    protected string $title = 'Kegiatan Indikator';

    protected array $with = ['indicator.standard'];

    protected function query(): Builder
    {
        return Activity::query()->with($this->with)
            ->when(request('indicator_id'), fn ($query, $id) => $query->where('indicator_id', $id))
            ->orderBy('indicator_id')
            ->orderBy('urutan')->orderBy('id');
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Standar', 'value' => fn ($row) => $row->indicator->standard->kode],
            ['label' => 'Indikator', 'value' => fn ($row) => $row->indicator->kode.' — '.Str::limit($row->indicator->nama, 70)],
            ['label' => 'Kegiatan', 'value' => fn ($row) => $row->nama],
            ['label' => 'Urutan', 'value' => fn ($row) => $row->urutan],
            ['label' => 'Status', 'value' => fn ($row) => $row->is_active ? 'Aktif' : 'Nonaktif'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'indicator_id' => [
                'label' => 'Indikator',
                'type' => 'select',
                'default' => request('indicator_id'),
                'options' => Indicator::with('standard')->orderBy('kode')->get()
                    ->mapWithKeys(fn ($indicator) => [
                        $indicator->id => $indicator->standard->kode.' · '.$indicator->kode.' — '.Str::limit($indicator->nama, 70),
                    ])->all(),
                'rules' => 'required|exists:indicators,id',
            ],
            'nama' => ['label' => 'Nama Kegiatan', 'rules' => 'required|string|max:255'],
            'deskripsi' => ['label' => 'Petunjuk / Deskripsi', 'type' => 'textarea', 'rules' => 'nullable|string|max:3000'],
            'urutan' => ['label' => 'Urutan', 'type' => 'number', 'default' => 0, 'rules' => 'required|integer|min:0|max:65535'],
            'is_active' => ['label' => 'Kegiatan aktif', 'type' => 'checkbox', 'default' => true, 'rules' => 'boolean'],
        ];
    }

    public function destroy(string $id): RedirectResponse
    {
        $activity = Activity::findOrFail($id);
        if ($activity->evidences()->exists()) {
            return back()->with('error', 'Kegiatan ini sudah memiliki bukti. Nonaktifkan kegiatan agar riwayat tetap utuh.');
        }

        return parent::destroy($id);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $activity = Activity::findOrFail($id);
        if ($activity->evidences()->exists() && (int) $request->input('indicator_id') !== $activity->indicator_id) {
            return back()->withInput()->with('error', 'Kegiatan yang sudah memiliki bukti tidak dapat dipindahkan ke indikator lain.');
        }

        return parent::update($request, $id);
    }
}
