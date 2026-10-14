# 🎬 SIMULASI END-TO-END: Proses Lengkap Siklus SPMI Dari Awal

## ✅ Tahap 0: SETUP AWAL (Sekarang Sudah Done!)

### ✓ Apa yang Sudah Dikonfigurasi

**Database State:**
```
✅ Cycle 2026
   - Tahun: 2026
   - Nama: Siklus SPMI 2026
   - Tahap Aktif: Evaluasi
   - Status: Aktif

✅ Semester Windows (JUST ADDED!)
   - Semester 1 (Ganjil):
     * Pengisian: 01/10/2026 08:00 - 31/10/2026 17:00
     * Pemeriksaan: 01/11/2026 08:00 - 30/11/2026 17:00
```

**Hari ini**: 14 Oktober 2026 (masuk dalam window pengisian!) ✓

---

## 🚀 Tahap 1: PENETAPAN (August-September)

**Admin Actions:**
- [x] Setup Cycle (SUDAH: 2026)
- [x] Setup Standar (assumsi: sudah ada)
- [x] Setup Indikator (assumsi: sudah ada)
- [ ] (Opsional) Setup Units, Users, Assignments

**System State:**
```
Cycles table:    1 row (Siklus 2026, Tahap: Penetapan)
Semester Windows: (kosong, setup belum)
```

---

## 🎯 Tahap 2: PELAKSANAAN (October-November) ← SEKARANG DI SINI!

**Admin Actions (Sudah Dilakukan):**
- [x] Konfigurasi Jadwal Pengisian & Pemeriksaan
- [ ] Click tombol "Advance" → Lanjut ke Pelaksanaan

**Auditee/User Actions:**
- Login → Menu Evaluasi Diri
- Lihat Jadwal (sekarang sudah ada!)
- Isi indikator
- Submit evaluasi (Simpan Permanen)

**System State:**
```
Cycles: Tahap = Pelaksanaan
Semester Windows: ✓ Dikonfigurasi (BARU!)
Self Evaluations: Status = Draft/Submitted
```

---

## 📊 SIMULASI STEP-BY-STEP

### **STEP 1: Admin Advance ke Pelaksanaan**

Sekarang siklus masih di tahap **Evaluasi** (karena database sudah sebelumnya). Mari kita "rewind" ke Pelaksanaan untuk simulasi yang tepat.

**Action:**
```
Menu → Pengaturan → Siklus PPEPP
  ↓
Lihat: "Siklus 2026" dengan Tahap "Evaluasi"
  ↓
Klik tombol: "Tutup tahap & lanjut ke Pengendalian"
  ↓
Confirm: "Tutup tahap aktif sekarang?"
  ↓
Result: Tahap berubah (Penetapan → Pelaksanaan → Evaluasi → Pengendalian)
```

**Problem:** Tahap one-way! Tidak bisa mundur.

**Workaround untuk testing:**
- Option A: Restart siklus dari database (reset to Penetapan)
- Option B: Proceed dengan siklus current di tahap Evaluasi (auditee masih bisa isi karena fix di session ini)

Mari kita lanjut dengan **Option B** (proceed dengan Evaluasi).

---

### **STEP 2: Auditee Login & Lihat Jadwal**

**URL:** `https://spmi.ai-tekno.my.id/self-evaluations`

**Flow:**

1. **Admin Login As Auditee**
   ```
   Menu Pengaturan → Pengguna
   Cari user dengan role "auditee"
   Klik tombol "Login As" (sebelah aksi)
   ```

2. **Auditee View Evaluasi Diri**
   ```
   URL: https://spmi.ai-tekno.my.id/self-evaluations
   
   Harusnya lihat:
   ✓ Jadwal Pengisian ditampilkan!
     "Pengisian: 01/10/2026 08:00 – 31/10/2026 17:00
      Pemeriksaan: 01/11/2026 08:00 – 30/11/2026 17:00"
   
   ✓ Form editable (bukan read-only)
   ✓ Indikator list ditampilkan
   ```

