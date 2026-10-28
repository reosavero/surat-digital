<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Surat Digital - TVRI Jawa Timur' ?></title>
    <link rel="shortcut icon" href="<?= BASE_URL ?>/assets/img/favicon-tvri.svg">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/flatpickr/flatpickr.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/calendar-custom.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
    <style>
        a[data-href], [data-href], .btn, .btn-btn, .btn-action, .nav-link-item, [role="button"] {
            cursor: pointer !important;
        }
    </style>
    <script src="<?= BASE_URL ?>/assets/js/hide-url-preview.js"></script>
</head>
<body>
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Left Sidebar Executive Navigation -->
    <aside class="app-sidebar" id="appSidebar">
        <?php 
        $userRole = Session::get('user_role', 'admin');
        $userName = Session::get('user_name', 'User');
        $userInitials = strtoupper(substr($userName, 0, 1));
        $isKapelRole = in_array($userRole, ['kapel_bidang', 'kapel']);
        $isSuperadminRole = ($userRole === 'superadmin');

        // Determine normalized route path (excluding project subfolder name like /surat-digital)
        $reqUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptDir !== '/' && $scriptDir !== '\\' && str_starts_with($reqUri, $scriptDir)) {
            $reqUri = substr($reqUri, strlen($scriptDir));
        }
        $currentPath = '/' . trim($reqUri, '/');

        $isEditingSurat = !empty($_GET['id']) && (int)$_GET['id'] > 0;
        $isCreatePage = in_array($currentPath, ['/surat-prinsip', '/surat-produksi', '/surat-nota-dinas', '/surat/create-prinsip', '/surat/create-produksi', '/surat/create-nota-dinas']);
        $isProcessPage = str_contains($currentPath, 'process');

        // Calculate active state for History, Revisi, Upload Lampiran, Disposisi & Excel Disposisi pages or revision edit mode
        $isUploadLampiranActive = str_starts_with($currentPath, '/surat/upload-lampiran');
        $isExcelDisposisiActive = str_starts_with($currentPath, '/excel-disposisi');
        $isHistoryActive = str_starts_with($currentPath, '/surat/history') || $currentPath === '/history';
        $isRevisiActive = str_starts_with($currentPath, '/surat/revisi') || $currentPath === '/revisi' || ($isCreatePage && $isEditingSurat);
        $isDisposisiActive = str_starts_with($currentPath, '/disposisi') || str_contains($currentPath, 'disposisi-process');

        // Calculate Dashboard & Surat active states
        $isDash1Active = ($currentPath === '/dashboard' || $currentPath === '/' || $isProcessPage || ($isCreatePage && !$isEditingSurat)) && !$isRevisiActive && !$isHistoryActive && !$isUploadLampiranActive && !$isDisposisiActive && !$isExcelDisposisiActive;
        $isSuratActive = (($currentPath === '/surat' || str_starts_with($currentPath, '/surat/')) && !str_starts_with($currentPath, '/surat/revisi') && !str_starts_with($currentPath, '/surat/history') && !str_starts_with($currentPath, '/surat/upload-lampiran') && !$isProcessPage && !$isRevisiActive && !$isHistoryActive && !$isUploadLampiranActive && !$isDisposisiActive && !$isExcelDisposisiActive);


        // Calculate revision count for sidebar badge
        $sidebarRevisiCount = 0;
        if (class_exists('Surat')) {
            $suratModelNav = new Surat();
            $sidebarRevisiStats = $suratModelNav->getStatsForRole($userRole, (int)Session::get('user_id'));
            $sidebarRevisiCount = $sidebarRevisiStats['revisi'] ?? 0;
        }

        // Calculate disposisi count for TU/KPP/Kapel sidebar badge
        $sidebarDisposisiCount = 0;
        if (in_array($userRole, ['tata_usaha', 'tu', 'ktu', 'kpp_bidang', 'kpp', 'kapel_bidang', 'kapel'])) {
            if (class_exists('Disposisi')) {
                $dispoModelNav = new Disposisi();
                $dispoListNav = $dispoModelNav->getDisposisiForUser((int)Session::get('user_id'));
                $sidebarDisposisiCount = count($dispoListNav);
            }
        }
        ?>

        <!-- Brand Header -->
        <a href="<?= BASE_URL ?><?= $isSuperadminRole ? '/users' : ($isKapelRole ? '/disposisi' : '/dashboard') ?>" class="sidebar-header" style="text-decoration: none;">
            <img src="<?= BASE_URL ?>/assets/img/tvri3.svg" alt="TVRI Logo">
            <div>
                <div class="sidebar-brand-title">SURAT DIGITAL</div>
                <div class="sidebar-brand-sub">LPP TVRI JAWA TIMUR</div>
            </div>
        </a>

        <!-- Sidebar Navigation Menu -->
        <nav class="sidebar-nav">
            <?php if ($isKapelRole): ?>
            <a href="<?= BASE_URL ?>/disposisi" class="nav-link-item <?= $isDisposisiActive ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-arrow-down-fill" style="color: #0284C7;"></i> Disposisi
            </a>
            <?php elseif (!$isSuperadminRole): ?>
            <a href="<?= BASE_URL ?>/dashboard" class="nav-link-item <?= $isDash1Active ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            <?php endif; ?>

            <?php if (in_array($userRole, ['kpp_bidang', 'kpp'])): ?>
            <a href="<?= BASE_URL ?>/disposisi" class="nav-link-item <?= $isDisposisiActive ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-arrow-down-fill" style="color: #0284C7;"></i> Disposisi
            </a>
            <?php endif; ?>

            <?php if (in_array($userRole, ['tata_usaha', 'tu', 'ktu'])): ?>
            <a href="<?= BASE_URL ?>/disposisi" class="nav-link-item <?= $isDisposisiActive ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-arrow-down-fill" style="color: #0284C7;"></i> Disposisi
                <?php if ($sidebarDisposisiCount > 0): ?>
                    <span style="background: #0284C7; color: #FFFFFF; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 20px; margin-left: auto; display: inline-flex; align-items: center; justify-content: center; min-width: 20px;">
                        <?= $sidebarDisposisiCount ?>
                    </span>
                <?php endif; ?>
            </a>
            <?php endif; ?>

            <?php if (in_array($userRole, ['sekretaris', 'sekre'])): ?>
            <a href="<?= BASE_URL ?>/surat/upload-lampiran" class="nav-link-item <?= $isUploadLampiranActive ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-arrow-up-fill" style="color: #D97706;"></i> Upload Lampiran
            </a>
            <a href="<?= BASE_URL ?>/excel-disposisi" class="nav-link-item <?= $isExcelDisposisiActive ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-excel-fill" style="color: #10B981;"></i> Kelola Excel Disposisi
            </a>
            <?php endif; ?>



            <?php if ($userRole === 'admin'): ?>
            <a href="<?= BASE_URL ?>/surat/revisi" class="nav-link-item <?= $isRevisiActive ? 'active' : '' ?>">
                <i class="bi bi-arrow-counterclockwise" style="color: #EA580C;"></i> Revisi
                <?php if ($sidebarRevisiCount > 0): ?>
                    <span style="background: #EF4444; color: #FFFFFF; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 20px; margin-left: auto; display: inline-flex; align-items: center; justify-content: center; min-width: 20px;">
                        <?= $sidebarRevisiCount ?>
                    </span>
                <?php endif; ?>
            </a>
            <?php endif; ?>

            <?php if ($userRole === 'superadmin'): ?>
            <a href="<?= BASE_URL ?>/users" class="nav-link-item <?= str_starts_with($currentPath, '/users') ? 'active' : '' ?>">
                <i class="bi bi-people-fill" style="color: #DB2777;"></i> Manajemen User & Role
            </a>
            <a href="<?= BASE_URL ?>/activity-logs" class="nav-link-item <?= str_starts_with($currentPath, '/activity-logs') ? 'active' : '' ?>">
                <i class="bi bi-journal-text" style="color: #0284C7;"></i> Log Aktivitas Sistem
            </a>
            <?php endif; ?>

            <?php if (!$isKapelRole): ?>
            <a href="<?= BASE_URL ?>/surat" class="nav-link-item <?= $isSuratActive ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text-fill"></i> Surat
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <!-- Authenticated User Profile Display (Non-clickable) -->
            <div class="sidebar-user-card">
                <div class="user-avatar-badge"><?= $userInitials ?></div>
                <div class="user-meta-info">
                    <span class="user-display-name"><?= htmlspecialchars($userName) ?></span>
                    <span class="user-role-label"><i class="bi bi-shield-check"></i> <?= User::getRoleLabel($userRole) ?></span>
                </div>
            </div>

            <!-- Dividing Line Between Role Button & Logout Button -->
            <div class="sidebar-footer-divider"></div>

            <a href="<?= BASE_URL ?>/logout" class="btn-sidebar-logout">
                <i class="bi bi-box-arrow-right"></i> Keluar Akun
            </a>
        </div>
    </aside>

    <!-- Right Workspace Area -->
    <div class="app-main">
        <!-- Top Navbar -->
        <header class="app-topbar">
            <div class="topbar-left-group">
                <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" aria-label="Buka Menu Navigasi">
                    <i class="bi bi-list"></i>
                </button>
                <div class="topbar-title">
                    <i class="bi bi-building-fill-gear" style="color: var(--brand-primary); flex-shrink: 0;"></i>
                    <span class="topbar-title-text">Surat Digital TVRI Jawa Timur</span>
                </div>
            </div>
        </header>

        <style>
        /* Top Screen Dropdown Toast Container (Positioned Safely Below Topbar Navbar) */
        .toast-container {
            position: fixed;
            top: 72px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 99999999;
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
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15), 0 4px 12px rgba(0, 0, 0, 0.05);
            z-index: 99999999;
            width: auto;
            max-width: 550px;
            color: #1f2937;
            animation: sonnerDropDown 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            cursor: pointer;
            transition: transform 0.15s ease, opacity 0.15s ease;
        }
        .sonner-toast:hover {
            transform: scale(1.02);
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
        .sonner-message { font-size: 13.5px; font-weight: 600; color: #1f2937; line-height: 1.4; }

        .sonner-toast.toast-out {
            animation: sonnerDropUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes sonnerDropDown {
            0% { opacity: 0; transform: translateY(-16px) scale(0.96); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes sonnerDropUp {
            0% { opacity: 1; transform: translateY(0) scale(1); }
            100% { opacity: 0; transform: translateY(-16px) scale(0.96); }
        }

        @media (max-width: 767px) {
            .toast-container {
                top: 68px;
                left: 12px;
                right: 12px;
                transform: none;
                width: auto;
                max-width: calc(100vw - 24px);
            }
        }
        </style>

        <!-- Top Screen Dropdown Toast Notifications Container -->
        <div class="toast-container" id="toastContainer">
            <?php $flashMessages = Session::getFlash(); ?>
            <?php if (!empty($flashMessages)): ?>
                <?php $_fi = 0; ?>
                <?php foreach ($flashMessages as $typeKey => $flashMsg): 
                    if (is_array($flashMsg)) {
                        $rawType = $flashMsg['type'] ?? (is_string($typeKey) ? $typeKey : 'info');
                        $msgText = $flashMsg['message'] ?? '';
                    } else {
                        $rawType = is_string($typeKey) ? $typeKey : 'info';
                        $msgText = (string)$flashMsg;
                    }
                    $type = ($rawType === 'error' || $rawType === 'danger') ? 'error' : $rawType;
                    $icon = $type === 'success' ? 'check-circle-fill' : ($type === 'error' ? 'x-circle-fill' : ($type === 'warning' ? 'exclamation-triangle-fill' : 'info-circle-fill'));
                ?>
                    <div class="sonner-toast sonner-toast-<?= $type ?>" id="flashToast-<?= $_fi ?>" onclick="dismissSonnerToastEl(this)" title="Klik untuk menutup pesan">
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

        function initMainToasts() {
            var toasts = document.querySelectorAll('.toast-container .sonner-toast');
            toasts.forEach(function(toast) {
                if (toast.dataset.dismissScheduled) return;
                toast.dataset.dismissScheduled = 'true';
                setTimeout(function() {
                    dismissSonnerToastEl(toast);
                }, 4500);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMainToasts);
        } else {
            initMainToasts();
        }
        </script>

        <!-- Main Workspace -->
        <main class="app-page">
            <?= $content ?? '' ?>
        </main>
    </div>

    <!-- Mobile Sidebar Interaction Script -->
    <script>
    (function() {
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const closeBtn = document.getElementById('sidebarCloseBtn');
        const backdrop = document.getElementById('sidebarBackdrop');

        function toggleSidebar() {
            document.body.classList.toggle('sidebar-open');
        }
        function closeSidebar() {
            document.body.classList.remove('sidebar-open');
        }

        if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (backdrop) backdrop.addEventListener('click', closeSidebar);

        // Auto-close sidebar on mobile when navigating
        document.querySelectorAll('.app-sidebar .nav-link-item, .app-sidebar .btn-sidebar-logout').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    closeSidebar();
                }
            });
        });

        // Close on ESC key
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
                closeSidebar();
            }
        });
    })();
    </script>

    <!-- Calendar Popup Scripts (Flatpickr + Indonesian Locale + Custom Init) -->
    <script src="<?= BASE_URL ?>/assets/vendor/flatpickr/flatpickr.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/vendor/flatpickr/id.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/calendar-init.js?v=<?= time() ?>"></script>
    <!-- Modal Entrance & Exit Animations System + Viewport Hoisting -->
    <script>
    (function() {
        var modalSelector = '[id*="Modal"], [id*="modal"], [id*="Popup"], [id*="popup"], [id*="Overlay"], [id*="overlay"]';

        // Play entrance animation on a modal element
        function animateModalIn(el) {
            if (!el) return;
            el.classList.remove('modal-animate-out', 'modal-is-closing');
            el.classList.remove('modal-animate-in');
            // Force browser reflow so CSS animation triggers freshly from 0%
            void el.offsetWidth;
            el.classList.add('modal-animate-in');
        }

        // Play exit animation on a modal element, then hide
        function animateModalOut(el, callback) {
            if (!el) {
                if (typeof callback === 'function') callback();
                return;
            }
            if (el.classList.contains('modal-animate-out')) return;

            el.classList.remove('modal-animate-in');
            el.classList.add('modal-animate-out', 'modal-is-closing');

            setTimeout(function() {
                try {
                    if (typeof callback === 'function') {
                        callback();
                    } else {
                        el.style.display = 'none';
                        document.body.style.overflow = '';
                    }
                } finally {
                    el.classList.remove('modal-animate-out', 'modal-is-closing');
                }
            }, 230);
        }

        window.closeAppModalWithAnimation = animateModalOut;
        window.openAppModalWithAnimation = animateModalIn;

        function getActiveModal() {
            var active = null;
            var maxZ = -1;
            document.querySelectorAll(modalSelector).forEach(function(m) {
                if (m.style && m.style.display && m.style.display !== 'none' && !m.classList.contains('modal-animate-out')) {
                    var z = parseInt(window.getComputedStyle(m).zIndex, 10) || 0;
                    if (z >= maxZ) {
                        maxZ = z;
                        active = m;
                    }
                }
            });
            return active;
        }

        // Setup MutationObserver on all modals to detect display: flex/block changes
        function observeModalElements() {
            var elements = document.querySelectorAll(modalSelector);
            elements.forEach(function(el) {
                if (!el) return;
                var inlineStyle = el.getAttribute('style') || '';
                var compStyle = window.getComputedStyle(el);
                var isFixed = compStyle.position === 'fixed' || inlineStyle.indexOf('position: fixed') !== -1 || inlineStyle.indexOf('position:fixed') !== -1;

                if (isFixed) {
                    el.classList.add('app-modal-portal');
                    if (el.parentElement && el.parentElement !== document.body) {
                        document.body.appendChild(el);
                    }

                    if (!el.__modalObserverAttached) {
                        el.__modalObserverAttached = true;
                        var prevDisplay = el.style.display;
                        var observer = new MutationObserver(function() {
                            var currentDisplay = el.style.display;
                            if (currentDisplay !== 'none' && prevDisplay === 'none') {
                                // Transitioned from hidden to visible -> play entrance!
                                animateModalIn(el);
                            }
                            prevDisplay = currentDisplay;
                        });
                        observer.observe(el, { attributes: true, attributeFilter: ['style', 'class'] });
                    }
                }
            });
        }

        // Known close functions across all pages
        var knownCloseFns = [
            'closeReviewPopup', 'closeReviewModal', 'closeBarcodeModal', 'closeSignatureModal',
            'closePenandatanganWarningModal', 'closeTtdWarningModal', 'closeTtdChoiceModal',
            'closeCreateUserModal', 'closeEditUserModal', 'closeDeleteModal', 'closeApproveModal',
            'closeRejectModal', 'closeClearLogsModal', 'closeSuratFormatModal', 'closeRevisiModal',
            'closeRevisionModal', 'closeKppWarningModal', 'closeAgendaWarningModal',
            'closeDisposisiReviewModal', 'closeDeleteSuratModal', 'closeKepstaSignatureModal',
            'closeKepstaWarningModal', 'closeKepstaReviewModal', 'closeValidationWarningModal'
        ];

        // Known open functions across all pages
        var knownOpenFns = [
            'openReviewModal', 'openBarcodeModal', 'openSignatureModal', 'openReviewPopup',
            'openSuratFormatModal', 'openClearLogsModal', 'openCreateUserModal', 'openEditUserModal',
            'openDeleteModal', 'openApproveModal', 'openRejectModal', 'openRevisiModal',
            'openRevisionModal', 'openAgendaWarningModal', 'openDisposisiReviewModal',
            'openDeleteSuratModal', 'openTtdChoiceModal', 'openManualSignatureModal',
            'openKepstaSignatureModal', 'openValidationWarningModal'
        ];

        function wrapFunction(fnName, isClose) {
            if (typeof window[fnName] !== 'function') return;
            if (window[fnName].__modalWrapped) return;

            var origFn = window[fnName];
            if (isClose) {
                var wrappedClose = function() {
                    var args = arguments;
                    var ctx = this;
                    var activeModal = getActiveModal();
                    if (activeModal && !activeModal.classList.contains('modal-animate-out')) {
                        animateModalOut(activeModal, function() {
                            origFn.apply(ctx, args);
                        });
                    } else {
                        origFn.apply(ctx, args);
                    }
                };
                wrappedClose.__modalWrapped = true;
                window[fnName] = wrappedClose;
            } else {
                var wrappedOpen = function() {
                    var args = arguments;
                    var ctx = this;
                    var res = origFn.apply(ctx, args);
                    var activeModal = getActiveModal();
                    if (activeModal) {
                        animateModalIn(activeModal);
                    }
                    return res;
                };
                wrappedOpen.__modalWrapped = true;
                window[fnName] = wrappedOpen;
            }
        }

        function wrapAllModalFunctions() {
            knownCloseFns.forEach(function(fn) { wrapFunction(fn, true); });
            knownOpenFns.forEach(function(fn) { wrapFunction(fn, false); });

            // Also dynamically scan window for any custom modal open/close functions
            try {
                var names = Object.getOwnPropertyNames(window);
                names.forEach(function(key) {
                    if (/^close.*(Modal|Popup)$/i.test(key)) {
                        wrapFunction(key, true);
                    } else if (/^open.*(Modal|Popup)$/i.test(key)) {
                        wrapFunction(key, false);
                    }
                });
            } catch (e) {}
        }

        // Initialize immediately & on events
        function initAll() {
            observeModalElements();
            wrapAllModalFunctions();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAll);
        } else {
            initAll();
        }
        window.addEventListener('load', initAll);

        // Backdrop click handler
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList && e.target.classList.contains('app-modal-portal')) {
                var modal = e.target;
                if (!modal.classList.contains('modal-animate-out')) {
                    var closeBtn = modal.querySelector('.btn-review-close, button[onclick*="close"], button[onclick*="Modal"], button[onclick*="Popup"], .btn-btn-secondary');
                    if (closeBtn) {
                        closeBtn.click();
                    } else {
                        animateModalOut(modal);
                    }
                }
            }
        }, true);

        // Global ESC key listener for modals
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                var activeModal = getActiveModal();
                if (activeModal && !activeModal.classList.contains('modal-animate-out')) {
                    var closeBtn = activeModal.querySelector('.btn-review-close, button[onclick*="close"], button[onclick*="Modal"], button[onclick*="Popup"], .btn-btn-secondary');
                    if (closeBtn) {
                        closeBtn.click();
                    } else {
                        animateModalOut(activeModal);
                    }
                }
            }
        });
    })();
    </script>
</body>
</html>

