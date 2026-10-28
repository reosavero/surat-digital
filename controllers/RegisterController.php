<?php

/**
 * Register Controller - Multi-step OTP Registration Workflow
 * Surat Digital TVRI Jawa Timur
 */

class RegisterController extends Controller
{
    public function showRegisterForm(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('/dashboard');
            return;
        }

        $this->layout = 'layouts/auth';
        $this->view('auth/register', [
            'title' => 'Buat Akun Baru - Surat Digital TVRI Jawa Timur'
        ]);
    }

    // Step 1: Process Step 1 inputs (Name, NIP, Divisi, Email) and Send OTP
    public function sendOtp(): void
    {
        header('Content-Type: application/json');

        if (Session::isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Anda sudah login.']);
            return;
        }

        $name = Security::sanitize($_POST['name'] ?? '');
        $nip = Security::sanitize($_POST['nip'] ?? '');
        $divisi = Security::sanitize($_POST['divisi'] ?? '');
        $email = Security::sanitize($_POST['email'] ?? '');

        if (empty($name) || empty($nip) || empty($divisi) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Harap lengkapi semua data diri (Nama, NIP, Bidang, Email).']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Format email tidak valid.']);
            return;
        }

        try {
            // Check if email or NIP already registered in DB (excluding rejected accounts)
            $existing = Database::fetch(
                "SELECT id FROM users WHERE (email = ? OR nip = ?) AND status != 'rejected' AND deleted_at IS NULL",
                [$email, $nip]
            );

            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'Email atau NIP sudah terdaftar di sistem.']);
                return;
            }

            // Generate 6-digit OTP code
            $otp = sprintf('%06d', mt_rand(100000, 999999));

            // Save registration state to session
            Session::set('reg_step1', [
                'name' => $name,
                'nip' => $nip,
                'divisi' => $divisi,
                'email' => $email,
                'otp' => $otp,
                'otp_verified' => false
            ]);

            // Attempt to send HTML email via SMTP Mailer (medsostvrijatim@gmail.com)
            $senderEmail = defined('SMTP_FROM') ? SMTP_FROM : 'medsostvrijatim@gmail.com';
            $subject = "Kode OTP Pendaftaran Akun Surat Digital TVRI Jatim";
            
            $bodyHtml = '
            <div style="font-family: Arial, sans-serif; max-width: 560px; margin: 0 auto; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 32px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
                <div style="text-align: center; margin-bottom: 24px;">
                    <h2 style="color: #005BAC; margin: 0; font-size: 22px; font-weight: 800;">Surat Digital TVRI Jawa Timur</h2>
                    <div style="font-size: 13px; color: #64748B; margin-top: 4px;">Persetujuan &amp; Verifikasi Otentikasi Akun</div>
                </div>
                <hr style="border: none; border-top: 1px solid #E2E8F0; margin-bottom: 24px;">
                <p style="font-size: 14px; color: #1E293B; margin-bottom: 16px;">Yth. <strong>' . htmlspecialchars($name) . '</strong>,</p>
                <p style="font-size: 14px; color: #334155; line-height: 1.6; margin-bottom: 24px;">
                    Berikut adalah Kode OTP (One-Time Password) untuk memverifikasi pendaftaran akun Surat Digital TVRI Jawa Timur Anda:
                </p>
                <div style="text-align: center; margin-bottom: 28px;">
                    <div style="display: inline-block; background: #EFF6FF; border: 2px dashed #2563EB; padding: 14px 32px; border-radius: 12px;">
                        <span style="font-family: monospace; font-size: 32px; font-weight: 800; letter-spacing: 8px; color: #1D4ED8;">' . $otp . '</span>
                    </div>
                </div>
                <p style="font-size: 13px; color: #64748B; line-height: 1.5; margin-bottom: 24px;">
                    Harap masukkan 6 digit kode OTP di atas pada halaman pendaftaran. Jangan berikan kode ini kepada siapapun demi keamanan akun Anda.
                </p>
                <hr style="border: none; border-top: 1px solid #E2E8F0; margin-bottom: 20px;">
                <div style="font-size: 11.5px; color: #94A3B8; text-align: center;">
                    Pesan otomatis dikirim oleh Sistem Informasi Surat Digital LPP TVRI Stasiun Jawa Timur.<br>
                    Email Pengirim: ' . htmlspecialchars($senderEmail) . '
                </div>
            </div>';

            Mailer::send($email, $subject, $bodyHtml);

            // Log OTP locally for audit/fallback logging
            $logLine = date('Y-m-d H:i:s') . " | OTP to {$email} from {$senderEmail} | Code: {$otp}\n";
            @file_put_contents(__DIR__ . '/../logs/otp.log', $logLine, FILE_APPEND);

            echo json_encode([
                'success' => true,
                'message' => "Kode OTP telah dikirimkan dari {$senderEmail} ke email {$email}.",
                'email' => $email,
                'sender' => $senderEmail
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
        }
    }

    // Step 2: Verify input OTP code
    public function verifyOtp(): void
    {
        header('Content-Type: application/json');

        $inputOtp = Security::sanitize($_POST['otp'] ?? '');
        $regData = Session::get('reg_step1', null);

        if (!$regData || empty($regData['otp'])) {
            echo json_encode(['success' => false, 'message' => 'Sesi pendaftaran tidak ditemukan. Harap ulangi langkah pertama.']);
            return;
        }

        if (empty($inputOtp)) {
            echo json_encode(['success' => false, 'message' => 'Harap masukkan 6 digit kode OTP.']);
            return;
        }

        if ($inputOtp !== $regData['otp']) {
            echo json_encode(['success' => false, 'message' => 'Kode OTP salah. Harap periksa kembali email Anda.']);
            return;
        }

        // Mark OTP verified in session
        $regData['otp_verified'] = true;
        Session::set('reg_step1', $regData);

        echo json_encode([
            'success' => true,
            'message' => 'Kode OTP berhasil diverifikasi! Silakan buat password Anda.'
        ]);
    }

    // Step 3: Input Password & Complete Registration
    public function complete(): void
    {
        header('Content-Type: application/json');

        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $regData = Session::get('reg_step1', null);

        if (!$regData || empty($regData['otp_verified']) || !$regData['otp_verified']) {
            echo json_encode(['success' => false, 'message' => 'Verifikasi OTP belum selesai. Harap lakukan verifikasi OTP terlebih dahulu.']);
            return;
        }

        if (empty($password) || empty($confirmPassword)) {
            echo json_encode(['success' => false, 'message' => 'Password dan Konfirmasi Password wajib diisi.']);
            return;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password minimal harus 6 karakter.']);
            return;
        }

        if ($password !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Konfirmasi password tidak cocok.']);
            return;
        }

        try {
            $userModel = new User();

            // Auto-generate username from email prefix or NIP
            $emailParts = explode('@', $regData['email']);
            $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $emailParts[0]));
            if (empty($baseUsername)) {
                $baseUsername = 'user_' . mt_rand(1000, 9999);
            }

            $username = $baseUsername;
            $counter = 1;
            while ($userModel->findByUsernameOrEmail($username)) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            // Create user account with status 'pending' (waiting superadmin approval)
            $userModel->createUser([
                'name' => $regData['name'],
                'username' => $username,
                'email' => $regData['email'],
                'password' => $password,
                'role' => 'admin',
                'jabatan' => $regData['divisi'],
                'nip' => $regData['nip'],
                'divisi' => $regData['divisi'],
                'status' => 'pending'
            ]);

            // Clear registration session data
            Session::set('reg_step1', null);

            echo json_encode([
                'success' => true,
                'message' => 'Akun berhasil dibuat! Menunggu konfirmasi dari admin (Superadmin).'
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal mendaftarkan akun: ' . $e->getMessage()]);
        }
    }
}
