<div class="auth-card-header">
    <h3 class="auth-title">Masuk ke Akun</h3>
    <p class="auth-subtitle">Silakan masuk untuk melanjutkan ke Surat Digital</p>
</div>

<form action="<?= BASE_URL ?>/login" method="POST" id="loginForm" autocomplete="off" novalidate>
    <?= Session::csrfField() ?>

    <div class="form-group">
        <label class="form-label" for="loginUsername">Nama Lengkap / Email</label>
        <div class="input-wrapper">
            <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <input type="text" name="username" id="loginUsername" class="form-control" 
                   placeholder="Masukkan nama lengkap atau email" required autofocus>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="loginPassword">Password</label>
        <div class="input-wrapper">
            <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <input type="password" name="password" id="loginPassword" class="form-control" 
                   placeholder="Masukkan password" required>
            <button type="button" class="password-toggle" onclick="togglePassword(this)" 
                    aria-label="Toggle password visibility" tabindex="-1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </button>
        </div>
    </div>

    <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
        <span data-href="<?= BASE_URL ?>/forgot-password" role="button" class="auth-link" style="cursor: pointer;" onclick="window.location.href=this.getAttribute('data-href');">
            Lupa password?
        </span>
        <span data-href="<?= BASE_URL ?>/register" role="button" class="auth-link" style="cursor: pointer;" onclick="window.location.href=this.getAttribute('data-href');">
            Buat akun
        </span>
    </div>

    <button type="submit" class="btn btn-primary" id="loginSubmit">
        <span class="btn-text">Masuk</span>
        <span class="btn-loader" style="display: none;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="spin">
                <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
            </svg>
            Memproses…
        </span>
    </button>
</form>

<script>
function togglePassword(btn) {
    var wrapper = btn.closest('.input-wrapper');
    var input = wrapper.querySelector('input');
    var iconSvg = btn.querySelector('svg');
    if (input.type === 'password') {
        input.type = 'text';
        iconSvg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
    } else {
        input.type = 'password';
        iconSvg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    }
}

document.getElementById('loginForm').addEventListener('submit', function(e) {
    var btn = document.getElementById('loginSubmit');
    var textEl = btn.querySelector('.btn-text');
    var loaderEl = btn.querySelector('.btn-loader');
    btn.disabled = true;
    textEl.style.display = 'none';
    loaderEl.style.display = 'inline-flex';
});
</script>
