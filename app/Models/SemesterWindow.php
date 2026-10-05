<?php

namespace App\Models;

use App\Enums\Semester;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemesterWindow extends Model
{
    protected $fillable = [
        'cycle_id', 'semester', 'pengisian_mulai', 'pengisian_selesai',
        'pemeriksaan_mulai', 'pemeriksaan_selesai',
    ];

    protected function casts(): array
    {
        return [
            'semester' => Semester::class,
            'pengisian_mulai' => 'datetime',
            'pengisian_selesai' => 'datetime',
            'pemeriksaan_mulai' => 'datetime',
            'pemeriksaan_selesai' => 'datetime',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }
}
