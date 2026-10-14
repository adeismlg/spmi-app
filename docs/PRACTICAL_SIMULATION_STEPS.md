# 🎬 SIMULASI PRAKTIS: Step-by-Step Workflow

## 📊 STATUS DATA SEKARANG

```
✅ Cycle:              Siklus 2026 (tahun 2026, tahap: Evaluasi)
✅ Semester Windows:   Semester 1 (01/10 - 31/10 pengisian, 01/11 - 30/11 pemeriksaan)
✅ Indicators:         247 total
✅ Self Evaluations:   4 existing (1 verified, 2 submitted, 1 draft)
✅ Units:              ~80 units (jurusan, prodi, unit_kerja, lab dll)
✅ Users:              10+ users (mix of admin, PIC, etc)
```

---

## 🎯 SIMULASI: Login Auditee & Isi Evaluasi

**Tujuan:** Demonstrasi workflow lengkap dari user perspective

**Prerequisite Checks:**
```sql
-- Verify Semester Window exists for Cycle 2026, Semester 1
SELECT * FROM semester_windows 
WHERE cycle_id = (SELECT id FROM cycles WHERE tahun = 2026) 
AND semester = 1;

-- Check current time is within pengisian window
SELECT 
  pengisian_mulai, 
  pengisian_selesai,
  datetime('now') as current_time,
  CASE 
    WHEN datetime('now') BETWEEN pengisian_mulai AND pengisian_selesai THEN 'IN WINDOW ✓'
    ELSE 'OUT OF WINDOW ✗'
  END as status
FROM semester_windows 
WHERE cycle_id = 1 AND semester = 1;
```

---

## 🔑 STEP 1: Admin Setup - Login As Auditee

### Via Browser

**URL:** `https://spmi.ai-tekno.my.id/admin/impersonate`

**Atau via Menu:**
```
Dashboard → Pengaturan → Pengguna
  ↓
Cari user dengan role "auditee" (atau yang perlu test)
  ↓
Klik tombol "Login As" di baris user tsb
  ↓
Session: Anda sekarang jadi user tersebut
  ↓
URL redirect: /self-evaluations atau dashboard
```

**Expected Result:**
```
✅ Session aktif as selected user
✅ Menu bar shows user name
✅ Top-right ada button "Back to Admin" (untuk kembali)
✅ Can access evaluasi/audit sesuai role user
```

---

## 📋 STEP 2: Auditee Lihat Jadwal & List Evaluasi

### URL: `/self-evaluations`

**Expected View:**

```
┌─────────────────────────────────────────────────────────────┐
│ EVALUASI DIRI - SEMESTER GANJIL (1) TAHUN 2026              │
│                                                              │
│ ✅ JADWAL TERBUKA:                                           │
│    Pengisian: 01/10/2026 08:00 – 31/10/2026 17:00           │
│    Pemeriksaan: 01/11/2026 08:00 – 30/11/2026 17:00         │
│                                                              │
│ STATUS: Bisa diisi ✓                                        │
│                                                              │
│ TABLE:                                                       │
│ ┌──────┬──────────────────┬────────┬─────────────┬───────┐  │
│ │ No   │ Komponen/Indikator│ Status │ Ketua Prodi │ Aksi  │  │
│ ├──────┼──────────────────┼────────┼─────────────┼───────┤  │
│ │ 1    │ Ind 1.1          │ Draft  │ (Logged in) │ Edit  │  │
│ │ 2    │ Ind 1.2          │ Draft  │ (Logged in) │ Edit  │  │
│ │ 3    │ Ind 2.1          │ Draft  │ (Logged in) │ Edit  │  │
│ └──────┴──────────────────┴────────┴─────────────┴───────┘  │
│                                                              │
│ Note: Form EDITABLE karena dalam pengisian window           │
└─────────────────────────────────────────────────────────────┘
```

**If Outside Window (After 31/10 or Before 01/10):**

```
❌ JADWAL DITUTUP:
   "Periode pengisian sudah berakhir (31/10/2026 17:00).
    Evaluasi yang telah mendapat persetujuan pembukaan 
    dari admin masih dapat diperbaiki."
   
   Form: READ-ONLY (kecuali approved reopen)
```

