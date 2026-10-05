<?php

namespace App\Http\Controllers;

use App\Enums\ActionStatus;
use App\Http\Controllers\Concerns\LimitsToUser;
use App\Models\CorrectiveAction;
use App\Models\Finding;
use App\Models\User;
use App\Services\RtlReminderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CorrectiveActionController extends CrudController
{
    use LimitsToUser;

    protected string $model = CorrectiveAction::class;

    protected string $routeName = 'corrective-actions';

    protected string $title = 'Rencana Tindak Lanjut (RTL)';

    protected function canManage(): bool
    {
        return auth()->user()->hasAnyRole(['admin_spmi', 'auditee']);
    }

    protected function query(): Builder
    {
        $q = CorrectiveAction::query()->with(['finding.audit.unit', 'finding.checklist.indicator', 'pic'])->latest('id');

        return $this->limitToUser($q, 'finding.audit');
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Unit', 'value' => fn ($r) => $r->finding->audit->unit->nama],
            ['label' => 'Indikator', 'value' => fn ($r) => $r->finding->checklist?->indicator?->kode ?? '-'],
            ['label' => 'Tindakan', 'value' => fn ($r) => Str::limit($r->tindakan, 80)],
            ['label' => 'PIC', 'value' => fn ($r) => $r->pic?->name ?? '-'],
            ['label' => 'Target', 'value' => fn ($r) => ($r->target_selesai?->format('d/m/Y') ?? '-').($r->isOverdue() ? ' (terlambat)' : '')],
            ['label' => 'Status', 'value' => fn ($r) => $r->status->label()],
        ];
    }

    protected function rowActions(): array
    {
        return [[
            'label' => 'Ingatkan',
            'route' => fn ($r) => route('corrective-actions.remind', $r->id),
            'method' => 'post',
            'visible' => fn ($r) => $r->target_selesai !== null
                && ! in_array($r->status, [ActionStatus::Selesai, ActionStatus::Terverifikasi], true)
                && auth()->user()->hasAnyRole(['admin_spmi', 'auditor']),
            'class' => 'btn-outline-secondary',
            'confirm' => 'Kirim email pengingat ke penanggung jawab sekarang?',
        ], [
            'label' => 'Verifikasi',
            'route' => fn ($r) => route('corrective-actions.verify', $r->id),
            'method' => 'post',
            'visible' => fn ($r) => $r->status === ActionStatus::Selesai
                && auth()->user()->hasAnyRole(['admin_spmi', 'auditor']),
            'class' => 'btn-outline-success',
            'confirm' => 'Tandai RTL ini terverifikasi?',
        ]];
    }

    protected function fields(?Model $record = null): array
    {
        $findings = $this->limitToUser(
            Finding::with(['audit.unit', 'checklist.indicator']),
            'audit'
        )->latest('id')->get()->mapWithKeys(fn ($f) => [
            $f->id => "{$f->audit->unit->nama} — ".($f->checklist?->indicator?->kode ?? '-')." — {$f->kategori->label()}",
        ])->all();

        $statuses = collect(ActionStatus::cases())->reject(fn ($s) => $s === ActionStatus::Terverifikasi)
            ->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all();

        return [
            'finding_id' => ['label' => 'Temuan', 'type' => 'select', 'default' => request('finding_id'),
                'options' => $findings, 'rules' => ['required', 'exists:findings,id']],
            'akar_masalah' => ['label' => 'Akar Masalah', 'type' => 'textarea', 'rules' => 'nullable|string'],
            'tindakan' => ['label' => 'Tindakan Koreksi', 'type' => 'textarea', 'rules' => 'required|string'],
            'pic_user_id' => ['label' => 'Penanggung Jawab', 'type' => 'select', 'placeholder' => '— pilih —',
                'options' => User::orderBy('name')->pluck('name', 'id')->all(), 'rules' => 'nullable|exists:users,id'],
            'target_selesai' => ['label' => 'Target Selesai', 'type' => 'date', 'rules' => 'nullable|date'],
            'status' => ['label' => 'Status', 'type' => 'select', 'default' => 'rencana', 'options' => $statuses,
                'rules' => ['required', Rule::in(array_keys($statuses))]],
        ];
    }

    public function verify(Request $request, string $id): RedirectResponse
    {
        $action = $this->query()->findOrFail($id);

        abort_unless($action->status === ActionStatus::Selesai, 422, 'RTL harus berstatus Selesai sebelum diverifikasi.');

        $action->update([
            'status' => ActionStatus::Terverifikasi,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'catatan_verifikasi' => $request->input('catatan'),
        ]);

        return back()->with('success', 'RTL terverifikasi.');
    }

    /** Kirim pengingat email secara manual (mengabaikan jadwal otomatis). */
    public function remind(string $id, RtlReminderService $reminders): RedirectResponse
    {
        $action = $this->query()->findOrFail($id);

        abort_if($action->target_selesai === null, 422, 'RTL belum memiliki target selesai.');

        $count = $reminders->send($action);

        return back()->with(
            $count ? 'success' : 'error',
            $count ? "Pengingat dikirim ke {$count} penerima." : 'Tidak ada penerima dengan email yang valid (isi Penanggung Jawab).'
        );
    }
}
