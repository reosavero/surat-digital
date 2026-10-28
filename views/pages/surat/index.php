<?php 
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
        Daftar & Monitoring Surat Digital LPP TVRI Jawa Timur
    </h1>
    
    <p class="executive-subtitle-centered" style="font-size: 14.5px; color: #E0F2FE; font-weight: 500; margin-bottom: 14px; max-width: 600px;">
        <i class="bi bi-calendar3" style="color: #38BDF8; margin-right: 4px;"></i> <?= $dateFormatted ?> &bull; <i class="bi bi-clock-history" style="color: #38BDF8; margin-left: 4px; margin-right: 4px;"></i> Agenda Digital Aktif
    </p>

    <div style="font-size: 12.5px; color: #93C5FD; font-style: italic; background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(8px); display: inline-block; padding: 6px 20px; border-radius: 50px; border: 1px solid rgba(255, 255, 255, 0.15); margin-bottom: 4px;">
        “Setiap pagi adalah kesempatan baru untuk memberikan karya terbaik.” ✨
    </div>
</div>

<!-- Surat List Cards with Stepper Progress -->
<?php if (empty($suratList)): ?>
    <div class="card-panel-variant" style="padding: 60px; text-align: center; color: var(--text-muted);">
        <i class="bi bi-inbox" style="font-size: 40px; color: #CBD5E1; display: block; margin-bottom: 10px;"></i>
        Tidak ada data surat.
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 4px;">
        <?php 
        $renderedCount = 0;
        foreach ($suratList as $s): 
            $sStatus = $s['status'];
            $sMeta = !empty($s['meta']) && is_array($s['meta']) 
                ? $s['meta'] 
                : (!empty($s['meta_data']) ? (json_decode($s['meta_data'], true) ?: []) : (!empty($s['meta_json']) ? (json_decode($s['meta_json'], true) ?: []) : []));

            // Jika surat sudah selesai (di-TTD & diteruskan oleh Kepsta / completed), card surat di halaman surat otomatis terhapus untuk semua user
            if ($sStatus === 'completed') {
                continue;
            }

            if (in_array($role, ['tata_usaha', 'tu', 'ktu'])) {
                if (in_array($sStatus, ['submitted_to_kpp', 'draft'])) {
                    continue;
                }
                if (!empty($sMeta['from_upload_lampiran']) || ($s['type'] ?? '') === 'upload_lampiran') {
                    continue;
                }
            }
            if (in_array($role, ['sekretaris', 'sekre']) && in_array($sStatus, ['submitted_to_kpp', 'kpp_signed', 'submitted_to_tu', 'draft'])) {
                continue;
            }
            if (in_array($role, ['kepala_stasiun', 'kepsta']) && in_array($sStatus, ['submitted_to_kpp', 'kpp_signed', 'submitted_to_tu', 'tu_signed', 'revisi', 'draft'])) {
                continue;
            }
            $renderedCount++;
            $step1 = true;
            $step2 = in_array($sStatus, ['kpp_signed', 'submitted_to_tu', 'tu_signed', 'sekre_approved', 'completed']);
            $step3 = in_array($sStatus, ['tu_signed', 'sekre_approved', 'completed']);
            $step4 = in_array($sStatus, ['sekre_approved', 'completed']);
            $step5 = ($sStatus === 'completed');

            $progressWidth = '0%';
            if ($sStatus === 'submitted_to_kpp') $progressWidth = '20%';
            elseif (in_array($sStatus, ['kpp_signed', 'submitted_to_tu'])) $progressWidth = '40%';
            elseif ($sStatus === 'tu_signed') $progressWidth = '60%';
            elseif ($sStatus === 'sekre_approved') $progressWidth = '80%';
            elseif ($sStatus === 'completed') $progressWidth = '100%';
            elseif ($sStatus === 'revisi') $progressWidth = '40%';

            $sSifatRaw = !empty($sMeta['sifat_surat']) ? $sMeta['sifat_surat'] : (!empty($s['sifat_surat']) ? $s['sifat_surat'] : 'Biasa');
            $sSifatLabel = Disposisi::getSifatSuratLabel($sSifatRaw);
            ?>
            <div class="list-item-tracking-card" onclick="openReviewPopup(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['perihal']), ENT_QUOTES) ?>')" style="cursor: pointer;">
                <!-- Left Side: Title, Date, Meta & Actions -->
                <div class="list-item-meta-column">
                    <h3 style="font-size: 16px; font-weight: 800; color: var(--text-heading); margin-bottom: 6px; line-height: 1.3;">
                        <?= htmlspecialchars($s['perihal']) ?>
                    </h3>

                    <div style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 10px; display: flex; flex-wrap: wrap; gap: 14px;">
                        <span><i class="bi bi-calendar-event"></i> Tanggal: <strong><?= date('d F Y', strtotime($s['tanggal_surat'])) ?></strong></span>
                        <span><i class="bi bi-shield-exclamation" style="color: #D97706;"></i> Sifat: <strong><?= htmlspecialchars($sSifatLabel) ?></strong></span>
                        <span><i class="bi bi-person-circle"></i> Pembuat: <strong><?= htmlspecialchars($s['pembuat_nama'] ?? 'Admin') ?></strong></span>
                    </div>

                    <?php if (
                        ($role === 'kpp_bidang' && $s['status'] === 'submitted_to_kpp') ||
                        ($role === 'tata_usaha' && in_array($s['status'], ['kpp_signed', 'submitted_to_tu'])) ||
                        ($role === 'sekretaris' && $s['status'] === 'tu_signed') ||
                        ($role === 'kepala_stasiun' && $s['status'] === 'sekre_approved')
                    ): ?>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <?php if ($role === 'kpp_bidang' && $s['status'] === 'submitted_to_kpp'): ?>
                                <a href="<?= BASE_URL ?>/surat/kpp-process/<?= $s['id'] ?>" onclick="event.stopPropagation();" class="btn-btn btn-btn-primary btn-btn-sm" style="background-color: #0284C7;">
                                    <i class="bi bi-pen-fill"></i> TTD & Paraf KPP
                                </a>
                            <?php elseif ($role === 'tata_usaha' && in_array($s['status'], ['kpp_signed', 'submitted_to_tu'])): ?>
                                <a href="<?= BASE_URL ?>/surat/tu-process/<?= $s['id'] ?>" onclick="event.stopPropagation();" class="btn-btn btn-btn-primary btn-btn-sm">
                                    <i class="bi bi-pen-fill"></i> TTD Nota Dinas TU
                                </a>
                            <?php elseif ($role === 'sekretaris' && $s['status'] === 'tu_signed'): ?>
                                <a href="<?= BASE_URL ?>/surat/sekre-process/<?= $s['id'] ?>" onclick="event.stopPropagation();" class="btn-btn btn-btn-primary btn-btn-sm" style="background-color: #D97706;">
                                    <i class="bi bi-file-earmark-plus"></i> Disposisi Sekre
                                </a>
                            <?php elseif ($role === 'kepala_stasiun' && $s['status'] === 'sekre_approved'): ?>
                                <a href="<?= BASE_URL ?>/surat/kepsta-process/<?= $s['id'] ?>" onclick="event.stopPropagation();" class="btn-btn btn-btn-primary btn-btn-sm" style="background-color: #7E22CE;">
                                    <i class="bi bi-check-square"></i> Disposisi Kepsta
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Side: Horizontal Progress Stepper -->
                <div class="list-item-stepper-column">
                    <div class="horizontal-tracking-stepper">
                        <div class="stepper-bg-line"></div>
                        <div class="stepper-fill-line" style="width: <?= $progressWidth ?>;"></div>

                        <!-- Node 1: Draft Admin -->
                        <div class="stepper-step-node completed">
                            <div class="stepper-step-icon-circle">
                                <i class="bi bi-file-earmark-plus-fill"></i>
                            </div>
                            <div class="stepper-step-title">Draft Admin</div>
                            <div class="stepper-step-time"><?= date('d/m/Y', strtotime($s['created_at'] ?? $s['tanggal_surat'])) ?></div>
                        </div>

                        <!-- Node 2: TTD & Paraf KPP -->
                        <div class="stepper-step-node <?= $step2 ? 'completed' : ($sStatus === 'submitted_to_kpp' ? 'active' : '') ?>">
                            <div class="stepper-step-icon-circle">
                                <i class="bi bi-pen-fill"></i>
                            </div>
                            <div class="stepper-step-title">TTD & Paraf KPP</div>
                            <div class="stepper-step-time"><?= $step2 ? 'Terverifikasi' : ($sStatus === 'submitted_to_kpp' ? 'Proses KPP' : '-') ?></div>
                        </div>

                        <!-- Node 3: TTD Nota Dinas TU -->
                        <div class="stepper-step-node <?= $step3 ? 'completed' : (in_array($sStatus, ['kpp_signed', 'submitted_to_tu']) ? 'active' : '') ?>">
                            <div class="stepper-step-icon-circle">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div class="stepper-step-title">TTD Nota Dinas TU</div>
                            <div class="stepper-step-time"><?= $step3 ? 'Ter-TTD' : (in_array($sStatus, ['kpp_signed', 'submitted_to_tu']) ? 'Proses TU' : '-') ?></div>
                        </div>

                        <!-- Node 4: Disposisi Sekre -->
                        <div class="stepper-step-node <?= $step4 ? 'completed' : ($sStatus === 'tu_signed' ? 'active' : ($sStatus === 'revisi' ? 'revisi' : '')) ?>">
                            <div class="stepper-step-icon-circle">
                                <i class="bi bi-send-check-fill"></i>
                            </div>
                            <div class="stepper-step-title">Disposisi Sekre</div>
                            <div class="stepper-step-time"><?= $step4 ? 'Approved' : ($sStatus === 'tu_signed' ? 'Review' : ($sStatus === 'revisi' ? 'Perlu Revisi' : '-')) ?></div>
                        </div>

                        <!-- Node 5: Completed & GDrive -->
                        <div class="stepper-step-node <?= $step5 ? 'completed' : ($sStatus === 'sekre_approved' ? 'active' : '') ?>">
                            <div class="stepper-step-icon-circle">
                                <i class="bi bi-cloud-check-fill"></i>
                            </div>
                            <div class="stepper-step-title">Completed</div>
                            <div class="stepper-step-time"><?= $step5 ? 'Ter-upload' : ($sStatus === 'sekre_approved' ? 'Menunggu Kepsta' : '-') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($renderedCount === 0): ?>
            <div class="card-panel-variant" style="padding: 60px; text-align: center; color: var(--text-muted);">
                <i class="bi bi-inbox" style="font-size: 40px; color: #CBD5E1; display: block; margin-bottom: 10px;"></i>
                Tidak ada data surat aktif.
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<style>
@media (max-width: 768px) {
    #reviewSuratPopupModal {
        padding: 4px !important;
    }
    #reviewSuratPopupModal > div {
        width: 100% !important;
        max-width: 100% !important;
        height: 96vh !important;
        max-height: 96vh !important;
        border-radius: 14px !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
    }
    .popup-review-topbar {
        padding: 10px 14px !important;
    }
    .popup-review-icon {
        width: 32px !important;
        height: 32px !important;
        font-size: 16px !important;
        border-radius: 8px !important;
    }
    #popupReviewTitle {
        font-size: 13.5px !important;
        line-height: 1.25 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        max-width: calc(100vw - 110px) !important;
    }
    .popup-review-sub {
        display: none !important;
    }
    .popup-review-body {
        flex: 1 !important;
        height: 100% !important;
        min-height: 0 !important;
        overflow: hidden !important;
    }
    #popupReviewFrame {
        min-height: 0 !important;
        height: 100% !important;
    }
    .popup-review-footer {
        padding: 8px 14px !important;
    }
    .popup-review-footer .btn-btn {
        padding: 7px 14px !important;
        font-size: 12.5px !important;
    }
}
</style>