---

## ✏️ STEP 3: Auditee Edit Evaluasi

### Click "Edit" untuk indicator

**URL:** `/self-evaluations/{evaluation_id}/edit`

**Form Fields:**

```
┌────────────────────────────────────────────────────────┐
│ FORM EVALUASI DIRI INDIKATOR                           │
│                                                         │
│ 1. INDIKATOR: [Dropdown/Display]                       │
│    e.g., "Persentase lulusan yang bekerja di bidang..." │
│                                                         │
│ 2. CAPAIAN: [Select/Input]                             │
│    Options:                                            │
│    ○ Ada (Tercapai)                                    │
│    ○ Tidak Ada (Tidak Tercapai)                        │
│    ○ Nilai: [0-100%]                                   │
│                                                         │
│ 3. URAIAN/DESKRIPSI: [Textarea]                        │
│    "Jelaskan bagaimana capaian ini diraih..."          │
│    Min: 100 chars, Max: 1000 chars                     │
│                                                         │
│ 4. EVIDENCE/BUKTI:                                      │
│    [Optional] Upload files atau link dokumen           │
│    Tipe: PDF, DOC, XLS, JPG, PNG                       │
│    Size: Max 5MB per file                              │
│                                                         │
│ 5. ACTION BUTTONS:                                      │
│    [Simpan Sementara] [Simpan Permanen] [Batalkan]     │
└────────────────────────────────────────────────────────┘
```

**Behavior:**

| Action | Efek | Status Berubah |
|--------|------|-----------------|
| **Simpan Sementara** | Data disimpan, halaman tetap di form | DRAFT |
| **Simpan Permanen** | Data disimpan, form auto-close, back to list | SUBMITTED |
| **Edit lagi** | (setelah Simpan Sementara) Form editable | DRAFT (tetap) |
| **Batalkan** | Discard changes, back to list | (tidak berubah) |

---

## 📤 STEP 4: Test Draft Save

### Action:
```
1. Buka evaluasi indicator 1
2. Isi form:
   - Capaian: "Ada"
   - Uraian: "Prodi melakukan tracking lulusan melalui survey 
             yang dilakukan setiap semester. Tahun 2025, 
             95% lulusan bekerja dalam bidangnya."
3. Klik "Simpan Sementara"
```

### Expected Result:
```
✅ Form tetap terbuka (tidak auto-close)
✅ Data disimpan ke database
✅ Alert: "Data evaluasi berhasil disimpan"
✅ Status tetap DRAFT
✅ Bisa edit lagi (klik "Edit" di list)
✅ Can navigate ke indicator lain, kembali lagi
```

### Database Verification:
```sql
SELECT id, indicator_id, capaian, uraian, status 
FROM self_evaluations 
WHERE status = 'draft' AND id IN (1,2,3,4);
```

---

## ✅ STEP 5: Test Permanent Submit

### Action:
```
1. (Dari STEP 4, form masih terbuka)
2. Klik "Simpan Permanen"
```

### Expected Result:
```
✅ Form auto-close
✅ Alert: "Data evaluasi berhasil disimpan dan dikunci"
✅ Back to list (/self-evaluations)
✅ Status di list berubah DRAFT → SUBMITTED
✅ Tombol "Edit" berubah status (mungkin hilang atau disable)
✅ Form tidak bisa diubah (read-only)
```

### Database Verification:
```sql
SELECT id, status, submitted_at 
FROM self_evaluations 
WHERE status = 'submitted' 
ORDER BY submitted_at DESC LIMIT 1;
```

---

## 🔄 STEP 6: Test Edit After Submitted (Edge Case)

**Question:** Bisa edit setelah SUBMITTED?

**Answer:** Depends on Cycle Tahap & Window:

| Scenario | Tahap | Window Status | Edit? | Rationale |
|----------|-------|---------------|-------|-----------|
| Normal Submit | Pelaksanaan | Within pengisian | ✓ BISA | Standard workflow |
| Admin Reopen | Pelaksanaan | Within pengisian | ✓ BISA | Manual approval |
| Phase Advanced | Evaluasi | Within pengisian | ✓ BISA | Parallel workflow |
| Window Closed | Evaluasi | After pengisian | ✗ TIDAK | Deadline passed |
| No Window | Any | Not configured | ✗ TIDAK | Missing config |

