# Panduan Setup Siklus SPMI - Lengkap untuk Admin

## Jawaban Atas Pertanyaan: "Kapan siklus berjalan dan bagaimana admin mengontrolnya?"

### Workflow Siklus SPMI

Siklus berjalan dalam **4 tahap berurutan**:

```
Penetapan → Pelaksanaan → Evaluasi → Pengendalian
   ↓            ↓           ↓           ↓
Setup       Auditee      Auditor    Action
standar     isi form      check      plan
```

**Siklus TIDAK otomatis advance** - Admin harus manually klik tombol untuk lanjut ke tahap berikutnya.

---

## Setup Lengkap Siklus (Step-by-Step)

### **Tahap 1: Setup Awal di Penetapan**

**1.1 Buat/Aktifkan Siklus**
- Menu: **Pengaturan → Siklus PPEPP**
- Click: **Tambah** (atau buka Siklus yang sudah ada)
- Isi:
  - **Tahun**: 2026
  - **Nama**: Siklus SPMI 2026
  - **Edisi Standar**: Pilih edisi (e.g., Standar SPMI 2026)
  - **Jadikan siklus aktif**: ✓ (checkbox)
- Click: **Simpan**

✅ **Status**: Siklus aktif di tahap **Penetapan**

**1.2 Setup Standar & Indikator** (sebelumnya sudah dilakukan)
- Menu: **Penetapan → Standar Mutu**, **Indikator & Target**, dll
- Admin atur seluruh framework standar internal

**1.3 Buat Jadwal Pengisian & Pemeriksaan**
⚠️ **INI YANG PALING PENTING DAN SERING TERLUPAKAN!**

- Menu: **Pengaturan → Jadwal Semester**
- Click: **Tambah**
- Isi untuk **SETIAP Semester**:

```
┌─────────────────────────────────────────┐
│ Siklus: Siklus SPMI 2026                │
│ Semester: 1                             │
│ Pengisian dibuka: 01/10/2025 08:00      │
│ Pengisian ditutup: 30/11/2025 17:00     │
│ Pemeriksaan dibuka: 01/12/2025 08:00    │
│ Pemeriksaan ditutup: 31/12/2025 17:00   │
└─────────────────────────────────────────┘

CATATAN:
- Tanggal harus di masa depan (atau now jika sudah dibuka)
- Pengisian harus selesai SEBELUM pemeriksaan dimulai
- Validasi: pemeriksaan_mulai >= pengisian_selesai
```

- Repeat untuk **Semester 2**
- Click: **Simpan**

✅ **Sekarang** Auditee bisa isi evaluasi di tahap Pelaksanaan!

---

### **Tahap 2: Pelaksanaan (Pengisian Evaluasi)**

**2.1 Lanjut ke tahap Pelaksanaan**
- Menu: **Pengaturan → Siklus PPEPP**
- Click tombol: **Tutup tahap & lanjut ke Pelaksanaan**
- Confirm: "Tutup tahap aktif sekarang?"

✅ **Siklus sekarang di tahap Pelaksanaan**

**2.2 Auditee isi evaluasi**
- Auditee login → **Evaluasi Diri**
- Lihat **Jadwal Pengisian** yang sudah dikonfigurasi
- Isi indikator → **Simpan Permanen** (submit)

**2.3 Assign Auditor** (parallel)
- Menu: **Evaluasi → Audit Mutu Internal**
- Create audit records dan assign auditor ke setiap unit

---

### **Tahap 3: Evaluasi (Audit & Review)**

**3.1 Lanjut ke tahap Evaluasi**
- Menu: **Pengaturan → Siklus PPEPP**
- Click tombol: **Tutup tahap & lanjut ke Evaluasi**
- Confirm: "Tutup tahap aktif sekarang?"

✅ **Siklus sekarang di tahap Evaluasi**

**3.2 Saat ini, Auditee TETAP BISA edit evaluasi**
- Alasan: Window pengisian masih dalam rentang yang dikonfigurasi
- Atau: Jika ada approved reopen request

**3.3 Auditor review bukti & audit**
- Auditor login → **Audit Mutu Internal**
- Plot hasil audit
- Tulis temuan

**3.4 Lihat Laporan**
- Admin → **Laporan Audit (Lab & Prodi)**
- Lihat agregasi data audit per unit type (PRODI vs LAB)

---

### **Tahap 4: Pengendalian (Tindak Lanjut)**

**4.1 Lanjut ke tahap Pengendalian**
- Menu: **Pengaturan → Siklus PPEPP**
- Click tombol: **Tutup tahap & lanjut ke Pengendalian**

✅ **Siklus sekarang di tahap Pengendalian**

**4.2 Selesaikan Siklus**
- Semua audit & temuan selesai dikelola
- Click tombol: **Selesaikan siklus**
- Siklus menjadi **nonaktif**

---

## ⚠️ MASALAH SAAT INI

**Di database Anda:**
```
✅ Siklus 2026 ada dan AKTIF
❌ Semester Windows BELUM dikonfigurasi
```

**Consequence:**
- Auditee melihat pesan: **"Jadwal pengisian untuk semester ini belum ditetapkan Admin SPMI. Isian belum dapat diubah."**
- Padahal siklus sudah di tahap **Evaluasi**

