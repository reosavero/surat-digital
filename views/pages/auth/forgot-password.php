<div class="auth-card-header">
    <h3 class="auth-title">Lupa Password</h3>
    <p class="auth-subtitle">Masukkan alamat email Anda untuk melakukan reset password</p>
</div>

<form action="<?= BASE_URL ?>/forgot-password" method="POST" id="forgotForm" autocomplete="off" novalidate>
    <?= Session::csrfField() ?>

    <div class="form-group">
        <label class="form-label" for="forgotEmail">Alamat Email</label>
        <div class="input-wrapper">
            <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
            <input type="email" name="email" id="forgotEmail" class="form-control" placeholder="Masukkan alamat email terdaftar" required autofocus>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 24px;">
        <button type="submit" class="btn btn-primary" id="forgotSubmit">
            <span class="btn-text">Kirim Instruksi Reset</span>
            <span class="btn-loader" style="display: none;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="spin">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                </svg> Memproses…
            </span>
        </button>

        <a href="<?= BASE_URL ?>/login" class="btn btn-secondary" style="text-align: center;">
            Kembali ke Login
        </a>
    </div>
</form>

<script>
document.getElementById('forgotForm').addEventListener('submit', function() {
    var btn = document.getElementById('forgotSubmit');
    btn.querySelector('.btn-text').style.display = 'none';
    btn.querySelector('.btn-loader').style.display = 'inline-flex';
});
</script>
