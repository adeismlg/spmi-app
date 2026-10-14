# Dokumentasi: Diferensiasi Audit Lab & Prodi

## Ikhtisar Perubahan

Sistem SPMI sekarang mendukung diferensiasi yang lebih jelas antara audit untuk **unit akademik (Program Studi)** dan **laboratorium** melalui:

1. **Enum UnitType yang diperluas** dengan tipe `Laboratorium`
2. **Dashboard Laporan Audit** yang menampilkan status audit terpisah per tipe unit
3. **Service layer** untuk reporting audit yang granular

---

## File yang Diubah/Ditambah

### 1. **app/Enums/UnitType.php** (DIUBAH)
Penambahan case dan method helper:

```php
case Laboratorium = 'laboratorium';

public function isAcademic(): bool {
    return in_array($this, [self::Prodi, self::Jurusan, self::Fakultas, self::Institusi]);
}

public function isLab(): bool {
    return $this === self::Laboratorium;
}
```

**Kegunaan:**
- Diferensiasi tipe unit yang lebih jelas
- Helper methods untuk filtering data berdasarkan tipe unit

---

### 2. **app/Services/AuditReportingService.php** (BARU)
Service untuk mengompilasi data audit berdasarkan tipe unit.

**Fitur utama:**
- `auditSummaryByType($cycle, $unitId)`: Ambil ringkasan audit terpisah untuk academic vs lab
- `getAuditsByUnitType()`: Query audit dengan aggregation findings, RTL, dan compliance metrics
- `calculateSummary()`: Ringkasan keseluruhan dengan persentase penyelesaian RTL

**Metrics yang dikompilasi:**
- Status audit (rencana, berjalan, selesai, dibatalkan)
- Kepatuhan checklist (%)
- Total temuan per audit
- Tindak Lanjut (RTL) dengan status selesai/terverifikasi
- Persentase RTL terselesaikan

---

### 3. **app/Http/Controllers/AuditReportingController.php** (BARU)
Controller untuk menampilkan laporan audit.

**Method:**
- `index()`: Tampilkan laporan audit dengan filter cycle dan unit

**Fitur:**
- Akses: Admin, Pimpinan, Auditor
- Filter: Siklus dan Unit (Prodi/Lab)
- Output: Dua tabel terpisah untuk academic units dan laboratories

---

### 4. **resources/views/audits/reporting.blade.php** (BARU)
Dashboard Laporan Audit dengan UI yang responsif.

**Komponen:**
- **Summary Cards**: Total audit, kepatuhan rata-rata, temuan, RTL selesai
- **Tabel Unit Akademik**: Status, compliance, findings, RTL untuk setiap prodi
- **Tabel Laboratorium**: Status, compliance, findings, RTL untuk setiap lab
- **Progress bars**: Visualisasi kepatuhan dan penyelesaian RTL per unit
- **Status badges**: Visual indicator status audit dan RTL

**Fitur:**
- Filter interaktif (Cycle & Unit)
- Responsive design (mobile-friendly)
- Color-coded compliance indicators (green ≥80%, yellow ≥60%, red <60%)

---

### 5. **routes/web.php** (DIUBAH)
Penambahan:
- Import `AuditReportingController`
- Route: `GET /audit-reporting` → `audits.reporting`

**Akses:**
- Semua user terauth (data akan dibatasi di controller berdasarkan role dan unit)

---

### 6. **config/spmi.php** (DIUBAH)
Update pemetaan PIC untuk laboratorium:

```php
['pattern' => '/kepala laboratorium|kepala lab\b|ka\.?\s*lab/iu', 
 'jabatan' => 'ketua_unit', 
 'unit' => 'Laboratorium', 
 'unit_type' => 'laboratorium',  // Baru
 'pasti' => false]
```

---

### 7. **resources/views/layouts/partials/menu.blade.php** (DIUBAH)
Penambahan menu item:

```blade
<li class="nav-item">
    <a class="nav-link {{ $on('audits.reporting') }}" href="{{ route('audits.reporting') }}">
        <i class="bi bi-graph-up"></i> Laporan Audit (Lab & Prodi)
    </a>
</li>
```

**Akses:** Admin SPMI, Pimpinan, Auditor

---

## Cara Penggunaan

### 1. **Buat Unit Laboratorium**
Sebagai Admin SPMI:
1. Navigasi ke **Pengaturan → Unit / Prodi**
2. Klik **Tambah Unit**
3. Pilih **Tipe: Laboratorium**
4. Isi kode, nama, dan parent unit (optional)

Contoh:
- Kode: `LAB-MESIN`
- Nama: `Lab Mesin Produksi`
- Tipe: `Laboratorium`
- Parent: `Prodi D3 Teknik Mesin` (optional)

