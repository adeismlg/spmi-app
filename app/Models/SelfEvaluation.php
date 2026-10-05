<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SelfEvaluation extends Model
{
    protected $fillable = [
        'cycle_id', 'unit_id', 'indicator_id', 'semester',
        'capaian', 'skor', 'uraian', 'status', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'capaian' => 'decimal:2',
            'skor' => 'decimal:2',
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    public function reopenRequests(): HasMany
    {
        return $this->hasMany(ReopenRequest::class);
    }
}
