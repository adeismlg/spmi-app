<?php

namespace App\Enums;

enum EvidenceStatus: string
{
    case Pending = 'pending_review';
    case Verified = 'verified';
    case Revision = 'revision_requested';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu pemeriksaan',
            self::Verified => 'Terverifikasi',
            self::Revision => 'Perlu perbaikan',
            self::Superseded => 'Diganti setelah perbaikan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'secondary',
            self::Verified => 'success',
            self::Revision => 'warning',
            self::Superseded => 'light text-dark',
        };
    }
}
