@extends('layouts.app')
@section('title', 'Laporan')

@section('content')
<div class="card" style="max-width:720px">
    <div class="card-header"><h5 class="mb-0">Ekspor Laporan</h5></div>
    <div class="card-body">
        <form method="GET" action="{{ route('reports.download') }}">
            <div class="mb-3">
                <label class="form-label">Jenis laporan</label>
                <select name="type" id="type" class="form-select" required>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Siklus</label>
                    <select name="cycle_id" class="form-select" required>
                        @foreach ($cycles as $c)
                            <option value="{{ $c->id }}" @selected($c->id === $currentCycleId)>{{ $c->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Unit</label>
                    <select name="unit_id" class="form-select">
                        @unless ($lockUnit && $units->count() === 1)
                            <option value="">Semua unit{{ $lockUnit ? ' yang saya tangani' : '' }}</option>
                        @endunless
                        @foreach ($units as $u)
                            <option value="{{ $u->id }}">{{ $u->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label d-block">Format</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="format" id="fmt-pdf" value="pdf" checked>
                    <label class="form-check-label" for="fmt-pdf"><i class="bi bi-file-earmark-pdf text-danger"></i> PDF</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="format" id="fmt-xlsx" value="xlsx">
                    <label class="form-check-label" for="fmt-xlsx"><i class="bi bi-file-earmark-excel text-success"></i> Excel</label>
                </div>
            </div>

            <button class="btn btn-primary"><i class="bi bi-download"></i> Unduh</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Ringkasan siklus hanya tersedia sebagai PDF.
    const type = document.getElementById('type'), xlsx = document.getElementById('fmt-xlsx'), pdf = document.getElementById('fmt-pdf');
    function sync() {
        const summary = type.value === 'summary';
        xlsx.disabled = summary;
        if (summary) pdf.checked = true;
    }
    type.addEventListener('change', sync); sync();
</script>
@endpush