**Test Case:**
```
1. (Form SUBMITTED from STEP 5)
2. Try to click "Edit" again
3. Check: Form editable? Read-only?
4. Expected: EDITABLE (because tahap = Evaluasi & within window)
```

---

## 🔍 STEP 7: Verify Audit Reporting

**URL:** `/audit-reporting`

**Expected View:**

```
┌──────────────────────────────────────────────────────────┐
│ LAPORAN AUDIT MUTU INTERNAL - SIKLUS 2026               │
│                                                          │
│ TAB 1: PER PRODI                                         │
│ ┌────────────────────────────────────────────────────┐  │
│ │ PRODI                              │ TOTAL │ STATUS  │  │
│ ├─────────────────────────────────────┼───────┼────────┤  │
│ │ D3 Administrasi Bisnis              │   5   │ 3/5 ✓  │  │
│ │ D3 Akuntansi                        │   5   │ 2/5    │  │
│ │ D4 Teknik Informatika               │   8   │ 7/8 ✓  │  │
│ ...                                                       │
│                                                          │
│ TAB 2: PER LABORATORIUM                                  │
│ ┌────────────────────────────────────────────────────┐  │
│ │ LAB                                │ TOTAL │ STATUS  │  │
│ ├─────────────────────────────────────┼───────┼────────┤  │
│ │ Lab Simulasi Perusahaan             │   3   │ 2/3    │  │
│ │ Lab Bahasa                          │   2   │ 1/2    │  │
│ ...                                                       │
│                                                          │
│ CHART: Grafik distribusi status                         │
│ [████ Submitted] [███ Draft] [██ Pending]               │
└──────────────────────────────────────────────────────────┘
```

**If Error 500:** Check previous fix (`AuditReportingService`)
```php
// Should use arrow notation, not array access
$result->forEach(fn($item) => $item->count); // ✓ Correct
$result['count']; // ✗ Wrong
```

---

## 🎓 STEP 8: Auditor Review Evaluation

**Prerequisite:** Audit assignment sudah ada

**URL:** `/audit-mutu-internal` (or similar)

**Flow:**

```
1. Auditor Login (via Admin "Login As")
2. Go to Audit Menu
3. Find audit assignment (for the unit with submitted evaluation)
4. Click audit → view checklist
5. Review evidence from auditee evaluation
6. Add findings/notes
7. Approve atau Reject
8. Move to next unit
```

**Expected:** 
```
✅ Can see auditee's submitted evaluation
✅ Can see evidence/files
✅ Can add audit findings
✅ Can approve/reject
```

---

## 🔄 STEP 9: Admin Advance to Next Phase

**URL:** `/pengaturan-admin/siklus`

**Action:**
```
1. View current cycle (Siklus 2026, Tahap: Evaluasi)
2. Click "Tutup Tahap Saat Ini"
3. Confirm: "Anda yakin ingin lanjut ke tahap berikutnya?"
4. Click "Ya, Lanjutkan"
```

**Expected Result:**
```
✅ Tahap berubah: Evaluasi → Pengendalian
✅ Database updated: cycles.tahap_aktif = 'pengendalian'
✅ Auditee akses ke evaluasi DITUTUP (no more edits)
✅ Admin bisa mulai follow-up actions
```

**Timeline:**
```
Penetapan (Agustus)
    ↓
Pelaksanaan (Sept-Oct) → Auditee fills
    ↓
Evaluasi (Nov-Dec) → Auditor reviews
    ↓
Pengendalian (Jan) → Admin do corrections
```

---

## 🧪 COMPLETE TEST SCENARIO

**Waktu dibutuhkan:** ~30 menit

