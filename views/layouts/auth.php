<!DOCTYPE html>
<html lang="id" data-theme="<?= Session::get('theme', 'light') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#005BAC" />
    <meta name="csrf-token" content="<?= Session::csrfToken() ?>">
    <title><?= $title ?? 'Surat Digital - TVRI Jawa Timur' ?></title>
    <link rel="shortcut icon" href="<?= BASE_URL ?>/assets/img/favicon-tvri.svg">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        a[data-href], [data-href], .btn, .btn-btn, .btn-action, .nav-link-item, [role="button"] {
            cursor: pointer !important;
        }
    </style>
    <script src="<?= BASE_URL ?>/assets/js/hide-url-preview.js"></script>
    <style>
        * { box-sizing: border-box; }
        html { overflow: hidden; height: 100%; background-color: #fafafa; }
        body { margin: 0; padding: 0; overflow: auto; height: 100%; -webkit-overflow-scrolling: touch; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        
        /* Top Screen Dropdown Toast Container */
        .toast-container {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999999;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            pointer-events: none;
            max-width: 90vw;
        }
        .sonner-toast {
            pointer-events: auto;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: #ffffff;
            border: 1px solid #eaeaec;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15), 0 4px 12px rgba(0, 0, 0, 0.05);
            z-index: 9999999;
            width: auto;
            max-width: 500px;
            color: #1f2937;
            animation: sonnerDropDown 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .sonner-icon {
            font-size: 18px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
        }
        .sonner-toast-success .sonner-icon { color: #0f973d; }
        .sonner-toast-error .sonner-icon { color: #d92d2d; }
        .sonner-toast-warning .sonner-icon { color: #e68a00; }
        .sonner-toast-info .sonner-icon { color: #2563eb; }
        .sonner-content { flex: 1; min-width: 0; }
        .sonner-message { font-size: 13.5px; font-weight: 500; color: #1f2937; line-height: 1.4; }

        .sonner-toast.toast-out {
            animation: sonnerDropUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes sonnerDropDown {
            0% { opacity: 0; transform: translateY(-24px) scale(0.95); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes sonnerDropUp {
            0% { opacity: 1; transform: translateY(0) scale(1); }
            100% { opacity: 0; transform: translateY(-24px) scale(0.95); }
        }

        @media (max-width: 767px) {
            .toast-container {
                top: 14px;
                left: 12px;
                right: 12px;
                transform: none;
                width: auto;
                max-width: calc(100vw - 24px);
            }
        }

        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #003B71 0%, #005BAC 50%, #0077D6 100%);
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        .auth-wrapper {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 2;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 32px;
            animation: authHeaderFadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .auth-header-logo {
            display: block;
            margin: 0 auto 16px;
            width: 90px;
            height: auto;
            animation: logoPulseFloat 4s ease-in-out infinite;
        }
        .auth-header-title {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 4px 0;
            letter-spacing: -0.5px;
        }
        .auth-header-tagline {
            font-size: 14px;
            color: rgba(255,255,255,0.85);
            margin: 0 0 2px 0;
        }
        .auth-header-location {
            font-size: 13px;
            color: rgba(255,255,255,0.65);
            margin: 0;
            font-weight: 500;
        }
        .auth-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2), 0 8px 24px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            animation: authCardScaleUp 0.85s cubic-bezier(0.16, 1, 0.3, 1) 0.15s both;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .auth-card:hover {
            box-shadow: 0 24px 70px rgba(0,0,0,0.25), 0 10px 30px rgba(0,0,0,0.12);
        }
        .auth-card-header {
            margin-bottom: 24px;
            text-align: center;
        }
        .auth-title {
            font-size: 22px;
            font-weight: 700;
            color: #1a1d29;
            margin: 0 0 4px 0;
        }
        .auth-subtitle {
            font-size: 14px;
            color: #6b6b76;
            margin: 0;
        }
        .auth-link {
            color: #005BAC;
            font-size: 13px;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease, transform 0.2s ease;
            display: inline-block;
        }
        .auth-link:hover {
            text-decoration: underline;
            color: #003399;
            transform: translateY(-1px);
        }
        
        /* Floating Animated Bubbles */
        .auth-bubbles {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .auth-bubble {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.15), rgba(255,255,255,0.03));
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            will-change: transform;
        }
        .auth-bubble-1 { width: 400px; height: 400px; top: -100px; right: -100px; animation: floatOrb 16s ease-in-out infinite; }
        .auth-bubble-2 { width: 300px; height: 300px; bottom: -50px; left: -80px; animation: floatOrbAlt 20s ease-in-out infinite 1s; }
        .auth-bubble-3 { width: 200px; height: 200px; top: 30%; right: -60px; animation: floatOrb 14s ease-in-out infinite 2s; }
        .auth-bubble-4 { width: 150px; height: 150px; bottom: 20%; left: 10%; animation: floatOrbAlt 18s ease-in-out infinite 0.5s; }
        .auth-bubble-5 { width: 100px; height: 100px; top: 15%; left: 5%; animation: floatOrb 12s ease-in-out infinite 1.5s; }
        .auth-bubble-6 { width: 80px; height: 80px; bottom: 10%; right: 15%; animation: floatOrbAlt 15s ease-in-out infinite 2.5s; }
        .auth-bubble-7 { width: 60px; height: 60px; top: 40%; left: 30%; animation: floatOrb 10s ease-in-out infinite 3s; }
        
        @keyframes floatOrb {
            0%, 100% { transform: translateY(0) scale(1) rotate(0deg); }
            50% { transform: translateY(-30px) scale(1.08) rotate(12deg); }
        }
        @keyframes floatOrbAlt {
            0%, 100% { transform: translateY(0) scale(1) rotate(0deg); }
            50% { transform: translateY(25px) scale(0.92) rotate(-10deg); }
        }
        @keyframes logoPulseFloat {
            0%, 100% { transform: translateY(0) scale(1); filter: drop-shadow(0 4px 12px rgba(0,0,0,0.15)); }
            50% { transform: translateY(-7px) scale(1.04); filter: drop-shadow(0 12px 24px rgba(0,91,172,0.45)); }
        }
        @keyframes authHeaderFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes authCardScaleUp {
            from { opacity: 0; transform: translateY(35px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .input-wrapper { position: relative; display: flex; align-items: center; width: 100%; }
        .input-wrapper .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
            z-index: 2;
            transition: color 0.2s ease;
        }
        .input-wrapper:focus-within .input-icon { color: #005BAC; }
        .input-wrapper .form-control {
            width: 100%;
            height: 46px;
            padding: 12px 42px 12px 42px !important;
            font-size: 14px;
            font-family: inherit;
            color: #1f2937;
            background: #f9fafb;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .input-wrapper .form-control:focus {
            border-color: #005BAC;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(0, 91, 172, 0.1);
        }
        .input-wrapper .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            border-radius: 6px;
            transition: color 0.2s ease;
        }
        .input-wrapper .password-toggle:hover { color: #1f2937; }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #003399, #005BAC);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(0,51,153,0.35);
        }
        .btn-secondary {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background-color: #e2e8f0;
        }
        
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Mobile Breakpoint for Authentication */
        @media (max-width: 576px) {
            .auth-container {
                padding: 16px 12px;
                min-height: 100vh;
                height: auto;
                align-items: flex-start;
                padding-top: 24px;
                padding-bottom: 24px;
            }
            .auth-header {
                margin-bottom: 20px;
            }
            .auth-header-logo {
                width: 70px;
                margin-bottom: 10px;
            }
            .auth-header-title {
                font-size: 22px;
            }
            .auth-header-tagline {
                font-size: 13px;
            }
            .auth-header-location {
                font-size: 12px;
            }
            .auth-card {
                padding: 22px 18px !important;
                border-radius: 16px !important;
            }
            .auth-title {
                font-size: 19px;
            }
            .input-wrapper .form-control {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Screen Dropdown Toast Notifications -->
    <div class="toast-container" id="toastContainer">
        <?php $flashMessages = Session::getFlash(); ?>
        <?php if (!empty($flashMessages)): ?>
            <?php $_fi = 0; ?>
            <?php foreach ($flashMessages as $typeKey => $flash): 
                if (is_array($flash)) {
                    $rawType = $flash['type'] ?? (is_string($typeKey) ? $typeKey : 'info');
                    $msgText = $flash['message'] ?? '';
                } else {
                    $rawType = is_string($typeKey) ? $typeKey : 'info';
                    $msgText = (string)$flash;
                }
                $type = ($rawType === 'error' || $rawType === 'danger') ? 'error' : $rawType;
                $icon = $type === 'success' ? 'check-circle-fill' : ($type === 'error' ? 'x-circle-fill' : ($type === 'warning' ? 'exclamation-triangle-fill' : 'info-circle-fill'));
            ?>
                <div class="sonner-toast sonner-toast-<?= $type ?>" id="flashToast-<?= $_fi ?>">
                    <span class="sonner-icon">
                        <i class="bi bi-<?= $icon ?>"></i>
                    </span>
                    <div class="sonner-content">
                        <div class="sonner-message"><?= htmlspecialchars($msgText) ?></div>
                    </div>
                </div>
            <?php $_fi++; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="auth-container">
        <div class="auth-bubbles">
            <div class="auth-bubble auth-bubble-1"></div>
            <div class="auth-bubble auth-bubble-2"></div>
            <div class="auth-bubble auth-bubble-3"></div>
            <div class="auth-bubble auth-bubble-4"></div>
            <div class="auth-bubble auth-bubble-5"></div>
            <div class="auth-bubble auth-bubble-6"></div>
            <div class="auth-bubble auth-bubble-7"></div>
        </div>

        <div class="auth-wrapper">
            <div class="auth-header">
                <img src="<?= BASE_URL ?>/assets/img/tvri3.svg" alt="Logo TVRI" class="auth-header-logo">
                <h1 class="auth-header-title">Surat Digital</h1>
                <p class="auth-header-tagline">Website Sistem Manajemen Surat Digital</p>
                <p class="auth-header-location">TVRI Jawa Timur</p>
            </div>

            <div class="auth-card">
                <?= $content ?? '' ?>
            </div>
        </div>
    </div>

    <script>
        function dismissSonnerToastEl(toast) {
            if (!toast || !toast.parentNode) return;
            toast.classList.add('toast-out');
            setTimeout(function() {
                if (toast && toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }

        function showSonnerToast(message, type) {
            type = type || 'success';
            var container = document.getElementById('toastContainer');
            if (!container) return;

            var icon = 'check-circle-fill';
            if (type === 'error' || type === 'danger') icon = 'x-circle-fill';
            if (type === 'warning') icon = 'exclamation-triangle-fill';
            if (type === 'info') icon = 'info-circle-fill';

            var toast = document.createElement('div');
            toast.className = 'sonner-toast sonner-toast-' + type;
            toast.innerHTML = '<span class="sonner-icon"><i class="bi bi-' + icon + '"></i></span><div class="sonner-content"><div class="sonner-message">' + message + '</div></div>';

            container.appendChild(toast);

            setTimeout(function() {
                dismissSonnerToastEl(toast);
            }, 3500);
        }

        function initAuthToasts() {
            var toasts = document.querySelectorAll('.toast-container .sonner-toast');
            toasts.forEach(function(toast) {
                if (toast.dataset.dismissScheduled) return;
                toast.dataset.dismissScheduled = 'true';
                setTimeout(function() {
                    dismissSonnerToastEl(toast);
                }, 3500);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAuthToasts);
        } else {
            initAuthToasts();
        }
    </script>
</body>
</html>
