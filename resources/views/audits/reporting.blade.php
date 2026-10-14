@extends('layouts.app')
@section('title', 'Laporan Audit - Unit Akademik & Laboratorium')

@section('content')
@php
    $summary = $summary;
@endphp

<div class="container-fluid">
    <!-- Header -->
    <section class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1">
                    <i class="bi bi-file-earmark-text me-2"></i>Laporan Audit
                </h1>
                <p class="text-muted mb-0">Perbandingan status audit antara unit akademik dan laboratorium</p>
            </div>
        </div>

        <!-- Filter -->
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Siklus</label>
                <select name="cycle_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Pilih Siklus --</option>
                    @foreach ($cycles as $c)
                        <option value="{{ $c->id }}" @selected($cycle && $cycle->id === $c->id)>
                            {{ $c->nama }} (Tahun {{ $c->tahun }})
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($units->isNotEmpty())
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Unit (Prodi / Lab)</label>
                    <select name="unit_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Semua Unit --</option>
                        @foreach ($units as $u)
                            <option value="{{ $u->id }}" @selected($unitId === $u->id)>
                                [{{ $u->tipe }}] {{ $u->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </form>
    </section>

    @if (!$cycle)
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Silakan pilih siklus untuk menampilkan laporan audit.
        </div>
    @else
        <!-- Summary Cards -->
        <section class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Total Audit</p>
                                <h4 class="mb-0">{{ $summary['total_audits'] }}</h4>
                            </div>
                            <i class="bi bi-file-earmark-check text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                        <small class="text-success">{{ $summary['completed_audits'] }} selesai · {{ $summary['completion_pct'] }}%</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Kepatuhan Rata-rata</p>
                                <h4 class="mb-0">{{ $summary['avg_compliance'] }}%</h4>
                            </div>
                            <i class="bi bi-percent text-success" style="font-size: 1.5rem;"></i>
                        </div>
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                style="width: {{ min($summary['avg_compliance'], 100) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Total Temuan</p>
                                <h4 class="mb-0">{{ $summary['total_findings'] }}</h4>
                            </div>
                            <i class="bi bi-exclamation-circle text-warning" style="font-size: 1.5rem;"></i>
                        </div>
                        <small class="text-muted">RTL: {{ $summary['total_rtl'] }}</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">RTL Terselesaikan</p>
                                <h4 class="mb-0">{{ $summary['rtl_pct_resolved'] }}%</h4>
                            </div>
                            <i class="bi bi-check-circle text-info" style="font-size: 1.5rem;"></i>
                        </div>
                        <small class="text-muted">{{ $summary['rtl_selesai'] + $summary['rtl_terverifikasi'] }} dari {{ $summary['total_rtl'] }}</small>
                    </div>
                </div>
            </div>
        </section>

        <!-- Unit Akademik (Prodi) -->
        <section class="mb-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-mortarboard me-2"></i>Unit Akademik (Program Studi)
                        <span class="badge bg-secondary">{{ count($audits) }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if ($audits->isEmpty())
                        <div class="alert alert-info m-3 mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Tidak ada audit untuk unit akademik pada siklus ini.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Unit</th>
                                        <th>Status</th>
                                        <th>Semester</th>
                                        <th class="text-center">Kepatuhan</th>
                                        <th class="text-center">Temuan</th>
                                        <th class="text-center">RTL</th>
                                        <th class="text-end pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($audits as $audit)
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-semibold">{{ $audit['unit_nama'] }}</div>
                                                <small class="text-muted">{{ $audit['unit_kode'] }}</small>
                                            </td>
                                            <td>
                                                @php
                                                    $statusColors = [
                                                        'rencana' => 'warning',
                                                        'berjalan' => 'info',
                                                        'selesai' => 'success',
                                                        'dibatalkan' => 'danger',
                                                    ];
                                                @endphp
                                                <span class="badge bg-{{ $statusColors[$audit['status']] ?? 'secondary' }}">
                                                    {{ ucfirst($audit['status']) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if ($audit['semester'])
                                                    <small class="badge bg-light text-dark">Semester {{ $audit['semester'] }}</small>
                                                @else
                                                    <small class="text-muted">-</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <small class="fw-semibold">{{ $audit['compliance_pct'] }}%</small>
                                                    <div class="progress" style="width: 80px; height: 4px;">
                                                        <div class="progress-bar" role="progressbar" 
                                                            style="width: {{ min($audit['compliance_pct'], 100) }}%; background-color: {{ $audit['compliance_pct'] >= 80 ? '#28a745' : ($audit['compliance_pct'] >= 60 ? '#ffc107' : '#dc3545') }};"
                                                        ></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-warning">{{ $audit['total_findings'] }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ $audit['rtl_pct_resolved'] }}%</span>
                                                <br>
                                                <small class="text-muted">{{ $audit['rtl_selesai'] + $audit['rtl_terverifikasi'] }}/{{ $audit['total_rtl'] }}</small>
                                            </td>
                                            <td class="text-end pe-3">
                                                <a href="{{ route('audits.show', $audit['id']) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <!-- Laboratorium -->
        <section class="mb-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-flask me-2"></i>Laboratorium
                        <span class="badge bg-secondary">{{ count($labs) }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if ($labs->isEmpty())
                        <div class="alert alert-info m-3 mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Tidak ada audit untuk laboratorium pada siklus ini.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Laboratorium</th>
                                        <th>Status</th>
                                        <th>Semester</th>
                                        <th class="text-center">Kepatuhan</th>
                                        <th class="text-center">Temuan</th>
                                        <th class="text-center">RTL</th>
                                        <th class="text-end pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($labs as $lab)
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-semibold">{{ $lab['unit_nama'] }}</div>
                                                <small class="text-muted">{{ $lab['unit_kode'] }}</small>
                                            </td>
                                            <td>
                                                @php
                                                    $statusColors = [
                                                        'rencana' => 'warning',
                                                        'berjalan' => 'info',
                                                        'selesai' => 'success',
                                                        'dibatalkan' => 'danger',
                                                    ];
                                                @endphp
                                                <span class="badge bg-{{ $statusColors[$lab['status']] ?? 'secondary' }}">
                                                    {{ ucfirst($lab['status']) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if ($lab['semester'])
                                                    <small class="badge bg-light text-dark">Semester {{ $lab['semester'] }}</small>
                                                @else
                                                    <small class="text-muted">-</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <small class="fw-semibold">{{ $lab['compliance_pct'] }}%</small>
                                                    <div class="progress" style="width: 80px; height: 4px;">
                                                        <div class="progress-bar" role="progressbar" 
                                                            style="width: {{ min($lab['compliance_pct'], 100) }}%; background-color: {{ $lab['compliance_pct'] >= 80 ? '#28a745' : ($lab['compliance_pct'] >= 60 ? '#ffc107' : '#dc3545') }};"
                                                        ></div>
                                                    </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-warning">{{ $lab['total_findings'] }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ $lab['rtl_pct_resolved'] }}%</span>
                                                <br>
                                                <small class="text-muted">{{ $lab['rtl_selesai'] + $lab['rtl_terverifikasi'] }}/{{ $lab['total_rtl'] }}</small>
                                            </td>
                                            <td class="text-end pe-3">
                                                <a href="{{ route('audits.show', $lab['id']) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif
</div>

@push('styles')
<style>
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: box-shadow 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
</style>
@endpush
@endsection
