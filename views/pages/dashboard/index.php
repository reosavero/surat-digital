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
    $timeEmoji = '<ctrl42>';
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
        <i class="bi bi-calendar3" style="color: #38BDF8; margin-right: 4px;"></i> <?= $dateFormatted ?> &bull; <i class="bi bi-clock-history" style="color: #38BDF8; margin-left: 4px; margin-right: 4px;"></i> Agenda Digital Aktif
    </p>

    <div style="font-size: 12.5px; color: #93C5FD; font-style: italic; background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(8px); display: inline-block; padding: 6px 20px; border-radius: 50px; border: 1px solid rgba(255, 255, 255, 0.15); margin-bottom: 4px;">
        <?= $quoteText ?>
    </div>
</div>

<?php 
$isKpp = ($user['role'] === 'kpp_bidang' || $user['role'] === 'kpp');
$isKepsta = ($user['role'] === 'kepala_stasiun' || $user['role'] === 'kepsta');
$isSekre = ($user['role'] === 'sekretaris' || $user['role'] === 'sekre');
$isTu = ($user['role'] === 'tata_usaha' || $user['role'] === 'tu');
?>
<?php if ($isKpp || $isTu || $isSekre || $isKepsta): ?>


    <!-- KPP, TU, Sekretaris & Kepsta Dashboard: Card List Surat -->
    <div style="margin-top: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">

            <div>
                <h2 style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-file-earmark-text-fill" style="color: <?= $isKpp ? '#4338CA' : ($isKepsta ? '#7E22CE' : ($isSekre ? '#D97706' : '#2563EB')) ?>;"></i> 
                    <?= $isKpp ? 'Daftar Surat Masuk' : ($isKepsta ? 'Daftar Disposisi Masuk' : 'Daftar Surat') ?>
                </h2>
            </div>

        </div>

        <?php if (empty($recentSurat)): ?>
            <div style="background: #FFFFFF; border-radius: 16px; padding: 48px; text-align: center; border: 1px solid #E2E8F0; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <i class="bi bi-inbox-fill" style="font-size: 42px; color: #CBD5E1; display: block; margin-bottom: 12px;"></i>
                <h3 style="font-size: 16px; font-weight: 700; color: #334155; margin-bottom: 4px;">Belum Ada Disposisi / Surat Masuk</h3>
                <p style="font-size: 13.5px; color: #64748B; margin: 0;">Surat atau lembar disposisi yang perlu diproses akan tampil di sini.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: flex-start;">
                <?php foreach ($recentSurat as $s): ?>
                    <?php 
                    if ($isKpp) {
                        $targetProcessUrl = BASE_URL . '/surat/kpp-process/' . $s['id'];
                    } else if ($isKepsta) {
                        $targetProcessUrl = BASE_URL . '/surat/kepsta-process/' . $s['id'];
                    } else if ($isSekre) {
                        $targetProcessUrl = BASE_URL . '/surat/sekre-process/' . $s['id'];
                    } else {
                        $targetProcessUrl = BASE_URL . '/surat/tu-process/' . $s['id'];
                    }

                    $sMeta = !empty($s['meta']) && is_array($s['meta']) 
                        ? $s['meta'] 
                        : (!empty($s['meta_data']) ? (json_decode($s['meta_data'], true) ?: []) : (!empty($s['meta_json']) ? (json_decode($s['meta_json'], true) ?: []) : []));
                    $sSifatRaw = !empty($sMeta['sifat_surat']) ? $sMeta['sifat_surat'] : (!empty($s['sifat_surat']) ? $s['sifat_surat'] : 'Biasa');
                    $sSifatLabel = Disposisi::getSifatSuratLabel($sSifatRaw);

                    $jenisLabel = Surat::getTypeLabel($s['type'] ?? '', $sMeta);
                    ?>
                    <a href="<?= $targetProcessUrl ?>" style="width: 360px; max-width: 100%; text-decoration: none; color: inherit; display: block;">
                        <div class="card-panel-variant" style="padding: 22px; border-radius: 16px; background: #FFFFFF; border: 1.5px solid #E2E8F0; transition: all 0.25s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between; height: 100%; cursor: pointer;"
                             onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='<?= $isKepsta ? '#7E22CE' : ($isSekre ? '#D97706' : '#2563EB') ?>'; this.style.boxShadow='0 12px 24px -8px <?= $isKepsta ? 'rgba(126, 34, 206, 0.2)' : ($isSekre ? 'rgba(217, 119, 6, 0.2)' : 'rgba(37, 99, 235, 0.2)') ?>';"
                             onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='#E2E8F0'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.03)';">
                            <div>
                                <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 10px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= htmlspecialchars($s['perihal']) ?>
                                </h3>

                                <div style="font-size: 13px; color: #475569; margin-bottom: 6px; display: flex; flex-direction: column; gap: 5px;">
                                    <div><i class="bi bi-tag-fill" style="color: #3B82F6;"></i> Jenis: <strong><?= htmlspecialchars($jenisLabel) ?></strong></div>
                                    <div><i class="bi bi-person-fill" style="color: #64748B;"></i> Pembuat: <strong><?= htmlspecialchars($s['pembuat_nama'] ?? 'Admin') ?></strong></div>
                                    <div><i class="bi bi-calendar-event" style="color: #64748B;"></i> Tanggal: <strong><?= date('d F Y', strtotime($s['tanggal_surat'])) ?></strong></div>
                                    <div><i class="bi bi-shield-exclamation" style="color: #D97706;"></i> Sifat Surat: <strong><?= htmlspecialchars($sSifatLabel) ?></strong></div>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php elseif ($user['role'] === 'admin'): ?>
    <!-- Admin Dashboard: 3 Main Action Cards for Creating Surat -->
    <div style="display: flex; flex-wrap: wrap; gap: 24px; margin-top: 24px; justify-content: flex-start;">
        <!-- Card 1: Surat Izin Prinsip -->
        <div class="card-panel-variant" style="width: 360px; max-width: 100%; padding: 28px; display: flex; flex-direction: column; justify-content: space-between; border-radius: var(--radius-lg, 14px); transition: all 0.25s ease; border: 1px solid #E2E8F0; background: #FFFFFF; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);">
            <div>
                <div style="width: 56px; height: 56px; border-radius: 14px; background: #EFF6FF; color: #2563EB; display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 20px;">
                    <i class="bi bi-file-earmark-check-fill"></i>
                </div>
                <h2 style="font-size: 20px; font-weight: 800; color: var(--text-heading); margin-bottom: 8px;">
                    Buat Surat Izin Prinsip
                </h2>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 24px;">
                    Buat permohonan izin prinsip untuk pelaksanaan kegiatan operasional stasiun LPP TVRI Jawa Timur.
                </p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/surat-prinsip" class="btn-btn btn-btn-primary" style="width: 100%; justify-content: center; padding: 12px 20px; font-size: 14.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="bi bi-plus-circle-fill"></i> Buat Surat Izin Prinsip
                </a>
            </div>
        </div>

        <!-- Card 2: Surat Izin Produksi -->
        <div class="card-panel-variant" style="width: 360px; max-width: 100%; padding: 28px; display: flex; flex-direction: column; justify-content: space-between; border-radius: var(--radius-lg, 14px); transition: all 0.25s ease; border: 1px solid #E2E8F0; background: #FFFFFF; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);">
            <div>
                <div style="width: 56px; height: 56px; border-radius: 14px; background: #EFF6FF; color: #2563EB; display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 20px;">
                    <i class="bi bi-camera-reels-fill"></i>
                </div>
                <h2 style="font-size: 20px; font-weight: 800; color: var(--text-heading); margin-bottom: 8px;">
                    Buat Surat Izin Produksi
                </h2>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 24px;">
                    Buat permohonan izin produksi / pengambilan gambar untuk program acara LPP TVRI Jawa Timur.
                </p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/surat-produksi" class="btn-btn btn-btn-primary" style="width: 100%; justify-content: center; padding: 12px 20px; font-size: 14.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="bi bi-plus-circle-fill"></i> Buat Surat Izin Produksi
                </a>
            </div>
        </div>

        <!-- Card 3: Surat Nota Dinas -->
        <div class="card-panel-variant" style="width: 360px; max-width: 100%; padding: 28px; display: flex; flex-direction: column; justify-content: space-between; border-radius: var(--radius-lg, 14px); transition: all 0.25s ease; border: 1px solid #E2E8F0; background: #FFFFFF; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);">
            <div>
                <div style="width: 56px; height: 56px; border-radius: 14px; background: #EFF6FF; color: #2563EB; display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 20px;">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>
                <h2 style="font-size: 20px; font-weight: 800; color: var(--text-heading); margin-bottom: 8px;">
                    Buat Surat Nota Dinas
                </h2>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 24px;">
                    Buat permohonan nota dinas internal stasiun (seperti permohonan cuti tahunan, penugasan dinas, atau kedinasan stasiun).
                </p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/surat-nota-dinas" class="btn-btn btn-btn-primary" style="width: 100%; justify-content: center; padding: 12px 20px; font-size: 14.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="bi bi-plus-circle-fill"></i> Buat Surat Nota Dinas
                </a>
            </div>
        </div>
    </div>

    <!-- Popup Choice Modal (Surat Dinas vs Surat Permohonan) -->
    <div id="suratFormatModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #FFFFFF; width: 100%; max-width: 540px; border-radius: 20px; padding: 30px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); position: relative;">
            <!-- Close Button -->
            <button type="button" onclick="closeSuratFormatModal()" style="position: absolute; top: 18px; right: 18px; background: #F1F5F9; border: none; width: 34px; height: 34px; border-radius: 50%; font-size: 18px; cursor: pointer; color: #64748B; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
                &times;
            </button>

            <div style="text-align: center; margin-bottom: 24px;">
                <div id="formatModalBadge" style="width: 56px; height: 56px; border-radius: 16px; background: #EFF6FF; color: #2563EB; display: inline-flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 12px;">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>
                <h2 id="formatModalTitle" style="font-size: 20px; font-weight: 800; color: #0F172A; margin-bottom: 6px;">
                    Pilih Format Surat
                </h2>
                <p style="font-size: 13.5px; color: #64748B; margin: 0;">
                    Silakan pilih format jenis surat yang ingin Anda buat di bawah ini:
                </p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <!-- Option 1: Surat Dinas -->
                <a id="linkSuratDinas" href="#" style="text-decoration: none; padding: 22px 18px; border-radius: 14px; border: 2px solid #E2E8F0; background: #FFFFFF; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px; transition: all 0.2s ease;"
                   onmouseover="this.style.borderColor='var(--brand-primary, #0052CC)'; this.style.background='#F8FAFC'; this.style.transform='translateY(-2px)';"
                   onmouseout="this.style.borderColor='#E2E8F0'; this.style.background='#FFFFFF'; this.style.transform='translateY(0)';">
                    <div style="width: 46px; height: 46px; border-radius: 12px; background: #EFF6FF; color: #1D4ED8; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                        <i class="bi bi-building-fill-gear"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 15px; font-weight: 800; color: #0F172A; margin-bottom: 4px;">Surat Dinas</h3>
                        <p style="font-size: 12px; color: #64748B; margin: 0; line-height: 1.35;">
                            Format resmi kedinasan penugasan & kegiatan internal TVRI.
                        </p>
                    </div>
                </a>

                <!-- Option 2: Surat Permohonan -->
                <a id="linkSuratPermohonan" href="#" style="text-decoration: none; padding: 22px 18px; border-radius: 14px; border: 2px solid #E2E8F0; background: #FFFFFF; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px; transition: all 0.2s ease;"
                   onmouseover="this.style.borderColor='#7E22CE'; this.style.background='#FAF5FF'; this.style.transform='translateY(-2px)';"
                   onmouseout="this.style.borderColor='#E2E8F0'; this.style.background='#FFFFFF'; this.style.transform='translateY(0)';">
                    <div style="width: 46px; height: 46px; border-radius: 12px; background: #FAF5FF; color: #7E22CE; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                        <i class="bi bi-file-earmark-text-fill"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 15px; font-weight: 800; color: #0F172A; margin-bottom: 4px;">Surat Permohonan</h3>
                        <p style="font-size: 12px; color: #64748B; margin: 0; line-height: 1.35;">
                            Format permohonan resmi yang diajukan ke pimpinan / eksternal.
                        </p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script>
    function openSuratFormatModal(type) {
        const modal = document.getElementById('suratFormatModal');
        const title = document.getElementById('formatModalTitle');
        const badge = document.getElementById('formatModalBadge');
        const linkDinas = document.getElementById('linkSuratDinas');
        const linkPermohonan = document.getElementById('linkSuratPermohonan');

        const baseUrl = '<?= BASE_URL ?>';

        if (type === 'prinsip') {
            title.textContent = 'Pilih Format Surat Izin Prinsip';
            badge.innerHTML = '<i class="bi bi-file-earmark-check-fill"></i>';
            badge.style.background = '#EFF6FF';
            badge.style.color = '#2563EB';

            linkDinas.href = baseUrl + '/surat-prinsip?kat=dinas';
            linkPermohonan.href = baseUrl + '/surat-prinsip?kat=permohonan';
        } else if (type === 'produksi') {
            title.textContent = 'Pilih Format Surat Izin Produksi';
            badge.innerHTML = '<i class="bi bi-camera-reels-fill"></i>';
            badge.style.background = '#FAF5FF';
            badge.style.color = '#7E22CE';

            linkDinas.href = baseUrl + '/surat-produksi?kat=dinas';
            linkPermohonan.href = baseUrl + '/surat-produksi?kat=permohonan';
        }

        modal.style.display = 'flex';
    }

    function closeSuratFormatModal() {
        document.getElementById('suratFormatModal').style.display = 'none';
    }

    window.addEventListener('click', function(e) {
        const modal = document.getElementById('suratFormatModal');
        if (e.target === modal) {
            closeSuratFormatModal();
        }
    });
    </script>
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

    <!-- Varied Task Context Guidance Card Panel -->
    <div class="guidance-card-variant">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--brand-primary); margin-bottom: 4px;">
                    <i class="bi bi-shield-check"></i> DESKRIPSI TUGAS ROLE AKTIF
                </div>
                <h3 style="font-size: 16px; font-weight: 800; color: var(--text-heading); margin-bottom: 4px;">
                    Peran Pengguna: <?= User::getRoleLabel($user['role']) ?>
                </h3>
                <p style="font-size: 13.5px; color: var(--text-muted); margin: 0; max-width: 800px;">
                    <?php if ($user['role'] === 'tata_usaha'): ?>
                        Wewenang Anda: Menginputkan <strong>Nomor Surat Agenda TU</strong> dan memberikan <strong>Tanda Tangan Digital TU</strong>.
                    <?php elseif ($user['role'] === 'sekretaris'): ?>
                        Wewenang Anda: Memeriksa surat dari TU, menentukan <strong>Approve/Revisi</strong>, menetapkan <strong>Sifat Surat</strong>, dan menerbitkan Disposisi.
                    <?php elseif ($user['role'] === 'kepala_stasiun'): ?>
                        Wewenang Anda: Memeriksa Lembar Disposisi, mencentang <strong>Tabel Instruksi Disposisi</strong>, dan membubuhkan <strong>TTD Kepsta</strong> (PDF & GDrive otomatis).
                    <?php else: ?>
                        Wewenang Anda: Manajemen penuh pengguna, struktur role organisasi, audit log, dan integrasi Google Drive API.
                    <?php endif; ?>
                </p>
            </div>

            <a href="<?= BASE_URL ?>/surat" class="btn-btn btn-btn-secondary">
                <i class="bi bi-folder-symlink-fill"></i> Buka Inbox Surat (<?= count($recentSurat) ?> Agenda)
            </a>
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
                                    <?php if ($user['role'] === 'tata_usaha' && $s['status'] === 'submitted_to_tu'): ?>
                                        <a href="<?= BASE_URL ?>/surat/tu-process/<?= $s['id'] ?>" class="btn-btn btn-btn-primary btn-btn-sm">
                                            <i class="bi bi-pen-fill"></i> Nomor & TTD
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
