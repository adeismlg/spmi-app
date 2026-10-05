<?php

namespace App\Models;

use App\Enums\ReopenRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReopenRequest extends Model
{
    protected $fillable = [
        'self_evaluation_id', 'requested_by', 'alasan', 'status',
        'resolved_by', 'catatan_admin', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReopenRequestStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function selfEvaluation(): BelongsTo
    {
        return $this->belongsTo(SelfEvaluation::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
