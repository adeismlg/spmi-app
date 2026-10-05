<?php

namespace App\Http\Controllers;

use App\Enums\EvaluationPeriod;
use App\Enums\Jabatan;
use App\Enums\Jenjang;
use App\Enums\StatementStatus;
use App\Models\Standard;
use App\Models\Statement;
use App\Services\AssignmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Pernyataan Standar: satu baris pada dokumen standar (nomor, pernyataan ABCD, PIC baku, periode, referensi). */
class StatementController extends CrudController
{
    protected string $model = Statement::class;

    protected string $routeName = 'statements';

    protected string $title = 'Pernyataan Standar';

    protected function query(): Builder
    {
        return Statement::query()->with('standard')
            ->when(request('standard_id'), fn ($q, $id) => $q->where('statements.standard_id', $id))
            ->when(request('status'), fn ($q, $s) => $q->where('statements.status', $s))
            ->when(request('penugasan') === 'kosong', fn ($q) => $q->whereDoesntHave('assignments')
                ->where('statements.status', '!=', StatementStatus::UsulanHapus->value))
            ->join('standards', 'standards.id', '=', 'statements.standard_id')
            ->orderBy('standards.nomor')->orderBy('statements.nomor')
            ->select('statements.*')
            ->withCount('assignments');
    }

    protected function filters(): array
    {
        return [
            'standard_id' => ['label' => 'Standar', 'options' => Standard::orderBy('nomor')->get()
                ->mapWithKeys(fn ($s) => [$s->id => "{$s->nomor}. {$s->nama}"])->all()],
            'status' => ['label' => 'Status', 'options' => $this->enumOptions(StatementStatus::class)],
            'penugasan' => ['label' => 'Penugasan', 'options' => ['kosong' => 'Belum ditugaskan ke unit mana pun']],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'No', 'value' => fn ($r) => $r->nomor ?? '-'],
            ['label' => 'Pernyataan', 'value' => fn ($r) => Str::limit($r->pernyataan ?? '-', 90)],
            ['label' => 'PIC', 'value' => fn ($r) => $r->picLabel()],
            ['label' => 'Periode', 'value' => fn ($r) => $r->periode_evaluasi?->label() ?? '-'],
            ['label' => 'Unit', 'value' => fn ($r) => $r->assignments_count ?: '—'],
            ['label' => 'Status', 'badge' => fn ($r) => [$r->status->label(), match ($r->status) {
                StatementStatus::Aktif => 'success', StatementStatus::UsulanBaru => 'info',
                StatementStatus::UsulanHapus => 'danger', StatementStatus::Gabungan => 'warning',
            }]],
        ];
    }

    protected function rowActions(): array
    {
        return [[
            'label' => 'Penugasan',
            'route' => fn ($r) => route('statements.assignments.edit', $r->id),
            'method' => 'get',
            'class' => 'btn-outline-primary',
        ]];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'standard_id' => ['label' => 'Standar', 'type' => 'select', 'default' => request('standard_id'),
                'options' => Standard::orderBy('nomor')->get()->mapWithKeys(fn ($s) => [$s->id => "{$s->nomor}. {$s->nama}"])->all(),
                'rules' => ['required', 'exists:standards,id']],
            'nomor' => ['label' => 'No Standar', 'help' => 'Contoh: 1.01',
                'rules' => ['nullable', 'regex:/^\d{1,2}\.\d{2}$/',
                    Rule::unique('statements', 'nomor')->where('standard_id', request('standard_id'))->ignore($record?->id)]],
            'pernyataan' => ['label' => 'Pernyataan Standar (Berbasis ABCD)', 'type' => 'textarea', 'rules' => 'nullable|string'],
            'abcd_audience' => ['label' => 'A — Audience', 'type' => 'textarea', 'rules' => 'nullable|string', 'help' => 'Siapa pelaksananya.'],
            'abcd_behaviour' => ['label' => 'B — Behaviour', 'type' => 'textarea', 'rules' => 'nullable|string', 'help' => 'Perilaku/tindakan yang operasional.'],
            'abcd_condition' => ['label' => 'C — Condition', 'type' => 'textarea', 'rules' => 'nullable|string', 'help' => 'Kondisi/batasan pelaksanaan.'],
            'abcd_degree' => ['label' => 'D — Degree', 'type' => 'textarea', 'rules' => 'nullable|string', 'help' => 'Ukuran/target yang terukur.'],
            'pic_jabatan' => ['label' => 'PIC (jabatan baku)', 'type' => 'multiselect', 'options' => $this->enumOptions(Jabatan::class),
                'rules' => 'nullable|array',
                'help' => 'Menentukan jabatan yang mengisi evaluasi dan tipe unit yang ditugaskan. Perubahan menghitung ulang penugasan otomatis.'],
            'pic_unit' => ['label' => 'Nama unit (untuk Ketua Unit)', 'rules' => 'nullable|string|max:255',
                'value' => fn ($r) => implode(', ', $r?->pic_unit ?? []),
                'help' => 'Pisahkan dengan koma, mis. "P3M, UPA TIK". Unit dibuat otomatis bila belum ada.'],
            'jenjang' => ['label' => 'Berlaku untuk jenjang (Koordinator Prodi)', 'type' => 'multiselect',
                'options' => $this->enumOptions(Jenjang::class), 'rules' => 'nullable|array',
                'help' => 'Kosongkan bila berlaku untuk semua prodi.'],
            'pic' => ['label' => 'PIC asli di dokumen (arsip)', 'rules' => 'nullable|max:255'],
            'periode_evaluasi' => ['label' => 'Periode Evaluasi', 'type' => 'select', 'placeholder' => '— pilih —',
                'options' => $this->enumOptions(EvaluationPeriod::class), 'rules' => ['nullable', Rule::enum(EvaluationPeriod::class)],
                'help' => 'Setiap semester = ganjil & genap; tahunan = genap; setiap 5 tahun = genap pada tahun kalender kelipatan 5.'],
            'referensi' => ['label' => 'Referensi', 'type' => 'textarea', 'rules' => 'nullable|string'],
            'status' => ['label' => 'Status Usulan', 'type' => 'select', 'default' => 'aktif',
                'options' => $this->enumOptions(StatementStatus::class), 'rules' => ['required', Rule::enum(StatementStatus::class)]],
            'catatan' => ['label' => 'Catatan Reviewer', 'type' => 'textarea', 'rules' => 'nullable|string'],
        ];
    }

    protected function extraRules(?Model $record): array
    {
        return [
            'pic_jabatan.*' => [Rule::enum(Jabatan::class)],
            'jenjang.*' => [Rule::enum(Jenjang::class)],
        ];
    }

    protected function beforeSave(array $data, Request $request, ?Model $record): array
    {
        $units = array_values(array_filter(array_map('trim', explode(',', (string) ($data['pic_unit'] ?? '')))));

        $data['pic_unit'] = $units ?: null;
        $data['pic_jabatan'] = ! empty($data['pic_jabatan']) ? array_values($data['pic_jabatan']) : null;
        $data['jenjang'] = ! empty($data['jenjang']) ? array_values($data['jenjang']) : null;

        return $data;
    }

    protected function afterSave(Model $record, Request $request): void
    {
        app(AssignmentService::class)->forStatement($record);
    }
}