```
START: Admin at Dashboard
   ↓
[5 min] Admin Login As Auditee
   ↓
[10 min] Auditee View Jadwal & Fill Evaluasi
   - Open /self-evaluations
   - Verify jadwal visible ✓
   - Click Edit indicator 1
   - Fill form (Capaian, Uraian)
   - Simpan Sementara ✓
   - Verify status = DRAFT in list
   - Click Edit again
   - Simpan Permanen ✓
   - Verify status = SUBMITTED in list
   ↓
[5 min] Back to Admin
   - Click "Back to Admin" button
   - Navigate to Audit Reporting
   - Verify dashboard shows data ✓
   ↓
[10 min] (Optional) Auditor Review
   - Admin Login As Auditor
   - Navigate to Audit
   - Find unit & evaluation
   - Review evidence
   - Add findings
   ↓
END: Complete workflow verified ✓
```

---

## ✅ CHECKLIST: Verifikasi Setiap Step

```
SETUP:
□ Cycle 2026 aktif
□ Semester Window 1 configured (01/10 - 31/10)
□ Test user ada dengan role auditee
□ Current time between 01/10 dan 31/10 (atau adjust server time)
□ At least 1 indicator ada untuk semester 1

AUDITEE EXPERIENCE:
□ Login As Auditee works
□ /self-evaluations shows jadwal ✓
□ Form EDITABLE (not read-only)
□ Can fill indicator (Capaian, Uraian, Evidence)
□ Simpan Sementara works (status = DRAFT)
□ Can edit again after DRAFT
□ Simpan Permanen works (status = SUBMITTED)
□ After submit, form READ-ONLY
□ Edit button available (or disabled gracefully)

REPORTING:
□ /audit-reporting loads without 500 error
□ Shows PRODI tab with submitted counts
□ Shows LAB tab with submitted counts
□ Graphs/charts render correctly

AUDITOR EXPERIENCE:
□ Login As Auditor works
□ Can view audit list
□ Can access submitted evaluation
□ Can see evidence files
□ Can add findings/notes
□ Can approve/reject

WORKFLOW:
□ Admin can advance tahap (Evaluasi → Pengendalian)
□ After advance, auditee cannot edit anymore
□ After advance, pemeriksaan window is active
```

---

## 📊 SQL QUERIES: Validation

```sql
-- 1. Check Cycle & Windows
SELECT c.id, c.tahun, c.tahap_aktif, COUNT(sw.id) as windows_count
FROM cycles c
LEFT JOIN semester_windows sw ON c.id = sw.cycle_id
WHERE c.is_active = 1
GROUP BY c.id;

-- 2. Check Self Evaluations by Status
SELECT status, COUNT(*) as count
FROM self_evaluations
WHERE cycle_id = 1 AND semester_id = 1
GROUP BY status;

-- 3. Check Users & Roles
SELECT u.id, u.name, u.email, GROUP_CONCAT(r.name) as roles
FROM users u
LEFT JOIN model_has_roles mhr ON u.id = mhr.model_id
LEFT JOIN roles r ON mhr.role_id = r.id
WHERE u.id IN (1,2,3,4,5)
GROUP BY u.id;

-- 4. Check Unit Types
SELECT tipe, COUNT(*) as count
FROM units
GROUP BY tipe;

-- 5. Check Indicators Count
SELECT COUNT(*) as total_indicators FROM indicators;
```

---

## 🐛 TROUBLESHOOTING

| Symptom | Cause | Fix |
|---------|-------|-----|
| Jadwal not showing | No SemesterWindow | Create in Pengaturan → Jadwal Semester |
| Form READ-ONLY | Outside window dates | Check/adjust dates in SemesterWindow |
| 500 Error /audit-reporting | Array/Object mismatch | Already fixed in this session ✓ |
| Cannot edit after submit | Tahap wrong or window closed | Check cycle tahap & current date |
| Auditor cannot see evaluation | No audit assignment | Assign auditor to unit in Audit menu |
| Impersonate "Back to Admin" fails | Missing auth middleware | Already fixed in this session ✓ |

---

## 📝 RELATED DOCS

- [CYCLE_SETUP_GUIDE.md](./CYCLE_SETUP_GUIDE.md)
- [SYSTEM_READY_STATUS.md](./SYSTEM_READY_STATUS.md)
- [ADMIN_CYCLE_CONTROL_FAQ.md](./ADMIN_CYCLE_CONTROL_FAQ.md)
- [ADMIN_IMPERSONATION.md](./ADMIN_IMPERSONATION.md)
