<?php

namespace App\Http\Controllers;

use App\Enums\IndicatorType;
use App\Models\AuditChecklist;
use App\Models\Cycle;
use App\Models\Indicator;
use App\Models\IndicatorTarget;
use App\Models\SelfEvaluation;
use App\Models\Standard;
use App\Models\StandardEdition;
use App\Models\Statement;
use App\Support\TargetParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IndicatorController extends CrudController
{
    protected string $model = Indicator::class;

    protected string $routeName = 'indicators';

    protected string $title = 'Indikator Ketercapaian';

    protected function query(): Builder
    {
        return Indicator::query()->with(['standard.edition', 'statement', 'targets'])
            ->when(request('standard_id'), fn ($q, $id) => $q->where('indicators.standard_id', $id))
            ->join('standards', 'standards.id', '=', 'indicators.standard_id')
            ->orderBy('standards.nomor')->orderBy('indicators.kode')
            ->select('indicators.*');
    }

    protected function filters(): array
    {
        return [
            'standard_id' => ['label' => 'Standar', 'options' => Standard::with('edition')->orderByDesc('standard_edition_id')->orderBy('nomor')->get()
                ->mapWithKeys(fn ($s) => [$s->id => "{$s->edition?->kode} · {$s->nomor}. {$s->nama}"])->all()],
        ];
    }

    protected function columns(): array
    {
        $tahun = (int) (Cycle::current()?->tahun ?? IndicatorTarget::TARGET_YEARS[0]);

        return [
            ['label' => 'Kode', 'value' => fn ($r) => $r->kode],
            ['label' => 'Edisi / Standar', 'value' => fn ($r) => ($r->standard?->edition?->kode ?? 'LEGACY').' · '.($r->standard?->kode ?? '-')],
            ['label' => 'Indikator', 'value' => fn ($r) => Str::limit($r->nama, 100)],
            ['label' => 'Jenis', 'value' => fn ($r) => $r->tipe->label()],
            ['label' => 'Baseline', 'value' => fn ($r) => $r->baseline()?->label($r->tipe) ?? '—'],
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
        [$baselineYear, $targetYears] = $this->targetPeriod($record);
        $baselineLabel = $record || request('statement_id')
            ? 'Baseline '.$baselineYear
            : 'Baseline (tahun sebelum edisi)';
        if (! $record && ! request('statement_id')) {
            $targetYears = $this->availableTargetYears();
        }
        $fields = [
            'statement_id' => ['label' => 'Pernyataan Standar', 'type' => 'select', 'default' => request('statement_id'),
                'options' => Statement::with('standard.edition')->join('standards', 'standards.id', '=', 'statements.standard_id')
                    ->orderBy('standards.nomor')->orderBy('statements.nomor')->select('statements.*')->get()
                    ->mapWithKeys(fn ($s) => [$s->id => "{$s->standard?->edition?->kode} · ".($s->nomor ?? '—').' — '.Str::limit($s->pernyataan ?? '', 70)])->all(),
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
            'baseline' => ['label' => $baselineLabel, 'virtual' => true, 'rules' => 'nullable|string|max:80',
                'value' => fn ($r) => $r?->targetInput('baseline', $baselineYear),
                'help' => 'Isi angka (80), dengan pembanding (>70), atau "ada". Untuk persen cukup tulis 80, bukan 0,8.'],
        ];

        foreach ($targetYears as $year) {
            $fields["target_{$year}"] = [
                'label' => "Target {$year}", 'virtual' => true, 'rules' => 'nullable|string|max:80',
                'value' => fn ($r) => $r?->targetInput('target', $year),
            ];
        }

        return $fields;
    }

    /**
     * @return array{0: int, 1: array<int, int>}
     */
    private function targetPeriod(?Model $record = null, ?Request $request = null): array
    {
        $statementId = $request?->input('statement_id') ?? request('statement_id');
        $standardId = $statementId
            ? Statement::find($statementId)?->standard_id
            : $record?->standard_id;
        $edition = $standardId
            ? Standard::with('edition')->find($standardId)?->edition
            : null;

        if (! $edition || $edition->tahun_mulai <= 1900) {
            return [IndicatorTarget::BASELINE_YEAR, IndicatorTarget::TARGET_YEARS];
        }

        $startYear = $edition->tahun_mulai;
        $endYear = $edition->tahun_selesai ?? ($startYear + 4);

        return [$startYear - 1, range($startYear, $endYear)];
    }

    /**
     * @return array<int, int>
     */
    private function availableTargetYears(): array
    {
        $years = collect(IndicatorTarget::TARGET_YEARS);

        foreach (StandardEdition::query()->where('tahun_mulai', '>', 1900)->get() as $edition) {
            $endYear = $edition->tahun_selesai ?? ($edition->tahun_mulai + 4);
            $years = $years->merge(range($edition->tahun_mulai, $endYear));
        }

        return $years->unique()->sort()->values()->all();
    }

    protected function beforeSave(array $data, Request $request, ?Model $record): array
    {
        $statement = Statement::findOrFail($data['statement_id']);
        $standard = Standard::with('edition')->findOrFail($statement->standard_id);
        if ($standard->edition?->hasCycleData()) {
            throw ValidationException::withMessages([
                'statement_id' => 'Isi edisi yang sudah memiliki hasil evaluasi atau audit tidak dapat diubah. Buat edisi baru untuk revisi.',
            ]);
        }

        if ($record && (SelfEvaluation::where('indicator_id', $record->id)->exists()
            || AuditChecklist::where('indicator_id', $record->id)->exists())) {
            throw ValidationException::withMessages([
                'statement_id' => 'Indikator ini sudah digunakan dalam evaluasi atau audit dan tidak dapat diubah.',
            ]);
        }

        $data['standard_id'] = $statement->standard_id;
        $data['satuan'] = IndicatorType::from($data['tipe'])->unit();

        return $data;
    }

    protected function afterSave(Model $record, Request $request): void
    {
        $tipe = $record->tipe;

        [$baselineYear, $targetYears] = $this->targetPeriod($record, $request);
        $inputs = ['baseline' => [$baselineYear => $request->input('baseline')]];
        foreach ($targetYears as $year) {
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

    public function destroy(string $id): RedirectResponse
    {
        $indicator = Indicator::findOrFail($id);
        if ($indicator->standard?->edition?->hasCycleData()) {
            return back()->with('error', 'Indikator pada edisi yang sudah memiliki evaluasi atau audit tidak dapat dihapus.');
        }

        if ($indicator->selfEvaluations()->exists() || $indicator->checklists()->exists()) {
            return back()->with('error', 'Indikator ini sudah digunakan dalam evaluasi atau audit dan tidak dapat dihapus.');
        }

        return parent::destroy($id);
    }
}
