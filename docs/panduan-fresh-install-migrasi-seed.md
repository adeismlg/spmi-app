# Panduan Fresh Install, Migrasi, dan Seed Aplikasi SPMI

## 1. Tujuan dan ruang lingkup

Panduan ini menjelaskan instalasi baru aplikasi SPMI, pengisian skema basis data melalui migration, pembuatan akun administrator pertama, pemuatan standar, seed data demonstrasi, dan peningkatan versi aplikasi yang sudah berjalan.

Panduan ditulis untuk deployment pada Windows Server/komputer lokal di belakang Cloudflare Tunnel. Perintah Artisan dijalankan dari direktori root proyek, yaitu direktori yang berisi file `artisan`.

> **Peringatan produksi:** seed demo membuat akun dengan password bawaan dan memasukkan data simulasi. Jangan jalankan `DemoSeeder`, `SpmiSeeder`, atau `migrate:fresh --seed` pada basis data produksi.

## 2. Prasyarat server

Siapkan:

- PHP 8.3 atau lebih baru beserta ekstensi yang dibutuhkan oleh paket Composer;
- Composer;
- Node.js dan npm untuk membangun aset frontend;
- SQLite untuk instalasi satu server sederhana, atau MySQL/MariaDB bila menggunakan server database terpisah;
- direktori proyek yang dapat dibaca oleh PHP dan dapat ditulisi oleh akun service web pada `storage/` dan `bootstrap/cache/`.

Periksa versi dan kebutuhan ekstensi:

```powershell
php -v
composer -V
node -v
npm -v
composer check-platform-reqs
```

## 3. Ambil kode aplikasi

Salin/checkout rilis aplikasi ke direktori yang ditetapkan di server. Contoh:

```powershell
Set-Location C:\Apps
git clone <URL-REPOSITORY> spmi-app
Set-Location C:\Apps\spmi-app
```

Jika kode sudah disalin atau diperbarui, pastikan lokasi kerja adalah root proyek:

```powershell
Test-Path .\artisan
```

Hasil harus `True`.

## 4. Pasang dependency dan aset

Untuk server produksi, pasang dependency PHP tanpa paket development, kemudian pasang dependency frontend dan buat build produksi:

```powershell
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm install --no-audit --no-fund
npm run build
```

Build harus menghasilkan `public/build/manifest.json`. Jika aset dibangun pada mesin lain, salin seluruh direktori `public/build/` dari rilis yang sama ke server.

## 5. Siapkan `.env`

Salin template hanya jika `.env` belum ada. Jangan menimpa `.env` server yang sudah berisi konfigurasi atau rahasia:

```powershell
if (-not (Test-Path .\.env)) {
    Copy-Item .\.env.example .\.env
}
```

Edit `.env` langsung di server. Untuk deployment publik, sekurang-kurangnya atur:

```dotenv
APP_NAME="SPMI Polinema"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://spmi.ai-tekno.my.id
APP_LOCALE=id
APP_FALLBACK_LOCALE=id

DB_CONNECTION=sqlite

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=<alamat-pengirim-gmail>
MAIL_PASSWORD=<app-password-yang-baru>
MAIL_FROM_ADDRESS=<alamat-pengirim-gmail>
MAIL_FROM_NAME="${APP_NAME}"
DOSEN_EMAIL=<email-penerima-notifikasi>

GOOGLE_CLIENT_ID=<client-id-google>
GOOGLE_CLIENT_SECRET=<client-secret-google>
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Gunakan rahasia yang baru dan jangan menyimpan `.env` ke repository. Kredensial SMTP/OAuth yang pernah dibagikan atau terekspos harus dicabut dan diganti. Untuk Google OAuth, daftarkan URI callback yang persis sama di Google Cloud Console.

Buat application key satu kali pada instalasi baru:

```powershell
php artisan key:generate --force
```

Jangan membuat ulang `APP_KEY` pada instalasi yang sudah berisi data; perubahan key dapat membuat data terenkripsi yang ada tidak dapat dibaca.

## 6. Siapkan basis data kosong

### SQLite

Template aplikasi menggunakan SQLite. Buat file basis data hanya bila belum tersedia:

```powershell
if (-not (Test-Path .\database\database.sqlite)) {
    New-Item -ItemType File -Path .\database\database.sqlite | Out-Null
}
```

Pastikan service PHP/web server mempunyai izin baca/tulis pada file basis data dan direktori `database/`.

### MySQL atau MariaDB

Buat basis data dan akun khusus aplikasi pada server database, lalu isi `.env` tanpa membagikan password:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spmi
DB_USERNAME=<user-aplikasi>
DB_PASSWORD=<password-kuat>
```

