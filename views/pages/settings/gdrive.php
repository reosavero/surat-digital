<!-- Centered Page Hero Card Banner -->
<div class="hero-card-variant hero-theme-emerald" style="padding: 28px; margin-bottom: 24px;">
    <div class="welcome-badge-strip-centered" style="margin-bottom: 10px; color: #A7F3D0;">
        <i class="bi bi-cloud-arrow-up-fill"></i> INTEGRASI GOOGLE DRIVE API
    </div>
    <h1 style="font-size: 22px; font-weight: 800; color: #FFFFFF; margin-bottom: 6px;">
        Konfigurasi Penyimpanan Otomatis Surat Digital
    </h1>
    <p style="font-size: 13.5px; color: #A7F3D0; max-width: 600px; margin: 0 auto;">
        Setiap surat yang disetujui Kepala Stasiun (Disposisi + Surat Utama) langsung tersimpan di Cloud Storage Google Drive.
    </p>
</div>

    <form action="<?= BASE_URL ?>/settings/gdrive" method="POST" class="card-panel-variant">
        <?= Session::csrfField() ?>

        <?php $isConnected = !empty($tokenStatus['connected']); ?>
        <?php if ($isConnected): ?>
        <div style="background: #ECFDF5; border: 1.5px solid #10B981; padding: 18px; border-radius: var(--radius-md); margin-bottom: 24px; font-size: 13.5px; color: #065F46; display: flex; align-items: flex-start; gap: 14px;">
            <i class="bi bi-cloud-check-fill" style="font-size: 28px; color: #059669; flex-shrink: 0;"></i>
            <div>
                <strong style="font-size: 14.5px; color: #047857;">Status Integrasi Google Drive: Aktif &amp; Terhubung</strong><br>
                Token OAuth valid. Berkas PDF surat dari TU dan Kepsta akan secara otomatis di-generate dan langsung terunggah ke folder Google Drive tujuan.
            </div>
        </div>
        <?php else: ?>
        <div style="background: #FEF2F2; border: 1.5px solid #F87171; padding: 18px; border-radius: var(--radius-md); margin-bottom: 24px; font-size: 13.5px; color: #991B1B; display: flex; align-items: flex-start; gap: 14px;">
            <i class="bi bi-exclamation-octagon-fill" style="font-size: 28px; color: #DC2626; flex-shrink: 0;"></i>
            <div>
                <strong style="font-size: 14.5px; color: #B91C1C;">Status Integrasi: Token Google Drive Kedaluwarsa (Expired / Revoked)</strong><br>
                <span><?= htmlspecialchars($tokenStatus['message'] ?? 'Token belum terhubung.') ?></span>
                <div style="margin-top: 10px; padding: 12px; background: #FFFFFF; border-radius: 8px; border: 1px solid #FECACA; font-size: 12.5px; color: #374151; line-height: 1.6;">
                    <strong>Cara Memperbarui Refresh Token (1 Menit):</strong>
                    <ol style="margin: 6px 0 0 18px; padding: 0;">
                        <li>Buka <a href="https://developers.google.com/oauthplayground" target="_blank" style="color: #2563EB; font-weight: 700; text-decoration: underline;">Google OAuth 2.0 Playground</a>.</li>
                        <li>Klik ikon <strong>Gear</strong> (kanan atas) &rarr; centang <em>"Use your own OAuth credentials"</em> &rarr; masukkan <strong>Client ID</strong> dan <strong>Client Secret</strong> di bawah.</li>
                        <li>Pada Step 1 di sebelah kiri, cari <strong>Drive API v3</strong> &rarr; centang <code>https://www.googleapis.com/auth/drive</code> &rarr; klik tombol <strong>Authorize APIs</strong>.</li>
                        <li>Pilih akun Google TVRI dan izinkan akses, lalu pada Step 2 klik <strong>Exchange authorization code for tokens</strong>.</li>
                        <li>Salin nilai <strong>Refresh token</strong> baru &rarr; tempel ke kolom <em>Refresh Token</em> di bawah &rarr; klik <strong>Simpan Konfigurasi</strong>.</li>
                    </ol>
                    <div style="margin-top: 8px; font-size: 11.5px; color: #047857; font-weight: 600;">
                        <i class="bi bi-lightbulb-fill" style="color: #D97706;"></i> Tips agar token tidak kedaluwarsa tiap 7 hari: Di Google Cloud Console &rarr; menu <em>OAuth consent screen</em> &rarr; klik tombol <strong>"Publish App"</strong> (ubah ke Production), atau gunakan <strong>Service Account JSON Key</strong>.
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group-block" style="margin-bottom: 20px;">
            <label class="form-label-custom">
                Folder ID Google Drive - User KTU (Tanpa Lembar Disposisi) <span style="color: var(--danger);">*</span>
            </label>
            <input type="text" name="gdrive_ktu_folder_id" value="<?= htmlspecialchars($settings['gdrive_ktu_folder_id'] ?? '1m1SVmXboA73OFJKSK-BjfRtYXMdxlfm7') ?>" required class="form-input-custom" style="font-family: monospace; font-weight: 700; color: #065F46; border-color: #059669;">
            <span style="font-size: 12px; color: var(--text-muted); margin-top: 4px; display: block;">File PDF tanpa lembar disposisi akan otomatis diunggah ke folder ini saat KTU mengklik button teruskan ke sekretaris.</span>
        </div>

        <div class="form-group-block" style="margin-bottom: 20px;">
            <label class="form-label-custom">
                Folder ID Google Drive - User Kepsta (Dengan Lembar Disposisi) <span style="color: var(--danger);">*</span>
            </label>
            <input type="text" name="gdrive_kepsta_folder_id" value="<?= htmlspecialchars($settings['gdrive_kepsta_folder_id'] ?? '1q43jnW7Tw0ghLY7rrx-OPI3aTQAronrB') ?>" required class="form-input-custom" style="font-family: monospace; font-weight: 700; color: #065F46; border-color: #059669;">
            <span style="font-size: 12px; color: var(--text-muted); margin-top: 4px; display: block;">File PDF gabungan lengkap dengan lembar disposisi akan otomatis diunggah ke folder ini saat Kepala Stasiun (Kepsta) memberikan persetujuan.</span>
        </div>

        <div style="background: #F8FAFC; padding: 18px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 24px;">
            <h3 style="font-size: 14px; font-weight: 800; color: var(--text-heading); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-key-fill" style="color: #D97706;"></i> Kredensial API Google Cloud Console (OAuth 2.0 / Service Account)
            </h3>

            <div class="form-group-block">
                <label class="form-label-custom" style="font-size: 12px;">Client ID</label>
                <input type="text" name="gdrive_client_id" value="<?= htmlspecialchars($settings['gdrive_client_id'] ?? '') ?>" placeholder="987654321-xxx.apps.googleusercontent.com" class="form-input-custom" style="font-family: monospace;">
            </div>

            <div class="form-group-block">
                <label class="form-label-custom" style="font-size: 12px;">Client Secret</label>
                <input type="password" name="gdrive_client_secret" value="<?= htmlspecialchars($settings['gdrive_client_secret'] ?? '') ?>" placeholder="GOCS-xxx-secret" class="form-input-custom">
            </div>

            <div class="form-group-block">
                <label class="form-label-custom" style="font-size: 12px;">Refresh Token / Service Account Key</label>
                <textarea name="gdrive_refresh_token" rows="3" class="form-input-custom" style="font-family: monospace;" placeholder="Paste Refresh Token atau Key JSON Google Cloud API di sini..."><?= htmlspecialchars($settings['gdrive_refresh_token'] ?? '') ?></textarea>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="submit" class="btn-btn btn-btn-primary" style="background-color: #059669;">
                <i class="bi bi-save-fill"></i> Simpan Konfigurasi Google Drive
            </button>
        </div>
    </form>
</div>
