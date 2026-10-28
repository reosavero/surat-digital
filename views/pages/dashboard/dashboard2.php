<?php
$hour = (int)date('H');
if ($hour >= 4 && $hour < 11) {
    $timeGreeting = 'Selamat Pagi';
    $timeEmoji = '🌅';
    $quoteText = '“Setiap pagi adalah kesempatan baru untuk memberikan karya terbaik.” ✨';
} elseif ($hour >= 11 && $hour < 15) {
    $timeGreeting = 'Selamat Siang';
    $timeEmoji = '☀️';
    $quoteText = '“Dedikasi dan ketelitian Anda hari ini adalah inspirasi bagi seluruh tim.” 🌟';
} elseif ($hour >= 15 && $hour < 18) {
    $timeGreeting = 'Selamat Sore';
    $timeEmoji = '🍃';
    $quoteText = '“Kebaikan dan kerja keras yang ditanam hari ini akan berbuah manis besok.” 🍃';
} else {
    $timeGreeting = 'Selamat Malam';
    $timeEmoji = '🌙';
    $quoteText = '“Terima kasih atas dedikasi Anda hari ini. Selamat beristirahat.” ✨';
}

$daysIndo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$monthsIndo = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

$hariIni = $daysIndo[date('l')] ?? date('l');
$tglIni = date('d');
$blnIni = $monthsIndo[(int)date('m')] ?? date('m');
$thnIni = date('Y');

$dateFormatted = "$hariIni, $tglIni $blnIni $thnIni";
?>

<!-- Centered Animated Executive Welcome Hero Card -->
<div class="executive-welcome-card-centered">
    <h1 class="executive-title-centered" style="font-size: 26px; margin-top: 4px;">
        <?= $timeGreeting ?>, <?= htmlspecialchars($user['name']) ?> <?= $timeEmoji ?>
    </h1>
    
    <p class="executive-subtitle-centered" style="font-size: 14.5px; color: #E0F2FE; font-weight: 500; margin-bottom: 14px; max-width: 600px;">
        <i class="bi bi-calendar3" style="color: #38BDF8; margin-right: 4px;"></i> <?= $dateFormatted ?> &bull; <i class="bi bi-grid-fill" style="color: #38BDF8; margin-left: 4px; margin-right: 4px;"></i> Dashboard 2
    </p>

    <div style="font-size: 12.5px; color: #93C5FD; font-style: italic; background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(8px); display: inline-block; padding: 6px 20px; border-radius: 50px; border: 1px solid rgba(255, 255, 255, 0.15); margin-bottom: 4px;">
        <?= $quoteText ?>
    </div>
</div>

