<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Terkunci — menunggu pemeriksaan',
            self::Verified => 'Seluruh bukti terverifikasi',
        };
    }
}
