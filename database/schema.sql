-- Database Schema for Surat Digital TVRI Jawa Timur
-- Standalone Database: surat_digital

CREATE DATABASE IF NOT EXISTS `surat_digital` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `surat_digital`;

-- Table Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'kpp_bidang', 'tata_usaha', 'sekretaris', 'kepala_stasiun', 'superadmin') NOT NULL DEFAULT 'admin',
  `jabatan` VARCHAR(100) NULL,
  `nip` VARCHAR(50) NULL,
  `divisi` VARCHAR(100) NULL,
  `status` ENUM('active', 'pending', 'rejected') NOT NULL DEFAULT 'active',
  `signature` VARCHAR(255) NULL,
  `avatar` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table Surat
CREATE TABLE IF NOT EXISTS `surat` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode_surat` VARCHAR(50) NOT NULL UNIQUE,
  `type` ENUM('izin_prinsip', 'izin_produksi') NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `nomor_surat` VARCHAR(100) NULL,
  `perihal` VARCHAR(255) NOT NULL,
  `tanggal_surat` DATE NOT NULL,
  `lampiran` VARCHAR(100) NULL DEFAULT '1 (satu) Berkas',
  `kepada` VARCHAR(255) NOT NULL,
  `isi_surat` LONGTEXT NULL,
  `meta_data` JSON NULL,
  `status` ENUM('draft', 'submitted_to_kpp', 'kpp_signed', 'submitted_to_tu', 'tu_signed', 'sekre_approved', 'revisi', 'completed', 'rejected') NOT NULL DEFAULT 'draft',
  `kpp_user_id` INT UNSIGNED NULL,
  `kpp_signature` VARCHAR(255) NULL,
  `kpp_signed_at` DATETIME NULL,
  `tu_user_id` INT UNSIGNED NULL,
  `tu_signature` VARCHAR(255) NULL,
  `tu_signed_at` DATETIME NULL,
  `pdf_path` VARCHAR(255) NULL,
  `gdrive_file_id` VARCHAR(255) NULL,
  `gdrive_web_link` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_status` (`status`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table Disposisi
CREATE TABLE IF NOT EXISTS `disposisi` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `surat_id` INT UNSIGNED NOT NULL UNIQUE,
  `sekre_user_id` INT UNSIGNED NULL,
  `sifat_surat` VARCHAR(50) NULL DEFAULT NULL,
  `catatan_sekre` TEXT NULL,
  `sekre_approved_at` DATETIME NULL,
  `kepsta_user_id` INT UNSIGNED NULL,
  `checklist_json` JSON NULL,
  `catatan_kepsta` TEXT NULL,
  `kepsta_signature` VARCHAR(255) NULL,
  `kepsta_signed_at` DATETIME NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`surat_id`) REFERENCES `surat`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table Surat Logs (Audit & Tracking Timeline)
