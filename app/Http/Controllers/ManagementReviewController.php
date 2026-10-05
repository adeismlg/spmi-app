<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\Finding;
use App\Models\ManagementReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ManagementReviewController extends CrudController
{
    protected string $model = ManagementReview::class;

    protected string $routeName = 'management-reviews';

    protected string $title = 'Rapat Tinjauan Manajemen (RTM)';

    protected function canManage(): bool
    {
        return auth()->user()->hasRole('admin_spmi');
    }

    protected function query(): Builder
    {
        return ManagementReview::query()->with('cycle')->withCount('findings')->latest('tanggal');
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Siklus', 'value' => fn ($r) => $r->cycle->tahun],
            ['label' => 'Judul', 'value' => fn ($r) => $r->judul],
            ['label' => 'Tanggal', 'value' => fn ($r) => $r->tanggal->format('d/m/Y')],
            ['label' => 'Temuan Dibahas', 'value' => fn ($r) => $r->findings_count],
            ['label' => 'Keputusan', 'value' => fn ($r) => \Illuminate\Support\Str::limit($r->keputusan ?? '-', 70)],
        ];
    }

    protected function rowActions(): array
    {
        return [[
            'label' => 'Berkas',
            'route' => fn ($r) => asset('storage/'.$r->file_path),
            'method' => 'get',
            'visible' => fn ($r) => (bool) $r->file_path,
            'class' => 'btn-outline-primary',
        ]];
    }

    protected function fields(?Model $record = null): array
    {
        $findings = Finding::with(['audit.unit', 'checklist.indicator'])
            ->whereHas('audit.cycle', fn ($q) => $q->where('is_active', true))
            ->get()->mapWithKeys(fn ($f) => [
                $f->id => "{$f->audit->unit->nama} — ".($f->checklist?->indicator?->kode ?? '-')." — {$f->kategori->label()}",
            ])->all();

        return [
            'cycle_id' => ['label' => 'Siklus', 'type' => 'select', 'default' => Cycle::current()?->id,
                'options' => Cycle::orderByDesc('tahun')->pluck('nama', 'id')->all(), 'rules' => ['required', 'exists:cycles,id']],
            'judul' => ['label' => 'Judul Rapat', 'rules' => 'required|max:255'],
            'tanggal' => ['label' => 'Tanggal', 'type' => 'date', 'rules' => 'required|date'],
            'notulen' => ['label' => 'Notulen', 'type' => 'textarea', 'rules' => 'nullable|string'],
            'keputusan' => ['label' => 'Keputusan', 'type' => 'textarea', 'rules' => 'nullable|string'],
            'rekomendasi' => ['label' => 'Rekomendasi untuk Siklus Berikutnya', 'type' => 'textarea', 'rules' => 'nullable|string',
                'help' => 'Menjadi masukan tahap Peningkatan / Penetapan siklus berikutnya.'],
            'file_path' => ['label' => 'Berkas Notulen', 'type' => 'file', 'rules' => 'nullable|file|max:10240'],
            'findings' => ['label' => 'Temuan yang Dibahas', 'type' => 'multiselect', 'virtual' => true,
                'options' => $findings, 'rules' => 'nullable|array',
                'value' => fn ($r) => $r?->findings->pluck('id')->all() ?? []],
        ];
    }

    protected function extraRules(?Model $record): array
    {
        return ['findings.*' => 'exists:findings,id'];
    }

    protected function afterSave(Model $record, Request $request): void
    {
        $record->findings()->sync((array) $request->input('findings', []));
    }
}
