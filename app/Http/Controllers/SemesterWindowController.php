<?php

namespace App\Http\Controllers;

use App\Enums\Semester;
use App\Models\Cycle;
use App\Models\SemesterWindow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SemesterWindowController extends CrudController
{
    protected string $model = SemesterWindow::class;

    protected string $routeName = 'semester-windows';

    protected string $title = 'Jadwal Pengisian & Pemeriksaan';

    protected array $with = ['cycle'];

    protected function columns(): array
    {
        return [
            ['label' => 'Siklus', 'value' => fn ($row) => $row->cycle->nama],
            ['label' => 'Semester', 'value' => fn ($row) => $row->semester->label()],
            ['label' => 'Pengisian', 'value' => fn ($row) => $row->pengisian_mulai->format('d/m/Y H:i').' – '.$row->pengisian_selesai->format('d/m/Y H:i')],
            ['label' => 'Pemeriksaan', 'value' => fn ($row) => $row->pemeriksaan_mulai->format('d/m/Y H:i').' – '.$row->pemeriksaan_selesai->format('d/m/Y H:i')],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'cycle_id' => [
                'label' => 'Siklus',
                'type' => 'select',
                'options' => Cycle::orderByDesc('tahun')->pluck('nama', 'id')->all(),
                'rules' => ['required', 'exists:cycles,id'],
            ],
            'semester' => [
                'label' => 'Semester',
                'type' => 'select',
                'options' => collect(Semester::cases())->mapWithKeys(fn ($semester) => [$semester->value => $semester->label()])->all(),
                'rules' => ['required', Rule::enum(Semester::class)],
            ],
            'pengisian_mulai' => $this->dateTimeField('Pengisian dibuka', $record?->pengisian_mulai),
            'pengisian_selesai' => $this->dateTimeField('Pengisian ditutup', $record?->pengisian_selesai),
            'pemeriksaan_mulai' => $this->dateTimeField('Pemeriksaan dibuka', $record?->pemeriksaan_mulai),
            'pemeriksaan_selesai' => $this->dateTimeField('Pemeriksaan ditutup', $record?->pemeriksaan_selesai),
        ];
    }

    protected function extraRules(?Model $record): array
    {
        return [
            'cycle_id' => ['required', 'exists:cycles,id'],
            'semester' => ['required', Rule::enum(Semester::class),
                Rule::unique('semester_windows', 'semester')->where('cycle_id', request('cycle_id'))->ignore($record?->id)],
        ];
    }

    protected function beforeSave(array $data, Request $request, ?Model $record): array
    {
        Validator::make($data, [
            'pengisian_selesai' => ['required', 'date', 'after:pengisian_mulai'],
            'pemeriksaan_mulai' => ['required', 'date', 'after_or_equal:pengisian_selesai'],
            'pemeriksaan_selesai' => ['required', 'date', 'after:pemeriksaan_mulai'],
        ], [
            'pengisian_selesai.after' => 'Waktu penutupan pengisian harus setelah pembukaan.',
            'pemeriksaan_mulai.after_or_equal' => 'Pemeriksaan harus dimulai setelah pengisian ditutup.',
            'pemeriksaan_selesai.after' => 'Waktu penutupan pemeriksaan harus setelah pembukaan.',
        ])->validate();

        return $data;
    }

    private function dateTimeField(string $label, mixed $value): array
    {
        return [
            'label' => $label,
            'type' => 'datetime-local',
            'rules' => 'required|date',
            'value' => fn () => $value?->format('Y-m-d\TH:i'),
        ];
    }
}
