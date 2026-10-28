<style>
/* Reset Password Wizard & Fullscreen Viewport Modal Styles */
.modal-backdrop-custom {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(15, 23, 42, 0.7) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    z-index: 9999999 !important;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    box-sizing: border-box;
    overflow-y: auto;
}

.modal-backdrop-custom.show {
    display: flex !important;
}

.modal-card-custom {
    background: #FFFFFF;
    border-radius: 20px;
    width: 100%;
    max-width: 440px;
    margin: auto;
    padding: 28px 24px;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
    border: 1px solid rgba(255, 255, 255, 0.6);
    position: relative;
    box-sizing: border-box;
    animation: modalScaleIn 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes modalScaleIn {
    0% { opacity: 0; transform: scale(0.92) translateY(12px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-close-btn {
    position: absolute;
    top: 14px;
    right: 16px;
    background: transparent;
    border: none;
    font-size: 16px;
    color: #94A3B8;
    cursor: pointer;
    padding: 8px;
    border-radius: 8px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close-btn:hover {
    color: #1E293B;
    background: #F1F5F9;
}

/* Modal Wizard Step Indicator (Mirroring Register Flow) */
.modal-wizard-steps {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
    position: relative;
    padding: 0 32px;
}

.modal-wizard-steps::before {
    content: "";
    position: absolute;
    top: 17px;
    left: 50px;
    right: 50px;
    height: 3px;
    background: #E2E8F0;
    z-index: 1;
}

.modal-wizard-progress {
    position: absolute;
    top: 17px;
    left: 50px;
    height: 3px;
    background: #005BAC;
    z-index: 2;
    transition: width 0.35s ease;
    width: 0%;
}

.modal-step-item {
    position: relative;
    z-index: 3;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    text-align: center;
}

.modal-step-number {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #FFFFFF;
    border: 2px solid #CBD5E1;
    color: #64748B;
    font-weight: 700;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}

.modal-step-item.active .modal-step-number {
    border-color: #005BAC;
    background: #005BAC;
    color: #FFFFFF;
    box-shadow: 0 0 0 4px rgba(0, 91, 172, 0.15);
}

.modal-step-item.completed .modal-step-number {
    border-color: #16A34A;
    background: #16A34A;
    color: #FFFFFF;
}

.modal-step-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.modal-step-item.active .modal-step-label {
    color: #005BAC;
}

.modal-step-item.completed .modal-step-label {
    color: #16A34A;
}

.modal-step-content {
    display: none;
    animation: fadeInModalStep 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.modal-step-content.active {
    display: block;
}

@keyframes fadeInModalStep {
    0% { opacity: 0; transform: translateY(8px); }
    100% { opacity: 1; transform: translateY(0); }
}
</style>

<div class="auth-card-header">
    <h3 class="auth-title">Lupa Password</h3>
    <p class="auth-subtitle">Masukkan alamat email Anda untuk menerima kode OTP reset password</p>
</div>

<!-- Dynamic Alert for Main Page -->
<div id="forgotAlertBox" style="display: none; background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 16px; border-radius: 12px; font-size: 13px; font-weight: 600; margin-bottom: 18px;"></div>

<!-- Main Email Input Form -->
<form id="forgotForm" onsubmit="handleForgotSubmit(event)" autocomplete="off" novalidate>
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
            <span class="btn-text"><i class="bi bi-send-fill" style="margin-right: 4px;"></i> Kirim Instruksi Reset</span>
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


<!-- ========================================================================= -->
<!-- POPUP MODAL: VERIFIKASI KODE OTP & INPUT PASSWORD BARU -->
<!-- ========================================================================= -->
<div id="otpResetModal" class="modal-backdrop-custom" onclick="handleBackdropClick(event)">
    <div class="modal-card-custom" onclick="event.stopPropagation()">
        <button type="button" class="modal-close-btn" onclick="closeResetModal()" title="Tutup Popup" aria-label="Tutup">
            <i class="bi bi-x-lg"></i>
        </button>

        <div style="text-align: center; margin-bottom: 20px;">
            <h4 style="margin: 0 0 4px 0; font-size: 19px; font-weight: 800; color: #005BAC;">
                Reset Password Akun
            </h4>
            <p style="margin: 0; font-size: 12.5px; color: #64748B;">
                Verifikasi identitas Anda untuk mengatur ulang kata sandi
            </p>
        </div>

        <!-- Progress Steps in Modal -->
        <div class="modal-wizard-steps" id="modalWizardSteps">
            <div class="modal-wizard-progress" id="modalWizardProgress"></div>

            <div class="modal-step-item active" id="modalStepInd1">
                <div class="modal-step-number" id="modalStepNum1">1</div>
                <div class="modal-step-label">Verifikasi OTP</div>
            </div>
            <div class="modal-step-item" id="modalStepInd2">
                <div class="modal-step-number" id="modalStepNum2">2</div>
                <div class="modal-step-label">Password Baru</div>
            </div>
        </div>

        <!-- Modal Dynamic Alert Box -->
        <div id="modalAlertBox" style="display: none; background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 600; margin-bottom: 16px; line-height: 1.4;"></div>

        <!-- ------------------------------------------------------------- -->
        <!-- POPUP LANGKAH 1: INPUT KODE OTP -->
        <!-- ------------------------------------------------------------- -->
        <div class="modal-step-content active" id="modalStepContent1">
            <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 12px; padding: 14px; margin-bottom: 18px; text-align: center;">
                <div style="font-size: 13.5px; color: #1E40AF; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="bi bi-envelope-check-fill" style="font-size: 18px; color: #2563EB;"></i> Periksa Email Anda
                </div>
                <div style="font-size: 12.5px; color: #475569; margin-top: 5px; line-height: 1.5; word-break: break-word; overflow-wrap: break-word;">
                    Kode OTP 6 digit telah dikirimkan ke <strong id="modalTargetEmail" style="color: #0F172A; font-weight: 700;"></strong>
                </div>
            </div>

            <form id="formModalOtp" onsubmit="handleModalOtpSubmit(event)" autocomplete="off">
                <div class="form-group" style="text-align: center; margin-bottom: 16px;">
                    <label class="form-label" for="modalOtpInput" style="text-align: center; display: block; font-size: 13px; margin-bottom: 8px;">Masukkan 6 Digit Kode OTP</label>
                    <div style="max-width: 240px; width: 100%; margin: 0 auto;">
                        <input type="text" id="modalOtpInput" placeholder="* * * * * *" maxlength="6" inputmode="numeric" pattern="[0-9]*" required style="letter-spacing: 8px !important; font-weight: 800 !important; text-align: center !important; font-size: 22px !important; padding: 10px 14px !important; width: 100% !important; height: 50px !important; border: 1.5px solid #CBD5E1; border-radius: 12px; background: #F8FAFC; outline: none; box-sizing: border-box !important; transition: border-color 0.2s, box-shadow 0.2s;">
                    </div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <span style="font-size: 12px; color: #64748B;">Tidak menerima kode?</span>
                    <button type="button" id="btnResendOtp" onclick="resendOtp()" style="background: none; border: none; padding: 0; font-size: 12px; font-weight: 700; color: #005BAC; cursor: pointer; text-decoration: underline;">
                        Kirim Ulang OTP
                    </button>
                </div>

                <button type="submit" class="btn btn-primary" id="btnModalVerifyOtp" style="width: 100%; justify-content: center;">
                    <span class="btn-text"><i class="bi bi-shield-check"></i> Verifikasi OTP</span>
                    <span class="btn-loader" style="display: none;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="spin">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                        </svg> Memverifikasi…
                    </span>
                </button>
            </form>
        </div>

        <!-- ------------------------------------------------------------- -->
        <!-- POPUP LANGKAH 2: INPUT PASSWORD BARU -->
        <!-- ------------------------------------------------------------- -->
        <div class="modal-step-content" id="modalStepContent2">
            <form id="formModalPassword" onsubmit="handleModalPasswordSubmit(event)" autocomplete="off">
                <div class="form-group">
                    <label class="form-label" for="modalNewPassword">Password Baru</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <input type="password" id="modalNewPassword" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword(this)" aria-label="Toggle password" tabindex="-1">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="modalNewPasswordConfirm">Konfirmasi Password Baru</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <input type="password" id="modalNewPasswordConfirm" class="form-control" placeholder="Ulangi password baru" required minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword(this)" aria-label="Toggle password" tabindex="-1">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn btn-primary" id="btnModalSavePassword" style="width: 100%; justify-content: center; background-color: #005BAC; border-color: #005BAC;">
                        <span class="btn-text"><i class="bi bi-check-lg"></i> Simpan Password Baru</span>
                        <span class="btn-loader" style="display: none;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="spin">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                            </svg> Menyimpan…
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <!-- ------------------------------------------------------------- -->
        <!-- POPUP LANGKAH 3: TAMPILAN BERHASIL (SUCCESS STATE) -->
        <!-- ------------------------------------------------------------- -->
        <div id="modalSuccessContent" style="display: none; text-align: center; padding: 10px 0;">
            <div style="width: 68px; height: 68px; border-radius: 50%; background: #DCFCE7; color: #16A34A; display: inline-flex; align-items: center; justify-content: center; font-size: 34px; margin-bottom: 18px; border: 2px solid #86EFAC;">
                <i class="bi bi-check-lg"></i>
            </div>
            <h4 style="margin: 0 0 8px 0; font-size: 19px; font-weight: 800; color: #14532D;">Password Berhasil Diubah!</h4>
            <p style="margin: 0 0 22px 0; font-size: 13.5px; color: #64748B; line-height: 1.5;">
                Password akun Anda telah berhasil diperbarui. Silakan masuk kembali dengan password baru Anda.
            </p>
            <a href="<?= BASE_URL ?>/login" class="btn btn-primary" style="width: 100%; justify-content: center; background-color: #005BAC; border-color: #005BAC; font-weight: 700; padding: 12px 20px; font-size: 14.5px;">
                <i class="bi bi-box-arrow-in-right"></i> Masuk Sekarang
            </a>
        </div>

    </div>
</div>

<script>
let resendTimer = null;
let resendCountdown = 0;

// Hoist modal to document.body so it isn't trapped or clipped by .auth-card
function hoistModalToBody() {
    var modal = document.getElementById('otpResetModal');
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
}
hoistModalToBody();
document.addEventListener('DOMContentLoaded', hoistModalToBody);

function showForgotAlert(msg) {
    const box = document.getElementById('forgotAlertBox');
    box.textContent = msg;
    box.style.display = 'block';
}

function hideForgotAlert() {
    const box = document.getElementById('forgotAlertBox');
    box.style.display = 'none';
}

function showModalAlert(msg) {
    const box = document.getElementById('modalAlertBox');
    box.textContent = msg;
    box.style.display = 'block';
}

function hideModalAlert() {
    const box = document.getElementById('modalAlertBox');
    box.style.display = 'none';
}

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

function openResetModal() {
    hoistModalToBody();
    const modal = document.getElementById('otpResetModal');
    if (!modal) return;
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeResetModal() {
    const modal = document.getElementById('otpResetModal');
    if (!modal) return;
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

function handleBackdropClick(e) {
    if (e.target === document.getElementById('otpResetModal')) {
        closeResetModal();
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeResetModal();
    }
});

function goToModalStep(step) {
    hideModalAlert();

    const progressLine = document.getElementById('modalWizardProgress');
    const stepInd1 = document.getElementById('modalStepInd1');
    const stepInd2 = document.getElementById('modalStepInd2');
    const num1 = document.getElementById('modalStepNum1');
    const num2 = document.getElementById('modalStepNum2');
    const content1 = document.getElementById('modalStepContent1');
    const content2 = document.getElementById('modalStepContent2');

    if (step === 1) {
        progressLine.style.width = '0%';
        stepInd1.className = 'modal-step-item active';
        num1.textContent = '1';
        stepInd2.className = 'modal-step-item';
        num2.textContent = '2';

        content1.classList.add('active');
        content2.classList.remove('active');
        setTimeout(() => document.getElementById('modalOtpInput')?.focus(), 250);
    } else if (step === 2) {
        progressLine.style.width = 'calc(100% - 100px)';
        stepInd1.className = 'modal-step-item completed';
        num1.innerHTML = '<i class="bi bi-check-lg"></i>';
        stepInd2.className = 'modal-step-item active';
        num2.textContent = '2';

        content1.classList.remove('active');
        content2.classList.add('active');
        setTimeout(() => document.getElementById('modalNewPassword')?.focus(), 250);
    }
}

function startResendCountdown(seconds = 30) {
    const btn = document.getElementById('btnResendOtp');
    if (!btn) return;

    if (resendTimer) clearInterval(resendTimer);
    resendCountdown = seconds;
    btn.disabled = true;
    btn.style.opacity = '0.6';
    btn.style.cursor = 'not-allowed';
    btn.textContent = `Kirim Ulang OTP (${resendCountdown}s)`;

    resendTimer = setInterval(() => {
        resendCountdown--;
        if (resendCountdown <= 0) {
            clearInterval(resendTimer);
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
            btn.textContent = 'Kirim Ulang OTP';
        } else {
            btn.textContent = `Kirim Ulang OTP (${resendCountdown}s)`;
        }
    }, 1000);
}

// Step 0: Input Email -> Send OTP & Open Popup Modal
function handleForgotSubmit(e) {
    e.preventDefault();
    hideForgotAlert();

    const emailInput = document.getElementById('forgotEmail');
    const email = emailInput.value.trim();

    if (!email) {
        showForgotAlert('Harap masukkan alamat email Anda.');
        return;
    }

    const btn = document.getElementById('forgotSubmit');
    const textEl = btn.querySelector('.btn-text');
    const loaderEl = btn.querySelector('.btn-loader');
    btn.disabled = true;
    textEl.style.display = 'none';
    loaderEl.style.display = 'inline-flex';

    const formData = new FormData();
    formData.append('email', email);

    fetch('<?= BASE_URL ?>/forgot-password/send-otp', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        textEl.style.display = 'inline-flex';
        loaderEl.style.display = 'none';

        if (res.success) {
            document.getElementById('modalTargetEmail').textContent = res.email || email;
            document.getElementById('modalOtpInput').value = '';
            
            // Reset modal state
            document.getElementById('modalWizardSteps').style.display = 'flex';
            document.getElementById('modalSuccessContent').style.display = 'none';
            goToModalStep(1);

            openResetModal();
            startResendCountdown(30);

            if (typeof showSonnerToast === 'function') {
                showSonnerToast('Kode OTP telah dikirimkan ke email Anda!', 'success');
            }
        } else {
            showForgotAlert(res.message || 'Gagal mengirimkan kode OTP.');
            if (typeof showSonnerToast === 'function') {
                showSonnerToast(res.message || 'Gagal mengirim kode OTP.', 'error');
            }
        }
    })
    .catch(err => {
        btn.disabled = false;
        textEl.style.display = 'inline-flex';
        loaderEl.style.display = 'none';
        showForgotAlert('Terjadi kesalahan koneksi jaringan.');
    });
}

// Step 1: Resend OTP
function resendOtp() {
    if (resendCountdown > 0) return;

    hideModalAlert();
    const email = document.getElementById('forgotEmail').value.trim();
    if (!email) return;

    const btn = document.getElementById('btnResendOtp');
    btn.textContent = 'Mengirim…';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('email', email);

    fetch('<?= BASE_URL ?>/forgot-password/send-otp', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            startResendCountdown(30);
            if (typeof showSonnerToast === 'function') {
                showSonnerToast('Kode OTP baru telah dikirim ke email Anda!', 'success');
            }
        } else {
            btn.disabled = false;
            btn.textContent = 'Kirim Ulang OTP';
            showModalAlert(res.message || 'Gagal mengirim ulang kode OTP.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.textContent = 'Kirim Ulang OTP';
        showModalAlert('Terjadi kesalahan koneksi saat mengirim ulang OTP.');
    });
}

// Step 2: Verify OTP
function handleModalOtpSubmit(e) {
    e.preventDefault();
    hideModalAlert();

    const otp = document.getElementById('modalOtpInput').value.trim();
    if (!otp) {
        showModalAlert('Harap masukkan 6 digit kode OTP.');
        return;
    }

    const btn = document.getElementById('btnModalVerifyOtp');
    const textEl = btn.querySelector('.btn-text');
    const loaderEl = btn.querySelector('.btn-loader');
    btn.disabled = true;
    textEl.style.display = 'none';
    loaderEl.style.display = 'inline-flex';

    const formData = new FormData();
    formData.append('otp', otp);

    fetch('<?= BASE_URL ?>/forgot-password/verify-otp', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        textEl.style.display = 'inline-flex';
        loaderEl.style.display = 'none';

        if (res.success) {
            goToModalStep(2);
            if (typeof showSonnerToast === 'function') {
                showSonnerToast('Kode OTP berhasil diverifikasi!', 'success');
            }
        } else {
            showModalAlert(res.message || 'Kode OTP tidak cocok.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        textEl.style.display = 'inline-flex';
        loaderEl.style.display = 'none';
        showModalAlert('Terjadi kesalahan koneksi jaringan.');
    });
}

// Step 3: Input New Password & Complete
function handleModalPasswordSubmit(e) {
    e.preventDefault();
    hideModalAlert();

    const password = document.getElementById('modalNewPassword').value;
    const confirmPassword = document.getElementById('modalNewPasswordConfirm').value;

    if (!password || !confirmPassword) {
        showModalAlert('Harap isi Password Baru dan Konfirmasi Password.');
        return;
    }

    if (password.length < 6) {
        showModalAlert('Password minimal harus 6 karakter.');
        return;
    }

    if (password !== confirmPassword) {
        showModalAlert('Konfirmasi password tidak cocok dengan password baru.');
        return;
    }

    const btn = document.getElementById('btnModalSavePassword');
    const textEl = btn.querySelector('.btn-text');
    const loaderEl = btn.querySelector('.btn-loader');
    btn.disabled = true;
    textEl.style.display = 'none';
    loaderEl.style.display = 'inline-flex';

    const formData = new FormData();
    formData.append('password', password);
    formData.append('confirm_password', confirmPassword);

    fetch('<?= BASE_URL ?>/forgot-password/reset-password', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        textEl.style.display = 'inline-flex';
        loaderEl.style.display = 'none';

        if (res.success) {
            // Hide wizard step headers and forms
            document.getElementById('modalWizardSteps').style.display = 'none';
            document.getElementById('modalStepContent1').classList.remove('active');
            document.getElementById('modalStepContent2').classList.remove('active');
            
            // Show Success view
            document.getElementById('modalSuccessContent').style.display = 'block';

            if (typeof showSonnerToast === 'function') {
                showSonnerToast('Password berhasil diubah!', 'success');
            }
        } else {
            showModalAlert(res.message || 'Gagal mengubah password.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        textEl.style.display = 'inline-flex';
        loaderEl.style.display = 'none';
        showModalAlert('Terjadi kesalahan jaringan.');
    });
}
</script>