3. **Before Setup (Hari kemarin):**
   ```
   Alert: "Jadwal pengisian untuk semester ini belum 
           ditetapkan Admin SPMI. Isian belum dapat diubah."
   Form: READ-ONLY ❌
   ```

4. **After Setup (Hari ini, setelah Semester Window ditambah):**
   ```
   Alert: "Pengisian: 01/10/2026 08:00 – 31/10/2026 17:00
           Pemeriksaan: 01/11/2026 08:00 – 30/11/2026 17:00"
   Form: EDITABLE ✅
   ```

---

### **STEP 3: Auditee Isi Evaluasi**

**Action:**
```
1. Lihat list indikator (dalam tabel)
2. Untuk setiap indikator:
   - Isi "Capaian" (ada/tidak ada, atau nilai)
   - Isi "Uraian" (deskripsi pencapaian)
3. Klik "Simpan Draft" untuk save sementara
4. Klik "Simpan Permanen" untuk submit (final)
```

**Expected Result:**
```
✓ Draft saved
✓ Bisa edit lagi jika masih dalam pengisian window
✓ Permanen submitted:
  - Status berubah ke "Submitted"
  - Tombol submit hilang
  - Form jadi read-only (untuk submitted)
```

---

### **STEP 4: Auditor Audit**

**Prerequisite:**
```
- Auditor harus sudah di-assign ke audit
- Ada Audit record untuk unit/semester ini
```

**Flow:**

1. **Auditor Login & Lihat Audit**
   ```
   Menu → Audit Mutu Internal
   ```

2. **Plot Audit Hasil**
   ```
   Buka audit → lihat self-evaluation yang sudah submitted
   Buka detail → lihat evidence & bukti
   Review bukti → approve/reject
   ```

3. **Lihat Laporan**
   ```
   Menu → Laporan Audit (Lab & Prodi)
   
   Lihat:
   ✓ Total audit by unit type (PRODI vs LAB)
   ✓ Status distribution
   ✓ Aggregated data
   ```

---

### **STEP 5: Admin Lanjut ke Tahap Evaluasi**

**Action:**
```
Menu → Pengaturan → Siklus PPEPP
  ↓
Klik: "Tutup tahap & lanjut ke Evaluasi"
  ↓
Tahap berubah: Pelaksanaan → Evaluasi
  ↓
Window pemeriksaan menjadi aktif (01/11/2026 08:00)
```

**System Behavior:**
```
✓ Auditee STILL BISA edit (karena fix di session ini!)
  - Selama dalam window pengisian (01/10 - 31/10)
  - Atau ada approved reopen request

✓ Auditor bisa lihat evaluasi & review bukti
  - Selama dalam window pemeriksaan (01/11 - 30/11)
```

---

## 📋 TESTING CHECKLIST

### **Test Data Preparation**

**Cycle:**
- [x] Cycle 2026 ada & aktif
- [x] Tahap: Evaluasi (current)
- [x] Standard Edition: linkage correct

**Semester Windows:**
- [x] Semester 1 dikonfigurasi (01/10 - 31/10 pengisian)
- [ ] Semester 2 dikonfigurasi (jika perlu test semester 2)

**Test Users:**
- [ ] Admin user ada (sudah ada: admin_spmi)
- [ ] Auditee user ada (perlu check)
- [ ] Auditor user ada (perlu check)
- [ ] All users punya unit/jabatan assignment

**Indicators & Assignments:**
- [ ] Indicators ada untuk semester 1
- [ ] Standard Edition linkage correct
- [ ] Assignments ada (indicator -> unit -> jabatan)

---

## 🧪 MINI SIMULATION

### Scenario: Auditee TI isi evaluasi semester 1

**Prerequisites:**
```
1. Unit: Program Studi Teknik Informatika
2. Auditee: Dr. Ahmad (PIC Program Studi)
3. Semester: 1 (Ganjil)
4. Indikators: 5 indikator sample
```

**Flow:**

