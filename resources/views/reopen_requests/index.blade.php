@extends('layouts.app')
@section('title', 'Permintaan Pembukaan Isian')

@section('content')
<div class="card">
    <div class="card-header fw-semibold">Permintaan Pembukaan Kembali</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Pengajuan</th>
                <th>Unit / Siklus</th>
                <th>Permintaan</th>
                <th>Status</th>
                <th style="width:30%">Tindakan Admin</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($requests as $reopenRequest)
                <tr>
                    <td>
                        {{ $reopenRequest->selfEvaluation->indicator->standard->kode }} ·
                        {{ $reopenRequest->selfEvaluation->indicator->kode }}<br>
                        <span class="small text-muted">{{ $reopenRequest->requester->name }} · {{ $reopenRequest->created_at->format('d/m/Y H:i') }}</span>
                    </td>
                    <td>{{ $reopenRequest->selfEvaluation->unit->nama }}<br>{{ $reopenRequest->selfEvaluation->cycle->nama }}</td>
                    <td>{{ $reopenRequest->alasan }}</td>
                    <td>{{ $reopenRequest->status->label() }}</td>
                    <td>
                        @if ($reopenRequest->status === \App\Enums\ReopenRequestStatus::Pending)
                            <form method="POST" action="{{ route('reopen-requests.resolve', $reopenRequest->id) }}">
                                @csrf
                                <input name="catatan_admin" class="form-control form-control-sm mb-2" placeholder="Catatan (opsional)">
                                <div class="d-flex gap-2">
                                    <button name="keputusan" value="approve" class="btn btn-sm btn-success">Setujui</button>
                                    <button name="keputusan" value="reject" class="btn btn-sm btn-outline-danger">Tolak</button>
                                </div>
                            </form>
                        @else
                            {{ $reopenRequest->resolver?->name ?? '—' }}
                            @if ($reopenRequest->catatan_admin)<div class="small text-muted">{{ $reopenRequest->catatan_admin }}</div>@endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada permintaan pembukaan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $requests->links() }}</div>
</div>
@endsection