<!-- Modal Review Surat Popup Container -->
<div id="reviewSuratPopupModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 16px;">
    <div style="background: #FFFFFF; width: 98vw; max-width: 1440px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(126, 34, 206, 0.25); overflow: hidden; display: flex; flex-direction: column; max-height: 94vh; border: 1px solid #E9D5FF;">
        <!-- Modal Top Bar Header -->
        <div class="popup-review-topbar" style="padding: 16px 24px; background: linear-gradient(135deg, #FAF5FF 0%, #EFF6FF 100%); border-bottom: 1px solid #E9D5FF; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                <div class="popup-review-icon" style="width: 40px; height: 40px; border-radius: 12px; background: #FFFFFF; color: #7E22CE; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 2px 8px rgba(126, 34, 206, 0.15); border: 1px solid #E9D5FF; flex-shrink: 0;">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>
                <div style="min-width: 0;">
                    <h3 id="popupReviewTitle" style="margin: 0; font-size: 17px; font-weight: 800; color: #581C87;">Review Dokumen Surat</h3>
                    <div class="popup-review-sub" style="font-size: 12px; color: #6B21A8; margin-top: 1px;">Pratinjau Lengkap Digital Agenda &amp; Berkas Lampiran</div>
                </div>
            </div>
        </div>

        <!-- Modal Body (Iframe) -->
        <div class="popup-review-body" style="padding: 0; background: #F8FAFC; flex: 1; overflow: hidden; position: relative; min-height: 68vh;">
            <iframe id="popupReviewFrame" src="" style="width: 100%; height: 100%; min-height: 68vh; border: none;"></iframe>
        </div>

        <!-- Modal Bottom Bar Actions -->
        <div class="popup-review-footer" style="padding: 14px 24px; background: #FFFFFF; border-top: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 -4px 12px rgba(0,0,0,0.03);">
            <div>
                <button type="button" onclick="printReviewFrame()" class="btn-btn" style="background: #FFFFFF; color: #1E293B; border: 1.5px solid #CBD5E1; font-weight: 700; box-shadow: 0 1px 2px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 8px; transition: all 0.2s;" onmouseover="this.style.borderColor='#94A3B8';" onmouseout="this.style.borderColor='#CBD5E1';">
                    <i class="bi bi-printer-fill" style="color: #0284C7; font-size: 16px;"></i> Print Surat
                </button>
            </div>
            <div>
                <button type="button" onclick="closeReviewPopup()" class="btn-btn btn-btn-secondary" style="min-width: 120px; justify-content: center; font-weight: 700;">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function openReviewPopup(suratId, perihal) {
    const modal = document.getElementById('reviewSuratPopupModal');
    const iframe = document.getElementById('popupReviewFrame');
    const title = document.getElementById('popupReviewTitle');
    
    if (title) title.textContent = 'Review Dokumen: ' + perihal;
    if (iframe) iframe.src = '<?= BASE_URL ?>/surat/detail/' + suratId + '?modal=1';
    document.body.style.overflow = 'hidden';
    if (modal) modal.style.display = 'flex';
}

