# JAWABAN: Kapan Siklus Berjalan & Bagaimana Admin Mengontrol?

## TL;DR - Ringkas

**Siklus TIDAK otomatis berjalan!** Admin harus:
1. ✅ **Buat Siklus** (SUDAH DILAKUKAN)
2. ✅ **Aktifkan Siklus** (SUDAH DILAKUKAN)
3. ❌ **Setup Jadwal Pengisian & Pemeriksaan** ← **INI YANG TERLUPAKAN!**
4. ❌ **Advance tahap saat timing tepat** ← **BELUM DILAKUKAN**

---

## 📊 Kondisi Database Saat Ini vs Requirement

| Component | Status DB | Kebutuhan Auditee Isi | Catatan |
|-----------|-----------|----------------------|---------|
| **Cycle 2026** | ✅ Ada | ✅ Diperlukan | Sudah aktif, tahap Evaluasi |
| **Semester Window** | ❌ **KOSONG** | ✅ **CRITICAL!** | 👈 **INI MASALAHNYA** |
| **Indikator Assignment** | ? | ✅ Diperlukan | Perlu di-check |
| **Tahap Workflow** | Evaluasi | ✅ OK | Allow both Pelaksanaan & Evaluasi (fixed) |

---

## 🔴 MASALAH UTAMA

**Pesan yang Auditee lihat:**
```
"Jadwal pengisian untuk semester ini belum ditetapkan Admin SPMI. 
Isian belum dapat diubah."
```

**Sebabnya:**
- Tidak ada **Semester Window** di database
- Siklus ada, tapi tidak ada jadwal pengisian & pemeriksaan
- Sistem tidak bisa tahu "kapan" pengisian dibuka dan ditutup

---

## ✅ SOLUSI (Admin Harus Lakukan)

### Step 1: Buka Halaman Jadwal Semester
```
Menu → Pengaturan → Jadwal Semester
```

### Step 2: Click "Tambah" untuk Semester 1
```
┌─────────────────────────────────────────────────────┐
│ Siklus:               Siklus SPMI 2026              │
│ Semester:             Semester Ganjil (1)           │
│ Pengisian dibuka:     01/10/2025 08:00              │
│ Pengisian ditutup:    30/11/2025 17:00              │
│ Pemeriksaan dibuka:   01/12/2025 08:00              │
│ Pemeriksaan ditutup:  31/12/2025 17:00              │
│                                                     │
│  [Simpan] [Batal]                                   │
└─────────────────────────────────────────────────────┘

TIPS:
- Pengisian = waktu auditee isi evaluasi
- Pemeriksaan = waktu auditor review bukti
- Harus pengisian_selesai ≤ pemeriksaan_mulai
```

### Step 3: Repeat untuk Semester 2
```
- Semester Genap (2)
- Pengisian: Feb 2026 - April 2026
- Pemeriksaan: May 2026 - June 2026
```

### Step 4: Sekarang Auditee Bisa Isi! 🎉

---

## 📅 Timeline yang Ideal untuk Siklus

```
BULAN          ADMIN ACTION                  AUDITEE CAN DO
─────────────────────────────────────────────────────────
Agustus        Setup Cycle & Standar        —
               (Tahap: Penetapan)

September      Setup Jadwal Semester        —
               (Create SemesterWindow)
               
Oktober-Nov    Advance → Pelaksanaan       ✅ Isi Evaluasi
               (Open pengisian)
               
Desember-Jan   Advance → Evaluasi          ✅ Still edit*
               (Assign Auditor)            (*within window)
               
Feb-April      Repeat Semester 2           ✅ Isi Evaluasi 2
               
Mei            Advance → Pengendalian      —
               (Process findings)
               
Juni           Selesaikan Siklus           —
               Mark is_active = false
```

---

## 🎯 Apa yang Harus Admin Konfigurasi?

### 1️⃣ **Cycle Management** (Pengaturan → Siklus PPEPP)
```
✅ CREATE: Siklus SPMI 2026
✅ ACTIVATE: is_active = true
✅ ADVANCE: Tahap saat waktunya
   Penetapan → Pelaksanaan → Evaluasi → Pengendalian
```

### 2️⃣ **Jadwal Semester** (Pengaturan → Jadwal Semester)
```
❌ MISSING: Harus add untuk Semester 1 & 2
   - Cycle: Siklus SPMI 2026
   - Semester: 1 (Ganjil) & 2 (Genap)
   - Pengisian dates: Oct-Nov untuk sem 1, Feb-Apr untuk sem 2
   - Pemeriksaan dates: Dec-Jan untuk sem 1, May-Jun untuk sem 2
```

