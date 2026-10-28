<?php

/**
 * User Controller - Surat Digital TVRI Jawa Timur
 * Exclusive for Superadmin to manage accounts, roles, and signatures
 */

class UserController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        if (!Session::isLoggedIn()) {
            $this->redirect('/login');
            exit;
        }

        $role = Session::get('user_role', '');
        if ($role !== 'superadmin') {
            Session::setFlash('error', 'Akses ditolak. Fitur Manajemen User hanya untuk Superadmin.');
            $this->redirect('/dashboard');
            exit;
        }

        $this->userModel = new User();
    }

    public function index(): void
    {
        try {
            Database::query("UPDATE users SET divisi = REPLACE(divisi, '&amp;', '&'), jabatan = REPLACE(jabatan, '&amp;', '&') WHERE divisi LIKE '%&amp;%' OR jabatan LIKE '%&amp;%'");
        } catch (Throwable $e) {
            // Ignore if error occurs
        }

        $users = $this->userModel->getAllUsers();
        $pendingUsers = $this->userModel->getPendingUsers();
        $singleRoleCounts = $this->userModel->getSingleRoleCounts();

        $existingKppDivs = Database::fetchAll("SELECT DISTINCT divisi FROM users WHERE role IN ('kpp_bidang', 'kpp') AND deleted_at IS NULL AND divisi IS NOT NULL AND divisi != ''");
        $kppDivisiList = array_values(array_unique(array_map(function($d) {
            return html_entity_decode(trim($d), ENT_QUOTES, 'UTF-8');
        }, array_column($existingKppDivs, 'divisi'))));

        $allKppBidangs = [
            'Program',
            'Berita',
            'Konten Media Baru (KMB)',
            'Teknik',
            'Pengembangan dan Usaha (PU)',
            'Umum',
            'Keuangan'
        ];

        $remainingKppBidangs = array_filter($allKppBidangs, function($b) use ($kppDivisiList) {
            return !in_array(trim($b), $kppDivisiList);
        });
        $kppAllExist = empty($remainingKppBidangs);

        $existingKapelDivs = Database::fetchAll("SELECT DISTINCT divisi FROM users WHERE role IN ('kapel_bidang', 'kapel') AND deleted_at IS NULL AND divisi IS NOT NULL AND divisi != ''");
        $kapelDivisiList = array_values(array_unique(array_map(function($d) {
            return html_entity_decode(trim($d), ENT_QUOTES, 'UTF-8');
        }, array_column($existingKapelDivs, 'divisi'))));

        $allKapelBidangs = [
            'Kapel Monev Program',
            'Kapel Pengelola SDM & RB',
            'Kapel Dokumentasi & Distribusi Audio Video',
            'Kapel Perlengkapan & Sarpras',
            'Kapel CA & OR',
            'Kapel Pengelola BMN',
            'Kapel Berita',
            'Kapel Humas & Protokol',
            'Kapel Monev Berita',
            'Kapel Konten, Kreatif Digital',
            'Kapel Penjualan & Pemasaran',
            'Kapel Analitik & Distribusi Konten',
            'Kapel IT',
            'PPK I',
            'Kapel Produksi & Penyiaran Teknik',
            'PPK II',
            'Kapel Produksi Program',
            'Kapel Transmisi',
            'Kapel Promo',
            'Kapel Evaluasi & Pelaporan Keuangan'
        ];

        $remainingKapelBidangs = array_filter($allKapelBidangs, function($b) use ($kapelDivisiList) {
            return !in_array(trim($b), $kapelDivisiList, true);
        });
        $kapelAllExist = empty($remainingKapelBidangs);

        $this->view('users/index', [
            'title' => 'Manajemen User & Role - Superadmin',
            'users' => $users,
            'pendingUsers' => $pendingUsers,
            'singleRoleCounts' => $singleRoleCounts,
            'kppDivisiList' => $kppDivisiList,
            'kppAllExist' => $kppAllExist,
            'kapelDivisiList' => $kapelDivisiList,
            'allKapelBidangs' => $allKapelBidangs,
            'kapelAllExist' => $kapelAllExist
        ]);
    }

    public function approve(int $id): void
    {
        $user = $this->userModel->findById($id);
        if ($user) {
            $this->userModel->approveUser($id);
            ActivityLog::log('APPROVE_USER', 'Superadmin menyetujui pendaftaran akun user: ' . $user['name'] . ' (' . $user['email'] . ')');

            // Send notification email to user's Gmail
            if (!empty($user['email'])) {
                $loginUrl = 'http://jatim.tvri.go.id/surat-digital';
                $subject = 'Persetujuan Akun Surat Digital TVRI Jawa Timur';
                $bodyHtml = "
                <div style=\"font-family: 'Inter', Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;\">
                    <div style=\"background: linear-gradient(135deg, #003B71, #005BAC); padding: 24px; text-align: center; color: #ffffff;\">
                        <h2 style=\"margin: 0; font-size: 20px; font-weight: 800;\">Surat Digital TVRI Jawa Timur</h2>
                        <p style=\"margin: 4px 0 0 0; font-size: 13px; opacity: 0.9;\">Persetujuan Akun Pengguna</p>
                    </div>
                    <div style=\"padding: 28px; color: #1e293b; font-size: 14px; line-height: 1.6;\">
                        <p style=\"margin-top: 0;\">Halo <strong>" . htmlspecialchars($user['name']) . "</strong>,</p>
                        <p>Selamat! Pendaftaran akun Anda pada sistem <strong>Surat Digital TVRI Jawa Timur</strong> telah <strong>DISETUJUI / DITERIMA</strong> oleh Superadmin.</p>
                        
                        <div style=\"background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin: 20px 0;\">
                            <div style=\"font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;\">Detail Akun Anda:</div>
                            <div style=\"margin-bottom: 4px;\"><strong>Nama:</strong> " . htmlspecialchars($user['name']) . "</div>
                            <div style=\"margin-bottom: 4px;\"><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</div>
                            <div style=\"margin-bottom: 4px;\"><strong>NIP / Username:</strong> " . htmlspecialchars($user['nip'] ?? $user['username'] ?? '-') . "</div>
                            <div><strong>Role Sistem:</strong> " . htmlspecialchars(User::getRoleLabel($user['role'] ?? 'admin')) . "</div>
                        </div>

                        <p>Anda sekarang dapat masuk ke dalam sistem menggunakan email/username dan password yang telah Anda daftarkan sebelumnya.</p>

                        <div style=\"text-align: center; margin: 28px 0;\">
                            <a href=\"{$loginUrl}\" target=\"_blank\" style=\"display: inline-block; background-color: #005BAC; color: #ffffff; padding: 13px 28px; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 12px rgba(0, 91, 172, 0.25);\">
                                🚀 Masuk ke Aplikasi Surat Digital
                            </a>
                        </div>
                        <p style=\"font-size: 12.5px; color: #64748b; text-align: center;\">Atau akses melalui URL berikut: <br><a href=\"{$loginUrl}\" style=\"color: #005BAC;\">{$loginUrl}</a></p>
                    </div>
                    <div style=\"background: #f1f5f9; padding: 16px 28px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;\">
                        Email ini dikirimkan secara otomatis oleh Sistem Surat Digital TVRI Jawa Timur.
                    </div>
                </div>
                ";

                Mailer::send($user['email'], $subject, $bodyHtml);
            }

            Session::setFlash('success', 'Akun ' . htmlspecialchars($user['name']) . ' berhasil disetujui dan email pemberitahuan telah dikirimkan!');
        } else {
            Session::setFlash('error', 'User tidak ditemukan.');
        }
        $this->redirect('/users');
    }

    public function reject(int $id): void
    {
        $user = $this->userModel->findById($id);
        if ($user) {
            $reason = Security::sanitize($_POST['rejection_reason'] ?? $_GET['reason'] ?? 'Persyaratan pendaftaran tidak memenuhi kriteria.');
            
            $this->userModel->rejectUser($id);
            ActivityLog::log('REJECT_USER', 'Superadmin menolak pendaftaran akun user: ' . $user['name'] . '. Alasan: ' . $reason);

            // Send notification email to user's Gmail
            if (!empty($user['email'])) {
                $subject = 'Pemberitahuan Status Pendaftaran Akun Surat Digital TVRI Jawa Timur';
                $bodyHtml = "
                <div style=\"font-family: 'Inter', Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;\">
                    <div style=\"background: linear-gradient(135deg, #991B1B, #DC2626); padding: 24px; text-align: center; color: #ffffff;\">
                        <h2 style=\"margin: 0; font-size: 20px; font-weight: 800;\">Surat Digital TVRI Jawa Timur</h2>
                        <p style=\"margin: 4px 0 0 0; font-size: 13px; opacity: 0.9;\">Pemberitahuan Pendaftaran Akun</p>
                    </div>
                    <div style=\"padding: 28px; color: #1e293b; font-size: 14px; line-height: 1.6;\">
                        <p style=\"margin-top: 0;\">Halo <strong>" . htmlspecialchars($user['name']) . "</strong>,</p>
                        <p>Mohon maaf, pendaftaran akun Anda untuk sistem <strong>Surat Digital TVRI Jawa Timur</strong> saat ini <strong>TIDAK DAPAT DISETUJUI / DITOLAK</strong> oleh Superadmin.</p>
                        
                        <div style=\"background-color: #FEF2F2; border-left: 4px solid #DC2626; padding: 16px; border-radius: 8px; margin: 20px 0; color: #991B1B;\">
                            <strong style=\"display: block; margin-bottom: 6px; font-size: 13px;\">Alasan Penolakan:</strong>
                            <div style=\"font-size: 14px; color: #7F1D1D; line-height: 1.5; font-style: italic;\">\"" . nl2br(htmlspecialchars($reason)) . "\"</div>
                        </div>

                        <p style=\"font-size: 13px; color: #64748b;\">Jika Anda memerlukan bantuan lebih lanjut atau merasa ini adalah kekeliruan, silakan hubungi Administrator / Superadmin TVRI Jawa Timur.</p>
                    </div>
                    <div style=\"background: #f1f5f9; padding: 16px 28px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;\">
                        Email ini dikirimkan secara otomatis oleh Sistem Surat Digital TVRI Jawa Timur.
                    </div>
                </div>
                ";

                Mailer::send($user['email'], $subject, $bodyHtml);
            }

            Session::setFlash('error', 'Pendaftaran akun ' . htmlspecialchars($user['name']) . ' ditolak dan email pemberitahuan telah dikirimkan.');
        } else {
            Session::setFlash('error', 'User tidak ditemukan.');
        }
        $this->redirect('/users');
    }

    public function store(): void
    {
        $name = Security::sanitize($_POST['name'] ?? '');
        $nip = Security::sanitize($_POST['nip'] ?? '');
        $divisi = trim(htmlspecialchars_decode(Security::sanitize($_POST['divisi'] ?? ''), ENT_QUOTES));
        $email = Security::sanitize($_POST['email'] ?? '');
        $role = Security::sanitize($_POST['role'] ?? 'admin');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($nip) || empty($email) || empty($role) || empty($password)) {
            Session::setFlash('error', 'Semua bidang wajib diisi.');
            $this->redirect('/users');
            return;
        }

        if (User::isSingleUserRole($role) && $this->userModel->countUsersByRole($role) >= 1) {
            Session::setFlash('error', 'Role ' . User::getRoleLabel($role) . ' sudah memiliki 1 user terdaftar. Sistem membatasi hanya 1 user untuk role ini. Silakan edit atau hapus akun yang ada terlebih dahulu.');
            $this->redirect('/users');
            return;
        }

        if (in_array($role, ['kpp_bidang', 'kpp', 'kapel_bidang', 'kapel']) && empty($divisi)) {
            Session::setFlash('error', 'Bidang wajib dipilih untuk pengguna KPP Bidang / Kapel Bidang.');
            $this->redirect('/users');
            return;
        }

        if (in_array($role, ['kapel_bidang', 'kapel'])) {
            $checkExistingKapel = Database::fetch("SELECT id FROM users WHERE role IN ('kapel_bidang', 'kapel') AND (divisi = ? OR divisi = ?) AND deleted_at IS NULL", [$divisi, htmlspecialchars($divisi, ENT_QUOTES, 'UTF-8')]);
            if ($checkExistingKapel) {
                Session::setFlash('error', 'Bidang "' . htmlspecialchars($divisi) . '" sudah memiliki 1 user Kapel terdaftar. Sistem membatasi hanya 1 user untuk setiap bidang Kapel.');
                $this->redirect('/users');
                return;
            }
        }
        if (in_array($role, ['kpp_bidang', 'kpp'])) {
            $checkExistingKpp = Database::fetch("SELECT id FROM users WHERE role IN ('kpp_bidang', 'kpp') AND (divisi = ? OR divisi = ?) AND deleted_at IS NULL", [$divisi, htmlspecialchars($divisi, ENT_QUOTES, 'UTF-8')]);
            if ($checkExistingKpp) {
                Session::setFlash('error', 'Bidang "' . htmlspecialchars($divisi) . '" sudah memiliki 1 user KPP terdaftar. Sistem membatasi hanya 1 user untuk setiap bidang KPP.');
                $this->redirect('/users');
                return;
            }
        }

        if (!empty($confirmPassword) && $password !== $confirmPassword) {
            Session::setFlash('error', 'Konfirmasi password tidak cocok dengan password yang dimasukkan.');
            $this->redirect('/users');
            return;
        }

        if (in_array($role, ['kpp_bidang', 'kpp'])) {
            $bidangVal = $divisi;
            $jabatanVal = str_starts_with($divisi, 'Ketua') ? $divisi : ('Ketua Perencana dan Pengendalian ' . $divisi);
        } elseif (in_array($role, ['kapel_bidang', 'kapel'])) {
            $bidangVal = $divisi;
            $jabatanVal = $divisi;
        } else {
            $bidangVal = null;
            $jabatanVal = User::getRoleLabel($role);
        }

        $baseUsername = User::generateAutoUsername($role, $bidangVal, $nip);
        $autoUsername = User::getUniqueUsername($baseUsername);

        try {
            $this->userModel->createUser([
                'name' => $name,
                'username' => $autoUsername,
                'nip' => $nip,
                'divisi' => $bidangVal,
                'jabatan' => $jabatanVal,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'status' => 'active'
            ]);
            ActivityLog::log('CREATE_USER', 'Superadmin membuat user baru: ' . $name . ' (' . User::getRoleLabel($role) . ')');
            Session::setFlash('success', 'User baru (' . $name . ') dengan role ' . User::getRoleLabel($role) . ' berhasil ditambahkan.');
        } catch (Throwable $e) {
            Session::setFlash('error', 'Gagal menambahkan user: NIP/Email/Username sudah digunakan.');
        }

        $this->redirect('/users');
    }

    public function update(int $id): void
    {
        $name = Security::sanitize($_POST['name'] ?? '');
        $email = Security::sanitize($_POST['email'] ?? '');
        $nip = Security::sanitize($_POST['nip'] ?? '');
        $role = Security::sanitize($_POST['role'] ?? 'admin');
        $divisi = trim(htmlspecialchars_decode(Security::sanitize($_POST['divisi'] ?? ''), ENT_QUOTES));
        $jabatan = trim(htmlspecialchars_decode(Security::sanitize($_POST['jabatan'] ?? ''), ENT_QUOTES));
        $password = $_POST['password'] ?? '';

        $existingUser = $this->userModel->findById($id);
        if ($existingUser && $existingUser['role'] !== $role && User::isSingleUserRole($role) && $this->userModel->countUsersByRole($role) >= 1) {
            Session::setFlash('error', 'Role ' . User::getRoleLabel($role) . ' sudah memiliki 1 user terdaftar. Silakan hapus atau edit akun yang ada terlebih dahulu.');
            $this->redirect('/users');
            return;
        }

        if (in_array($role, ['kapel_bidang', 'kapel'])) {
            $checkExistingKapel = Database::fetch("SELECT id FROM users WHERE role IN ('kapel_bidang', 'kapel') AND (divisi = ? OR divisi = ?) AND id != ? AND deleted_at IS NULL", [$divisi, htmlspecialchars($divisi, ENT_QUOTES, 'UTF-8'), $id]);
            if ($checkExistingKapel) {
                Session::setFlash('error', 'Bidang "' . htmlspecialchars($divisi) . '" sudah memiliki 1 user Kapel terdaftar. Silakan edit atau hapus akun yang ada terlebih dahulu.');
                $this->redirect('/users');
                return;
            }
        }

        if (in_array($role, ['kpp_bidang', 'kpp'])) {
            $bidangVal = $divisi;
            $jabatanVal = !empty($jabatan) ? $jabatan : (str_starts_with($divisi, 'Ketua') ? $divisi : ('Ketua Perencana dan Pengendalian ' . $divisi));
        } elseif (in_array($role, ['kapel_bidang', 'kapel'])) {
            $bidangVal = $divisi;
            $jabatanVal = !empty($jabatan) ? $jabatan : $divisi;
        } else {
            $bidangVal = null;
            $jabatanVal = !empty($jabatan) ? $jabatan : User::getRoleLabel($role);
        }

        $baseUsername = User::generateAutoUsername($role, $bidangVal, $nip);
        $autoUsername = User::getUniqueUsername($baseUsername, $id);

        $updateData = [
            'name' => $name,
            'email' => $email,
            'nip' => $nip,
            'username' => $autoUsername,
            'role' => $role,
            'jabatan' => $jabatanVal,
            'divisi' => $bidangVal
        ];

        if (!empty($password)) {
            $updateData['password'] = $password;
        }

        $this->userModel->updateUser($id, $updateData);
        ActivityLog::log('UPDATE_USER', 'Superadmin memperbarui data user: ' . $name . ' (ID: ' . $id . ')');
        Session::setFlash('success', 'Data user (' . htmlspecialchars($name) . ') berhasil diperbarui.');
        $this->redirect('/users');
    }

    public function delete(int $id): void
    {
        if ($id === (int)Session::get('user_id')) {
            Session::setFlash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            $this->redirect('/users');
            return;
        }

        $targetUser = $this->userModel->findById($id);
        $targetName = $targetUser['name'] ?? "ID #$id";

        $this->userModel->deleteUser($id);
        ActivityLog::log('DELETE_USER', 'Superadmin menghapus user: ' . $targetName . ' (ID: ' . $id . ')');
        Session::setFlash('success', 'User berhasil dihapus.');
        $this->redirect('/users');
    }
}
