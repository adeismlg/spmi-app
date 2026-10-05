@extends('layouts.app')
@section('title', 'Daftar Tilik')

@section('content')
<a href="{{ route('audits.index') }}" class="btn btn-sm btn-link px-0 mb-2">&larr; Daftar audit</a>

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between gap-3">
        <div>
            <h5 class="mb-1">{{ $audit->unit->nama }}</h5>
            <div class="text-muted">Siklus {{ $audit->cycle->tahun }} · {{ $audit->semester->label() }} · Status: <strong>{{ $audit->status->label() }}</strong></div>
            <div class="mt-2 small">
                Auditor:
                @forelse ($audit->auditors as $a)
                    <span class="badge bg-light text-dark border">{{ $a->name }}@if ($a->pivot->peran === 'ketua') (ketua)@endif</span>
                @empty
                    <span class="text-muted">belum ditugaskan</span>
                @endforelse
            </div>
        </div>
        <div class="small text-muted text-end">
            Desk evaluation: {{ $audit->tanggal_desk_evaluation?->format('d/m/Y') ?? '-' }}<br>
            Visitasi: {{ $audit->tanggal_visitasi?->format('d/m/Y') ?? '-' }}
        </div>
    </div>
</div>

@unless ($editable)
    <div class="alert alert-info">Daftar tilik hanya dapat diisi pada tahap Evaluasi oleh auditor yang ditugaskan.</div>
@endunless

@if ($audit->checklists->isEmpty())
    <div class="card"><div class="card-body text-center py-5">
        <p class="text-muted">Daftar tilik belum dibuat.</p>
        @if ($editable)
            <form method="POST" action="{{ route('audits.checklist.generate', $audit->id) }}">
                @csrf
                <button class="btn btn-primary">Buat daftar tilik semester ini</button>
            </form>
        @endif
    </div></div>
@else
    <form method="POST" action="{{ route('audits.checklist.update', $audit->id) }}">
        @csrf @method('PUT')

        @php $currentStandard = null; @endphp
        <div class="card mb-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width:26%">Indikator</th>
                        <th>Evaluasi Diri — {{ $audit->semester->label() }}</th>
                        <th style="width:160px">Kesesuaian</th>
                        <th style="width:100px">Skor Audit</th>
                        <th style="width:26%">Catatan</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($audit->checklists as $row)
                        @if ($currentStandard !== $row->indicator->standard_id)
                            @php $currentStandard = $row->indicator->standard_id; @endphp
                            <tr class="table-light"><td colspan="5" class="fw-semibold">{{ $row->indicator->standard->kode }} — {{ $row->indicator->standard->nama }}</td></tr>
                        @endif
                        <tr>
                            <td><span class="text-muted">{{ $row->indicator->kode }}</span> {{ $row->indicator->nama }}
                                <div class="small text-muted">Target {{ $audit->cycle->tahun }}: {{ $row->indicator->targetLabel($audit->cycle->tahun) }}</div>
                            </td>
                            <td class="small">
                                @if ($e = $semesterEvals->get($row->indicator_id))
                                    <div>Capaian <strong>{{ $e->capaian ?? '—' }}</strong> · skor <strong>{{ $e->skor ?? '—' }}</strong>
                                        <a href="{{ route('self-evaluations.show', $e->id) }}" title="Bukti dukung"><i class="bi bi-paperclip"></i>{{ $e->evidences_count }}</a>
                                    </div>
                                @else
                                    <span class="text-muted">belum diisi auditee pada semester ini</span>
                                @endif
                                @foreach ($previousOpenFindings->get($row->indicator_id, collect()) as $previous)
                                    <div class="mt-2 p-2 border-start border-warning border-3 bg-light">
                                        <strong>Temuan {{ $previous->audit->semester->label() }} belum ditutup:</strong>
                                        {{ $previous->finding->uraian }}
                                        <div class="text-muted">
                                            RTL:
                                            @forelse ($previous->finding->correctiveActions as $action)
                                                {{ $action->status->label() }}@if (! $loop->last), @endif
                                            @empty
                                                belum dibuat
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach
                            </td>
                            <td>
                                <select name="items[{{ $row->id }}][kesesuaian]" class="form-select form-select-sm" @disabled(! $editable)>
                                    <option value="">—</option>
                                    @foreach ($conformities as $value => $label)
                                        <option value="{{ $value }}" @selected(old("items.$row->id.kesesuaian", $row->kesesuaian?->value) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" step="any" min="0" max="100" class="form-control form-control-sm"
                                       name="items[{{ $row->id }}][skor_audit]" value="{{ old("items.$row->id.skor_audit", $row->skor_audit) }}" @disabled(! $editable)>
                            </td>
                            <td>
                                <textarea rows="2" class="form-control form-control-sm @error("items.$row->id.catatan") is-invalid @enderror"
                                          name="items[{{ $row->id }}][catatan]" @disabled(! $editable)>{{ old("items.$row->id.catatan", $row->catatan) }}</textarea>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($editable)
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary">Simpan</button>
                @if ($audit->status === \App\Enums\AuditStatus::DeskEvaluation)
                    <button name="to_visitasi" value="1" class="btn btn-outline-primary">Simpan & lanjut ke Visitasi</button>
                @endif
                <button name="finish" value="1" class="btn btn-outline-success" onclick="return confirm('Selesaikan audit ini?')">Simpan & selesaikan audit</button>
            </div>
        @endif
    </form>

    @if ($editable)
        <form method="POST" action="{{ route('audits.checklist.generate', $audit->id) }}" class="mt-3">
            @csrf
            <button class="btn btn-sm btn-link px-0">Sinkronkan indikator baru ke daftar tilik</button>
        </form>
    @endif
@endif
@endsection
