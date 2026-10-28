<!-- Hero Card Banner Profil Pengguna -->
<div class="hero-card-variant hero-theme-navy" style="padding: 28px; margin-bottom: 24px;">
    <div class="welcome-badge-strip-centered" style="margin-bottom: 10px;">
        <i class="bi bi-person-badge-fill"></i> PROFIL AKUN PENGGUNA RESMI
    </div>
    <h1 style="font-size: 24px; font-weight: 800; color: #FFFFFF; margin-bottom: 6px;">
        <?= htmlspecialchars($user['name'] ?? Session::get('user_name')) ?>
    </h1>
    <p style="font-size: 13.5px; color: #93C5FD; max-width: 550px; margin: 0 auto;">
        Peran Pengguna: <strong><?= User::getRoleLabel($user['role'] ?? Session::get('user_role')) ?></strong> &bull; LPP TVRI Stasiun Jawa Timur
    </p>
</div>

<div style="max-width: 800px; margin: 0 auto;">
    <div class="action-card-variant">
        <div class="action-card-header-accent">
            <div class="action-card-icon-glow">
                <i class="bi bi-person-vcard-fill"></i>
            </div>
            <div>
                <h3 style="font-size: 16px; font-weight: 800; color: var(--text-heading); margin: 0;">Rincian Hak Akses & Akun</h3>
                <span style="font-size: 12px; color: var(--text-muted);">Informasi terdaftar dalam database sistem Surat Digital</span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
            <div style="background: #F8FAFC; padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px;">Nama Lengkap</div>
                <div style="font-size: 15px; font-weight: 700; color: var(--text-heading);"><?= htmlspecialchars($user['name'] ?? '-') ?></div>
            </div>

            <div style="background: #F8FAFC; padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px;">Username</div>
                <div style="font-size: 15px; font-weight: 700; color: var(--brand-primary); font-family: monospace;"><?= htmlspecialchars($user['username'] ?? '-') ?></div>
            </div>

            <div style="background: #F8FAFC; padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px;">Alamat Email</div>
                <div style="font-size: 14px; font-weight: 600; color: var(--text-heading);"><?= htmlspecialchars($user['email'] ?? '-') ?></div>
            </div>

            <div style="background: #F8FAFC; padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px;">Jabatan / Unit Kerja</div>
                <div style="font-size: 14px; font-weight: 600; color: var(--text-heading);"><?= htmlspecialchars($user['jabatan'] ?? '-') ?></div>
            </div>
        </div>

        <div style="background: #EFF6FF; border: 1px solid #BFDBFE; padding: 18px 22px; border-radius: var(--radius-md); display: flex; align-items: center; gap: 14px; margin-bottom: 24px;">
            <i class="bi bi-shield-lock-fill" style="font-size: 28px; color: var(--brand-primary);"></i>
            <div>
                <strong style="font-size: 14px; color: #1E3A8A;">Status Hak Akses: Active & Verifikasi Berhasil</strong><br>
                <span style="font-size: 12.5px; color: #1E40AF;">Anda memiliki kewenangan resmi sebagai <strong><?= User::getRoleLabel($user['role'] ?? Session::get('user_role')) ?></strong> dalam pengolahan alur surat digital.</span>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <a href="<?= BASE_URL ?>/dashboard" class="btn-btn btn-btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
            </a>
            <a href="<?= BASE_URL ?>/logout" class="btn-btn btn-btn-primary" style="background-color: #DC2626;">
                <i class="bi bi-box-arrow-right"></i> Keluar Akun
            </a>
        </div>
    </div>
</div>
