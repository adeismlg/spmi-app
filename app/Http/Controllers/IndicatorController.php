<?php

namespace App\Http\Controllers;

use App\Enums\IndicatorType;
use App\Models\Indicator;
use App\Models\IndicatorTarget;
use App\Models\Standard;
use App\Models\Statement;
use App\Support\TargetParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IndicatorController extends CrudController
{
    protected string $model = Indicator::class;

    protected string $routeName = 'indicators';

    protected string $title = 'Indikator Ketercapaian';

    protected function query(): Builder
    {
        return Indicator::query()->with(['standard', 'statement', 'targets'])
            ->when(request('standard_id'), fn ($q, $id) => $q->where('indicators.standard_id', $id))
            ->join('standards', 'standards.id', '=', 'indicators.standard_id')
            ->orderBy('standards.nomor')->orderBy('indicators.kode')
            ->select('indicators.*');
    }

    protected function filters(): array
    {
        return [
            'standard_id' => ['label' => 'Standar', 'options' => Standard::orderBy('nomor')->get()
                ->mapWithKeys(fn ($s) => [$s->id => "{$s->nomor}. {$s->nama}"])->all()],
        ];
    }

    protected function columns(): array
    {
        $tahun = (int) (\App\Models\Cycle::current()?->tahun ?? IndicatorTarget::TARGET_YEARS[0]);

        return [
            ['label' => 'Kode', 'value' => fn ($r) => $r->kode],
            ['label' => 'Indikator', 'value' => fn ($r) => Str::limit($r->nama, 100)],
            ['label' => 'Jenis', 'value' => fn ($r) => $r->tipe->label()],
            ['label' => 'Baseline '.IndicatorTarget::BASELINE_YEAR, 'value' => fn ($r) => $r->baseline()?->label($r->tipe) ?? '—'],
            ['label' => "Target {$tahun}", 'value' => fn ($r) => $r->targetLabel($tahun)],
        ];
    }

    protected function rowActions(): array
    {
        return [[
            'label' => 'Atur Kegiatan',
            'route' => fn ($row) => route('activities.index', ['indicator_id' => $row->id]),
            'method' => 'get',
            'class' => 'btn-outline-primary',
        ]];
    }

    protected function fields(?Model $record = null): array
    {
        $fields = [
            'statement_id' => ['label' => 'Pernyataan Standar', 'type' => 'select', 'default' => request('statement_id'),
                'options' => Statement::with('standard')->join('standards', 'standards.id', '=', 'statements.standard_id')
                    ->orderBy('standards.nomor')->orderBy('statements.nomor')->select('statements.*')->get()
                    ->mapWithKeys(fn ($s) => [$s->id => ($s->nomor ?? '—').' — '.Str::limit($s->pernyataan ?? '', 70)])->all(),
                'rules' => ['required', 'exists:statements,id']],
            'kode' => ['label' => 'Kode', 'help' => 'Umumnya sama dengan No Standar, mis. 1.01.',
                'rules' => ['required', 'max:30',
                    Rule::unique('indicators', 'kode')
                        ->where('standard_id', Statement::find(request('statement_id'))?->standard_id ?? $record?->standard_id)
                        ->ignore($record?->id)]],
            'nama' => ['label' => 'Indikator Ketercapaian', 'type' => 'textarea', 'rules' => 'required|string'],
            'tipe' => ['label' => 'Jenis Nilai', 'type' => 'select', 'default' => 'persen',
                'options' => $this->enumOptions(IndicatorType::class), 'rules' => ['required', Rule::enum(IndicatorType::class)],
                'help' => 'Menentukan cara menghitung skor: persen/angka/rupiah = capaian ÷ target; ada = ya/tidak; kualitatif = skor manual.'],
            'bobot' => ['label' => 'Bobot', 'type' => 'number', 'default' => 1, 'rules' => 'required|numeric|min:0'],
            'catatan' => ['label' => 'Catatan Reviewer', 'type' => 'textarea', 'rules' => 'nullable|string'],
            'baseline' => ['label' => 'Baseline '.IndicatorTarget::BASELINE_YEAR, 'virtual' => true, 'rules' => 'nullable|string|max:80',
                'value' => fn ($r) => $r?->targetInput('baseline', IndicatorTarget::BASELINE_YEAR),
                'help' => 'Isi angka (80), dengan pembanding (>70), atau "ada". Untuk persen cukup tulis 80, bukan 0,8.'],
        ];

        foreach (IndicatorTarget::TARGET_YEARS as $year) {
            $fields["target_{$year}"] = [
                'label' => "Target {$year}", 'virtual' => true, 'rules' => 'nullable|string|max:80',
                'value' => fn ($r) => $r?->targetInput('target', $year),
            ];
        }

        return $fields;
    }

    protected function beforeSave(array $data, Request $request, ?Model $record): array
    {
        $statement = Statement::findOrFail($data['statement_id']);
        $data['standard_id'] = $statement->standard_id;
        $data['satuan'] = IndicatorType::from($data['tipe'])->unit();

        return $data;
    }

    protected function afterSave(Model $record, Request $request): void
    {
        $tipe = $record->tipe;

        $inputs = ['baseline' => [IndicatorTarget::BASELINE_YEAR => $request->input('baseline')]];
        foreach (IndicatorTarget::TARGET_YEARS as $year) {
            $inputs['target'][$year] = $request->input("target_{$year}");
        }

        foreach ($inputs as $jenis => $perTahun) {
            foreach ($perTahun as $tahun => $raw) {
                $key = ['indicator_id' => $record->id, 'jenis' => $jenis, 'tahun' => $tahun];
                $parsed = TargetParser::parse($raw, $tipe);

                $parsed === null
                    ? IndicatorTarget::where($key)->delete()
                    : IndicatorTarget::updateOrCreate($key, $parsed);
            }
        }
    }
}
