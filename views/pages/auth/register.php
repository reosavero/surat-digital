<style>
/* Multi-step Registration Wizard Styles */
.reg-wizard-steps {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    position: relative;
}
.reg-wizard-steps::before {
    content: "";
    position: absolute;
    top: 18px;
    left: 16.67%;
    right: 16.67%;
    height: 3px;
    background: #E2E8F0;
    z-index: 1;
}
.reg-wizard-progress {
    position: absolute;
    top: 18px;
    left: 16.67%;
    height: 3px;
    background: #005BAC;
    z-index: 2;
    transition: width 0.35s ease;
    width: 0%;
    max-width: 66.67%;
}
.step-item {
    position: relative;
    z-index: 3;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    flex: 1;
    text-align: center;
}
.step-number {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #FFFFFF;
    border: 2px solid #CBD5E1;
    color: #64748B;
    font-weight: 700;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}
.step-item.active .step-number {
    border-color: #005BAC;
    background: #005BAC;
    color: #FFFFFF;
    box-shadow: 0 0 0 4px rgba(0, 91, 172, 0.15);
}
.step-item.completed .step-number {
    border-color: #16A34A;
    background: #16A34A;
    color: #FFFFFF;
}
.step-label {
    font-size: 11.5px;
    font-weight: 700;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.step-item.active .step-label {
    color: #005BAC;
}
.step-item.completed .step-label {
    color: #16A34A;
}

@media (max-width: 480px) {
    .step-number { width: 32px; height: 32px; font-size: 12px; }
    .step-label { font-size: 10px; letter-spacing: 0; }
    .reg-wizard-steps::before, .reg-wizard-progress { top: 16px; }
}

.step-card-content {
    display: none;
    animation: fadeInStep 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.step-card-content.active {
    display: block;
}

@keyframes fadeInStep {
    0% { opacity: 0; transform: translateY(10px); }
    100% { opacity: 1; transform: translateY(0); }
}

.otp-preview-banner {
    background: #EFF6FF;
    border: 1.5px dashed #3B82F6;
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 20px;
    text-align: center;
}
.otp-preview-code {
    font-family: 'Courier New', Courier, monospace;
    font-size: 24px;
    font-weight: 800;
    letter-spacing: 6px;
    color: #1D4ED8;
    background: #FFFFFF;
    padding: 6px 16px;
    border-radius: 8px;
    display: inline-block;
    border: 1px solid #BFDBFE;
    margin-top: 6px;
}
</style>

<div class="auth-card-header">
    <h3 class="auth-title">Pendaftaran Akun Pengguna</h3>
    <p class="auth-subtitle">Sistem Pengelolaan Surat Digital TVRI Jawa Timur</p>
</div>

<!-- Step Progress Indicator -->
<div class="reg-wizard-steps">
    <div class="reg-wizard-progress" id="wizardProgressLine"></div>

    <div class="step-item active" id="stepIndicator1">
        <div class="step-number" id="stepNum1">1</div>
        <div class="step-label">Data Diri</div>
    </div>
    <div class="step-item" id="stepIndicator2">
        <div class="step-number" id="stepNum2">2</div>
        <div class="step-label">Verifikasi OTP</div>
    </div>
    <div class="step-item" id="stepIndicator3">
        <div class="step-number" id="stepNum3">3</div>
        <div class="step-label">Password</div>
    </div>
</div>

<!-- Dynamic Alert Error Message -->
<div id="regAlertBox" style="display: none; background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 16px; border-radius: 12px; font-size: 13px; font-weight: 600; margin-bottom: 18px;"></div>

<!-- ========================================================================= -->
<!-- LANGKAH 1: INPUT DATA DIRI (Nama lengkap disertai gelar, NIP, Email) -->
<!-- ========================================================================= -->
<div class="step-card-content active" id="stepContent1">
    <form id="formStep1" onsubmit="handleStep1Submit(event)" autocomplete="off">
        <div class="form-group">
            <label class="form-label" for="regName">Nama Lengkap (disertai Gelar)</label>
            <div class="input-wrapper">
                <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <input type="text" id="regName" class="form-control" placeholder="Nama lengkap" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="regNip">NIP</label>
            <div class="input-wrapper">
                <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                    <line x1="7" y1="8" x2="17" y2="8"></line>
                    <line x1="7" y1="12" x2="13" y2="12"></line>
                </svg>
                <input type="text" id="regNip" class="form-control" placeholder="1234567890" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Bidang</label>
            <div class="input-wrapper" id="regBidangContainer" style="position: relative;">
                <input type="hidden" id="regDivisi" value="">

                <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 21h18"></path>
                    <path d="M5 21V7l8-4v18"></path>
                    <path d="M19 21V11l-6-3"></path>
                </svg>

                <button type="button" class="custom-select-trigger" id="regBidangDropdownTrigger" onclick="toggleRegBidangMenu(event)" style="width: 100%; display: flex; align-items: center; justify-content: space-between; height: 46px; padding: 12px 16px 12px 42px; background: #f9fafb; border: 1.5px solid #e5e7eb; border-radius: 12px; font-size: 14px; color: #1f2937; font-weight: 500; cursor: pointer; transition: all 0.2s ease; outline: none; position: relative;">
                    <span id="regBidangSelectedText" style="color: #9ca3af; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">-- Pilih Bidang --</span>
                    <i class="bi bi-chevron-down" id="regBidangChevronIcon" style="font-size: 13px; color: #6b7280; transition: transform 0.2s ease;"></i>
                </button>

                <!-- Custom Positioned Dropdown Menu for Selecting Bidang -->
                <div id="regBidangDropdownMenu" style="display: none; position: absolute; top: calc(100% + 6px); left: 0; width: 100%; z-index: 99999; background: #FFFFFF; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15), 0 4px 12px rgba(0,0,0,0.06); border: 1.5px solid #005BAC; padding: 6px;">
                    <div style="max-height: 230px; overflow-y: auto; padding-right: 2px;" id="regBidangOptionsList">
                        
                        <div class="custom-option-item" data-val="Tata Usaha (TU)" onclick="selectRegBidangOption('Tata Usaha (TU)')" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; cursor: pointer; transition: background 0.15s ease; margin-bottom: 2px;">
                            <span style="font-size: 13.5px; font-weight: 600; color: #1F2937;">Tata Usaha (TU)</span>
                            <i class="bi bi-check2 check-icon" style="display: none; color: #005BAC; font-size: 16px; font-weight: 800;"></i>
                        </div>

                        <div class="custom-option-item" data-val="Berita" onclick="selectRegBidangOption('Berita')" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; cursor: pointer; transition: background 0.15s ease; margin-bottom: 2px;">
                            <span style="font-size: 13.5px; font-weight: 600; color: #1F2937;">Berita</span>
                            <i class="bi bi-check2 check-icon" style="display: none; color: #005BAC; font-size: 16px; font-weight: 800;"></i>
                        </div>

                        <div class="custom-option-item" data-val="Program" onclick="selectRegBidangOption('Program')" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; cursor: pointer; transition: background 0.15s ease; margin-bottom: 2px;">
                            <span style="font-size: 13.5px; font-weight: 600; color: #1F2937;">Program</span>
                            <i class="bi bi-check2 check-icon" style="display: none; color: #005BAC; font-size: 16px; font-weight: 800;"></i>
                        </div>

                        <div class="custom-option-item" data-val="Konten Media Baru (KMB)" onclick="selectRegBidangOption('Konten Media Baru (KMB)')" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; cursor: pointer; transition: background 0.15s ease; margin-bottom: 2px;">
                            <span style="font-size: 13.5px; font-weight: 600; color: #1F2937;">Konten Media Baru (KMB)</span>
                            <i class="bi bi-check2 check-icon" style="display: none; color: #005BAC; font-size: 16px; font-weight: 800;"></i>
                        </div>

                        <div class="custom-option-item" data-val="Teknik" onclick="selectRegBidangOption('Teknik')" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; cursor: pointer; transition: background 0.15s ease; margin-bottom: 2px;">
                            <span style="font-size: 13.5px; font-weight: 600; color: #1F2937;">Teknik</span>
                            <i class="bi bi-check2 check-icon" style="display: none; color: #005BAC; font-size: 16px; font-weight: 800;"></i>
                        </div>

                        <div class="custom-option-item" data-val="Pengembangan dan Usaha (PU)" onclick="selectRegBidangOption('Pengembangan dan Usaha (PU)')" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; cursor: pointer; transition: background 0.15s ease; margin-bottom: 2px;">
                            <span style="font-size: 13.5px; font-weight: 600; color: #1F2937;">Pengembangan dan Usaha (PU)</span>
                            <i class="bi bi-check2 check-icon" style="display: none; color: #005BAC; font-size: 16px; font-weight: 800;"></i>
                        </div>

                        <div class="custom-option-item" data-val="Umum" onclick="selectRegBidangOption('Umum')" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; cursor: pointer; transition: background 0.15s ease; margin-bottom: 2px;">
                            <span style="font-size: 13.5px; font-weight: 600; color: #1F2937;">Umum</span>
                            <i class="bi bi-check2 check-icon" style="display: none; color: #005BAC; font-size: 16px; font-weight: 800;"></i>
                        </div>

                        <div class="custom-option-item" data-val="Keuangan" onclick="selectRegBidangOption('Keuangan')" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; cursor: pointer;">
                            <span style="font-size: 13.5px; font-weight: 600; color: #1F2937;">Keuangan</span>
                            <i class="bi bi-check2 check-icon" style="display: none; color: #005BAC; font-size: 16px; font-weight: 800;"></i>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="regEmail">Email</label>
            <div class="input-wrapper">
                <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
                <input type="email" id="regEmail" class="form-control" placeholder="email@gmail.com" required>
            </div>
        </div>

        <div style="display: flex; flex-direction: row; gap: 12px; margin-top: 24px;">
            <a href="<?= BASE_URL ?>/login" class="btn btn-secondary" style="text-align: center; flex: 1; justify-content: center;">
                Kembali ke Login
            </a>

            <button type="submit" class="btn btn-primary" id="btnStep1" style="flex: 1; justify-content: center;">
                <span class="btn-text">Selanjutnya <i class="bi bi-arrow-right"></i></span>
                <span class="btn-loader" style="display: none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="spin">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                    </svg> Memproses…
                </span>
            </button>
        </div>
    </form>
</div>

<!-- ========================================================================= -->
<!-- LANGKAH 2: VERIFIKASI KODE OTP -->
<!-- ========================================================================= -->
<div class="step-card-content" id="stepContent2">
    <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 14px; padding: 16px; margin-bottom: 22px; text-align: center;">
        <div style="font-size: 14.5px; color: #1E40AF; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
            <i class="bi bi-envelope-check-fill" style="font-size: 20px; color: #2563EB;"></i> Silakan Periksa Email Gmail Anda
        </div>
        <div style="font-size: 13px; color: #475569; margin-top: 6px; line-height: 1.45;">
            Kode OTP telah dikirimkan ke <strong id="targetEmailText" style="color: #0F172A;">email@domain.com</strong>.
        </div>
    </div>

    <form id="formStep2" onsubmit="handleStep2Submit(event)" autocomplete="off">
        <div class="form-group" style="text-align: center;">
            <label class="form-label" for="regOtp" style="text-align: center; display: block;">Masukkan Kode OTP</label>
            <div class="input-wrapper" style="max-width: 220px; width: 100%; margin: 0 auto; box-sizing: border-box;">
                <input type="text" id="regOtp" class="form-control" placeholder="* * * * * *" maxlength="6" inputmode="numeric" pattern="[0-9]*" required style="letter-spacing: 6px; font-weight: 800; text-align: center; font-size: 20px; padding: 10px 16px !important; width: 100%; box-sizing: border-box;">
            </div>
        </div>

        <div style="display: flex; flex-direction: row; gap: 12px; margin-top: 24px;">
            <button type="button" onclick="goToStep(1)" class="btn btn-secondary" style="text-align: center; flex: 1; justify-content: center;">
                <i class="bi bi-arrow-left"></i> Kembali
            </button>

            <button type="submit" class="btn btn-primary" id="btnStep2" style="flex: 1; justify-content: center;">
                <span class="btn-text"><i class="bi bi-shield-check"></i> Verifikasi OTP</span>
                <span class="btn-loader" style="display: none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="spin">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                    </svg> Memverifikasi…
                </span>
            </button>
        </div>
    </form>
</div>

<!-- ========================================================================= -->
<!-- LANGKAH 3: INPUT PASSWORD (TANPA USERNAME) -->
<!-- ========================================================================= -->
<div class="step-card-content" id="stepContent3">
    <form id="formStep3" onsubmit="handleStep3Submit(event)" autocomplete="off">
        <div class="form-group">
            <label class="form-label" for="regPassword">Password Akun</label>
            <div class="input-wrapper">
                <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <input type="password" id="regPassword" class="form-control" placeholder="Minimal 6 karakter" required>
                <button type="button" class="password-toggle" onclick="togglePassword(this)" aria-label="Toggle password" tabindex="-1">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="regConfirmPassword">Konfirmasi Password</label>
            <div class="input-wrapper">
                <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <input type="password" id="regConfirmPassword" class="form-control" placeholder="Ulangi password" required>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 24px;">
            <button type="submit" class="btn btn-primary" id="btnStep3" style="background-color: #7E22CE; border-color: #7E22CE;">
                <span class="btn-text"><i class="bi bi-check-lg"></i> Selesai</span>
                <span class="btn-loader" style="display: none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="spin">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                    </svg> Memproses…
                </span>
            </button>
        </div>
    </form>
</div>

<!-- ========================================================================= -->
<!-- VIEW SUCCESS: MENUNGGU KONFIRMASI DARI ADMIN (SUPERADMIN) -->
<!-- ========================================================================= -->
<div id="pendingApprovalContent" style="display: none; text-align: center; animation: fadeInStep 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;">
    <div style="width: 72px; height: 72px; border-radius: 50%; background: #FEF3C7; color: #D97706; display: inline-flex; align-items: center; justify-content: center; font-size: 36px; margin: 8px auto 20px auto; border: 2px solid #FCD34D;">
        <i class="bi bi-hourglass-split"></i>
    </div>
    
    <h3 style="margin: 0 0 12px 0; font-size: 20px; font-weight: 800; color: #92400E;">Menunggu Konfirmasi dari Admin</h3>
    <p style="margin: 0 0 24px 0; font-size: 14px; color: #64748B; line-height: 1.6;">
        Pendaftaran akun Anda telah berhasil diproses! Akun Anda saat ini <strong>menunggu persetujuan / konfirmasi dari Superadmin</strong> sebelum dapat digunakan untuk masuk.
    </p>

    <a href="<?= BASE_URL ?>/login" class="btn btn-primary" style="width: 100%; justify-content: center; background-color: #005BAC; border-color: #005BAC; font-weight: 700; padding: 12px 20px; font-size: 15px;">
        <i class="bi bi-box-arrow-in-right"></i> Ke Halaman Login
    </a>
</div>



<script>
let currentStep = 1;

function showRegAlert(msg) {
    const box = document.getElementById('regAlertBox');
    box.textContent = msg;
    box.style.display = 'block';
}

function hideRegAlert() {
    const box = document.getElementById('regAlertBox');
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

function goToStep(step) {
    hideRegAlert();
    currentStep = step;

    const progressLine = document.getElementById('wizardProgressLine');
    if (step === 1) progressLine.style.width = '0%';
    if (step === 2) progressLine.style.width = '33.33%';
    if (step === 3) progressLine.style.width = '66.67%';

    [1, 2, 3].forEach(s => {
        const ind = document.getElementById('stepIndicator' + s);
        const cnt = document.getElementById('stepContent' + s);
        const num = document.getElementById('stepNum' + s);

        if (s < step) {
            ind.className = 'step-item completed';
            num.innerHTML = '<i class="bi bi-check-lg"></i>';
        } else if (s === step) {
            ind.className = 'step-item active';
            num.textContent = s;
        } else {
            ind.className = 'step-item';
            num.textContent = s;
        }

        if (s === step) {
            cnt.classList.add('active');
        } else {
            cnt.classList.remove('active');
        }
    });
}

// Handler Step 1: Submit Data Diri -> Send OTP
function handleStep1Submit(e) {
    e.preventDefault();
    hideRegAlert();

    const name = document.getElementById('regName').value.trim();
    const nip = document.getElementById('regNip').value.trim();
    const divisi = document.getElementById('regDivisi').value.trim();
    const email = document.getElementById('regEmail').value.trim();

    if (!divisi) {
        showRegAlert('Harap pilih Bidang pekerjaan Anda.');
        return;
    }

    const btn = document.getElementById('btnStep1');
    const textEl = btn.querySelector('.btn-text');
    const loaderEl = btn.querySelector('.btn-loader');
    btn.disabled = true;
    textEl.style.display = 'none';
    loaderEl.style.display = 'inline-flex';

    const formData = new FormData();
    formData.append('name', name);
    formData.append('nip', nip);
    formData.append('divisi', divisi);
    formData.append('email', email);

    fetch('<?= BASE_URL ?>/register/send-otp', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        textEl.style.display = 'inline-flex';
        loaderEl.style.display = 'none';

        if (res.success) {
            document.getElementById('targetEmailText').textContent = res.email;
            document.getElementById('regOtp').value = ''; // User inputs OTP from their Gmail inbox
            goToStep(2);
        } else {
            showRegAlert(res.message || 'Gagal mengirim OTP.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        textEl.style.display = 'inline-flex';
        loaderEl.style.display = 'none';
        showRegAlert('Terjadi kesalahan jaringan.');
    });
}

// Handler Step 2: Submit Verification OTP
function handleStep2Submit(e) {
    e.preventDefault();
    hideRegAlert();

    const otp = document.getElementById('regOtp').value.trim();
    const btn = document.getElementById('btnStep2');
    const textEl = btn.querySelector('.btn-text');
    const loaderEl = btn.querySelector('.btn-loader');
    btn.disabled = true;
    textEl.style.display = 'none';
    loaderEl.style.display = 'inline-flex';

    const formData = new FormData();
    formData.append('otp', otp);

    fetch('<?= BASE_URL ?>/register/verify-otp', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        textEl.style.display = 'inline-flex';
        loaderEl.style.display = 'none';

        if (res.success) {
            goToStep(3);
            showSonnerToast('Email dan OTP Berhasil Diverifikasi!', 'success');
        } else {
            showRegAlert(res.message || 'Kode OTP tidak cocok.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        textEl.style.display = 'inline-flex';
        loaderEl.style.display = 'none';
        showRegAlert('Terjadi kesalahan jaringan.');
    });
}

// Handler Step 3: Input Password & Complete Registration
function handleStep3Submit(e) {
    e.preventDefault();
    hideRegAlert();

    const password = document.getElementById('regPassword').value;
    const confirmPassword = document.getElementById('regConfirmPassword').value;

    if (password !== confirmPassword) {
        showRegAlert('Konfirmasi password tidak cocok dengan password yang dimasukkan.');
        return;
    }

    const btn = document.getElementById('btnStep3');
    const textEl = btn.querySelector('.btn-text');
    const loaderEl = btn.querySelector('.btn-loader');
    btn.disabled = true;
    textEl.style.display = 'none';
    loaderEl.style.display = 'inline-flex';

    const formData = new FormData();
    formData.append('password', password);
    formData.append('confirm_password', confirmPassword);

    fetch('<?= BASE_URL ?>/register/complete', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        textEl.style.display = 'inline-flex';
        loaderEl.style.display = 'none';

        if (res.success) {
            const cardHeader = document.querySelector('.auth-card-header');
            const wizardSteps = document.querySelector('.reg-wizard-steps');
            const alertBox = document.getElementById('regAlertBox');
            const step3Content = document.getElementById('stepContent3');

            if (cardHeader) cardHeader.style.display = 'none';
            if (wizardSteps) wizardSteps.style.display = 'none';
            if (alertBox) alertBox.style.display = 'none';
            if (step3Content) step3Content.style.display = 'none';

            const approvalContent = document.getElementById('pendingApprovalContent');
            if (approvalContent) approvalContent.style.display = 'block';
        } else {
            showRegAlert(res.message || 'Gagal mendaftarkan akun.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        textEl.style.display = 'inline-flex';
        loaderEl.style.display = 'none';
        showRegAlert('Terjadi kesalahan jaringan.');
    });
}

function toggleRegBidangMenu(e) {
    if (e) e.stopPropagation();
    var menu = document.getElementById('regBidangDropdownMenu');
    var trigger = document.getElementById('regBidangDropdownTrigger');
    var chevron = document.getElementById('regBidangChevronIcon');
    var optionsList = document.getElementById('regBidangOptionsList');
    if (!menu || !trigger) return;

    var isOpen = menu.style.display === 'block';
    if (isOpen) {
        menu.style.display = 'none';
        if (chevron) chevron.style.transform = 'rotate(0deg)';
        if (trigger) trigger.style.borderColor = '#e5e7eb';
    } else {
        var rect = trigger.getBoundingClientRect();
        var spaceBelow = window.innerHeight - rect.bottom;

        if (spaceBelow < 220) {
            trigger.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        setTimeout(function() {
            var updatedRect = trigger.getBoundingClientRect();
            var availableSpace = Math.max(120, window.innerHeight - updatedRect.bottom - 20);
            if (optionsList) {
                optionsList.style.maxHeight = Math.min(200, availableSpace) + 'px';
            }

            menu.style.bottom = 'auto';
            menu.style.top = 'calc(100% + 6px)';
            menu.style.display = 'block';
            if (chevron) chevron.style.transform = 'rotate(180deg)';
            if (trigger) trigger.style.borderColor = '#005BAC';
        }, spaceBelow < 220 ? 120 : 0);
    }
}

function selectRegBidangOption(val) {
    var hiddenInput = document.getElementById('regDivisi');
    var textEl = document.getElementById('regBidangSelectedText');

    if (hiddenInput) hiddenInput.value = val;
    if (textEl) {
        textEl.textContent = val;
        textEl.style.color = '#1f2937';
        textEl.style.fontWeight = '500';
    }

    var items = document.querySelectorAll('#regBidangDropdownMenu .custom-option-item');
    items.forEach(function(item) {
        var isSelected = item.getAttribute('data-val') === val;
        if (isSelected) {
            item.style.background = '#EFF6FF';
            var check = item.querySelector('.check-icon');
            if (check) check.style.display = 'inline-block';
        } else {
            item.style.background = 'transparent';
            var check = item.querySelector('.check-icon');
            if (check) check.style.display = 'none';
        }
    });

    toggleRegBidangMenu(null);
}

document.addEventListener('click', function(e) {
    var container = document.getElementById('regBidangContainer');
    if (container && !container.contains(e.target)) {
        var menu = document.getElementById('regBidangDropdownMenu');
        var chevron = document.getElementById('regBidangChevronIcon');
        var trigger = document.getElementById('regBidangDropdownTrigger');
        if (menu && menu.style.display === 'block') {
            menu.style.display = 'none';
            if (chevron) chevron.style.transform = 'rotate(0deg)';
            if (trigger) trigger.style.borderColor = '#e5e7eb';
        }
    }
});
</script>
