@extends('layouts.app')
@section('title', 'Kegiatan & Bukti')

@push('styles')
<style>
    .evidence-card {
        border-left: 4px solid #0d6efd;
        transition: box-shadow 0.2s;
    }
    
    .evidence-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }
    
    .evidence-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: center;
        font-size: 0.9rem;
    }
    
    .doc-type-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 0.25rem;
        font-weight: 500;
        font-size: 0.85rem;
    }
    
    .evidence-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
</style>
@endpush

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
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>{{ $loop->iteration }}. {{ $activity->nama }}</strong>
                @if ($activity->deskripsi)<div class="small text-muted mt-1">{{ $activity->deskripsi }}</div>@endif
            </div>
            @if ($editable)
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#evidenceUploadModal" 
                        onclick="setActivityId({{ $activity->id }})">
                    <i class="bi bi-cloud-arrow-up"></i> Upload Bukti
                </button>
            @endif
        </div>
        <div class="list-group list-group-flush">
            @forelse ($activityEvidence as $evidence)
                <div class="list-group-item evidence-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <div class="fw-semibold fs-6">{{ $evidence->judul }}</div>
                            
                            <!-- Metadata Display -->
                            <div class="evidence-meta mt-2">
                                @if ($evidence->document_type)
                                    <span class="doc-type-badge bg-{{ $evidence->document_type->badge() }} text-white">
                                        {{ $evidence->document_type->label() }}
                                    </span>
                                @endif
                                <span class="text-muted">
                                    <i class="bi bi-person-circle"></i> {{ $evidence->uploader?->name }}
                                </span>
                                <span class="text-muted">
                                    <i class="bi bi-calendar"></i> {{ $evidence->created_at->format('d/m/Y H:i') }}
                                </span>
                                <span class="badge bg-{{ $evidence->status->badge() }}">{{ $evidence->status->label() }}</span>
                            </div>
                            
                            <!-- Description -->
                            @if ($evidence->keterangan)
                                <div class="mt-2 p-2 bg-light rounded">
                                    <small class="text-muted"><strong>Keterangan:</strong></small><br>
                                    <small>{{ $evidence->keterangan }}</small>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Action Buttons -->
                        @if ($editable)
                            <form method="POST" action="{{ route('evidences.destroy', $evidence->id) }}" onsubmit="return confirm('Hapus bukti ini?')" class="ms-2">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- File/URL Links -->
                    <div class="evidence-actions mt-2">
                        @if ($evidence->file_path)
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('evidences.download', $evidence->id) }}">
                                <i class="bi bi-download"></i> Unduh berkas
                            </a>
                        @endif
                        @if ($evidence->url)
                            <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ $evidence->url }}">
                                <i class="bi bi-link-45deg"></i> Buka tautan
                            </a>
                        @endif
                    </div>

                    <!-- Review History -->
                    @if ($evidence->reviews->isNotEmpty())
                        <div class="mt-3 pt-3 border-top">
                            <div class="small fw-semibold mb-2">
                                <i class="bi bi-check-circle"></i> Riwayat pemeriksaan
                            </div>
                            @foreach ($evidence->reviews as $review)
                                <div class="small mb-1">
                                    <strong>{{ $review->created_at->format('d/m/Y H:i') }}</strong> — 
                                    <span class="badge bg-{{ $review->status->badge() }}">{{ $review->status->label() }}</span>
                                    <br>
                                    <span class="text-muted">{{ $review->reviewer->name }}</span>
                                    @if ($review->komentar)
                                        <p class="mb-0 mt-1 text-muted">{{ $review->komentar }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Auditor Review Form -->
                    @if ($canReview && $evidence->status !== \App\Enums\EvidenceStatus::Superseded)
                        <form method="POST" action="{{ route('evidences.review', $evidence->id) }}" class="mt-3 pt-3 border-top">
                            @csrf @method('PUT')
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <select name="status" class="form-select form-select-sm" required>
                                        <option value="">— Pilih aksi —</option>
                                        <option value="verified">✓ Validasi bukti</option>
                                        <option value="revision_requested">↻ Minta perbaikan</option>
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <input name="komentar" class="form-control form-control-sm" maxlength="3000" 
                                           placeholder="Komentar (wajib jika meminta perbaikan)">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-sm btn-outline-primary w-100" type="submit">
                                        Simpan
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            @empty
                <div class="list-group-item text-muted text-center py-4">
                    <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.3;"></i>
                    <p class="mb-0 mt-2">Belum ada bukti untuk kegiatan ini</p>
                </div>
            @endforelse
        </div>
    </div>
@endforeach

<!-- Modal Upload Bukti (new) -->
@if ($editable)
    @include('self_evaluations.partials.evidence-upload-form')
@endif

<!-- Bukti lama tanpa kegiatan -->
@if ($eval->evidences->whereNull('activity_id')->isNotEmpty())
    <div class="mt-4">
        <h6>Bukti Lama (tidak terkait kegiatan)</h6>
        @foreach ($eval->evidences->whereNull('activity_id') as $evidence)
            <div class="alert alert-secondary">{{ $evidence->judul }}</div>
        @endforeach
    </div>
@endif

@if ($editable)
    <script>
        let currentActivityId = null;
        
        function setActivityId(activityId) {
            currentActivityId = activityId;
            document.getElementById('evidenceForm').action = 
                '{{ route("activities.evidences.store", [$eval->id, "__ACTIVITY_ID__"]) }}'.replace('__ACTIVITY_ID__', activityId);
        }
    </script>
@endif
@endsection
