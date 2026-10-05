<?php

namespace App\Models;

use App\Enums\Jabatan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatementAssignment extends Model
{
    protected $fillable = ['statement_id', 'unit_id', 'jabatan', 'sumber'];

    protected function casts(): array
    {
        return ['jabatan' => Jabatan::class];
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(Statement::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
