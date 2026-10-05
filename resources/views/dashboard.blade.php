@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $stats = $data['stats'];
    $radar = $data['radar'];
    $trend = $data['trend'];
@endphp

<form method="GET" class="row g-2 mb-4 align-items-end">
    <div class="col-auto">
        <label class="form-label small mb-1">Siklus</label>
        <select name="cycle_id" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach ($cycles as $c)
                <option value="{{ $c->id }}" @selected($cycle && $cycle->id === $c->id)>{{ $c->nama }}</option>
            @endforeach
        </select>
    </div>
    @if ($units->isNotEmpty())
        <div class="col-auto">
            <label class="form-label small mb-1">Unit</label>
            <select name="unit_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua unit</option>
                @foreach ($units as $u)
                    <option value="{{ $u->id }}" @selected($unitId === $u->id)>{{ $u->nama }}</option>
                @endforeach
            </select>
        </div>
    @endif
</form>

@if (! $cycle)
    <div class="alert alert-warning">Belum ada siklus. Buat siklus di menu Pengaturan → Siklus PPEPP.</div>
@else
    <div class="row g-3 mb-4">
        @foreach ([
            ['Unit mengajukan evaluasi diri', $stats['unit_submitted'], 'bi-clipboard2-check', 'primary'],
            ['Audit selesai', $stats['audits_done'].' / '.$stats['audits_total'], 'bi-search', 'info'],
            ['Total temuan', $stats['findings_total'], 'bi-exclamation-triangle', 'warning'],
            ['RTL selesai', $stats['actions_done_pct'].'%', 'bi-check2-circle', 'success'],
            ['RTL terlambat', $stats['actions_overdue'], 'bi-alarm', 'danger'],
        ] as [$label, $value, $icon, $color])
            <div class="col-6 col-lg">
                <div class="card h-100"><div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div><div class="text-muted small">{{ $label }}</div><div class="fs-3 fw-bold">{{ $value }}</div></div>
                        <span class="text-{{ $color }} fs-3"><i class="bi {{ $icon }}"></i></span>
                    </div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header fw-semibold">Capaian per Standar — Target vs Evaluasi Diri vs Audit</div>
                <div class="card-body"><div style="max-height:420px"><canvas id="radarChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header fw-semibold">Tren Antar Tahun (rata-rata skor)</div>
                <div class="card-body"><canvas id="trendChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header fw-semibold">Temuan per Kategori</div>
                <ul class="list-group list-group-flush">
                    @foreach (\App\Enums\Conformity::cases() as $c)
                        @continue(! $c->isFinding())
                        <li class="list-group-item d-flex justify-content-between">
                            <span><span class="badge bg-{{ $c->badge() }} me-2">&nbsp;</span>{{ $c->label() }}</span>
                            <strong>{{ $stats['findings_by_kategori'][$c->value] ?? 0 }}</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header fw-semibold">Status Rencana Tindak Lanjut</div>
                <ul class="list-group list-group-flush">
                    @foreach (\App\Enums\ActionStatus::cases() as $s)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $s->label() }}</span>
                            <strong>{{ $stats['actions_by_status'][$s->value] ?? 0 }}</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
@if ($cycle)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const radar = @json($radar);
    const trend = @json($trend);

    new Chart(document.getElementById('radarChart'), {
        type: 'radar',
        data: {
            labels: radar.labels,
            datasets: [
                { label: 'Target', data: radar.target, borderColor: '#a1acb8', borderDash: [6, 4], fill: false, pointRadius: 0 },
                { label: 'Evaluasi Diri', data: radar.self, borderColor: '#696cff', backgroundColor: 'rgba(105,108,255,.18)', fill: true },
                { label: 'Audit', data: radar.audit, borderColor: '#ff3e1d', backgroundColor: 'rgba(255,62,29,.12)', fill: true },
            ],
        },
        options: { scales: { r: { min: 0, max: 100, ticks: { stepSize: 20 } } } },
    });

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trend.labels,
            datasets: [
                { label: 'Evaluasi Diri', data: trend.self, borderColor: '#696cff', tension: .3 },
                { label: 'Audit', data: trend.audit, borderColor: '#ff3e1d', tension: .3 },
            ],
        },
        options: { scales: { y: { min: 0, max: 100 } } },
    });
</script>
@endif
@endpush
