<?php

namespace App\Http\Controllers;

use App\Enums\ReopenRequestStatus;
use App\Enums\SubmissionStatus;
use App\Models\ReopenRequest;
use App\Models\SelfEvaluation;
use App\Services\EvaluationAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReopenRequestController extends Controller
{
    public function index(): View
    {
        return view('reopen_requests.index', [
            'requests' => ReopenRequest::with([
                'selfEvaluation.indicator.standard',
                'selfEvaluation.unit',
                'selfEvaluation.cycle',
                'requester',
                'resolver',
            ])->latest('id')->paginate(20),
        ]);
    }

    public function store(Request $request, SelfEvaluation $selfEvaluation, EvaluationAccess $access): RedirectResponse
    {
        abort_unless($request->user()->hasRole('auditee'), 403);
        abort_unless($access->canEdit($request->user(), $selfEvaluation), 403);
        $data = $request->validate(['alasan' => 'required|string|max:2000']);

        DB::transaction(function () use ($selfEvaluation, $request, $data) {
            $evaluation = SelfEvaluation::whereKey($selfEvaluation->id)->lockForUpdate()->firstOrFail();
            abort_if($evaluation->status === SubmissionStatus::Draft, 422, 'Evaluasi masih berupa draft dan belum perlu dibuka kembali.');
            abort_if($evaluation->reopenRequests()->where('status', ReopenRequestStatus::Pending)->exists(), 422, 'Permintaan pembukaan sudah menunggu persetujuan admin.');

            $evaluation->reopenRequests()->create([
                'requested_by' => $request->user()->id,
                'alasan' => $data['alasan'],
            ]);
        });

        return back()->with('success', 'Permintaan pembukaan pengisian dikirim ke Admin SPMI.');
    }

    public function resolve(Request $request, ReopenRequest $reopenRequest): RedirectResponse
    {
        abort_unless($reopenRequest->status === ReopenRequestStatus::Pending, 422, 'Permintaan ini sudah diproses.');

        $data = $request->validate([
            'keputusan' => 'required|in:approve,reject',
            'catatan_admin' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($data, $reopenRequest, $request) {
            $reopenRequest->update([
                'status' => $data['keputusan'] === 'approve'
                    ? ReopenRequestStatus::Approved
                    : ReopenRequestStatus::Rejected,
                'resolved_by' => $request->user()->id,
                'catatan_admin' => $data['catatan_admin'] ?? null,
                'resolved_at' => now(),
            ]);

            if ($data['keputusan'] === 'approve') {
                $reopenRequest->selfEvaluation->update(['status' => SubmissionStatus::Draft]);
            }
        });

        return back()->with('success', 'Permintaan pembukaan berhasil diproses.');
    }
}
