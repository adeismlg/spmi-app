<?php

namespace App\Http\Controllers;

use App\Enums\Conformity;
use App\Http\Controllers\Concerns\LimitsToUser;
use App\Models\Finding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FindingController extends CrudController
{
    use LimitsToUser;

    protected string $model = Finding::class;

    protected string $routeName = 'findings';

    protected string $title = 'Temuan Audit';

    protected function canManage(): bool
    {
        return auth()->user()->hasAnyRole(['admin_spmi', 'auditor']);
    }

    protected function query(): Builder
    {
        $q = Finding::query()->with(['audit.unit', 'audit.cycle', 'checklist.indicator'])
            ->withCount('correctiveActions')->latest('id');

        return $this->limitToUser($q, 'audit');
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Siklus', 'value' => fn ($r) => $r->audit->cycle->tahun],
            ['label' => 'Unit', 'value' => fn ($r) => $r->audit->unit->nama],
            ['label' => 'Indikator', 'value' => fn ($r) => $r->checklist?->indicator?->kode ?? '-'],
            ['label' => 'Kategori', 'badge' => fn ($r) => [$r->kategori->label(), $r->kategori->badge()]],
            ['label' => 'Uraian', 'value' => fn ($r) => Str::limit($r->uraian, 90)],
            ['label' => 'RTL', 'value' => fn ($r) => $r->corrective_actions_count],
        ];
    }

    protected function rowActions(): array
    {
        return [[
            'label' => 'Buat RTL',
            'route' => fn ($r) => route('corrective-actions.create', ['finding_id' => $r->id]),
            'method' => 'get',
            'visible' => fn ($r) => auth()->user()->hasAnyRole(['admin_spmi', 'auditee'])
                && $r->audit->cycle->tahap_aktif->value === 'pengendalian',
            'class' => 'btn-outline-warning',
        ]];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'kategori' => ['label' => 'Kategori', 'type' => 'select',
                'options' => collect(Conformity::cases())->filter->isFinding()->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all(),
                'rules' => ['required', Rule::enum(Conformity::class)]],
            'uraian' => ['label' => 'Uraian Temuan', 'type' => 'textarea', 'rules' => 'required|string'],
            'rekomendasi' => ['label' => 'Rekomendasi Auditor', 'type' => 'textarea', 'rules' => 'nullable|string'],
        ];
    }
}