function closeReviewPopup() {
    const modal = document.getElementById('reviewSuratPopupModal');
    const iframe = document.getElementById('popupReviewFrame');
    if (modal) modal.style.display = 'none';
    if (iframe) iframe.src = '';
    document.body.style.overflow = '';
}

async function printReviewFrame() {
    const iframe = document.getElementById('popupReviewFrame');
    if (!iframe || !iframe.contentWindow) return;

    try {
        if (typeof iframe.contentWindow.renderServerPdfCanvases === 'function') {
            await iframe.contentWindow.renderServerPdfCanvases();
        }
    } catch (e) {
        console.error('Error rendering PDF canvases before print:', e);
    }

    const doc = iframe.contentWindow.document;
    const papers = doc.querySelectorAll('.a4-paper-preview, .pdf-lampiran-card-preview');
    if (!papers || papers.length === 0) {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        return;
    }

    let papersHtml = '';
    papers.forEach((paper, idx) => {
        const isLampiran = paper.classList.contains('pdf-lampiran-card-preview');
        if (isLampiran) {
            const origImg = paper.querySelector('.pdf-img-item');
            const origCanvas = paper.querySelector('canvas');
            let lampiranImgSrc = null;

            if (origImg && origImg.src && origImg.src.length > 10 && !origImg.src.includes('about:blank')) {
                lampiranImgSrc = origImg.src;
            }
            if (!lampiranImgSrc && origCanvas && origCanvas.width > 0) {
                try {
                    lampiranImgSrc = origCanvas.toDataURL('image/png');
                } catch (e) {
                    console.error('Error converting canvas for lampiran:', e);
                }
            }
            if (!lampiranImgSrc) {
                const anyImg = paper.querySelector('img');
                if (anyImg && anyImg.src && anyImg.src.length > 10 && !anyImg.src.includes('about:blank')) {
                    lampiranImgSrc = anyImg.src;
                }
            }

            papersHtml += `
            <div class="printable-a4-page printable-lampiran-page" style="padding: 0 !important; margin: 0 auto !important; width: 210mm !important; height: 297mm !important; max-height: 297mm !important; box-sizing: border-box !important; display: block !important; overflow: hidden !important; page-break-after: always !important; break-after: page !important; border: none !important; box-shadow: none !important; background: #ffffff !important;">
                <img src="${lampiranImgSrc || ''}" class="pdf-img-item" style="width: 210mm !important; height: 297mm !important; object-fit: contain !important; display: block !important; margin: 0 auto !important; border: none !important; box-shadow: none !important; transform: none !important;">
            </div>`;
        } else {
            const cloned = paper.cloneNode(true);
            cloned.querySelectorAll('.no-print').forEach(el => el.remove());

            const origCanvases = paper.querySelectorAll('canvas');
            const clonedCanvases = cloned.querySelectorAll('canvas');
            origCanvases.forEach((origCanvas, i) => {
                if (clonedCanvases[i] && origCanvas.width > 0 && origCanvas.style.display !== 'none') {
                    try {
                        const dataUrl = origCanvas.toDataURL('image/png');
                        const img = document.createElement('img');
                        img.src = dataUrl;
                        img.style.maxWidth = '100%';
                        img.style.height = 'auto';
                        img.style.display = 'block';
                        img.style.margin = '0 auto';
                        clonedCanvases[i].parentNode.replaceChild(img, clonedCanvases[i]);
                    } catch (e) {
                        console.error('Error converting canvas:', e);
                    }
                } else if (clonedCanvases[i]) {
                    clonedCanvases[i].remove();
                }
            });

            cloned.querySelectorAll('.pdf-img-item').forEach(img => {
                if (img.src && img.src.length > 10 && !img.src.includes('about:blank')) {
                    img.style.display = 'block';
                    img.style.width = '100%';
                    img.style.height = 'auto';
                    img.style.visibility = 'visible';
                }
            });

            papersHtml += `<div class="printable-a4-page">${cloned.innerHTML}</div>`;
        }
    });

    const printWin = window.open('', '_blank', 'width=950,height=800');
    if (!printWin) {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        return;
    }

    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title></title>
            <base href="<?= (defined('BASE_URL') ? BASE_URL : '') ?>/">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
            <style>
                @page { size: A4 portrait; margin: 0; }
                html, body { font-family: Arial, sans-serif; background: #ffffff; color: #000000; margin: 0 !important; padding: 0 !important; width: 210mm !important; height: auto !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                img { max-width: 100% !important; display: inline-block !important; visibility: visible !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                .printable-a4-page { background: #FFFFFF !important; border: none !important; box-shadow: none !important; padding: 3cm !important; margin: 0 auto !important; width: 210mm !important; height: 297mm !important; max-height: 297mm !important; box-sizing: border-box !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; page-break-after: always !important; break-after: page !important; page-break-inside: avoid !important; break-inside: avoid !important; overflow: hidden !important; }
                .printable-a4-page.printable-lampiran-page { padding: 0 !important; border: none !important; box-shadow: none !important; overflow: hidden !important; width: 210mm !important; height: 297mm !important; background: #ffffff !important; display: block !important; }
                .printable-a4-page.printable-lampiran-page img,
                .printable-a4-page.printable-lampiran-page .pdf-img-item { width: 210mm !important; height: 297mm !important; object-fit: contain !important; display: block !important; margin: 0 auto !important; border: none !important; box-shadow: none !important; transform: none !important; }
                .printable-a4-page:last-child { page-break-after: avoid !important; break-after: avoid !important; }
                .no-print { display: none !important; }
                .pdf-canvas-item { display: none !important; }
                .pdf-img-item { display: block !important; visibility: visible !important; width: 100% !important; height: auto !important; }
                img.kpp-paraf-img, #pvKppParafImg {
                    display: block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                    width: auto !important;
                    max-width: 70px !important;
                    max-height: 48px !important;
                    object-fit: contain !important;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                @media print {
                    body { background: #fff !important; }
                    img { display: inline-block !important; visibility: visible !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                    img.kpp-paraf-img, #pvKppParafImg {
                        display: block !important;
                        visibility: visible !important;
                        opacity: 1 !important;
                        width: auto !important;
                        max-width: 70px !important;
                        max-height: 48px !important;
                        object-fit: contain !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                    .printable-a4-page { border: none !important; box-shadow: none !important; padding: 3cm !important; margin: 0 auto !important; }
                    .printable-a4-page.printable-lampiran-page { padding: 0 !important; border: none !important; box-shadow: none !important; }
                    .printable-a4-page.printable-lampiran-page img,
                    .printable-a4-page.printable-lampiran-page .pdf-img-item { width: 210mm !important; height: 297mm !important; object-fit: contain !important; display: block !important; margin: 0 auto !important; border: none !important; box-shadow: none !important; transform: none !important; }
                }
            </style>
        </head>
        <body>
            ${papersHtml}
            \<script\>
                function doPrint() {
                    const imgs = Array.from(document.images);
                    let loaded = 0;
                    const total = imgs.length;
                    function run() {
                        setTimeout(function() {
                            window.focus();
                            window.print();
                        }, 250);
                    }
                    if (total === 0) {
                        run();
                    } else {
                        let done = false;
                        function check() {
                            if (!done) {
                                done = true;
                                run();
                            }
                        }
                        imgs.forEach(function(img) {
                            if (img.complete && img.naturalWidth !== 0) {
                                loaded++;
                            } else {
                                img.onload = img.onerror = function() {
                                    loaded++;
                                    if (loaded >= total) check();
                                };
                            }
                        });
                        if (loaded >= total) check();
                        else setTimeout(check, 1500);
                    }
                }
                window.onload = doPrint;
                window.onafterprint = function() { window.close(); };
            <\/script>
        </body>
        </html>
    `);
    printWin.document.close();
}

window.addEventListener('click', function(e) {
    const modal = document.getElementById('reviewSuratPopupModal');
    if (e.target === modal) {
        closeReviewPopup();
    }
});

window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('reviewSuratPopupModal');
        if (modal && modal.style.display !== 'none') {
            closeReviewPopup();
        }
    }
});
</script>
