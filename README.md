# Sistem Informasi Surat Digital TVRI Jawa Timur

Aplikasi web terintegrasi pengelolaan dan disposisi surat dinas (Surat Izin Prinsip & Surat Izin Produksi) berbasis native PHP dengan arsitektur MVC, dilengkapi tanda tangan digital (TTE), paraf otomatis, audit trail timeline, integrasi Google Drive, dan ekspor disposisi.

---

## 🚀 Fitur Utama

- **Otentikasi & Multi-Role**:
  - `Admin Pembuat Surat`: Pengajuan surat, unggah lampiran, monitor tracking.
  - `KPP Bidang`: Review permohonan, pembubuhan tanda tangan & paraf digital.
  - `Tata Usaha (TU)`: Penomoran surat resmi, verifikasi format, pembubuhan cap/TTD TU.
  - `Sekretaris`: Review kelayakan surat & pembuatan lembar disposisi.
  - `Kepala Stasiun`: Disposisi pimpinan (catatan, checklist petunjuk, TTD pimpinan).
  - `Super Administrator`: Manajemen pengguna, audit log, konfigurasi sistem & Google Drive.
- **Tanda Tangan & Paraf Digital**: Canvas drawing responsif untuk TTD langsung dari browser.
- **Tracking & Audit Log**: Riwayat dan timeline status surat secara real-time.
- **Pratinjau & Cetak PDF**: Preview dokumen instan dan generate PDF siap cetak.
- **Ekspor Disposisi**: Rekap data disposisi dalam format Excel & tampilan siap cetak.
- **Keamanan Data**: Sanitasi input, proteksi CSRF, session hardening, dan pemisahan kredensial lokal.

---

## 🛠️ Persyaratan Sistem

- PHP 8.1 atau lebih baru (ekstensi: `pdo_mysql`, `curl`, `openssl`, `mbstring`, `fileinfo`)
- MySQL / MariaDB 10.4+
- Web Server Apache (XAMPP / Nginx) dengan modul `mod_rewrite` aktif

---

## 💻 Panduan Instalasi

1. **Clone Repositori**:
   ```bash
   git clone https://github.com/reosavero/surat-digital.git
   ```

2. **Konfigurasi Lingkungan**:
   - Duplikat berkas konfigurasi template:
     - `config/app.local.php.example` -> `config/app.local.php`
     - `config/database.local.php.example` -> `config/database.local.php`
   - Sesuaikan kredensial database dan SMTP pada berkas `.local.php` jika diperlukan.

3. **Migrasi Database**:
   - Buat database `surat_digital` pada MySQL.
   - Import skema dari `database/schema.sql` atau jalankan via CLI:
     ```bash
     php database/migrate.php
     ```
   - (Opsional) Jalankan seeding data awal:
     ```bash
     php database/seed_kpp_dummy_users.php
     php database/seed_sample.php
     ```

4. **Jalankan Aplikasi**:
   - Akses via browser: `http://localhost/surat-digital`

---

## 🔒 Keamanan & Privasi

- Seluruh berkas unggahan pengguna (`uploads/`), log transaksi (`logs/`), dan kredensial lokal (`config/*.local.php`, `.env`) telah diatur pada `.gitignore` agar tidak bocor ke publik.
- Berkas template konfigurasi disediakan dengan ekstensi `.example`.

---

## 📄 Lisensi

Dikembangkan untuk kebutuhan internal TVRI Stasiun Jawa Timur.
