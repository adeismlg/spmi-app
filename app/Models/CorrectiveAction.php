<?php

namespace App\Models;

use App\Enums\ActionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectiveAction extends Model
{
    protected $fillable = [
        'finding_id', 'akar_masalah', 'tindakan', 'pic_user_id', 'target_selesai',
        'status', 'catatan_verifikasi', 'verified_by', 'verified_at', 'last_reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActionStatus::class,
            'target_selesai' => 'date',
            'verified_at' => 'datetime',
            'last_reminded_at' => 'datetime',
        ];
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isOverdue(): bool
    {
        return $this->target_selesai !== null
            && $this->target_selesai->isPast()
            && ! in_array($this->status, [ActionStatus::Selesai, ActionStatus::Terverifikasi], true);
    }
}