| Step | Admin | Auditee | System |
|------|-------|---------|--------|
| 1 | Setup Jadwal ✓ | - | Semester Window created |
| 2 | - | Login | Session active |
| 3 | - | Go to Evaluasi Diri | List page loaded, jadwal ditampil ✓ |
| 4 | - | Click "Semester Ganjil" | Indicator table loaded |
| 5 | - | Fill all indicators | Form editable ✓ |
| 6 | - | Click "Simpan Draft" | Draft saved |
| 7 | - | Click "Simpan Permanen" | Status → Submitted |
| 8 | Assign Audit | - | Audit record created |
| 9 | Assign Auditor | - | Auditor linked to audit |
| 10 | Advance → Evaluasi | - | Tahap changed, pemeriksaan active |
| 11 | - | - | Audit reporting shows data ✓ |

---

## 📊 DATA QUERIES

**Check current state:**

```sql
-- Active cycle & tahap
SELECT id, tahun, tahap_aktif, is_active 
FROM cycles WHERE is_active = 1;

-- Semester windows for cycle 1
SELECT id, semester, pengisian_mulai, pengisian_selesai 
FROM semester_windows WHERE cycle_id = 1;

-- Test users
SELECT id, name, username FROM users 
WHERE username LIKE '%test%' OR username LIKE '%auditee%' OR username LIKE '%auditor%';

-- Self evaluations
SELECT COUNT(*) as count, status 
FROM self_evaluations 
WHERE cycle_id = 1 
GROUP BY status;

-- Audits
SELECT COUNT(*) as count, status 
FROM audits 
WHERE cycle_id = 1 
GROUP BY status;
```

---

## 🎯 EXPECTED OUTCOMES

### Before Setup (Yesterday):
```
❌ Auditee message: "Jadwal pengisian belum ditetapkan"
❌ Form: READ-ONLY
❌ Cannot submit
```

### After Setup (Today):
```
✅ Auditee message: Shows actual dates
✅ Form: EDITABLE
✅ Can submit → Status Submitted
✅ Can draft → Status Draft
✅ Can edit again (until pengisian_selesai)
```

### Audit Phase:
```
✅ Auditor can see submitted evaluations
✅ Auditor can review & approve evidence
✅ Audit Reporting shows aggregated data
✅ Lab & Prodi audits separated in dashboard
```

---

## 📝 KEY LEARNINGS

### Sistem Design:

1. **Tahap Berjenjang:** One-way progression
   - Penetapan → Pelaksanaan → Evaluasi → Pengendalian
   - Tidak bisa mundur

2. **Jadwal Dual-Layer:**
   - **Cycle Tahap:** Determines phase
   - **Semester Window:** Determines specific dates
   - Both perlu dikonfigurasi untuk auditee bisa isi

3. **Workflow Logic:**
   - Auditee bisa isi **if** (tahap == Pelaksanaan) **and** (now within pengisian window)
   - OR approved reopen request ada
   - This allows flexibility untuk perbaikan saat Evaluasi phase

4. **Database Relationships:**
   ```
   Cycle 1:n SemesterWindow
        1:n SelfEvaluation
        1:n Audit
   
   Indicators assigned via Standard > Statement > Assignments
   ```

---

## 🚀 NEXT STEPS

1. **Verify Test Data:**
   - Run database queries above
   - Check if Auditee user exists with correct assignments

2. **Test Auditee Flow:**
   - Admin Login As Auditee
   - Go to Evaluasi Diri
   - Verify jadwal shown & form editable
   - Fill & submit one indicator
   - Verify status changes

3. **Test Audit Flow:**
   - Create/Assign audit for the evaluation
   - Assign auditor
   - Auditor review evaluation & evidence
   - Check Audit Reporting dashboard

4. **Document Results:**
   - Screenshot setiap milestone
   - Note any errors
   - Verify sistem behaves as expected

---

## 📚 Related Documentation

- [CYCLE_SETUP_GUIDE.md](./CYCLE_SETUP_GUIDE.md) - Detailed setup
- [EVALUATION_WORKFLOW.md](./EVALUATION_WORKFLOW.md) - Workflow details
- [SYSTEM_READY_STATUS.md](./SYSTEM_READY_STATUS.md) - Quick status
