<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'jenis', 'nomor', 'judul', 'versi', 'status',
        'file_path', 'url', 'tanggal_berlaku', 'unit_id', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'jenis' => DocumentType::class,
            'status' => DocumentStatus::class,
            'tanggal_berlaku' => 'date',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
