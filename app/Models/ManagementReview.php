<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ManagementReview extends Model
{
    protected $fillable = [
        'cycle_id', 'judul', 'tanggal', 'notulen', 'keputusan', 'rekomendasi', 'file_path',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function findings(): BelongsToMany
    {
        return $this->belongsToMany(Finding::class, 'finding_management_review');
    }
}
