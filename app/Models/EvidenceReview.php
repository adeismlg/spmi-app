<?php

namespace App\Models;

use App\Enums\EvidenceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceReview extends Model
{
    protected $fillable = ['evidence_id', 'reviewer_id', 'status', 'komentar'];

    protected function casts(): array
    {
        return ['status' => EvidenceStatus::class];
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
