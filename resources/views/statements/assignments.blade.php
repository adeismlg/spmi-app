@extends('layouts.app')
@section('title', 'Penugasan Pernyataan')

@section('content')
<a href="{{ route('statements.index') }}" class="btn btn-sm btn-link px-0 mb-2">&larr; Pernyataan Standar</a>

<div class="card mb-3">
    <div class="card-body">
        <div class="text-muted small">Standar {{ $statement->standard->nomor }}. {{ $statement->standard->nama }}</div>
        <h5 class="mb-2">{{ $statement->nomor ?? '—' }}</h5>
        <p class="mb-2">{{ $statement->pernyataan }}</p>
        <div class="small">
            <span class="text-muted">PIC:</span> <strong>{{ $statement->picLabel() }}</strong>
            @if ($statement->jenjang) · <span class="text-muted">Jenjang:</span> <strong>{{ collect($statement->jenjang)->map(fn ($j) => \App\Enums\Jenjang::tryFrom($j)?->label())->implode(', ') }}</strong> @endif
            @if ($statement->pic_unit) · <span class="text-muted">Unit:</span> <strong>{{ implode(', ', $statement->pic_unit) }}</strong> @endif
            · <span class="text-muted">Periode:</span> <strong>{{ $statement->periode_evaluasi?->label() ?? '—' }}</strong>
        </div>
    </div>
</div>

<div class="alert alert-{{ $manual ? 'warning' : 'info' }} d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        @if ($manual)
            Penugasan ini diatur <strong>manual</strong> dan tidak ditimpa perhitungan otomatis.
        @else
            Penugasan dihitung <strong>otomatis</strong> dari PIC{{ $statement->jenjang ? ' dan jenjang' : '' }}. Mengubah pilihan di bawah menjadikannya manual.
        @endif
    </div>
    @if ($manual)
        <form method="POST" action="{{ route('statements.assignments.reset', $statement->id) }}" onsubmit="return confirm('Kembalikan ke penugasan otomatis?')">
            @csrf
            <button class="btn btn-sm btn-outline-secondary">Kembalikan ke otomatis</button>
        </form>
    @endif
</div>

<form method="POST" action="{{ route('statements.assignments.update', $statement->id) }}">
    @csrf @method('PUT')

    @foreach ($types as $type => $label)
        @continue(! isset($groups[$type]))
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>{{ $label }}</strong>
                <div class="form-check mb-0">
                    <input class="form-check-input check-all" type="checkbox" id="all-{{ $type }}" data-group="{{ $type }}">
                    <label class="form-check-label small" for="all-{{ $type }}">Pilih semua</label>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach ($groups[$type] as $u)
                        <div class="col-md-6 col-xl-4">
                            <div class="form-check">
                                <input class="form-check-input unit-check" data-group="{{ $type }}" type="checkbox"
                                       name="units[]" value="{{ $u->id }}" id="u{{ $u->id }}" @checked(in_array($u->id, $selected))>
                                <label class="form-check-label" for="u{{ $u->id }}">
                                    {{ $u->nama }}
                                    @if ($u->jenjang) <span class="badge bg-light text-muted border">{{ $u->jenjang->label() }}</span> @endif
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    <div class="d-flex gap-2">
        <button class="btn btn-primary">Simpan Penugasan</button>
        <a href="{{ route('statements.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.check-all').forEach(function (box) {
        const items = document.querySelectorAll('.unit-check[data-group="' + box.dataset.group + '"]');
        const sync = () => { box.checked = items.length > 0 && Array.from(items).every(i => i.checked); };
        box.addEventListener('change', () => items.forEach(i => i.checked = box.checked));
        items.forEach(i => i.addEventListener('change', sync));
        sync();
    });
</script>
@endpush
