<?php

namespace App\Enums;

enum EvidenceDocumentType: string
{
    case LaporanAkhir = 'laporan_akhir';
    case StrukturOrganisasi = 'struktur_organisasi';
    case ProkerKerja = 'proker_kerja';
    case DataStatistik = 'data_statistik';
    case DokumenPendukung = 'dokumen_pendukung';
    case PhotoEvidence = 'photo_evidence';
    case VideoEvidence = 'video_evidence';
    case CatatanBukti = 'catatan_bukti';
    case SuratPernyataan = 'surat_pernyataan';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::LaporanAkhir => 'Laporan Akhir',
            self::StrukturOrganisasi => 'Struktur Organisasi',
            self::ProkerKerja => 'Program Kerja',
            self::DataStatistik => 'Data Statistik',
            self::DokumenPendukung => 'Dokumen Pendukung',
            self::PhotoEvidence => 'Bukti Foto',
            self::VideoEvidence => 'Bukti Video',
            self::CatatanBukti => 'Catatan/Bukti Kegiatan',
            self::SuratPernyataan => 'Surat Pernyataan',
            self::Lainnya => 'Lainnya',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::LaporanAkhir => 'primary',
            self::StrukturOrganisasi => 'info',
            self::ProkerKerja => 'secondary',
            self::DataStatistik => 'success',
            self::DokumenPendukung => 'warning',
            self::PhotoEvidence => 'danger',
            self::VideoEvidence => 'danger',
            self::CatatanBukti => 'info',
            self::SuratPernyataan => 'primary',
            self::Lainnya => 'light',
        };
    }
}