### 3️⃣ **Indikator Assignment** (Pernyataan Standar)
```
? CHECK: Apakah ada assignment indikator ke units/jabatan?
   - Menu → Penetapan → Pernyataan Standar
   - Lihat kolom "Penugasan"
```

---

## ❓ Pertanyaan Pengguna vs Jawaban

**Q: "Jadi sebenarnya kapan siklus ini berjalan?"**
A: Siklus berjalan dalam **4 tahap berurutan**, masing-masing jangka waktu:
- **Penetapan**: Setup standar & indikator (Agustus-September)
- **Pelaksanaan**: Auditee isi evaluasi (Oktober-November)
- **Evaluasi**: Auditor audit & review (Desember-Januari)
- **Pengendalian**: Process findings (Februari-Mei)

**Q: "Bagaimana admin bisa mengontrol siklus ini?"**
A: Admin kontrol via:
1. **Cycles menu**: Create, activate, advance tahap
2. **Jadwal Semester menu**: Set tanggal pengisian & pemeriksaan
3. **Tombol "Advance"**: Manually lanjut ke tahap berikutnya

**Q: "Apakah siklus sudah berfungsi?"**
A: **Siklus sudah 70% berfungsi:**
- ✅ Cycle creation & activation: WORKS
- ✅ Tahap workflow: WORKS
- ✅ Evaluasi submission logic: WORKS (fixed dalam session ini)
- ❌ **Jadwal Semester: NOT CONFIGURED** ← Ini yang perlu admin setup
- ❌ Test data: Mungkin belum ada indicator assignment

---

## 🔍 Debugging: Cek Status Saat Ini

Jika admin ingin verify status sebelum setup:

**Buka Halaman:**
- **Siklus PPEPP** → Lihat cycle 2026 status
  - ✅ Ada: Siklus SPMI 2026, Aktif, Tahap Evaluasi
  
- **Jadwal Semester** → Lihat semester window
  - ❌ Kosong: "Belum ada data"
  
- **Pernyataan Standar** → Lihat assignment
  - ? Perlu check: Apakah ada assignment ke units?

**Database Query (untuk tech):*
```sql
SELECT * FROM cycles WHERE is_active = 1;
-- Should show: 1 row (2026, tahap_aktif='evaluasi')

SELECT * FROM semester_windows WHERE cycle_id = 1;
-- Currently: 0 rows (empty!)

SELECT * FROM standard_statements WHERE assignments LIKE '%unit%';
-- Check: Are there any assignments?
```

---

## 📋 Checklist untuk Admin

- [ ] Buka Pengaturan → Jadwal Semester
- [ ] Klik "Tambah"
- [ ] Pilih Siklus: Siklus SPMI 2026
- [ ] Pilih Semester: Semester Ganjil
- [ ] Isi Pengisian dibuka: 01/10/2025 08:00
- [ ] Isi Pengisian ditutup: 30/11/2025 17:00
- [ ] Isi Pemeriksaan dibuka: 01/12/2025 08:00
- [ ] Isi Pemeriksaan ditutup: 31/12/2025 17:00
- [ ] Klik: Simpan
- [ ] Repeat untuk Semester Genap
- [ ] Go to Evaluasi Diri → Verify jadwal sudah tampil
- [ ] Impersonate Auditee → Test isi form

---

## 📚 Full Documentation

Untuk detail lengkap, lihat:
- [CYCLE_SETUP_GUIDE.md](./CYCLE_SETUP_GUIDE.md) - Panduan setup lengkap
- [EVALUATION_WORKFLOW.md](./EVALUATION_WORKFLOW.md) - Workflow evaluasi
- [TESTING_CHECKLIST.md](./TESTING_CHECKLIST.md) - Testing procedures

---

## 🎯 Kesimpulan

| Pertanyaan | Jawaban |
|-----------|---------|
| Kapan siklus berjalan? | Dalam 4 tahap, admin control via "Advance" button |
| Bagaimana mengontrol? | Via 3 menu: Cycles, Jadwal Semester, Pengguna |
| Sudah berfungsi? | 70% - tinggal admin setup Jadwal Semester |
| Kenapa Auditee tidak bisa isi? | Semester Window belum dikonfigurasi |
| Solusinya? | Admin pergi ke Jadwal Semester → Tambah untuk setiap semester |
