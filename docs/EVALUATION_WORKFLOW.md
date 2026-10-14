# Prosedur Testing Evaluasi Diri dengan Reopen Request

## Skenario: Admin mengubah fase ke Evaluasi, kemudian Auditee perlu perbaikan

### Fase 1: PELAKSANAAN (Pengisian Normal)

**Admin:**
1. Masuk ke Pengaturan → Siklus
2. Ubah fase ke "Pelaksanaan" 
3. Pastikan Semester Window sudah di-set dengan tanggal pengisian yang aktif
   - Pengisian: harus dalam rentang tanggal sekarang
   - Pemeriksaan: tanggal pemeriksaan Evaluasi

**Auditee (User Biasa):**
1. Masuk dengan akun auditee
2. Pilih Evaluasi Diri → pilih semester
3. Isi semua indikator sesuai kebutuhan
4. Setelah selesai: klik "Simpan Permanen" untuk submit

### Fase 2: EVALUASI (Pemeriksaan + Reopen)

**Admin:**
1. Masuk ke Pengaturan → Siklus
2. Ubah fase ke "Evaluasi"
3. (Opsional) Plot audit dan lakukan audit checklist

**Result:** Auditee tidak bisa lagi mengisi (periode pengisian ditutup)

**Jika ada yang perlu diperbaiki:**

**Opsi A: Auditee buat reopen request**
1. Auditee akses Evaluasi Diri → klik tombol "Minta Pembukaan Kembali"
2. Isi alasan pembukaan kembali
3. Kirim request

**Opsi B: Admin buat reopen request langsung**
- (Fitur ini belum ada di UI, perlu dicheck apakah perlu ditambahkan)

**Admin approve reopen request:**
1. Masuk ke Pengaturan → Permintaan Pembukaan Pengisian
2. Lihat daftar pending requests
3. Klik tombol untuk approve/reject
4. Jika approve: evaluasi kembali ke Draft, auditee bisa edit

**Auditee edit lagi:**
1. Kembali ke Evaluasi Diri
2. Lihat status: "Login As Mode" sudah bisa edit lagi (approved reopen request)
3. Lakukan perbaikan sesuai feedback
4. Klik "Simpan Permanen" untuk submit ulang

## Checklist untuk Testing

### Fase Pelaksanaan
- [ ] Admin set fase ke Pelaksanaan
- [ ] Semester Window sudah dikonfigurasi dengan tanggal aktif
- [ ] Auditee bisa akses dan isi self-evaluation
- [ ] Tombol "Simpan Permanen" tersedia dan berfungsi
- [ ] Evaluasi ter-submit dengan status "Submitted"

### Fase Evaluasi - Lockout
- [ ] Admin ubah fase ke Evaluasi
- [ ] Auditee akses self-evaluation
- [ ] Alert muncul: "Periode pengisian normal sedang tidak dibuka"
- [ ] Form tidak editable (read-only mode)
- [ ] Tombol submit tidak tersedia

### Reopen Request
- [ ] Auditee klik "Minta Pembukaan Kembali" (atau tombol yang ada)
- [ ] Form reopen request modal muncul
- [ ] Isi alasan dan kirim
- [ ] Alert: "Permintaan pembukaan pengisian dikirim ke Admin SPMI"

### Admin Approve
- [ ] Admin akses Pengaturan → Permintaan Pembukaan Pengisian
- [ ] Lihat pending request dari auditee
- [ ] Klik approve
- [ ] Request status berubah ke "Approved"
- [ ] Evaluasi status berubah ke "Draft"

### Auditee Edit Ulang
- [ ] Auditee refresh halaman Evaluasi Diri
- [ ] Form kembali editable
- [ ] Lakukan perbaikan
- [ ] Submit ulang

## Catatan Penting

- Window pengisian harus dalam rentang tanggal hari ini agar pembukaan normal bisa dilakukan
- Reopen request hanya bisa dibuat jika evaluasi sudah Submitted (bukan Draft)
- Jika tidak ada approved reopen request saat fase Evaluasi, user tidak bisa edit
- Auditee perlu tombol/link yang jelas untuk membuat reopen request
