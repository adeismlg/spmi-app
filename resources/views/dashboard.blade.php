@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $stats = $data['stats'];
    $radar = $data['radar'];
    $trend = $data['trend'];
@endphp

@if ($cycle)
    <section class="dashboard-banner mb-3">
        <div class="dashboard-welcome">
            <span class="dashboard-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
            <div>
                <span class="dashboard-date">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</span>
                <h2>Hai, {{ auth()->user()->name }}!</h2>
                <span class="dashboard-role">{{ auth()->user()->jabatan?->label() ?? auth()->user()->getRoleNames()->first() }}</span>
                <p>Ringkasan mutu · Siklus PPEPP {{ $cycle->tahun }}</p>
            </div>
        </div>
        <div class="dashboard-stage" role="status" aria-label="Tahap PPEPP saat ini">
            <span class="dashboard-stage-label"><i class="bi bi-exclamation-circle me-1"></i>Perhatian · Tahap PPEPP</span>
            <strong>{{ $cycle->tahap_aktif->label() }}</strong>
            <small>Siklus {{ $cycle->nama }}</small>
        </div>
    </section>
@endif

<form method="GET" class="dashboard-filters d-flex flex-wrap gap-2 mb-4 align-items-end">
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
                <div class="card dashboard-stat h-100"><div class="card-body">
                    <div>
                        <div class="dashboard-stat-label">{{ $label }}</div>
                        <div class="dashboard-stat-value">{{ $value }}</div>
                    </div>
                    <span class="dashboard-stat-icon {{ $color }}"><i class="bi {{ $icon }}"></i></span>
                </div></div>
            </div>
        @endforeach
    </div>

    @if ($stats['actions_overdue'] > 0)
        <div class="dashboard-notice" role="alert">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            <div>
                <strong>Perhatian: {{ $stats['actions_overdue'] }} RTL terlambat</strong>
                <p>Segera tinjau tindak lanjut yang melewati batas waktu.</p>
            </div>
            <a href="{{ route('corrective-actions.index') }}">Lihat tindak lanjut <i class="bi bi-arrow-right"></i></a>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header fw-semibold">Capaian per Standar — Target vs Evaluasi Diri vs Audit</div>
                <div class="card-body dashboard-chart-body"><div style="max-height:420px"><canvas id="radarChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header fw-semibold">Tren Antar Tahun (rata-rata skor)</div>
                <div class="card-body dashboard-chart-body"><canvas id="trendChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header fw-semibold">Temuan per Kategori</div>
                <ul class="list-group list-group-flush dashboard-list">
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
                <ul class="list-group list-group-flush dashboard-list">
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
                { label: 'Evaluasi Diri', data: radar.self, borderColor: '#5b3fc6', backgroundColor: 'rgba(91,63,198,.16)', fill: true },
                { label: 'Audit', data: radar.audit, borderColor: '#18a88a', backgroundColor: 'rgba(24,168,138,.12)', fill: true },
            ],
        },
        options: { scales: { r: { min: 0, max: 100, ticks: { stepSize: 20 } } } },
    });

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trend.labels,
            datasets: [
                { label: 'Evaluasi Diri', data: trend.self, borderColor: '#5b3fc6', backgroundColor: 'rgba(91,63,198,.1)', tension: .3 },
                { label: 'Audit', data: trend.audit, borderColor: '#18a88a', backgroundColor: 'rgba(24,168,138,.1)', tension: .3 },
            ],
        },
        options: { scales: { y: { min: 0, max: 100 } } },
    });
</script>
@endif
@endpush