Pastikan akun basis data memiliki izin yang diperlukan untuk membuat dan memperbarui tabel selama deployment.

## 7. Jalankan migration pada instalasi baru

Pastikan `.env` menunjuk ke basis data yang benar, lalu periksa dan jalankan migration:

```powershell
php artisan config:clear
php artisan migrate:status
php artisan migrate --force
php artisan migrate:status
```

Migration menyiapkan tabel akun, unit, edisi standar, siklus PPEPP, evaluasi, bukti, audit, RTL, antrean, cache, sesi, dan peran. Edisi `LEGACY` dibuat otomatis sebagai bagian migration edisi standar; tidak perlu dibuat manual.

## 8. Seed peran dan buat administrator pertama

Pada produksi, seed hanya peran aplikasi:

```powershell
php artisan db:seed --class=RoleSeeder --force
```

Buat akun administrator pertama secara interaktif agar password tidak tertulis pada command history. Jalankan:

```powershell
php artisan tinker
```

Kemudian masukkan kode berikut ke prompt Tinker:

```php
$name = \Laravel\Prompts\text('Nama administrator', required: true);
$email = \Laravel\Prompts\text('Email administrator', required: true);
$password = \Laravel\Prompts\password('Password administrator (minimal 12 karakter)', required: true);
$data = \Illuminate\Support\Facades\Validator::make(
    ['name' => $name, 'email' => mb_strtolower(trim($email)), 'password' => $password],
    [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email'],
        'password' => ['required', \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()->symbols()],
    ],
)->validate();
$admin = \App\Models\User::create($data);
$admin->assignRole('admin_spmi');
```

Keluar dari Tinker dengan `exit`. Password ditangani oleh prompt tersembunyi dan model User melakukan hashing. Gunakan alamat email administrator institusi yang benar; jangan memakai akun `@spmi.test`.

## 9. Masukkan edisi standar dan data institusi

Setelah login sebagai administrator:

1. Buat **Edisi Standar** dengan kode dan rentang tahun yang sesuai dokumen resmi.
2. Buat siklus PPEPP dan pilih edisi tersebut.
3. Masukkan struktur **Unit/Prodi** sebelum menghitung penugasan berdasarkan PIC.
4. Bila tersedia workbook standar resmi yang sudah ditelaah, salin berkas ke server. Repository menyediakan contoh berkas `database/data/spmi-polinema-2026.xlsx`; pastikan berkas pada server adalah versi yang memang akan digunakan.
5. Jalankan dry-run terlebih dahulu:

```powershell
php artisan spmi:import-standards database/data/spmi-polinema-2026.xlsx --edition=SPMI-2026 --dry-run
```

Ganti `SPMI-2026` dengan kode edisi yang dibuat di aplikasi. Tinjau ringkasan dan peringatan pemetaan PIC. Jika hasilnya benar, jalankan kembali tanpa `--dry-run`:

```powershell
php artisan spmi:import-standards database/data/spmi-polinema-2026.xlsx --edition=SPMI-2026
```

Impor membuat atau memperbarui standar, pernyataan, indikator, baseline, target, dan penugasan otomatis pada edisi tersebut. Sebelum dipakai, administrator harus memeriksa hasil impor, penugasan, dan data yang belum memiliki PIC. Jangan menimpa edisi yang sudah memiliki hasil evaluasi atau audit; buat edisi baru untuk revisi.

## 10. Seed data demo pada development atau staging

Untuk mencoba seluruh alur PPEPP pada basis data nonproduksi yang baru:

```powershell
php artisan migrate:fresh
php artisan db:seed --class=DemoSeeder
```

`migrate:fresh` menghapus **semua tabel dan data** pada basis data aktif sebelum menjalankan ulang migration. Jalankan hanya setelah memastikan `.env` menunjuk ke basis data development/staging yang boleh dihapus.