CREATE TABLE IF NOT EXISTS `surat_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `surat_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `status_to` VARCHAR(50) NOT NULL,
  `note` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_surat_id` (`surat_id`),
  FOREIGN KEY (`surat_id`) REFERENCES `surat`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table Settings
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(100) PRIMARY KEY,
  `setting_value` LONGTEXT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Dummy Users for All Roles
-- Passwords: admin123, kpp123, tu123, sekre123, kepsta123, superadmin123
INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `role`, `jabatan`, `nip`, `divisi`, `status`)
VALUES 
(1, 'Admin Pembuat Surat', 'admin', 'admin@tvrijatim.go.id', '$2y$10$tVVUtWVW.tote8ClkUi3..z.NVXeBE1ZDeFoNu.FHSHj/LBBZpOx.', 'admin', 'Staf Administrasi Surat', '199001012015011001', NULL, 'active'),
(2, 'Petugas Tata Usaha', 'tu', 'tu@tvrijatim.go.id', '$2y$10$AhZHG8pCFzGQmTpbtITn6O5ow6arhVH6ubq5MCgx6H26vtGpLSNy.', 'tata_usaha', 'Petugas Tata Usaha & Penomoran', '198612252011012016', 'Tata Usaha', 'active'),
(3, 'Sekretaris Stasiun', 'sekre', 'sekretaris@tvrijatim.go.id', '$2y$10$XHcIit4H3H8pn.JjFZX1rO.bS3c7aORTIlRUbyx.pFmlV80iEoJGO', 'sekretaris', 'Sekretaris LPP TVRI Jawa Timur', '198405102008022003', 'Sekretariat', 'active'),
(4, 'Kepala Stasiun TVRI Jatim', 'kepsta', 'kepsta@tvrijatim.go.id', '$2y$10$q42Z8bjlzzctjwMHHS7JSuGwDgYm9IsjqkGHJuM3qUsW6FL218Qyy', 'kepala_stasiun', 'Kepala Stasiun LPP TVRI Jawa Timur', '197503261994031001', 'Pimpinan', 'active'),
(5, 'Super Administrator', 'superadmin', 'superadmin@tvrijatim.go.id', '$2y$10$rQ12HVRITlRHA41Ft7rzNu4PhB5qNZ94GUKFgBEAsXBRSjB0cwUfC', 'superadmin', 'System Super Administrator', '199999999999999999', 'IT / Superadmin', 'active'),
(7, 'Ir. Sepna Yuhana', 'kpp_program', 'kpp.program@tvrijatim.go.id', '$2y$10$o2q55IZEOpLgSCDhrKTkJ.4tZNhHxXDP7zjcXuAPouVZEaSpd5jQu', 'kpp_bidang', 'Ketua Perencana dan Pengendalian Program', '196809161993032006', 'Program', 'active'),
(8, 'Drs. Bambang Hariyanto, M.Si', 'kpp_berita', 'kpp.berita@tvrijatim.go.id', '$2y$10$o2q55IZEOpLgSCDhrKTkJ.4tZNhHxXDP7zjcXuAPouVZEaSpd5jQu', 'kpp_bidang', 'Ketua Perencana dan Pengendalian Berita', '197204151997031003', 'Berita', 'active'),
(9, 'Ahmad Syahroni, ST', 'kpp_teknik', 'kpp.teknik@tvrijatim.go.id', '$2y$10$o2q55IZEOpLgSCDhrKTkJ.4tZNhHxXDP7zjcXuAPouVZEaSpd5jQu', 'kpp_bidang', 'Ketua Perencana dan Pengendalian Teknik', '197508201998031002', 'Teknik', 'active'),
(10, 'Dian Sastro, S.Sos', 'kpp_kmb', 'kpp.kmb@tvrijatim.go.id', '$2y$10$o2q55IZEOpLgSCDhrKTkJ.4tZNhHxXDP7zjcXuAPouVZEaSpd5jQu', 'kpp_bidang', 'Ketua Perencana dan Pengendalian Konten Media Baru (KMB)', '198506152009022004', 'Konten Media Baru (KMB)', 'active'),
(11, 'Eko Prasetyo, SE', 'kpp_pu', 'kpp.pu@tvrijatim.go.id', '$2y$10$o2q55IZEOpLgSCDhrKTkJ.4tZNhHxXDP7zjcXuAPouVZEaSpd5jQu', 'kpp_bidang', 'Ketua Perencana dan Pengendalian Pengembangan dan Usaha (PU)', '198709212011011005', 'Pengembangan dan Usaha (PU)', 'active'),
(12, 'Siti Rahmawati, S.AP', 'kpp_umum', 'kpp.umum@tvrijatim.go.id', '$2y$10$o2q55IZEOpLgSCDhrKTkJ.4tZNhHxXDP7zjcXuAPouVZEaSpd5jQu', 'kpp_bidang', 'Ketua Perencana dan Pengendalian Umum', '198302112008012006', 'Umum', 'active'),
(13, 'Tri Wahyuni, SE, Ak', 'kpp_keuangan', 'kpp.keuangan@tvrijatim.go.id', '$2y$10$o2q55IZEOpLgSCDhrKTkJ.4tZNhHxXDP7zjcXuAPouVZEaSpd5jQu', 'kpp_bidang', 'Ketua Perencana dan Pengendalian Keuangan', '198911052014022007', 'Keuangan', 'active'),
(14, 'Hendra Wijaya, S.H', 'kpp_tu', 'kpp.tu@tvrijatim.go.id', '$2y$10$o2q55IZEOpLgSCDhrKTkJ.4tZNhHxXDP7zjcXuAPouVZEaSpd5jQu', 'kpp_bidang', 'Ketua Perencana dan Pengendalian Tata Usaha (TU)', '198003182006041008', 'Tata Usaha (TU)', 'active')
ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `role` = VALUES(`role`), `name` = VALUES(`name`), `jabatan` = VALUES(`jabatan`), `nip` = VALUES(`nip`), `divisi` = VALUES(`divisi`), `status` = VALUES(`status`);