<?php if ($user['role'] === 'admin'): ?>
    <!-- Admin Dashboard 2: 2 Main Action Cards for Surat Dinas & Surat Permohonan -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-top: 24px;">
        <!-- Card 1: Surat Dinas -->
        <div class="card-panel-variant" style="padding: 28px; display: flex; flex-direction: column; justify-content: space-between; border-radius: var(--radius-lg, 14px); transition: all 0.25s ease; border: 1px solid #E2E8F0; background: #FFFFFF; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);">
            <div>
                <div style="width: 56px; height: 56px; border-radius: 14px; background: #EFF6FF; color: #2563EB; display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 20px;">
                    <i class="bi bi-building-fill-gear"></i>
                </div>
                <h2 style="font-size: 20px; font-weight: 800; color: var(--text-heading); margin-bottom: 8px;">
                    Surat Dinas
                </h2>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 24px;">
                    Format resmi kedinasan penugasan & kegiatan internal LPP TVRI Stasiun Jawa Timur.
                </p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/surat-prinsip?kat=dinas" class="btn-btn btn-btn-primary" style="width: 100%; justify-content: center; padding: 12px 20px; font-size: 14.5px; font-weight: 700; text-decoration: none;">
                    <i class="bi bi-plus-circle-fill"></i> Buat Surat Dinas
                </a>
            </div>
        </div>

        <!-- Card 2: Surat Permohonan -->
        <div class="card-panel-variant" style="padding: 28px; display: flex; flex-direction: column; justify-content: space-between; border-radius: var(--radius-lg, 14px); transition: all 0.25s ease; border: 1px solid #E2E8F0; background: #FFFFFF; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);">
            <div>
                <div style="width: 56px; height: 56px; border-radius: 14px; background: #FAF5FF; color: #7E22CE; display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 20px;">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>
                <h2 style="font-size: 20px; font-weight: 800; color: var(--text-heading); margin-bottom: 8px;">
                    Surat Permohonan
                </h2>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 24px;">
                    Format permohonan resmi yang diajukan ke pimpinan / instansi terkait LPP TVRI Jawa Timur.
                </p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/surat-prinsip?kat=permohonan" class="btn-btn btn-btn-primary" style="width: 100%; justify-content: center; padding: 12px 20px; font-size: 14.5px; font-weight: 700; background-color: #7E22CE; border-color: #7E22CE; text-decoration: none;">
                    <i class="bi bi-plus-circle-fill"></i> Buat Surat Permohonan
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Varied Stat KPI Cards Grid (Centered Floating Layout) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 28px; margin-top: 24px;">
        <!-- Stat 1: Total Surat -->
        <div class="stat-card-variant stat-variant-blue">
            <div class="stat-icon-wrapper">
                <i class="bi bi-files"></i>
            </div>
            <div class="stat-number-display"><?= $stats['total'] ?></div>
            <div class="stat-title-label">Total Volume Surat</div>
            <div class="stat-pill-badge"><i class="bi bi-check2-all"></i> Seluruh Agenda</div>
        </div>

        <!-- Stat 2: Proses TU -->
        <div class="stat-card-variant stat-variant-ocean">
            <div class="stat-icon-wrapper">
                <i class="bi bi-pen-fill"></i>
            </div>
            <div class="stat-number-display"><?= $stats['pending_tu'] ?></div>
            <div class="stat-title-label">Proses Tata Usaha</div>
            <div class="stat-pill-badge"><i class="bi bi-clock-history"></i> Penomoran TU</div>
        </div>

        <!-- Stat 3: Review Sekre -->
        <div class="stat-card-variant stat-variant-amber">
            <div class="stat-icon-wrapper">
                <i class="bi bi-file-earmark-text-fill"></i>
            </div>
            <div class="stat-number-display"><?= $stats['pending_sekre'] ?></div>
            <div class="stat-title-label">Review Sekretaris</div>
            <div class="stat-pill-badge"><i class="bi bi-hourglass-split"></i> Disposisi Sekre</div>
        </div>

        <!-- Stat 4: Disposisi Kepsta -->
        <div class="stat-card-variant stat-variant-purple">
            <div class="stat-icon-wrapper">
                <i class="bi bi-check-square-fill"></i>
            </div>
            <div class="stat-number-display"><?= $stats['pending_kepsta'] ?></div>
            <div class="stat-title-label">Disposisi Kepsta</div>
            <div class="stat-pill-badge"><i class="bi bi-person-badge-fill"></i> Menunggu TTD</div>
        </div>

        <!-- Stat 5: Completed & GDrive -->
        <div class="stat-card-variant stat-variant-emerald">
            <div class="stat-icon-wrapper">
                <i class="bi bi-cloud-check-fill"></i>
            </div>
            <div class="stat-number-display"><?= $stats['completed'] ?></div>
            <div class="stat-title-label">Completed & GDrive</div>
            <div class="stat-pill-badge"><i class="bi bi-cloud-arrow-up-fill"></i> Ter-upload</div>
        </div>
    </div>

    <!-- Varied Floating Table Container Card Panel -->
    <div class="card-panel-variant" style="padding: 0; overflow: hidden;">
        <div style="padding: 18px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; background: #FFFFFF;">
            <h3 style="font-size: 15px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-list-columns-reverse" style="color: var(--brand-primary);"></i> Daftar Surat & Monitoring Tracking
            </h3>
            <a href="<?= BASE_URL ?>/surat" style="font-size: 12.5px; font-weight: 700; color: var(--brand-primary); text-decoration: none;">
                Lihat Seluruh Surat <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        <?php if (empty($recentSurat)): ?>
            <div style="padding: 48px; text-align: center; color: var(--text-muted);">
                <i class="bi bi-inbox" style="font-size: 36px; color: #CBD5E1; display: block; margin-bottom: 10px;"></i>
                Belum ada surat yang terdaftar di sistem.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kode & Type</th>
                            <th>Nomor & Perihal</th>
                            <th>Pembuat</th>
                            <th>Tanggal</th>
                            <th>Status Workflow</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSurat as $s): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; font-family: monospace; font-size: 12.5px; color: var(--text-heading);"><?= htmlspecialchars($s['kode_surat']) ?></div>
                                    <div style="font-size: 11.5px; color: var(--text-muted);"><?= Surat::getTypeLabel($s['type']) ?></div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--brand-primary); margin-bottom: 2px;">
                                        <?= htmlspecialchars($s['nomor_surat'] ?? '(Menunggu Penomoran TU)') ?>
                                    </div>
                                    <div style="color: var(--text-body); font-weight: 500;">
                                        <?= htmlspecialchars($s['perihal']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-heading);"><?= htmlspecialchars($s['pembuat_nama'] ?? 'Admin') ?></div>
                                </td>
                                <td style="color: var(--text-muted); font-weight: 500; white-space: nowrap;">
                                    <?= date('d/m/Y', strtotime($s['tanggal_surat'])) ?>
                                </td>
                                <td>
                                    <?= Surat::getStatusBadgeHtml($s['status']) ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="<?= BASE_URL ?>/surat/tracking/<?= $s['id'] ?>" class="btn-btn btn-btn-secondary btn-btn-sm" style="margin-right: 4px;">
                                        <i class="bi bi-diagram-3-fill"></i> Tracking
                                    </a>

                                    <?php if ($user['role'] === 'kpp_bidang' && $s['status'] === 'submitted_to_kpp'): ?>
                                        <a href="<?= BASE_URL ?>/surat/kpp-process/<?= $s['id'] ?>" class="btn-btn btn-btn-primary btn-btn-sm" style="background-color: #0284C7;">
                                            <i class="bi bi-pen-fill"></i> TTD & Paraf KPP
                                        </a>
                                    <?php elseif ($user['role'] === 'tata_usaha' && in_array($s['status'], ['kpp_signed', 'submitted_to_tu'])): ?>
                                        <a href="<?= BASE_URL ?>/surat/tu-process/<?= $s['id'] ?>" class="btn-btn btn-btn-primary btn-btn-sm">
                                            <i class="bi bi-pen-fill"></i> TTD Nota Dinas TU
                                        </a>
                                    <?php elseif ($user['role'] === 'sekretaris' && $s['status'] === 'tu_signed'): ?>
                                        <a href="<?= BASE_URL ?>/surat/sekre-process/<?= $s['id'] ?>" class="btn-btn btn-btn-primary btn-btn-sm" style="background-color: #D97706;">
                                            <i class="bi bi-file-earmark-plus"></i> Disposisi
                                        </a>
                                    <?php elseif ($user['role'] === 'kepala_stasiun' && $s['status'] === 'sekre_approved'): ?>
                                        <a href="<?= BASE_URL ?>/surat/kepsta-process/<?= $s['id'] ?>" class="btn-btn btn-btn-primary btn-btn-sm" style="background-color: #7E22CE;">
                                            <i class="bi bi-check-square"></i> Disposisi Kepsta
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>/surat/detail/<?= $s['id'] ?>" class="btn-btn btn-btn-secondary btn-btn-sm">
                                            <i class="bi bi-eye-fill"></i> Detail
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