---

## 🔧 SOLUSI: Admin harus setup Semester Windows

### Langkah-langkah Fix:

**Opsi A: Kembali ke Pelaksanaan + Setup Window**
```
1. Menu: Pengaturan → Siklus PPEPP
2. CATATAN: Tidak bisa mundur ke tahap sebelumnya!
   (Tahap one-way, tidak bisa balik)
3. Jadi harus buat Cycle baru atau...
```

**Opsi B: Setup Window untuk Cycle 2026 sekarang (meskipun di tahap Evaluasi)**
```
1. Menu: Pengaturan → Jadwal Semester
2. Click: Tambah
3. Siklus: Siklus SPMI 2026
4. Semester: 1
5. Pengisian: Tanggal yg mencakup sekarang
   Pengisian dibuka: 01/10/2024 08:00
   Pengisian ditutup: 31/12/2026 17:00
6. Pemeriksaan: Tanggal pemeriksaan
   Pemeriksaan dibuka: 01/01/2027 08:00
   Pemeriksaan ditutup: 31/03/2027 17:00
7. Click: Simpan
8. Repeat untuk Semester 2
```

**Setelah setup:**
- Auditee akan melihat jadwal pengisian di halaman Evaluasi Diri
- Form akan editable (asalkan within pengisian window)
- Auditee bisa submit evaluasi

---

## 📋 Checklist: Kapan Auditee Bisa Isi Evaluasi?

**Auditee bisa isi JIKA:**

```
✓ Siklus adalah "Aktif"
✓ DAN Tahap adalah "Pelaksanaan" ATAU "Evaluasi"  
✓ DAN Semester Window dikonfigurasi untuk semester tsb
✓ DAN Sekarang (now) within pengisian_mulai - pengisian_selesai
✓ ATAU Ada approved reopen request
```

**Auditee TIDAK bisa isi JIKA:**

```
✗ Siklus nonaktif
✗ ATAU Tidak ada Semester Window dikonfigurasi  
✗ ATAU Sekarang diluar pengisian window
✗ ATAU Status evaluasi bukan Draft (harus via Reopen Request)
```

---

## 📊 Database State vs Business Logic

| Component | Current State | Needed For Submission |
|-----------|---------------|-----------------------|
| Cycle (2026) | ✅ Aktif | ✅ Required |
| Tahap | Evaluasi | ✅ OK (allow both Pelaksanaan & Evaluasi) |
| Semester Window | ❌ TIDAK ADA | ⚠️ **MISSING!** |
| Indikator Assignment | ? | ✅ Required |

**Kesimpulan:** Sistem sudah siap, tapi **Admin belum setup Semester Windows!**

---

## 🚀 Rekomendasi Workflow Ideal

### Untuk Production:

```
1. Bulan Agustus: Setup Siklus + Standar (Tahap Penetapan)
   - Cycle.create()
   - Setup edisi standar & indikator
   
2. Bulan September: Setup jadwal + indikator assignments
   - SemesterWindow.create() dengan tanggal O
ct-Nov (Semester 1)
   - SemesterWindow.create() dengan tanggal Feb-Apr (Semester 2)
   - Assign indicators ke units/jabatan
   
3. Oktober-November: Advance ke Pelaksanaan, auditee isi
   - Cycle.advanceStage() → Pelaksanaan
   - Auditee fill evaluasi selama pengisian_window
   
4. Desember-Januari: Advance ke Evaluasi, auditor review
   - Cycle.advanceStage() → Evaluasi
   - Auditor audit & tulis temuan
   - Auditee bisa still refine (dalam window)
   
5. Februari-April: Process semester 2 sama
   
6. Mei: Advance ke Pengendalian
   - Cycle.advanceStage() → Pengendalian
   - Action items & corrective actions
   
7. Juni: Selesaikan & archive
   - Finalize siklus 2026
   - Create siklus 2027
```

---

## ❓ FAQ

**Q: Apakah admin bisa mundur dari Evaluasi ke Pelaksanaan?**
A: **TIDAK.** Tahap bersifat one-way. Jika sudah lanjut, tidak bisa balik.

**Q: Bagaimana jika auditee belum selesai saat tahap berubah?**
A: Auditee tetap bisa edit selama:
- Berada dalam pengisian_window, ATAU
- Ada approved reopen request

**Q: Bisakah dua semester window overlap?**
A: Ya, biasa terjadi:
- Semester 1: Oct-Nov pengisian, Dec-Jan pemeriksaan
- Semester 2: Feb-Apr pengisian, May-Jun pemeriksaan
Tidak overlap, terpisah.

**Q: Akibat tidak setup Semester Window?**
A: Auditee tidak bisa submit evaluasi karena sistem cek "apakah window dikonfigurasi?". Pesan: "Jadwal pengisian untuk semester ini belum ditetapkan Admin SPMI."

---

## 📚 Related Documentation

- [EVALUATION_WORKFLOW.md](./EVALUATION_WORKFLOW.md) - Testing workflow evaluasi
- [TESTING_CHECKLIST.md](./TESTING_CHECKLIST.md) - Full testing procedures
- [ADMIN_IMPERSONATION.md](./ADMIN_IMPERSONATION.md) - Login As feature guide
