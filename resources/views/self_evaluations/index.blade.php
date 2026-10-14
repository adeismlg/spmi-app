@extends('layouts.app')
@section('title', 'Evaluasi Diri')

@section('content')
@php use App\Enums\IndicatorType; @endphp

@push('styles')
<style>
    .evaluation-table-shell { max-width: 100%; }
    .evaluation-table { width: 100%; table-layout: fixed; }
    .evaluation-table th,
    .evaluation-table td { min-width: 0; overflow-wrap: anywhere; }
    .evaluation-table .form-control,
    .evaluation-table .form-select,
    .evaluation-table .input-group { width: 100%; min-width: 0; }

    @media (max-width: 767.98px) {
        .evaluation-table-shell { overflow: visible; }
        .evaluation-table,
        .evaluation-table tbody { display: block; width: 100%; }
        .evaluation-table thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; }
        .evaluation-table tbody tr { display: grid; grid-template-columns: minmax(0, 1fr); gap: .7rem; padding: 1rem; border-bottom: 1px solid #edf0f5; }
        .evaluation-table tbody tr:last-child { border-bottom: 0; }
        .evaluation-table tbody td { display: grid; grid-template-columns: minmax(6.5rem, 34%) minmax(0, 1fr); gap: .65rem; align-items: start; width: 100% !important; padding: 0; border: 0; overflow-wrap: anywhere; }
        .evaluation-table tbody td::before { content: attr(data-label); color: #737487; font-size: .72rem; font-weight: 700; }
        .evaluation-table tbody td:first-child { display: block; padding-bottom: .7rem; border-bottom: 1px solid #edf0f5; font-weight: 600; }
        .evaluation-table tbody td:first-child::before { content: none; }
        .evaluation-table tbody td:nth-child(2) { white-space: normal !important; }
        .evaluation-table .input-group { flex-wrap: nowrap; }
        .evaluation-table .input-group .form-control { min-width: 0; }
    }
</style>
@endpush

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="mb-0">Evaluasi Diri</h4>
        <div class="text-muted">{{ $unit->nama }} · Siklus {{ $cycle->tahun }}@if ($jabatanLabel) · {{ $jabatanLabel }}@endif</div>
    </div>

    @if (! $window)
        <div class="alert alert-warning">Jadwal pengisian untuk semester ini belum ditetapkan Admin SPMI. Isian belum dapat diubah.</div>
    @else
        <div class="alert alert-light border">
            Pengisian: {{ $window->pengisian_mulai->format('d/m/Y H:i') }} – {{ $window->pengisian_selesai->format('d/m/Y H:i') }}.
            Pemeriksaan: {{ $window->pemeriksaan_mulai->format('d/m/Y H:i') }} – {{ $window->pemeriksaan_selesai->format('d/m/Y H:i') }}.
        </div>
    @endif

    @if ($units->isNotEmpty())
        <form method="GET">
            <select name="unit_id" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach ($units as $u)
                    <option value="{{ $u->id }}" @selected($u->id === $unit->id)>{{ $u->nama }}</option>
                @endforeach
            </select>
        </form>
    @endif
</div>

<ul class="nav nav-pills mb-3 gap-1">
    @foreach (\App\Enums\Semester::cases() as $sem)
        <li class="nav-item">
            <a class="nav-link {{ $semester === $sem->value ? 'active' : '' }}"
               href="{{ route('self-evaluations.index', ['unit_id' => $unit->id, 'semester' => $sem->value]) }}">
                {{ $sem->label() }} <span class="badge bg-light text-dark ms-1">{{ $counts[$sem->value] }}</span>
            </a>
        </li>
    @endforeach
</ul>

@unless ($editable)
    <div class="alert alert-info">Periode pengisian normal sedang tidak dibuka. Evaluasi yang telah mendapat persetujuan pembukaan dari admin masih dapat diperbaiki.</div>
@endunless

<form method="POST" action="{{ route('self-evaluations.update', ['unit_id' => $unit->id]) }}">
    @csrf @method('PUT')
    <input type="hidden" name="semester" value="{{ $semester }}">

    @forelse ($groups as $standardId => $indicators)
        @php $standard = $standards[$standardId]; @endphp
        <div class="card mb-3">
            <div class="card-header">
                <strong>{{ $standard->nomor ? $standard->nomor.'.' : $standard->kode }}</strong> {{ $standard->nama }}
                @if ($standard->kelompok) <span class="badge bg-light text-muted border ms-2">{{ $standard->kelompok->label() }}</span> @endif
            </div>
            <div class="table-responsive evaluation-table-shell">
                <table class="table align-middle mb-0 evaluation-table">
                    <thead>
                    <tr>
                        <th style="width:20%">Indikator</th>
                        <th style="width:13%">Baseline → Target</th>
                        <th style="width:12%">Capaian</th>
                        <th style="width:10%">Skor</th>
                        <th style="width:28%">Uraian</th>
                        <th style="width:10%">Bukti</th>
                        <th style="width:7%">Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($indicators as $ind)
                        @php
                            $e = $evals->get($ind->id);
                            $rowEditable = $e
                                ? ($evaluationEditable[$ind->id] ?? false)
                                : $editable;
                            $target = $ind->targetFor($cycle->tahun);
                            $auto = $ind->tipe === IndicatorType::Ada || ($ind->tipe !== IndicatorType::Teks && $target?->nilai);
                            $statusRev = $ind->statement?->status;
                            $cap = old("items.$ind->id.capaian", $e?->capaian);
                            $answered = $cap !== null && $cap !== '';
                        @endphp
                        <tr>
                            <td data-label="Indikator">
                                <span class="text-muted">{{ $ind->kode }}</span> {{ $ind->nama }}
                                @if ($ind->statement)
                                    <div class="small text-muted mt-1">
                                        @if ($ind->statement->pic) PIC: {{ $ind->statement->pic }} · @endif
                                        {{ $ind->statement->periode_evaluasi?->label() }}
                                    </div>
                                    @if ($statusRev && $statusRev->value !== 'aktif')
                                        <span class="badge bg-warning text-dark">{{ $statusRev->label() }}</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-nowrap" data-label="Baseline / Target">
                                <span class="text-muted">{{ $ind->baseline()?->label($ind->tipe) ?? '—' }}</span>
                                <i class="bi bi-arrow-right mx-1"></i>
                                <strong>{{ $ind->targetLabel($cycle->tahun) }}</strong>
                            </td>
                            <td data-label="Capaian">
                                @if ($ind->tipe === IndicatorType::Ada)
                                    <select class="form-select form-select-sm" name="items[{{ $ind->id }}][capaian]" @disabled(! $rowEditable)>
                                        <option value="">—</option>
                                        <option value="1" @selected($answered && (float) $cap >= 1)>Ada</option>
                                        <option value="0" @selected($answered && (float) $cap == 0.0)>Tidak ada</option>
                                    </select>
                                @elseif ($ind->tipe === IndicatorType::Teks)
                                    <span class="small text-muted">kualitatif — isi skor</span>
                                @else
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="any" min="0" class="form-control"
                                               name="items[{{ $ind->id }}][capaian]" value="{{ old("items.$ind->id.capaian", $e?->capaian) }}" @disabled(! $rowEditable)>
                                        @if ($ind->tipe->unit()) <span class="input-group-text">{{ $ind->tipe->unit() }}</span> @endif
                                    </div>
                                @endif
                            </td>
                            <td data-label="Skor">
                                @if ($auto)
                                    <span class="fw-semibold">{{ $e?->skor ?? '—' }}</span>
                                    <div class="small text-muted">otomatis</div>
                                @else
                                    <input type="number" step="any" min="0" max="100" class="form-control form-control-sm"
                                           name="items[{{ $ind->id }}][skor]" value="{{ old("items.$ind->id.skor", $e?->skor) }}" @disabled(! $rowEditable)>
                                    @unless ($ind->tipe === IndicatorType::Teks)
                                        <div class="small text-muted">target {{ $cycle->tahun }} belum diisi</div>
                                    @endunless
                                @endif
                            </td>
                            <td data-label="Uraian">
                                <textarea rows="4" class="form-control form-control-sm" name="items[{{ $ind->id }}][uraian]" placeholder="Jelaskan hasil pencapaian dan bukti pendukungnya..." @disabled(! $rowEditable)>{{ old("items.$ind->id.uraian", $e?->uraian) }}</textarea>
                            </td>
                            <td class="text-nowrap" data-label="Bukti">
                                @if ($e)
                                    <a href="{{ route('self-evaluations.show', $e->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat dan upload bukti">
                                        <i class="bi bi-file-earmark-arrow-up"></i> {{ $e->evidences_count ?? $e->evidences()->count() }} bukti
                                    </a>
                                @else
                                    <span class="badge bg-light text-muted">Simpan terlebih dahulu</span>
                                @endif
                            </td>
                            <td data-label="Status">
                                @if ($e)
                                    <span class="badge bg-{{ $e->status->value === 'draft' ? 'secondary' : ($e->status->value === 'verified' ? 'success' : 'primary') }}">{{ $e->status->label() }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="alert alert-warning">Tidak ada indikator yang ditugaskan ke unit ini untuk {{ \App\Enums\Semester::from($semester)->label() }}.
            @if (auth()->user()->hasRole('admin_spmi')) Atur penugasan di menu Pernyataan Standar. @else Hubungi Admin SPMI bila ini tidak sesuai. @endif</div>
    @endforelse

    @if ($groups->flatten()->contains(fn ($indicator) => $evals->has($indicator->id)
        ? ($evaluationEditable[$indicator->id] ?? false)
        : $editable))
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary">Simpan Draft</button>
            <button name="submit" value="1" class="btn btn-primary" onclick="return confirm('Simpan permanen dan kunci isian semester ini?')">Simpan Permanen</button>
        </div>
    @endif
</form>
@endsection