`DemoSeeder` menjalankan `SpmiSeeder`, mengimpor workbook standar bila berkas ada, lalu membuat unit, akun, bukti, audit, temuan, RTL, dan dokumen simulasi. Seeder demo menggunakan password contoh `password` dan akun berdomain `spmi.test`. Jangan jalankan pada produksi atau membuka akun demo melalui jaringan publik. Ganti/hapus akun demo setelah simulasi.

`DatabaseSeeder` bawaan proyek membuat `test@example.com` dan bukan akun administrator SPMI. Karena itu, jangan mengandalkan `php artisan migrate --seed` untuk menyiapkan administrator produksi.

## 11. Perbarui database yang sudah berjalan

### Wajib sebelum deployment

1. Buat backup basis data yang konsisten.
2. Backup direktori `storage/`, terutama bukti evaluasi, dokumen mutu, dan berkas hasil tinjauan.
3. Simpan salinan `.env` di lokasi aman di luar repository.
4. Terapkan rilis aplikasi yang telah diuji.

Jalankan migration baru tanpa menghapus tabel:

```powershell
php artisan down
php artisan migrate:status
php artisan migrate --force
php artisan migrate:status
```

**Jangan** memakai `migrate:fresh`, `migrate:refresh`, atau `migrate:reset` untuk peningkatan versi yang mempertahankan data.

Jika instalasi lama menyimpan bukti di disk publik, periksa dan pindahkan berkas lama setelah backup:

```powershell
php artisan spmi:secure-evidences --dry-run
php artisan spmi:secure-evidences
```

Pastikan jumlah berkas yang tidak ditemukan adalah nol sebelum menutup maintenance mode.

## 12. Selesaikan konfigurasi deployment

Setelah `.env` dan migration siap, buat symlink storage pada instalasi baru (pada server yang sudah memilikinya, lewati perintah ini):

```powershell
php artisan storage:link
```

Lalu optimalkan aplikasi dan mulai ulang worker:

```powershell
php artisan optimize
php artisan queue:restart
php artisan up
```

Pastikan document root web server menunjuk ke direktori `public`, bukan root repository. Beri akun service web akses tulis ke `storage/` dan `bootstrap/cache/`. File `.env`, `vendor/`, `database/`, serta source aplikasi tidak boleh dapat diunduh langsung melalui web server.

Proses berikut harus dijalankan terus-menerus oleh service manager Windows:

```powershell
php artisan queue:work --tries=3
```

Aktifkan Scheduler Windows Task Scheduler agar menjalankan `php artisan schedule:run` setiap menit dari working directory root proyek. Scheduler menjalankan pengingat RTL otomatis setiap hari pukul 08.00 WIB. Untuk uji lokal, gunakan:

```powershell
php artisan schedule:work
```

Periksa deployment:

```powershell
php artisan about
php artisan migrate:status
php artisan schedule:list
php artisan queue:failed
```

Setelah aktif, uji login administrator, SSO Google (bila dikonfigurasi), akses unduh bukti privat, pengiriman email, antrean email, dan akses melalui domain publik.

## 13. Perintah penting dan batas penggunaannya

| Perintah | Kegunaan | Catatan |
|---|---|---|
| `php artisan migrate --force` | Menjalankan migration yang belum diterapkan | Pilihan normal saat deployment produksi |
| `php artisan db:seed --class=RoleSeeder --force` | Menambahkan role aplikasi | Aman diulang; tidak membuat akun demo |
| `php artisan spmi:import-standards ... --dry-run` | Memeriksa workbook tanpa menyimpan perubahan | Jalankan sebelum impor sebenarnya |
| `php artisan spmi:secure-evidences --dry-run` | Memeriksa berkas bukti lama yang akan dipindahkan | Jalankan sebelum pemindahan |
| `php artisan migrate:fresh` | Menghapus seluruh tabel lalu membangun ulang basis data | Hanya untuk instalasi disposable/nonproduksi |
| `php artisan db:seed --class=SpmiSeeder` | Membuat data contoh dasar dan admin default | Development saja; admin `admin@spmi.test` menggunakan password `password` |
| `php artisan db:seed --class=DemoSeeder` | Membuat akun dan alur data demo lengkap | Development/staging tertutup saja; menggunakan password demo |
