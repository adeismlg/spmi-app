@extends('layouts.app')
@section('title', 'Kegiatan & Bukti')

@section('content')
<a href="{{ route('self-evaluations.index', ['unit_id' => $eval->unit_id, 'semester' => $eval->semester]) }}" class="btn btn-sm btn-link px-0 mb-2">&larr; Kembali ke Evaluasi Diri</a>

<div class="card mb-3">
    <div class="card-body">
        <div class="text-muted small">{{ $eval->indicator->standard->kode }} · {{ $eval->unit->nama }} · Siklus {{ $eval->cycle->tahun }} · {{ \App\Enums\Semester::from($eval->semester)->label() }}</div>
        <h5 class="mb-2">{{ $eval->indicator->kode }} — {{ $eval->indicator->nama }}</h5>
        <div class="row g-3">
            <div class="col-auto"><span class="text-muted">Capaian:</span> <strong>{{ $eval->capaian ?? '—' }}</strong></div>
            <div class="col-auto"><span class="text-muted">Skor:</span> <strong>{{ $eval->skor ?? '—' }}</strong></div>
            <div class="col-auto"><span class="text-muted">Status pengajuan:</span> <strong>{{ $eval->status->label() }}</strong></div>
        </div>
        @if ($eval->uraian) <p class="mt-3 mb-0">{{ $eval->uraian }}</p> @endif
    </div>
</div>

@if (! $window)
    <div class="alert alert-warning">Jadwal pengisian dan pemeriksaan semester ini belum ditetapkan oleh Admin SPMI.</div>
@elseif ($canReview && ! now()->betweenIncluded($window->pemeriksaan_mulai, $window->pemeriksaan_selesai) && ! auth()->user()->hasRole('admin_spmi'))
    <div class="alert alert-info">Pemeriksaan dibuka {{ $window->pemeriksaan_mulai->format('d/m/Y H:i') }} sampai {{ $window->pemeriksaan_selesai->format('d/m/Y H:i') }}.</div>
@endif

@if ($canRequestReopen)
    <div class="card mb-3 border-warning">
        <div class="card-header fw-semibold">Minta Pembukaan Kembali</div>
        <div class="card-body">
            <form method="POST" action="{{ route('reopen-requests.store', $eval->id) }}">
                @csrf
                <label class="form-label" for="alasan">Alasan perbaikan</label>
                <textarea id="alasan" name="alasan" class="form-control mb-2" required maxlength="2000"></textarea>
                <button class="btn btn-outline-warning">Kirim Permintaan ke Admin</button>
            </form>
        </div>
    </div>
@endif

@foreach ($eval->indicator->activities as $activity)
    @php $activityEvidence = $eval->evidences->where('activity_id', $activity->id); @endphp
    <div class="card mb-3">
        <div class="card-header">
            <strong>{{ $loop->iteration }}. {{ $activity->nama }}</strong>
            @if ($activity->deskripsi)<div class="small text-muted mt-1">{{ $activity->deskripsi }}</div>@endif
        </div>
        <ul class="list-group list-group-flush">
            @forelse ($activityEvidence as $evidence)
                <li class="list-group-item">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold">{{ $evidence->judul }}</div>
                            <div class="small text-muted">{{ $evidence->uploader?->name }} · {{ $evidence->created_at->format('d/m/Y H:i') }}</div>
                            <span class="badge bg-{{ $evidence->status->badge() }}">{{ $evidence->status->label() }}</span>
                            @if ($evidence->file_path)
                                <a class="btn btn-sm btn-outline-primary ms-1" href="{{ route('evidences.download', $evidence->id) }}">Unduh berkas</a>
                            @endif
                            @if ($evidence->url)
                                <a class="btn btn-sm btn-outline-primary ms-1" target="_blank" rel="noopener" href="{{ $evidence->url }}">Buka tautan</a>
                            @endif
                        </div>
                        @if ($editable)
                            <form method="POST" action="{{ route('evidences.destroy', $evidence->id) }}" onsubmit="return confirm('Hapus bukti ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        @endif
                    </div>

                    @if ($evidence->reviews->isNotEmpty())
                        <div class="mt-2 border-top pt-2">
                            <div class="small fw-semibold">Riwayat pemeriksaan / komentar</div>
                            @foreach ($evidence->reviews as $review)
                                <div class="small text-muted">
                                    {{ $review->created_at->format('d/m/Y H:i') }} — {{ $review->reviewer->name }}:
                                    {{ $review->status->label() }}@if ($review->komentar) — {{ $review->komentar }}@endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($canReview && $evidence->status !== \App\Enums\EvidenceStatus::Superseded)
                        <form method="POST" action="{{ route('evidences.review', $evidence->id) }}" class="row g-2 mt-2">
                            @csrf @method('PUT')
                            <div class="col-md-3">
                                <select name="status" class="form-select form-select-sm" required>
                                    <option value="verified">Validasi bukti</option>
                                    <option value="revision_requested">Minta perbaikan</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <input name="komentar" class="form-control form-control-sm" maxlength="3000" placeholder="Komentar (wajib jika meminta perbaikan)">
                            </div>
                            <div class="col-md-2"><button class="btn btn-sm btn-outline-primary">Simpan Pemeriksaan</button></div>
                        </form>
                    @endif
                </li>
            @empty
                <li class="list-group-item text-muted">Belum ada bukti untuk kegiatan ini.</li>
            @endforelse
        </ul>

        @if ($editable)
            @foreach ($activityEvidence->where('status', \App\Enums\EvidenceStatus::Revision) as $revisionEvidence)
                <form method="POST" action="{{ route('activities.evidences.store', [$eval->id, $activity->id]) }}" enctype="multipart/form-data" class="card card-body bg-warning-subtle mb-2">
                    @csrf
                    <input type="hidden" name="replaces_evidence_id" value="{{ $revisionEvidence->id }}">
                    <div class="fw-semibold mb-2">Kirim perbaikan untuk: {{ $revisionEvidence->judul }}</div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Nama bukti</label>
                            <input name="judul" class="form-control" value="{{ $revisionEvidence->judul }}" required maxlength="255">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Berkas baru (maks. 10 MB)</label>
                            <input type="file" name="berkas" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">atau tautan baru</label>
                            <input type="url" name="url" class="form-control" placeholder="https://">
                        </div>
                        <div class="col-md-1"><button class="btn btn-sm btn-warning">Kirim</button></div>
                    </div>
                </form>
            @endforeach
            <div class="card-body">
                <form method="POST" action="{{ route('activities.evidences.store', [$eval->id, $activity->id]) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label" for="judul_{{ $activity->id }}">Nama / keterangan bukti</label>
                        <input id="judul_{{ $activity->id }}" name="judul" class="form-control" required maxlength="255">
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label">Berkas (maks. 10 MB)</label>
                            <input type="file" name="berkas" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">atau tautan</label>
                            <input type="url" name="url" class="form-control" placeholder="https://">
                        </div>
                        <div class="col-md-2"><button class="btn btn-primary w-100">Tambah Bukti</button></div>
                    </div>
                    @error('berkas') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    @error('url') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </form>
            </div>
        @endif
    </div>
@endforeach

@foreach ($eval->evidences->whereNull('activity_id') as $evidence)
    <div class="alert alert-secondary">Bukti lama tanpa kegiatan: {{ $evidence->judul }}</div>
@endforeach
@endsection
