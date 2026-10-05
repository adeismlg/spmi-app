<?php

namespace App\Models;

use App\Enums\Conformity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Finding extends Model
{
    protected $fillable = ['audit_id', 'audit_checklist_id', 'kategori', 'uraian', 'rekomendasi'];

    protected function casts(): array
    {
        return ['kategori' => Conformity::class];
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(AuditChecklist::class, 'audit_checklist_id');
    }

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class);
    }

    public function managementReviews(): BelongsToMany
    {
        return $this->belongsToMany(ManagementReview::class, 'finding_management_review');
    }
}
