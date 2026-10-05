<?php

namespace App\Models;

use App\Enums\Conformity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AuditChecklist extends Model
{
    protected $fillable = [
        'audit_id', 'indicator_id', 'self_evaluation_id',
        'kesesuaian', 'skor_audit', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'kesesuaian' => Conformity::class,
            'skor_audit' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // Butir yang tidak "sesuai" otomatis menjadi temuan; kembali "sesuai" -> temuan dihapus.
        static::saved(function (AuditChecklist $row) {
            if ($row->kesesuaian?->isFinding()) {
                Finding::updateOrCreate(
                    ['audit_checklist_id' => $row->id],
                    [
                        'audit_id' => $row->audit_id,
                        'kategori' => $row->kesesuaian,
                        'uraian' => $row->catatan ?? '-',
                    ],
                );
            } else {
                Finding::where('audit_checklist_id', $row->id)->delete();
            }
        });
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function selfEvaluation(): BelongsTo
    {
        return $this->belongsTo(SelfEvaluation::class);
    }

    public function finding(): HasOne
    {
        return $this->hasOne(Finding::class);
    }
}
