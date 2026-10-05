<?php

namespace App\Http\Controllers;

use App\Enums\AuditStatus;
use App\Enums\Conformity;
use App\Enums\CycleStage;
use App\Enums\Semester;
use App\Enums\SubmissionStatus;
use App\Http\Controllers\Concerns\LimitsToUser;
use App\Models\Audit;
use App\Models\Cycle;
use App\Models\SelfEvaluation;
use App\Models\Unit;
use App\Models\User;
use App\Services\OpenFindingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditController extends CrudController
{
    use LimitsToUser;

    protected string $model = Audit::class;

    protected string $routeName = 'audits';

    protected string $title = 'Audit Mutu Internal (AMI)';

    protected function canManage(): bool
    {
        return auth()->user()->hasRole('admin_spmi');
    }

    protected function query(): Builder
    {
        $q = Audit::query()->with(['cycle', 'unit', 'auditors'])->withCount('findings')->latest('id');

        return auth()->user()->seesAllUnits()
            ? $q
            : $q->whereHas('auditors', fn ($a) => $a->where('users.id', auth()->id()));
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Siklus', 'value' => fn ($r) => $r->cycle->tahun],
            ['label' => 'Unit', 'value' => fn ($r) => $r->unit->nama],
            ['label' => 'Semester', 'value' => fn ($r) => $r->semester->label()],
            ['label' => 'Desk Evaluation', 'value' => fn ($r) => $r->tanggal_desk_evaluation?->format('d/m/Y') ?? '-'],
            ['label' => 'Visitasi', 'value' => fn ($r) => $r->tanggal_visitasi?->format('d/m/Y') ?? '-'],
            ['label' => 'Auditor', 'value' => fn ($r) => $r->auditors->pluck('name')->implode(', ') ?: '-'],
            ['label' => 'Temuan', 'value' => fn ($r) => $r->findings_count],
            ['label' => 'Status', 'value' => fn ($r) => $r->status->label()],
        ];
    }

    protected function rowActions(): array
    {
        return [[
            'label' => 'Daftar Tilik',
            'route' => fn ($r) => route('audits.show', $r->id),
            'method' => 'get',
            'class' => 'btn-outline-primary',
        ]];
    }

    protected function fields(?Model $record = null): array
    {
        $auditors = User::role('auditor')->orderBy('name')->pluck('name', 'id')->all();

        return [
            'cycle_id' => ['label' => 'Siklus', 'type' => 'select', 'default' => Cycle::current()?->id,
                'options' => Cycle::orderByDesc('tahun')->pluck('nama', 'id')->all(), 'rules' => ['required', 'exists:cycles,id']],
            'unit_id' => ['label' => 'Auditee (Unit/Prodi)', 'type' => 'select',
                'options' => Unit::orderBy('nama')->pluck('nama', 'id')->all(),
                'rules' => ['required', 'exists:units,id',
                    Rule::unique('audits', 'unit_id')
                        ->where('cycle_id', request('cycle_id'))
                        ->where('semester', request('semester'))
                        ->ignore($record?->id)]],
            'semester' => ['label' => 'Semester Audit', 'type' => 'select',
                'default' => Semester::current()->value,
                'options' => collect(Semester::cases())->mapWithKeys(fn ($semester) => [$semester->value => $semester->label()])->all(),
                'rules' => ['required', Rule::enum(Semester::class)]],
            'tanggal_desk_evaluation' => ['label' => 'Tanggal Desk Evaluation', 'type' => 'date', 'rules' => 'nullable|date'],
            'tanggal_visitasi' => ['label' => 'Tanggal Visitasi', 'type' => 'date', 'rules' => 'nullable|date|after_or_equal:tanggal_desk_evaluation'],
            'status' => ['label' => 'Status', 'type' => 'select', 'default' => 'terjadwal',
                'options' => $this->enumOptions(AuditStatus::class), 'rules' => ['required', Rule::enum(AuditStatus::class)]],
            'ketua' => ['label' => 'Ketua Auditor', 'type' => 'select', 'virtual' => true, 'placeholder' => '— pilih —',
                'options' => $auditors, 'rules' => 'nullable|exists:users,id',
                'value' => fn ($r) => $r?->auditors->firstWhere('pivot.peran', 'ketua')?->id],
            'auditors' => ['label' => 'Anggota Auditor', 'type' => 'multiselect', 'virtual' => true,
                'options' => $auditors, 'rules' => 'nullable|array',
                'value' => fn ($r) => $r?->auditors->where('pivot.peran', 'anggota')->pluck('id')->all() ?? []],
        ];
    }

    protected function extraRules(?Model $record): array
    {
        return ['auditors.*' => 'exists:users,id'];
    }

    protected function afterSave(Model $record, Request $request): void
    {
        $sync = [];
        foreach ((array) $request->input('auditors', []) as $id) {
            $sync[$id] = ['peran' => 'anggota'];
        }
        if ($ketua = $request->input('ketua')) {
            $sync[$ketua] = ['peran' => 'ketua'];
        }
        $record->auditors()->sync($sync);
    }

    /** Halaman daftar tilik. */
    public function show(string $id): View
    {
        $audit = $this->query()->findOrFail($id);
        $audit->load([
            'checklists' => fn ($q) => $q->with(['indicator.standard', 'indicator.targets', 'selfEvaluation', 'finding'])
                ->join('indicators', 'indicators.id', '=', 'audit_checklists.indicator_id')
                ->orderBy('indicators.standard_id')->orderBy('indicators.kode')
                ->select('audit_checklists.*'),
        ]);

        return view('audits.show', [
            'audit' => $audit,
            'semesterEvals' => SelfEvaluation::where('cycle_id', $audit->cycle_id)
                ->where('unit_id', $audit->unit_id)->where('semester', $audit->semester->value)
                ->when(! auth()->user()->hasRole('admin_spmi'), fn ($query) => $query->whereIn('status', [
                    SubmissionStatus::Submitted->value,
                    SubmissionStatus::Verified->value,
                ]))
                ->withCount('evidences')->get()->keyBy('indicator_id'),
            'previousOpenFindings' => app(OpenFindingService::class)->before($audit)
                ->groupBy('indicator_id'),
            'editable' => $audit->cycle->isStageOpen(CycleStage::Evaluasi)
                && auth()->user()->hasAnyRole(['admin_spmi', 'auditor']),
            'conformities' => $this->enumOptions(Conformity::class),
        ]);
    }
}
