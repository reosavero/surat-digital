<?php

/**
 * Settings Controller - Surat Digital TVRI Jawa Timur
 * Superadmin panel for Google Drive API configuration
 */

class SettingsController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!Session::isLoggedIn()) {
            $this->redirect('/login');
            exit;
        }

        $role = Session::get('user_role', '');
        if ($role !== 'superadmin') {
            Session::setFlash('error', 'Akses ditolak. Fitur Pengaturan hanya untuk Superadmin.');
            $this->redirect('/dashboard');
            exit;
        }
    }

    public function gdrive(): void
    {
        $settings = Setting::getAll();

        $tokenStatus = [
            'connected' => false,
            'message' => 'Kredensial belum dikonfigurasi'
        ];

        $refreshToken = $settings['gdrive_refresh_token'] ?? '';
        $clientId = $settings['gdrive_client_id'] ?? '';

        if (!empty($refreshToken)) {
            $token = GoogleDriveService::getEffectiveAccessToken();
            if (!empty($token)) {
                $tokenStatus = [
                    'connected' => true,
                    'message' => 'Terhubung Aktif (Token Valid & Siap Menerima File PDF)'
                ];
            } else {
                $tokenStatus = [
                    'connected' => false,
                    'message' => 'Token Kedaluwarsa / Revoked (Google menonaktifkan token OAuth testing setelah 7 hari). Silakan perbarui Refresh Token di bawah.'
                ];
            }
        }

        $this->view('settings/gdrive', [
            'title' => 'Pengaturan Google Drive - Superadmin',
            'settings' => $settings,
            'tokenStatus' => $tokenStatus
        ]);
    }

    public function saveGdrive(): void
    {
        $folderId = Security::sanitize($_POST['gdrive_folder_id'] ?? '1q43jnW7Tw0ghLY7rrx-OPI3aTQAronrB');
        $ktuFolderId = Security::sanitize($_POST['gdrive_ktu_folder_id'] ?? '1m1SVmXboA73OFJKSK-BjfRtYXMdxlfm7');
        $kepstaFolderId = Security::sanitize($_POST['gdrive_kepsta_folder_id'] ?? '1q43jnW7Tw0ghLY7rrx-OPI3aTQAronrB');
        $clientId = Security::sanitize($_POST['gdrive_client_id'] ?? '');
        $clientSecret = Security::sanitize($_POST['gdrive_client_secret'] ?? '');
        $refreshToken = trim($_POST['gdrive_refresh_token'] ?? '');

        Setting::set('gdrive_folder_id', $folderId);
        Setting::set('gdrive_ktu_folder_id', $ktuFolderId);
        Setting::set('gdrive_kepsta_folder_id', $kepstaFolderId);
        Setting::set('gdrive_client_id', $clientId);
        Setting::set('gdrive_client_secret', $clientSecret);
        Setting::set('gdrive_refresh_token', $refreshToken);

        // Sync any pending surat uploads if token is now valid
        $syncResult = GoogleDriveService::syncPendingUploads();
        if (!empty($syncResult['synced']) && $syncResult['synced'] > 0) {
            Session::setFlash('success', 'Konfigurasi Google Drive berhasil disimpan! ' . $syncResult['message']);
        } else {
            Session::setFlash('success', 'Konfigurasi Google Drive berhasil disimpan!');
        }
        $this->redirect('/settings/gdrive');
    }
}
