<?php

namespace App\Models;

use App\Enums\EvidenceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evidence extends Model
{
    protected $table = 'evidences';

    protected $fillable = [
        'self_evaluation_id', 'activity_id', 'judul', 'file_path', 'url',
        'uploaded_by', 'status', 'reviewed_by', 'reviewed_at', 'superseded_by_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => EvidenceStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function selfEvaluation(): BelongsTo
    {
        return $this->belongsTo(SelfEvaluation::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(EvidenceReview::class)->with('reviewer')->latest();
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(Evidence::class, 'superseded_by_id');
    }
}