### 2. **Buat Audit untuk Lab**
Sebagai Admin SPMI:
1. Navigasi ke **Evaluasi → Audit Mutu Internal**
2. Klik **Tambah Audit**
3. Pilih **Unit** → Laboratorium yang sudah dibuat
4. Isi informasi audit lainnya
5. **Simpan**

### 3. **Lihat Laporan Audit**
Sebagai Admin/Pimpinan/Auditor:
1. Navigasi ke **Evaluasi → Laporan Audit (Lab & Prodi)**
2. Pilih **Siklus** untuk melihat data
3. (Optional) Pilih **Unit** untuk filter spesifik
4. Lihat dua tabel terpisah:
   - **Unit Akademik (Program Studi)**
   - **Laboratorium**

**Data yang ditampilkan:**
- Status audit (badge)
- Kepatuhan (dengan progress bar)
- Temuan & RTL (dengan persentase selesai)
- Aksi: Tombol lihat detail audit

---

## Query Database yang Digunakan

### AuditReportingService menggunakan:
```sql
SELECT 
  a.id, a.unit_id, u.nama, u.kode, a.status, a.semester,
  COUNT(DISTINCT ac.id) as total_checklist,
  COUNT(DISTINCT CASE WHEN ac.skor_audit >= 80 THEN ac.id END) as passed_checklist,
  COUNT(DISTINCT f.id) as total_findings,
  COUNT(DISTINCT ca.id) as total_rtl,
  COUNT(DISTINCT CASE WHEN ca.status IN ('selesai', 'terverifikasi') THEN ca.id END) as rtl_selesai
FROM audits a
JOIN units u ON u.id = a.unit_id
LEFT JOIN audit_checklists ac ON ac.audit_id = a.id
LEFT JOIN findings f ON f.audit_id = a.id
LEFT JOIN corrective_actions ca ON ca.finding_id = f.id
WHERE a.cycle_id = ?
  AND u.tipe IN ('prodi', 'laboratorium')
GROUP BY a.id, u.tipe
ORDER BY u.nama
```

---

## Testing Checklist

- [x] UnitType enum mencakup 'laboratorium'
- [x] AuditReportingService return data yang tepat
- [x] AuditReportingController filter data berdasarkan role
- [x] View render dengan benar
- [x] Route terdaftar
- [x] Menu item visible untuk authorized roles
- [x] Pint formatting pass
- [x] No PHP syntax errors

---

## Catatan Teknis

### Filtering & Security
- Semua user authenticated dapat akses laporan, namun:
  - **Admin SPMI**: Bisa lihat semua unit
  - **Pimpinan**: Bisa lihat semua unit
  - **Auditor**: Bisa lihat semua unit
  - **Auditee**: Hanya lihat unit mereka sendiri

### Performance
- AuditReportingService menggunakan query aggregation untuk efficiency
- Join ke 4 tabel (audits, units, audit_checklists, findings) dengan GROUP BY
- Rekomendasi: Tambah index di `units.tipe`, `audits.cycle_id`, `audits.unit_id` jika performa bermasalah

### Extensibility
- `AuditReportingService::getEmptySummary()` bisa diperluas untuk metrik tambahan
- View mudah dimodifikasi untuk menambah kolom/visualisasi
- Service bisa di-refactor untuk mendukung multi-level filtering di masa depan

---

## Integrasi dengan Sistem Existing

✅ **Kompatibel dengan:**
- Audit workflow existing (tidak ada breaking changes)
- Role-based access control (Laravel Spatie Permissions)
- Database schema (tidak perlu migration)
- Dashboard service (terpisah, tidak konflik)

🔄 **Dapat dikembangkan:**
- Dashboard widget yang menampilkan ringkasan lab
- Export laporan audit (PDF/Excel) dengan breakdown lab
- Notifikasi/reminder RTL per tipe unit
- Perbandingan trend compliance lab vs prodi per tahun

---

## Troubleshooting

**Q: Lab tidak muncul di dropdown filter?**
- A: Pastikan unit sudah dibuat dengan tipe 'laboratorium'

**Q: "Laporan Audit" tidak muncul di menu?**
- A: Pastikan user memiliki role: admin_spmi, pimpinan, atau auditor

**Q: Query lambat saat membuka laporan?**
- A: Tambah index:
```sql
CREATE INDEX idx_audits_cycle_unit ON audits(cycle_id, unit_id);
CREATE INDEX idx_units_tipe ON units(tipe);
CREATE INDEX idx_audit_checklists_audit ON audit_checklists(audit_id);
CREATE INDEX idx_findings_audit ON findings(audit_id);
```

