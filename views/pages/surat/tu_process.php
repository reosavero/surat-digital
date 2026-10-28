<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

<?php 
$meta = $surat['meta'] ?? [];
$katSurat = $meta['kategori_surat'] ?? 'Surat Dinas';
$isDinas = ($katSurat === 'Surat Dinas');
$isProduksi = (($surat['type'] ?? '') === 'izin_produksi');
$isNotaDinas = (($surat['type'] ?? '') === 'nota_dinas' || ($meta['kategori_surat'] ?? '') === 'Surat Nota Dinas');
$tglIndo = !empty($surat['tanggal_surat']) ? date('d F Y', strtotime($surat['tanggal_surat'])) : date('d F Y');
$pemohonTtd = $meta['pemohon_signature'] ?? '';
$kppTtdRaw = !empty($surat['kpp_signature']) ? $surat['kpp_signature'] : (!empty($meta['kpp_signature']) ? $meta['kpp_signature'] : (!empty($meta['pemohon_signature']) ? $meta['pemohon_signature'] : ''));
$kppTtd = ($kppTtdRaw === '/assets/img/ttd_tu_stamp.png') ? '' : $kppTtdRaw;

$getValidParaf = function(...$candidates) {
    foreach ($candidates as $c) {
        if (!empty($c) && is_string($c) && $c !== '/assets/img/ttd_tu_stamp.png') {
            return $c;
        }
    }
    return '';
};

$kppParaf = $getValidParaf(
    $meta['kpp_paraf'] ?? null,
    $meta['paraf_kpp'] ?? null,
    $surat['kpp_paraf'] ?? null,
    $meta['paraf'] ?? null,
    $surat['paraf'] ?? null,
    $meta['tu_paraf'] ?? null,
    $kppAccount['paraf'] ?? null,
    $kppAccount['signature'] ?? null,
    $kppTtd ?? null
);

$tuTtd = $surat['tu_signature'] ?? '';

$tuAccount = User::getSingleUserByRole('tata_usaha') ?: [];
$tuNamaDisplay = $tuAccount['name'] ?? ($meta['penandatangan_nama'] ?? 'Maya Sulfiana, S.Kom');
$tuNipRaw = $tuAccount['nip'] ?? ($meta['penandatangan_nip'] ?? '198612252011012016');
$tuNipDisplay = !empty($tuNipRaw) ? (str_starts_with(strtoupper(trim($tuNipRaw)), 'NIP') ? trim($tuNipRaw) : 'NIP. ' . trim($tuNipRaw)) : 'NIP. 198612252011012016';

$kppUserId = $surat['kpp_user_id'] ?? null;
$kppAccount = [];
if (!empty($kppUserId)) {
    $kppAccount = (new User())->findById((int)$kppUserId) ?: [];
}
if (empty($kppAccount['name']) && !empty($surat['kpp_nama'])) {
    $kppAccount = Database::fetch("SELECT * FROM users WHERE role IN ('kpp_bidang', 'kpp') AND (LOWER(name) = LOWER(?) OR name LIKE ?) AND deleted_at IS NULL LIMIT 1", [$surat['kpp_nama'], '%' . $surat['kpp_nama'] . '%']) ?: [];
}
if (empty($kppAccount['name'])) {
    $pemohonNama = $meta['pemohon_nama'] ?? '';
    if (!empty($pemohonNama)) {
        $kppAccount = Database::fetch("SELECT * FROM users WHERE role IN ('kpp_bidang', 'kpp') AND (LOWER(name) = LOWER(?) OR name LIKE ?) AND deleted_at IS NULL LIMIT 1", [$pemohonNama, '%' . $pemohonNama . '%']) ?: [];
    }
}
if (empty($kppAccount['name'])) {
    $pemohonJab = $meta['pemohon_jabatan'] ?? ($surat['kpp_jabatan'] ?? '');
    if (!empty($pemohonJab)) {
        $kppAccount = Database::fetch("SELECT * FROM users WHERE role IN ('kpp_bidang', 'kpp') AND (divisi IS NOT NULL AND divisi != '' AND ? LIKE CONCAT('%', divisi, '%')) AND deleted_at IS NULL LIMIT 1", [$pemohonJab]) ?: [];
    }
}
if (empty($kppAccount['name'])) {
    $kppAccount = User::getSingleUserByRole('kpp_bidang') ?: [];
}

$kppNamaDisplay = $kppAccount['name'] ?? ($surat['kpp_nama'] ?? ($meta['pemohon_nama'] ?? 'Ir. Sepna Yuhana'));
$kppNipRaw = $kppAccount['nip'] ?? ($surat['kpp_nip'] ?? ($meta['pemohon_nip'] ?? '196809161993032006'));
$kppNipDisplay = !empty($kppNipRaw) ? (str_starts_with(strtoupper(trim($kppNipRaw)), 'NIP') ? trim($kppNipRaw) : 'NIP. ' . trim($kppNipRaw)) : 'NIP. 19680916 199303 2 006';
?>

<style>
    .pdf-lampiran-wrapper-col {
        flex: 0 0 calc(50% - 10px);
        max-width: calc(50% - 10px);
        min-width: 0;
        box-sizing: border-box;
        margin-bottom: 20px;
    }
    .pdf-lampiran-card-preview {
        background: #FFFFFF !important;
        border: none !important;
        padding: 0 !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06) !important;
        position: relative !important;
        aspect-ratio: 210 / 297 !important;
        min-height: 0 !important;
        margin: 0 auto !important;
        box-sizing: border-box !important;
        border-radius: 4px !important;
        width: 100% !important;
        overflow: hidden !important;
    }
    .pdf-lampiran-card-preview img,
    .pdf-lampiran-card-preview .pdf-img-item,
    .pdf-lampiran-card-preview .pdf-canvas-item {
        width: 100% !important;
        height: auto !important;
        object-fit: contain !important;
        display: block !important;
        margin: 0 auto !important;
        border: none !important;
        box-shadow: none !important;
        transform: none !important;
    }

    /* TU Bottom Form Action Bar */
    .tu-bottom-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
        gap: 14px;
        flex-wrap: wrap;
    }
    .tu-bottom-left {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .tu-bottom-right {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .tu-sub-actions {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .btn-print-tu {
        background: #FFFFFF;
        color: #1E293B;
        border: 1.5px solid #CBD5E1;
        font-weight: 700;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-print-tu:hover {
        border-color: #94A3B8;
        background: #F8FAFC;
    }
    .btn-upload-tte {
        font-weight: 700;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 20px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-action-batal {
        padding: 12px 24px;
        font-size: 15px;
        font-weight: 600;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-action-revisi {
        padding: 12px 24px;
        font-size: 15px;
        font-weight: 700;
        background: #FFFBEB;
        color: #D97706;
        border: 1.5px solid #FCD34D;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-action-revisi:hover {
        background: #FEF3C7;
        border-color: #F59E0B;
    }
    .btn-action-teruskan {
        padding: 12px 28px;
        font-size: 15px;
        font-weight: 700;
        background-color: #2563EB;
        border-color: #2563EB;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(37,99,235,0.2);
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-action-teruskan:hover {
        background-color: #1D4ED8;
        border-color: #1D4ED8;
    }

    /* Signature & Paraf Modal */
    .sig-modal-dialog {
        background: #FFFFFF;
        width: 100%;
        max-width: 520px;
        border-radius: 20px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        border: 1px solid #E2E8F0;
        animation: sigModalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes sigModalFadeIn {
        from { opacity: 0; transform: scale(0.96) translateY(8px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .sig-modal-header {
        padding: 16px 22px;
        background: linear-gradient(135deg, #F8FAFC 0%, #EFF6FF 100%);
        border-bottom: 1px solid #E2E8F0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .sig-header-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: #EFF6FF;
        color: #2563EB;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
        border: 1px solid #BFDBFE;
        flex-shrink: 0;
    }
    .sig-header-title {
        margin: 0;
        font-size: 16.5px;
        font-weight: 800;
        color: #0F172A;
        line-height: 1.25;
    }
    .sig-header-subtitle {
        font-size: 11.5px;
        color: #64748B;
        margin-top: 1px;
    }
    .sig-modal-body {
        padding: 20px 22px 22px;
    }
    .sig-instructions-text {
        font-size: 13px;
        color: #475569;
        margin-top: 0;
        margin-bottom: 12px;
        line-height: 1.45;
    }
    .sig-canvas-wrapper {
        position: relative;
        width: 100%;
        height: 190px;
        background: #FFFFFF;
        border: 2px dashed #3B82F6;
        border-radius: 14px;
        margin-bottom: 16px;
        overflow: hidden;
        touch-action: none;
        user-select: none;
        -webkit-user-select: none;
        box-shadow: inset 0 2px 6px rgba(0,0,0,0.03);
    }
    #modalCanvas {
        width: 100%;
        height: 190px;
        cursor: crosshair;
        touch-action: none;
        display: block;
        position: relative;
        z-index: 2;
    }
    .sig-canvas-watermark {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        color: #94A3B8;
        font-size: 12.5px;
        font-weight: 500;
        pointer-events: none;
        z-index: 1;
        opacity: 0.7;
    }
    .sig-modal-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .sig-footer-subgroup {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .sig-footer-main {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .btn-sig-clear {
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 9px;
        background: #F1F5F9;
        color: #475569;
        border: 1px solid #CBD5E1;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-sig-cancel {
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-sig-save {
        padding: 9px 20px;
        font-size: 14px;
        font-weight: 700;
        background-color: #2563EB;
        border-color: #2563EB;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    /* Screen Responsiveness for Tablet & Mobile Devices */
    @media screen and (max-width: 992px) {
        .admin-review-grid-container {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            gap: 18px !important;
            width: 100% !important;
        }
        .admin-review-col {
            width: 100% !important;
            max-width: 100% !important;
            flex: 1 1 100% !important;
            box-sizing: border-box !important;
        }
        .a4-paper-preview {
            width: 100% !important;
            max-width: 100% !important;
            min-height: auto !important;
            height: auto !important;
            padding: 24px !important;
            box-sizing: border-box !important;
            border-radius: 14px !important;
        }
        .pdf-lampiran-wrapper-col {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
    }

    @media screen and (max-width: 768px) {
        .tu-bottom-actions {
            flex-direction: column-reverse !important;
            align-items: stretch !important;
            gap: 12px !important;
            margin-bottom: 24px !important;
            width: 100% !important;
        }
        .tu-bottom-right {
            display: flex !important;
            flex-direction: column !important;
            gap: 10px !important;
            width: 100% !important;
        }
        .btn-action-teruskan {
            order: -1 !important;
            width: 100% !important;
            padding: 14px 20px !important;
            font-size: 15.5px !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 14px rgba(37,99,235,0.28) !important;
        }
        .tu-sub-actions {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 10px !important;
            width: 100% !important;
        }
        .btn-action-batal, .btn-action-revisi {
            width: 100% !important;
            padding: 12px 14px !important;
            font-size: 14px !important;
            border-radius: 10px !important;
        }
        .tu-bottom-left {
            display: flex !important;
            flex-direction: column !important;
            gap: 10px !important;
            width: 100% !important;
        }
        .btn-upload-tte, .btn-print-tu {
            width: 100% !important;
            justify-content: center !important;
            padding: 12px 16px !important;
            font-size: 14px !important;
            border-radius: 10px !important;
        }
    }

    @media screen and (max-width: 576px) {
        .a4-paper-preview {
            padding: 16px 12px !important;
        }

        /* Mobile Signature & Paraf Modal */
        #signatureModalPopup {
            padding: 12px !important;
            align-items: flex-end !important;
        }
        .sig-modal-dialog {
            width: 100% !important;
            max-width: 100% !important;
            border-radius: 20px 20px 16px 16px !important;
            max-height: 94vh !important;
        }
        .sig-modal-header {
            padding: 14px 16px !important;
        }
        .sig-header-icon {
            width: 36px !important;
            height: 36px !important;
            font-size: 17px !important;
            border-radius: 10px !important;
        }
        .sig-header-title {
            font-size: 15px !important;
        }
        .sig-header-subtitle {
            font-size: 11px !important;
        }
        .sig-modal-body {
            padding: 14px 14px 18px !important;
            overflow-y: auto !important;
        }
        .sig-instructions-text {
            font-size: 12px !important;
            margin-bottom: 10px !important;
        }
        .sig-canvas-wrapper {
            height: 220px !important;
            margin-bottom: 14px !important;
            border-radius: 12px !important;
        }
        #modalCanvas {
            height: 220px !important;
        }
        .sig-modal-footer {
            flex-direction: column !important;
            gap: 10px !important;
            width: 100% !important;
        }
        .sig-footer-main {
            width: 100% !important;
            order: 1 !important;
        }
        .btn-sig-save {
            width: 100% !important;
            justify-content: center !important;
            padding: 13px !important;
            font-size: 15px !important;
            border-radius: 10px !important;
        }
        .sig-footer-subgroup {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 8px !important;
            width: 100% !important;
            order: 2 !important;
        }
        .btn-sig-clear, .btn-sig-cancel {
            width: 100% !important;
            justify-content: center !important;
            padding: 10.5px 12px !important;
            font-size: 13.5px !important;
            border-radius: 8px !important;
        }

        #ttdChoiceModal > div,
        #revisiModal > div,
        #tuWarningModal > div {
            width: 100% !important;
            max-width: 100% !important;
            border-radius: 18px !important;
        }
        #ttdChoiceModal [style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
            gap: 12px !important;
        }
    }

    @media print {
        @page { size: A4 portrait; margin: 0; }
        html, body { width: 210mm !important; height: auto !important; margin: 0 !important; padding: 0 !important; background: #ffffff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .no-print, button, .btn-btn { display: none !important; }
        #pvNomorBox, #pvTtdBox, #pvTtdBoxSingle, #pvParafBox { border: none !important; background: transparent !important; box-shadow: none !important; padding: 0 !important; }
        #pvNomorPlaceholder, #pvTtdPlaceholder, #pvTtdPlaceholderSingle, #pvParafPlaceholder { display: none !important; }
        .admin-review-grid-container, .admin-review-col {
            display: block !important; position: static !important; float: none !important; width: 210mm !important; max-width: 210mm !important; flex: none !important; height: auto !important; margin: 0 auto !important; padding: 0 !important; border: none !important; background: transparent !important; page-break-after: auto !important; break-after: auto !important;
        }
        .admin-review-col-page1 {
            page-break-before: auto !important; break-before: auto !important; page-break-after: always !important; break-after: page !important;
        }
        .admin-review-col-page2 {
            page-break-before: always !important; break-before: page !important; page-break-after: always !important; break-after: page !important;
        }
        .a4-paper-preview {
            visibility: visible !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; width: 210mm !important; max-width: 210mm !important; height: 297mm !important; max-height: 297mm !important; padding: 2.5cm !important; margin: 0 auto !important; box-shadow: none !important; border: none !important; background: #ffffff !important; box-sizing: border-box !important; page-break-after: always !important; break-after: page !important; overflow: hidden !important; flex: none !important;
        }
        .pdf-lampiran-wrapper-col {
            display: block !important; position: static !important; float: none !important; width: 100% !important; max-width: 100% !important; flex: none !important; height: 297mm !important; max-height: 297mm !important; margin: 0 auto !important; padding: 0 !important; border: none !important; background: transparent !important; page-break-before: always !important; break-before: page !important; page-break-after: avoid !important; break-after: avoid !important; page-break-inside: avoid !important; break-inside: avoid !important;
        }
        .admin-review-grid-container > .pdf-lampiran-wrapper-col:last-child {
            page-break-after: avoid !important; break-after: avoid !important;
        }
        .pdf-lampiran-card-preview {
            visibility: visible !important; display: block !important; width: 100% !important; max-width: 100% !important; height: 297mm !important; max-height: 297mm !important; padding: 0 !important; margin: 0 auto !important; box-shadow: none !important; border: none !important; background: #ffffff !important; box-sizing: border-box !important; page-break-before: avoid !important; break-before: avoid !important; overflow: hidden !important; flex: none !important;
        }
        .pdf-img-item, .pdf-lampiran-card-preview img {
            display: block !important; visibility: visible !important; width: 100% !important; max-width: 100% !important; height: 100% !important; max-height: 297mm !important; object-fit: contain !important; margin: 0 auto !important; padding: 0 !important; border: none !important;
        }
    }
</style>

<div style="max-width: 1360px; margin: 0 auto; padding-bottom: 40px;">
    <!-- Top Action & Navigation Header -->
    <div class="page-header-banner" style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-pen-fill" style="color: #2563EB;"></i> Proses Surat & Input Tata Usaha
            </h1>
        </div>
    </div>

    <?php if (!empty($meta['catatan_revisi'])): ?>
        <div style="background: #FEF2F2; border: 1.5px solid #FCA5A5; border-radius: 14px; padding: 16px 20px; margin-bottom: 24px; color: #991B1B; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.08);">
            <div style="font-weight: 800; font-size: 14px; margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-exclamation-triangle-fill" style="color: #DC2626; font-size: 18px;"></i> Catatan Revisi dari Sekretaris:
            </div>
            <div style="font-size: 13.5px; font-weight: 600; line-height: 1.5; color: #7F1D1D;">
                "<?= htmlspecialchars($meta['catatan_revisi']) ?>"
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isNotaDinas): ?>
    <!-- SINGLE A4 PAPER FOR NOTA DINAS -->
    <div style="display: flex; justify-content: center; margin-bottom: 28px; width: 100%;">
        <div class="card-panel-variant a4-paper-preview" style="width: 100%; max-width: 800px; padding: 2.5cm; border-radius: 18px; background: #FFFFFF; border: 1.5px solid #CBD5E1; box-shadow: 0 4px 20px rgba(0,0,0,0.06); font-family: Arial, sans-serif; color: #000000; position: relative; min-height: 270mm; margin: 0 auto; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <!-- Logo Top Left -->
                <div style="margin-bottom: 16px;">
                    <img src="<?= BASE_URL ?>/assets/img/tvri3.svg" style="height: 52px; width: auto;" alt="TVRI Logo">
                </div>

                <!-- Title Centered -->
                <div style="text-align: center; margin-bottom: 16px;">
                    <h2 style="font-size: 13.5px; font-weight: normal; letter-spacing: 0.5px; margin: 0; color: #000000; text-transform: uppercase; text-decoration: underline; font-family: Arial, sans-serif;">NOTA DINAS</h2>
                    <div style="font-size: 11.5px; color: #111111; margin-top: 4px; font-family: Arial, sans-serif;">
                        Nomor : <?= htmlspecialchars($surat['nomor_surat'] ?: '          /UM.03.02/I1.1.1/IX/2026') ?>
                    </div>
                </div>

                <!-- Metadata Table -->
                <table style="width: 100%; border-collapse: collapse; font-size: 11.5px; line-height: 1.3; margin-bottom: 8px; color: #000000;">
                    <tr>
                        <td style="width: 90px; vertical-align: top; padding: 2px 0;">Kepada Yth</td>
                        <td style="width: 12px; vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;">
                            <span style="font-weight: 500;"><?= htmlspecialchars($surat['kepada'] ?? 'Kepala TVRI Stasiun Jawa Timur') ?></span>
                            <?php if (!str_contains(strtolower($surat['kepada'] ?? ''), 'tvri stasiun jawa timur')): ?>
                                <div style="font-weight: 500; color: #000;"><?= htmlspecialchars($meta['kepada_sub'] ?? 'TVRI Stasiun Jawa Timur') ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">Dari</td>
                        <td style="vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($meta['dari'] ?? 'Kepala Sub Bagian Tata Usaha TVRI Jawa Timur') ?></td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">Hal</td>
                        <td style="vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0; font-weight: 500;">
                            <span><?= htmlspecialchars($surat['perihal']) ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">Sifat</td>
                        <td style="vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($meta['sifat_surat'] ?? 'Biasa') ?></td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">Lampiran</td>
                        <td style="vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($surat['lampiran'] ?? '3 (Tiga) Lembar') ?></td>
                    </tr>
                    <tr>
                        <td style="width: 90px; vertical-align: top; padding: 2px 0;">Tanggal</td>
                        <td style="width: 12px; vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;"><?= $tglIndo ?></td>
                    </tr>
                </table>

                <div style="border-top: 1.5px solid #000000; margin: 8px 0 14px 0;"></div>

                <?php if (!empty($meta['pemohon_nama'])): ?>
                <!-- Table Data Diri Pemohon -->
                <table style="width: 100%; border-collapse: collapse; font-size: 11px; line-height: 1.3; margin-bottom: 12px; color: #000000;">
                    <tr>
                        <td colspan="3" style="padding: 2px 0; font-weight: normal;">Saya yang bertanda tangan dibawah ini :</td>
                    </tr>
                    <tr>
                        <td style="width: 100px; vertical-align: top; padding: 1.5px 0; padding-left: 10px;">Nama</td>
                        <td style="width: 12px; vertical-align: top; padding: 1.5px 0;">:</td>
                        <td style="vertical-align: top; padding: 1.5px 0; font-weight: 500;"><?= htmlspecialchars($meta['pemohon_nama']) ?></td>
                    </tr>
                    <?php if (!empty($meta['pemohon_nopeg'])): ?>
                    <tr>
                        <td style="vertical-align: top; padding: 1.5px 0; padding-left: 10px;">NOPEG / NIP</td>
                        <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                        <td style="vertical-align: top; padding: 1.5px 0;"><?= htmlspecialchars($meta['pemohon_nopeg']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($meta['pemohon_jabatan'])): ?>
                    <tr>
                        <td style="vertical-align: top; padding: 1.5px 0; padding-left: 10px;">Jabatan</td>
                        <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                        <td style="vertical-align: top; padding: 1.5px 0;"><?= htmlspecialchars($meta['pemohon_jabatan']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($meta['pemohon_hp'])): ?>
                    <tr>
                        <td style="vertical-align: top; padding: 1.5px 0; padding-left: 10px;">Nomor HP.</td>
                        <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                        <td style="vertical-align: top; padding: 1.5px 0;"><?= htmlspecialchars($meta['pemohon_hp']) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php endif; ?>

                <div style="font-size: 11.5px; color: #000000; margin-bottom: 6px; line-height: 1.35;">Dengan hormat,</div>
                <div style="line-height: 1.35; text-align: justify; font-size: 11.5px; font-family: Arial, sans-serif; color: #000000; margin-bottom: 14px; white-space: pre-line;"><?= htmlspecialchars($meta['isi_surat_dinas'] ?? $surat['isi_surat']) ?></div>

                <?php 
                $isiTextDinas = trim($meta['isi_surat_dinas'] ?? $surat['isi_surat'] ?? '');
                $penutupDinas = trim($meta['penutup_surat'] ?? $meta['penutup_dinas'] ?? '');
                if (!empty($penutupDinas) && (str_contains($isiTextDinas, 'Demikian permohonan') || str_contains($isiTextDinas, $penutupDinas))) {
                    $penutupDinas = '';
                }
                if (!empty($penutupDinas)):
                ?>
                    <div style="line-height: 1.35; text-align: justify; font-size: 11.5px; font-family: Arial, sans-serif; color: #000000; margin-bottom: 14px; white-space: pre-line;"><?= htmlspecialchars($penutupDinas) ?></div>
                <?php endif; ?>

                <!-- Signature Block (2 columns: Left for TU TTD + Paraf KPP, Right for Pemohon) -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 14px; margin-bottom: 14px;">
                    <!-- Left Column: Kepala Sub Bagian Tata Usaha -->
                    <div style="width: 250px; text-align: left; font-size: 12px; line-height: 1.15; color: #000000;">
                        <div>Kepala Sub Bagian Tata Usaha</div>
                        <div>TVRI Stasiun Jawa Timur</div>
                        
                        <!-- Clickable TTD Area for TU -->
                        <?php 
                        $hasTuSigSingle = !empty($surat['tu_signature']);
                        $boxStyleSingle = $hasTuSigSingle 
                            ? 'height: 55px; width: 100%; margin: 4px 0; display: flex; align-items: center; justify-content: flex-start; cursor: pointer; border: none; background: transparent; padding: 0;'
                            : 'height: 60px; width: 185px; max-width: 100%; margin: 6px 0; display: flex; align-items: center; justify-content: center; text-align: center; cursor: pointer; border: 1.5px dashed #3B82F6; border-radius: 8px; background: #F8FAFC; transition: all 0.2s;';
                        ?>
                        <div id="pvTtdBoxSingle" onclick="openSignatureModal('ttd')" title="Klik di sini untuk membubuhkan / mengubah TTD Digital TU"
                             style="<?= $boxStyleSingle ?>"
                             onmouseover="if (!this.style.border || this.style.border.includes('dashed')) { this.style.background='#EFF6FF'; this.style.borderColor='#1D4ED8'; }"
                             onmouseout="if (!this.style.border || this.style.border.includes('dashed')) { this.style.background='#F8FAFC'; this.style.borderColor='#3B82F6'; }">
                            <img id="pvTtdImgSingle" src="<?= $hasTuSigSingle ? getImgUrl($surat['tu_signature']) : '' ?>" style="max-height: 55px; object-fit: contain; text-align: left; margin: 0; <?= !$hasTuSigSingle ? 'display: none;' : '' ?>">
                            <span id="pvTtdPlaceholderSingle" class="no-print" style="font-size: 11px; color: #2563EB; font-weight: 700; <?= $hasTuSigSingle ? 'display: none;' : '' ?>">
                                <i class="bi bi-pencil-square"></i> Klik untuk isi TTD
                            </span>
                        </div>

                        <!-- Name & NIP block with Paraf KPP Bidang displaying statically to the left -->
                        <div style="position: relative; display: inline-block;">
                            <?php if (!empty($kppParaf)): ?>
                                <div style="position: absolute; right: 100%; top: 50%; transform: translateY(-50%); margin-right: 8px; white-space: nowrap; display: flex; align-items: center; justify-content: center; z-index: 10;">
                                    <img src="<?= getImgUrl($kppParaf) ?>" class="kpp-paraf-img" style="max-height: 48px; width: auto; max-width: 70px; object-fit: contain; display: block; visibility: visible;" alt="Paraf KPP">
                                </div>
                            <?php endif; ?>

                            <div style="text-decoration: underline; font-weight: 500;"><?= htmlspecialchars($tuNamaDisplay) ?></div>
                            <div><?= htmlspecialchars($tuNipDisplay) ?></div>
                        </div>
                    </div>

                    <!-- Right Column: Hormat Saya (Pemohon TTD) -->
                    <div style="width: 230px; text-align: center; font-size: 12px; line-height: 1.15; color: #000000;">
                        <div>Hormat Saya,</div>
                        <div style="min-height: 35px; margin: 3px 0; display: flex; align-items: center; justify-content: center;">
                            <?php 
                            $pemohonSig = $meta['pemohon_signature_data'] ?? $meta['pemohon_signature'] ?? '';
                            ?>
                            <?php if (!empty($pemohonSig)): ?>
                                <img src="<?= getImgUrl($pemohonSig) ?>" style="max-height: 45px; object-fit: contain;">
                            <?php else: ?>
                                <div style="height: 35px;"></div>
                            <?php endif; ?>
                        </div>
                        <div><?= htmlspecialchars($meta['pemohon_nama'] ?? '') ?></div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div style="padding-top: 12px;">
                <?php if (!$isNotaDinas): ?>
                <?php 
                $adminBarcodeLink = !empty($meta['admin_gdrive_web_link']) 
                    ? $meta['admin_gdrive_web_link'] 
                    : (!empty($meta['gdrive_web_link']) 
                        ? $meta['gdrive_web_link'] 
                        : (!empty($surat['gdrive_web_link']) && !str_contains($surat['gdrive_web_link'], '/file/d/') 
                            ? $surat['gdrive_web_link'] 
                            : ''));

                $qrUrlTu = !empty($adminBarcodeLink) ? $adminBarcodeLink : 'https://drive.google.com';
                $qrSrcTu = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrUrlTu);
                ?>
                <div style="display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 6px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <a href="<?= htmlspecialchars($qrUrlTu) ?>" target="_blank" title="Scan / Klik untuk membuka URL Google Drive">
                            <img src="<?= $qrSrcTu ?>" style="width: 48px; height: 48px; border: 1.5px solid #000000; background: #FFFFFF; padding: 2px;" alt="Barcode QR Google Drive">
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1.2fr 1.2fr 1fr; gap: 10px; font-size: 9px; color: #333333; line-height: 1.35;">
                    <div>
                        <strong>LEMBAGA PENYIARAN PUBLIK</strong><br>
                        TELEVISI REPUBLIK INDONESIA<br>
                        STASIUN JAWA TIMUR
                    </div>
                    <div>
                        Jl. Mayjend Sungkono No.124,<br>
                        Surabaya, Jawa Timur, 60189<br>
                        Indonesia
                    </div>
                    <div>
                        P (031) 567452 5678298<br>
                        F (031) 5678452 5616774<br>
                        www.tvri.go.id
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- SIDE-BY-SIDE 2-COLUMN DOCUMENT PREVIEW (KANAN & KIRI - SAME AS ADMIN REVIEW) -->
    <div class="admin-review-grid-container" style="display: flex; flex-direction: row; gap: 24px; align-items: stretch; flex-wrap: nowrap; margin-bottom: 28px; width: 100%;">
        
        <!-- SURAT 1 (SISI KIRI): SURAT PERMOHONAN -->
        <div class="admin-review-col admin-review-col-page1" style="flex: 1 1 0px; min-width: 0; width: 50%; max-width: 50%; box-sizing: border-box; display: flex; flex-direction: column;">
            <div class="card-panel-variant a4-paper-preview" style="padding: 2.2cm 2.5cm; border-radius: 18px; background: #FFFFFF; border: 1.5px solid #CBD5E1; box-shadow: 0 4px 20px rgba(0,0,0,0.06); font-family: Arial, sans-serif; color: #000000; position: relative; min-height: 270mm; margin: 0 auto; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; width: 100%;">
                <div>
                    <!-- Logo Top Left -->
                    <div style="margin-bottom: 14px;">
                        <img src="<?= BASE_URL ?>/assets/img/tvri3.svg" style="height: 48px; width: auto;" alt="TVRI Logo">
                    </div>

                    <!-- Header Line: Perihal Left, Tanggal Right -->
                    <div style="font-size: 11px; line-height: 1.3; margin-bottom: 14px; color: #000000;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div>Perihal : <span style="font-weight: 500;"><?= htmlspecialchars($meta['perihal_permohonan'] ?? $surat['perihal']) ?></span></div>
                                <?php if (!empty($meta['lokasi_sub_permohonan']) || !empty($meta['sub_perihal'])): ?>
                                    <div style="font-weight: 500; color: #000;"><?= htmlspecialchars($meta['lokasi_sub_permohonan'] ?? $meta['sub_perihal'] ?? '') ?></div>
                                <?php endif; ?>
                            </div>
                            <div style="white-space: nowrap;">Surabaya, <?= $tglIndo ?></div>
                        </div>
                    </div>

                    <!-- Kepada Block -->
                    <div style="font-size: 11px; line-height: 1.3; margin-bottom: 14px; color: #000000;">
                        <div>Kepada :</div>
                        <div style="font-weight: 500;"><?= htmlspecialchars($meta['kepada_permohonan'] ?? $surat['kepada'] ?? 'Yth. Kepala Sub Bagian Tata Usaha') ?></div>
                        <div style="font-weight: 500;"><?= htmlspecialchars($meta['kepada_sub_permohonan'] ?? 'TVRI Stasiun Jawa Timur') ?></div>
                    </div>

                    <!-- Salutation & Pembuka -->
                    <?php 
                    $pembukaTextPermohonan = !empty($meta['pembuka_surat']) 
                        ? $meta['pembuka_surat'] 
                        : ((!empty($meta['isi_surat_permohonan']) && $meta['isi_surat_permohonan'] !== ($surat['isi_surat'] ?? '')) 
                            ? $meta['isi_surat_permohonan'] 
                            : ($isProduksi 
                                ? 'Sehubungan dengan adanya permintaan kerjasama dari bidang PU dan Promo, maka kami mengajukan permohonan produksi acara sebagai berikut :' 
                                : ($surat['isi_surat'] ?? '')));
                    $pembukaTextPermohonanClean = trim(str_replace('Demikian permohonan ini kami sampaikan, atas perhatian dan kerjasamanya kami ucapkan terima kasih.', '', $pembukaTextPermohonan));
                    ?>
                    <div style="font-size: 11px; line-height: 1.35; margin-bottom: 10px; color: #000000;">
                        <div style="margin-bottom: 6px;">Dengan hormat,</div>
                        <div style="white-space: pre-line; text-align: justify; line-height: 1.35; font-size: 11px; font-family: Arial, sans-serif; color: #000000;"><?= htmlspecialchars($pembukaTextPermohonanClean) ?></div>
                    </div>

                    <?php if ($isProduksi || !empty($meta['nama_program']) || !empty($meta['mata_acara'])): ?>
                    <!-- Detail Parameter Grid Preview (Centered, 2 Column Alignment) -->
                    <table style="width: 100%; border-collapse: collapse; font-size: 10.5px; line-height: 1.25; margin: 0 auto 12px auto; color: #000000;">
                        <tr>
                            <td style="width: 80px; vertical-align: top; padding: 2px 0;">Mata Acara</td>
                            <td style="width: 10px; vertical-align: top; padding: 2px 0;">:</td>
                            <td style="vertical-align: top; padding: 2px 0; width: 35%;"><?= htmlspecialchars($meta['mata_acara'] ?? '-') ?></td>
                            
                            <td style="width: 80px; vertical-align: top; padding: 2px 0;">Lokasi Syuting</td>
                            <td style="width: 10px; vertical-align: top; padding: 2px 0;">:</td>
                            <td style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($meta['lokasi_syuting'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 2px 0;">Judul</td>
                            <td style="vertical-align: top; padding: 2px 0;">:</td>
                            <td colspan="4" style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($meta['nama_program'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 2px 0;">Tgl. Syuting</td>
                            <td style="vertical-align: top; padding: 2px 0;">:</td>
                            <td style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($meta['tanggal_mulai'] ?? '-') ?></td>
                            
                            <td style="vertical-align: top; padding: 2px 0;">Paket</td>
                            <td style="vertical-align: top; padding: 2px 0;">:</td>
                            <td style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($meta['paket'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 2px 0;">Tgl. Siaran</td>
                            <td style="vertical-align: top; padding: 2px 0;">:</td>
                            <td style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($meta['tanggal_siaran'] ?? 'Menyesuaikan Pola') ?></td>
                            
                            <td style="vertical-align: top; padding: 2px 0;">Sumber Biaya</td>
                            <td style="vertical-align: top; padding: 2px 0;">:</td>
                            <td style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($meta['sumber_biaya'] ?? 'PNBP') ?></td>
                        </tr>
                    </table>
                    <?php endif; ?>

                    <!-- Optional Kru Table -->
                    <?php 
                    $jsonRaw = $meta['kru_table_json'] ?? '';
                    $jsonTable = !empty($jsonRaw) ? json_decode($jsonRaw, true) : null;
                    if ($jsonTable && !empty($jsonTable['headers']) && !empty($jsonTable['rows'])):
                        $tHeaders = $jsonTable['headers'];
                        $tRows = $jsonTable['rows'];
                        $tColWidths = $jsonTable['colWidths'] ?? [];
                        $tTotalCols = count($tHeaders);
                        if (empty($tColWidths) || count($tColWidths) !== $tTotalCols) {
                            if ($tTotalCols === 6) {
                                $tColWidths = [6, 28, 16, 6, 28, 16];
                            } elseif ($tTotalCols === 3) {
                                $tColWidths = [8, 52, 40];
                            } else {
                                $eqW = round(100 / $tTotalCols, 1);
                                $tColWidths = array_fill(0, $tTotalCols, $eqW);
                            }
                        }
                        $tFontSize = '9px';
                        $tPaddingTh = '2.5px 3px';
                        $tPaddingTd = '2px 3px';
                        $tLineHeight = '1.2';
                        if ($tTotalCols >= 9) {
                            $tFontSize = '7px';
                            $tPaddingTh = '1.5px 1.5px';
                            $tPaddingTd = '1.5px 1.5px';
                            $tLineHeight = '1.1';
                        } elseif ($tTotalCols >= 7) {
                            $tFontSize = '8px';
                            $tPaddingTh = '2px 2px';
                            $tPaddingTd = '1.5px 2px';
                            $tLineHeight = '1.15';
                        }
                    ?>
                        <div style="margin-bottom: 12px; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
                            <table style="width: 100%; border-collapse: collapse; font-size: <?= $tFontSize ?>; line-height: <?= $tLineHeight ?>; text-align: left; color: #000; border: 1px solid #000; table-layout: fixed; box-sizing: border-box;" border="1">
                                <thead>
                                    <tr style="background: #FFFFFF;">
                                        <?php foreach ($tHeaders as $idx => $h): ?>
                                            <?php
                                            $isNo = (strtoupper(trim($h)) === 'NO') || ($idx === 0) || ($tTotalCols === 6 && $idx === 3);
                                            $wVal = !empty($tColWidths[$idx]) ? ($tColWidths[$idx] . '%') : ($isNo ? '6%' : '');
                                            $wStyle = !empty($wVal) ? ('width: ' . $wVal . ';') : '';
                                            $alignStyle = $isNo ? 'text-align: center;' : 'text-align: left;';
                                            ?>
                                            <th style="padding: <?= $tPaddingTh ?>; border: 1px solid #000; font-weight: bold; font-family: Arial, sans-serif; <?= $wStyle ?> <?= $alignStyle ?> word-break: break-word; overflow-wrap: break-word; hyphens: auto; box-sizing: border-box;">
                                                <?= htmlspecialchars($h) ?>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tRows as $r): ?>
                                        <tr>
                                            <?php foreach ($r as $idx => $cell): ?>
                                                <?php
                                                $isNo = (isset($tHeaders[$idx]) && strtoupper(trim($tHeaders[$idx])) === 'NO') || ($idx === 0) || ($tTotalCols === 6 && $idx === 3);
                                                $wVal = !empty($tColWidths[$idx]) ? ($tColWidths[$idx] . '%') : ($isNo ? '6%' : '');
                                                $wStyle = !empty($wVal) ? ('width: ' . $wVal . ';') : '';
                                                $alignStyle = $isNo ? 'text-align: center;' : 'text-align: left;';
                                                ?>
                                                <td style="padding: <?= $tPaddingTd ?>; border: 1px solid #000; font-family: Arial, sans-serif; <?= $wStyle ?> <?= $alignStyle ?> word-break: break-word; overflow-wrap: break-word; box-sizing: border-box;">
                                                    <?= htmlspecialchars($cell) ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- Kalimat Penutup Surat Permohonan -->
                    <?php 
                    $penutupPermohonan = trim($meta['penutup_surat'] ?? $meta['penutup_permohonan'] ?? '');
                    if (empty($penutupPermohonan) && ($isProduksi || !empty($meta['pembuka_surat']))) {
                        $penutupPermohonan = 'Demikian permohonan ini kami sampaikan, atas perhatian dan kerjasamanya kami ucapkan terima kasih.';
                    }
                    if (!empty($penutupPermohonan) && (str_contains($pembukaTextPermohonanClean, 'Demikian permohonan') || str_contains($pembukaTextPermohonanClean, $penutupPermohonan))) {
                        $penutupPermohonan = '';
                    }
                    if (!empty($penutupPermohonan)):
                    ?>
                        <div style="margin-top: 10px; margin-bottom: 12px; font-size: 11px; line-height: 1.35; color: #000000; font-family: Arial, sans-serif;">
                            <?= htmlspecialchars($penutupPermohonan) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Signature Block KPP Bidang (Surat Permohonan) -->
                    <div style="display: flex; justify-content: flex-end; margin-top: 8px; margin-bottom: 6px;">
                        <div style="width: 210px; max-width: 100%; text-align: center; font-size: 10.5px; line-height: 1.25; color: #000000; word-wrap: break-word; overflow-wrap: break-word; box-sizing: border-box;">
                            <div style="white-space: normal; word-wrap: break-word; word-break: normal;"><?= htmlspecialchars($surat['kpp_jabatan'] ?? ($isProduksi ? 'Ketua Perencana dan Pengendalian Program' : 'Ketua Perencana dan Pengendalian Teknik')) ?></div>
                            <div>TVRI Stasiun Jawa Timur</div>
                            <div style="height: 48px; margin: 3px 0; display: flex; align-items: center; justify-content: center;">
                                <?php if (!empty($kppTtd)): ?>
                                    <img src="<?= getImgUrl($kppTtd) ?>" style="max-height: 44px; width: auto; object-fit: contain;" alt="TTD KPP Bidang">
                                <?php else: ?>
                                    <span style="font-size: 9.5px; color: #666666; font-style: italic; border: 1px dashed #999999; padding: 3px 8px; border-radius: 4px;">(TTD KPP Bidang)</span>
                                <?php endif; ?>
                            </div>
                            <div style="text-decoration: underline; font-weight: normal;"><?= htmlspecialchars($kppNamaDisplay) ?></div>
                            <div><?= htmlspecialchars($kppNipDisplay) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div style="padding-top: 10px;">
                    <div style="display: grid; grid-template-columns: 1.2fr 1.2fr 1fr; gap: 8px; font-size: 8px; color: #333333; line-height: 1.25;">
                        <div>
                            <strong>LEMBAGA PENYIARAN PUBLIK</strong><br>
                            TELEVISI REPUBLIK INDONESIA<br>
                            STASIUN JAWA TIMUR
                        </div>
                        <div>
                            Jl. Mayjend Sungkono No.124,<br>
                            Surabaya, Jawa Timur, 60189<br>
                            Indonesia
                        </div>
                        <div>
                            P (031) 567452 5678298<br>
                            F (031) 5678452 5616774<br>
                            www.tvri.go.id
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SURAT 2 (SISI KANAN): NOTA DINAS -->
        <div class="admin-review-col admin-review-col-page2" style="flex: 1 1 0px; min-width: 0; width: 50%; max-width: 50%; box-sizing: border-box; display: flex; flex-direction: column;">
            <div class="card-panel-variant a4-paper-preview" style="padding: 2.5cm; border-radius: 18px; background: #FFFFFF; border: 1.5px solid #CBD5E1; box-shadow: 0 4px 20px rgba(0,0,0,0.06); font-family: Arial, sans-serif; color: #000000; position: relative; min-height: 270mm; margin: 0 auto; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; width: 100%;">
                <div>
                    <!-- Logo Top Left -->
                    <div style="margin-bottom: 14px;">
                        <img src="<?= BASE_URL ?>/assets/img/tvri3.svg" style="height: 48px; width: auto;" alt="TVRI Logo">
                    </div>

                    <!-- Title Centered -->
                    <div style="text-align: center; margin-bottom: 14px;">
                        <h2 style="font-size: 12.5px; font-weight: normal; letter-spacing: 0.5px; margin: 0; color: #000000; text-transform: uppercase; text-decoration: underline; font-family: Arial, sans-serif;">NOTA DINAS</h2>
                        <div style="font-size: 11px; color: #111111; margin-top: 3px; font-family: Arial, sans-serif;">
                            Nomor : <?= htmlspecialchars($surat['nomor_surat'] ?: '          /UM.03.02/I1.1.1/IX/2026') ?>
                        </div>
                    </div>

                    <!-- Metadata Table -->
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px; line-height: 1.3; margin-bottom: 8px; color: #000000;">
                        <tr>
                            <td style="width: 85px; vertical-align: top; padding: 2px 0;">Kepada Yth</td>
                            <td style="width: 10px; vertical-align: top; padding: 2px 0;">:</td>
                            <td style="vertical-align: top; padding: 2px 0;">
                                <span style="font-weight: 500;"><?= htmlspecialchars($surat['kepada'] ?? 'Kepala TVRI Stasiun Jawa Timur') ?></span>
                                <?php if (!str_contains(strtolower($surat['kepada'] ?? ''), 'tvri stasiun jawa timur')): ?>
                                    <div style="font-weight: 500; color: #000;"><?= htmlspecialchars($meta['kepada_sub'] ?? 'TVRI Stasiun Jawa Timur') ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 2px 0;">Dari</td>
                            <td style="vertical-align: top; padding: 2px 0;">:</td>
                            <td style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($meta['dari'] ?? 'Kepala Sub Bagian Tata Usaha TVRI Jawa Timur') ?></td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 2px 0;">Hal</td>
                            <td style="vertical-align: top; padding: 2px 0;">:</td>
                            <td style="vertical-align: top; padding: 2px 0; font-weight: 500;">
                                <span><?= htmlspecialchars($surat['perihal']) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 2px 0;">Sifat</td>
                            <td style="vertical-align: top; padding: 2px 0;">:</td>
                            <td style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($meta['sifat_surat'] ?? 'Biasa') ?></td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 2px 0;">Lampiran</td>
                            <td style="vertical-align: top; padding: 2px 0;">:</td>
                            <td style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($surat['lampiran'] ?? '3 (Tiga) Lembar') ?></td>
                        </tr>
                        <tr>
                            <td style="width: 85px; vertical-align: top; padding: 2px 0;">Tanggal</td>
                            <td style="width: 10px; vertical-align: top; padding: 2px 0;">:</td>
                            <td style="vertical-align: top; padding: 2px 0;"><?= $tglIndo ?></td>
                        </tr>
                    </table>

                    <div style="border-top: 1.5px solid #000000; margin: 8px 0 12px 0;"></div>
                    <div style="font-size: 11px; color: #000000; margin-bottom: 6px; line-height: 1.35;">Dengan hormat,</div>
                    <div style="line-height: 1.35; text-align: justify; font-size: 11px; font-family: Arial, sans-serif; color: #000000; margin-bottom: 12px; white-space: pre-line;"><?= htmlspecialchars($meta['isi_surat_dinas'] ?? $surat['isi_surat']) ?></div>

                    <!-- Signature Block directly under content -->
                    <div style="display: flex; justify-content: flex-end; margin-top: 16px; margin-bottom: 16px;">
                        <div style="width: 200px; text-align: left; font-size: 10.5px; line-height: 1.25; color: #000000;">
                            <div>Mengetahui,</div>
                            <div>Kepala Sub Bagian Tata Usaha</div>
                            
                            <!-- Clickable TTD Area (width aligned with 'Usaha') -->
                            <?php 
                            $hasTuSig = !empty($surat['tu_signature']);
                            $boxStyle = $hasTuSig 
                                ? 'height: 48px; width: 100%; margin: 3px 0; display: flex; align-items: center; justify-content: flex-start; cursor: pointer; border: none; background: transparent; padding: 0;'
                                : 'height: 52px; width: 160px; max-width: 100%; margin: 4px 0; display: flex; align-items: center; justify-content: center; text-align: center; cursor: pointer; border: 1.5px dashed #3B82F6; border-radius: 8px; background: #F8FAFC; transition: all 0.2s;';
                            ?>
                            <div id="pvTtdBox" onclick="openSignatureModal('ttd')" title="Klik di sini untuk membubuhkan / mengubah TTD Digital TU"
                                 style="<?= $boxStyle ?>"
                                 onmouseover="if (!this.style.border || this.style.border.includes('dashed')) { this.style.background='#EFF6FF'; this.style.borderColor='#1D4ED8'; }"
                                 onmouseout="if (!this.style.border || this.style.border.includes('dashed')) { this.style.background='#F8FAFC'; this.style.borderColor='#3B82F6'; }">
                                <img id="pvTtdImg" src="<?= $hasTuSig ? getImgUrl($surat['tu_signature']) : '' ?>" style="max-height: 48px; object-fit: contain; text-align: left; margin: 0; <?= !$hasTuSig ? 'display: none;' : '' ?>">
                                <span id="pvTtdPlaceholder" class="no-print" style="font-size: 10px; color: #2563EB; font-weight: 700; <?= $hasTuSig ? 'display: none;' : '' ?>">
                                    <i class="bi bi-pencil-square"></i> Klik untuk isi TTD
                                </span>
                            </div>

                            <!-- Name & NIP block with Paraf KPP Bidang displaying statically to the left -->
                            <div style="position: relative; display: inline-block;">
                                <?php if (!empty($kppParaf)): ?>
                                    <div style="position: absolute; right: 100%; top: 50%; transform: translateY(-50%); margin-right: 8px; white-space: nowrap; display: flex; align-items: center; justify-content: center; z-index: 10;">
                                        <img src="<?= getImgUrl($kppParaf) ?>" class="kpp-paraf-img" style="max-height: 48px; width: auto; max-width: 70px; object-fit: contain; display: block; visibility: visible;" alt="Paraf KPP">
                                    </div>
                                <?php endif; ?>
                                <div style="text-decoration: underline; font-weight: 500;"><?= htmlspecialchars($tuNamaDisplay) ?></div>
                                <div><?= htmlspecialchars($tuNipDisplay) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div style="padding-top: 10px;">
                    <?php if (!$isNotaDinas): ?>
                    <?php 
                    $adminBarcodeLink = !empty($meta['admin_gdrive_web_link']) 
                        ? $meta['admin_gdrive_web_link'] 
                        : (!empty($meta['gdrive_web_link']) 
                            ? $meta['gdrive_web_link'] 
                            : (!empty($surat['gdrive_web_link']) && !str_contains($surat['gdrive_web_link'], '/file/d/') 
                                ? $surat['gdrive_web_link'] 
                                : ''));

                    $qrUrlTu = !empty($adminBarcodeLink) ? $adminBarcodeLink : 'https://drive.google.com';
                    $qrSrcTu = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrUrlTu);
                    ?>
                    <div style="display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 6px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <a href="<?= htmlspecialchars($qrUrlTu) ?>" target="_blank" title="Scan / Klik untuk membuka URL Google Drive">
                                <img src="<?= $qrSrcTu ?>" style="width: 48px; height: 48px; border: 1.5px solid #000000; background: #FFFFFF; padding: 2px;" alt="Barcode QR Google Drive">
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="display: grid; grid-template-columns: 1.2fr 1.2fr 1fr; gap: 8px; font-size: 8px; color: #333333; line-height: 1.25;">
                        <div>
                            <strong>LEMBAGA PENYIARAN PUBLIK</strong><br>
                            TELEVISI REPUBLIK INDONESIA<br>
                            STASIUN JAWA TIMUR
                        </div>
                        <div>
                            Jl. Mayjend Sungkono No.124,<br>
                            Surabaya, Jawa Timur, 60189<br>
                            Indonesia
                        </div>
                        <div>
                            P (031) 567452 5678298<br>
                            F (031) 5678452 5616774<br>
                            www.tvri.go.id
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- DOKUMEN PDF LAMPIRAN TER-UPLOAD -->
    <?php 
    $pdfLampirans = [];
    if (!empty($meta['lampiran_pdf_paths']) && is_array($meta['lampiran_pdf_paths'])) {
        $pdfLampirans = $meta['lampiran_pdf_paths'];
    } elseif (!empty($meta['lampiran_pdf_path'])) {
        $pdfLampirans = [$meta['lampiran_pdf_path']];
    }

    $printableLampiranHtml = '';
    if (!empty($pdfLampirans)) {
        $rows = '';
        foreach ($pdfLampirans as $i => $p) {
            $fn = htmlspecialchars(basename($p));
            $idx = $i + 1;
            $rows .= "<tr>
                <td style='padding: 10px 12px; border: 1px solid #000; text-align: center; font-size: 12px;'>{$idx}</td>
                <td style='padding: 10px 12px; border: 1px solid #000; font-size: 12px; font-weight: bold;'>{$fn}</td>
                <td style='padding: 10px 12px; border: 1px solid #000; font-size: 12px; text-align: center;'>PDF Lampiran</td>
                <td style='padding: 10px 12px; border: 1px solid #000; font-size: 12px; text-align: center;'>2 Lembar (Sisi Kiri & Kanan)</td>
                <td style='padding: 10px 12px; border: 1px solid #000; font-size: 12px; text-align: center; color: #047857; font-weight: bold;'>Tersimpan & Terlampir</td>
            </tr>";
        }
        $printableLampiranHtml = '<div style="page-break-after: always; break-after: page;"></div>' . 
        '<div class="a4-paper-preview" style="background: #FFFFFF; border: none; padding: 2.5cm; font-family: Arial, sans-serif; color: #000000; position: relative; min-height: 270mm; margin: 0 auto; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between;">' .
            '<div>' .
                '<div style="margin-bottom: 20px;"><img src="' . BASE_URL . '/assets/img/tvri3.svg" style="height: 58px; width: auto;" alt="TVRI Logo"></div>' .
                '<div style="font-size: 13px; line-height: 1.15; margin-bottom: 24px; color: #000000; border-bottom: 1.5px solid #000; padding-bottom: 12px;">' .
                    '<h2 style="font-size: 15px; font-weight: bold; margin: 0 0 6px 0; text-transform: uppercase;">DAFTAR LAMPIRAN BERKAS PENDUKUNG</h2>' .
                    '<div style="font-size: 12px; color: #333;">Dokumen Lampiran Resmi Izin Produksi TVRI Stasiun Jawa Timur</div>' .
                '</div>' .
                '<p style="font-size: 13px; line-height: 1.4; margin-bottom: 16px;">Berikut adalah daftar berkas PDF pendukung yang diunggah dan dilampirkan bersama Surat Permohonan & Nota Dinas ini:</p>' .
                '<table style="width: 100%; border-collapse: collapse; margin-bottom: 24px;">' .
                    '<thead><tr style="background: #F1F5F9;">' .
                        '<th style="padding: 10px 12px; border: 1px solid #000; font-size: 12px; width: 40px; text-align: center;">No</th>' .
                        '<th style="padding: 10px 12px; border: 1px solid #000; font-size: 12px; text-align: left;">Nama Berkas PDF</th>' .
                        '<th style="padding: 10px 12px; border: 1px solid #000; font-size: 12px; width: 100px; text-align: center;">Tipe Berkas</th>' .
                        '<th style="padding: 10px 12px; border: 1px solid #000; font-size: 12px; width: 140px; text-align: center;">Tampilan Lembar</th>' .
                        '<th style="padding: 10px 12px; border: 1px solid #000; font-size: 12px; width: 130px; text-align: center;">Status Verifikasi</th>' .
                    '</tr></thead>' .
                    '<tbody>' . $rows . '</tbody>' .
                '</table>' .
                '<div style="font-size: 11.5px; color: #475569; background: #F8FAFC; padding: 12px 16px; border-radius: 6px; border: 1px solid #CBD5E1; line-height: 1.4;">' .
                    '<strong>Catatan:</strong> Berkas PDF fisik lampiran dapat diakses dan diunduh secara penuh melalui portal Surat Digital TVRI Stasiun Jawa Timur.' .
                '</div>' .
            '</div>' .
            '<div style="padding-top: 12px;">' .
                '<div style="display: grid; grid-template-columns: 1.2fr 1.2fr 1fr; gap: 10px; font-size: 9px; color: #333333; line-height: 1.35;">' .
                    '<div><strong>LEMBAGA PENYIARAN PUBLIK</strong><br>TELEVISI REPUBLIK INDONESIA<br>STASIUN JAWA TIMUR</div>' .
                    '<div>Jl. Mayjend Sungkono No.124,<br>Surabaya, Jawa Timur, 60189<br>Indonesia</div>' .
                    '<div>P (031) 567452 5678298<br>F (031) 5678452 5616774<br>www.tvri.go.id</div>' .
                '</div>' .
            '</div>' .
        '</div>';
    }
    ?>

    <?php if (!empty($pdfLampirans)): ?>
        <div style="width: 100%; margin-top: 24px; margin-bottom: 32px; padding-top: 24px; border-top: 2px dashed #CBD5E1;">
            <div style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 20px; align-items: stretch; width: 100%;">
                <?php 
                foreach ($pdfLampirans as $idx => $pdfPath): 
                    $pageCount = getPdfPageCount($pdfPath);
                    for ($p = 1; $p <= $pageCount; $p++):
                        $subTitle = $pageCount === 1 ? '1 Lembar' : "Halaman $p dari $pageCount";
                        $pdfUrl = getImgUrl($pdfPath);
                ?>
                    <div class="pdf-lampiran-wrapper-col">
                        <div style="padding: 10px 14px; background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: 10px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;" class="no-print">
                            <div style="display: flex; align-items: center; gap: 10px; min-width: 0; overflow: hidden;">
                                <i class="bi bi-file-earmark-pdf-fill" style="color: #DC2626; font-size: 22px; flex-shrink: 0;"></i>
                                <div style="min-width: 0; overflow: hidden;">
                                    <div style="font-size: 13px; font-weight: 800; color: #991B1B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Lampiran PDF <?= $idx + 1 ?></div>
                                    <div style="font-size: 11px; color: #7F1D1D;"><?= $subTitle ?></div>
                                </div>
                            </div>
                            <span style="font-size: 10.5px; font-weight: 800; background: #DC2626; color: #FFFFFF; padding: 3px 10px; border-radius: 20px; flex-shrink: 0;">LEMBAR <?= $p ?></span>
                        </div>

                        <div class="pdf-lampiran-card-preview" style="background: #FFFFFF; border: none; padding: 0 !important; box-shadow: 0 4px 20px rgba(0,0,0,0.06); font-family: Arial, sans-serif; color: #000000; position: relative; aspect-ratio: 210 / 297; min-height: 0; margin: 0 auto; box-sizing: border-box; border-radius: 4px; width: 100%; overflow: hidden;">
                            <canvas class="pdf-canvas-item" data-pdf-url="<?= $pdfUrl ?>" data-page-num="<?= $p ?>" style="width: 100%; height: auto; object-fit: contain; display: block; background: #FFFFFF; border: none; margin: 0; padding: 0;"></canvas>
                            <img class="pdf-img-item" style="width: 100%; height: auto; object-fit: contain; display: none; margin: 0 auto; border: none; padding: 0;">
                        </div>
                    </div>
                <?php endfor; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ACTION BUTTONS FORM FOR TATA USAHA -->
    <form action="<?= BASE_URL ?>/surat/tu-submit/<?= $surat['id'] ?>" method="POST" id="tuProcessForm" enctype="multipart/form-data" autocomplete="off">
        <?= Session::csrfField() ?>
        <input type="hidden" name="action_type" id="inActionType" value="submit">
        <input type="hidden" name="catatan_revisi" id="inCatatanRevisi" value="">
        <input type="hidden" name="has_tu_tte_uploaded" id="hasTuTteUploaded" value="<?= !empty($meta['is_tu_tte_uploaded']) ? '1' : '0' ?>">
        <input type="file" id="inTuTtePdf" name="tu_tte_pdf" accept="application/pdf" style="display: none;" onchange="handleTuTteUpload(event)">

        <?php $defaultNomor = $surat['nomor_surat'] ?? ''; ?>
        <?php $parafSrc = $meta['tu_paraf'] ?? ''; ?>

        <input type="hidden" name="nomor_surat" id="inNomorSurat" value="<?= htmlspecialchars($defaultNomor) ?>">
        <input type="hidden" name="tu_signature_data" id="tuSignatureData" value="<?= !empty($surat['tu_signature']) ? BASE_URL . $surat['tu_signature'] : '' ?>">

        <!-- FORM ACTIONS BAR -->
        <div class="tu-bottom-actions">
            <div class="tu-bottom-left">
                <button type="button" onclick="printTuDocument()" class="btn-btn btn-print-tu">
                    <i class="bi bi-printer-fill" style="color: #2563EB; font-size: 16px;"></i> Print Surat
                </button>

                <button type="button" id="btnUploadTuTte" onclick="document.getElementById('inTuTtePdf').click()" class="btn-btn btn-upload-tte" style="background: <?= !empty($meta['is_tu_tte_uploaded']) ? '#ECFDF5' : '#EFF6FF' ?>; color: <?= !empty($meta['is_tu_tte_uploaded']) ? '#047857' : '#1E40AF' ?>; border: 1.5px solid <?= !empty($meta['is_tu_tte_uploaded']) ? '#10B981' : '#BFDBFE' ?>;">
                    <i class="bi bi-file-earmark-pdf-fill" style="color: <?= !empty($meta['is_tu_tte_uploaded']) ? '#059669' : '#2563EB' ?>; font-size: 16px;"></i> <?= !empty($meta['is_tu_tte_uploaded']) ? 'Ganti Surat TTE' : 'Upload Surat TTE (PDF)' ?>
                </button>
            </div>
            <div class="tu-bottom-right">
                <div class="tu-sub-actions">
                    <a href="<?= BASE_URL ?>/dashboard" class="btn-btn btn-btn-secondary btn-action-batal">
                        Batal
                    </a>

                    <button type="button" onclick="openRevisiModal()" class="btn-btn btn-action-revisi">
                        <i class="bi bi-arrow-counterclockwise"></i> Revisi
                    </button>
                </div>

                <button type="submit" id="btnSubmitTu" onclick="return prepareSubmitData(event)" class="btn-btn btn-btn-primary btn-action-teruskan">
                    <i class="bi bi-send-fill"></i> Teruskan ke Sekretaris
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ========================================== -->
<!-- MODAL POPUP PILIHAN METODE TTD KTU         -->
<!-- ========================================== -->
<div id="ttdChoiceModal" onclick="if(event.target === this) closeTtdChoiceModal()" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #FFFFFF; width: 100%; max-width: 520px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); overflow: hidden; display: flex; flex-direction: column;">
        <!-- Header -->
        <div style="padding: 18px 24px; background: #F8FAFC; border-bottom: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-pen-fill" style="color: #2563EB; font-size: 20px;"></i>
                <h3 style="margin: 0; font-size: 16.5px; font-weight: 800; color: #0F172A;">Pilihan Metode TTD KTU</h3>
            </div>
        </div>

        <!-- Body: 2 Kotak Kanan Kiri -->
        <div style="padding: 24px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <!-- Kotak 1: Upload Gambar TTE -->
                <button type="button" onclick="selectTtdChoice('upload')"
                        style="padding: 24px 16px; border-radius: 16px; border: 2px solid #E2E8F0; background: #FFFFFF; text-align: center; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 14px; transition: all 0.2s ease;"
                        onmouseover="this.style.borderColor='#2563EB'; this.style.background='#F0F6FF'; this.style.transform='translateY(-2px)';"
                        onmouseout="this.style.borderColor='#E2E8F0'; this.style.background='#FFFFFF'; this.style.transform='none';">
                    <div style="width: 56px; height: 56px; border-radius: 14px; background: #EFF6FF; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-cloud-arrow-up-fill" style="font-size: 28px; color: #2563EB;"></i>
                    </div>
                    <div>
                        <div style="font-size: 15px; font-weight: 800; color: #1E293B; margin-bottom: 4px;">Upload Gambar TTE</div>
                        <div style="font-size: 11.5px; color: #64748B; line-height: 1.35;">Unggah berkas gambar TTE</div>
                    </div>
                </button>

                <!-- Kotak 2: Isi TTD Manual -->
                <button type="button" onclick="selectTtdChoice('manual')"
                        style="padding: 24px 16px; border-radius: 16px; border: 2px solid #E2E8F0; background: #FFFFFF; text-align: center; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 14px; transition: all 0.2s ease;"
                        onmouseover="this.style.borderColor='#059669'; this.style.background='#ECFDF5'; this.style.transform='translateY(-2px)';"
                        onmouseout="this.style.borderColor='#E2E8F0'; this.style.background='#FFFFFF'; this.style.transform='none';">
                    <div style="width: 56px; height: 56px; border-radius: 14px; background: #ECFDF5; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-pencil-square" style="font-size: 28px; color: #059669;"></i>
                    </div>
                    <div>
                        <div style="font-size: 15px; font-weight: 800; color: #1E293B; margin-bottom: 4px;">Isi TTD Manual</div>
                        <div style="font-size: 11.5px; color: #64748B; line-height: 1.35;">Gambar TTD pada canvas</div>
                    </div>
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div style="padding: 12px 24px 18px 24px; display: flex; justify-content: flex-end; background: #F8FAFC; border-top: 1px solid #E2E8F0;">
            <button type="button" onclick="closeTtdChoiceModal()" class="btn-btn btn-btn-secondary" style="padding: 8px 18px;">
                Batal
            </button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL POPUP PENGERJAAN TTD DIGITAL TU      -->
<!-- ========================================== -->
<div id="signatureModalPopup" onclick="if(event.target === this) closeSignatureModal()" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px;">
    <div class="sig-modal-dialog">
        <div class="sig-modal-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="sig-header-icon">
                    <i id="modalIcon" class="bi bi-pencil-square"></i>
                </div>
                <div>
                    <h3 id="modalTitle" class="sig-header-title">Input Tanda Tangan Digital Tata Usaha</h3>
                    <div id="modalSubtitle" class="sig-header-subtitle">Bubuhkan tanda tangan digital</div>
                </div>
            </div>
        </div>

        <div class="sig-modal-body">
            <p id="modalInstruction" class="sig-instructions-text">
                Gunakan sentuhan jari atau mouse di area di bawah untuk membubuhkan TTD <?= htmlspecialchars($tuNamaDisplay) ?>:
            </p>

            <div class="sig-canvas-wrapper">
                <canvas id="modalCanvas"></canvas>
                <div class="sig-canvas-watermark">
                    <i class="bi bi-pencil"></i> Sentuh atau goreskan tanda tangan di sini
                </div>
            </div>

            <div class="sig-modal-footer">
                <div class="sig-footer-subgroup">
                    <button type="button" onclick="clearModalCanvas()" class="btn-btn btn-sig-clear">
                        <i class="bi bi-eraser-fill"></i> Hapus
                    </button>
                    <button type="button" onclick="closeSignatureModal()" class="btn-btn btn-btn-secondary btn-sig-cancel">
                        Batal
                    </button>
                </div>

                <div class="sig-footer-main">
                    <button type="button" onclick="saveSignatureFromModal()" class="btn-btn btn-btn-primary btn-sig-save">
                        <i class="bi bi-check-lg"></i> Simpan & Tempel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL POPUP REVISI SURAT                   -->
<!-- ========================================== -->
<div id="revisiModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #FFFFFF; width: 100%; max-width: 500px; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); padding: 24px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
            <i class="bi bi-exclamation-triangle-fill" style="color: #D97706; font-size: 24px;"></i>
            <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #92400E;">Kembalikan untuk Revisi</h3>
        </div>

        <p style="font-size: 13.5px; color: #64748B; margin-bottom: 16px; line-height: 1.5;">
            Masukkan alasan atau catatan revisi untuk dikembalikan kepada Admin Pembuat Surat.
        </p>

        <textarea id="modalRevisiNote" rows="4" placeholder="Ketikkan alasan revisi di sini..."
                  style="width: 100%; padding: 12px; border-radius: 10px; border: 1.5px solid #FCD34D; font-size: 14px; font-family: inherit; margin-bottom: 20px; box-sizing: border-box;"></textarea>

        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <button type="button" onclick="closeRevisiModal()" class="btn-btn btn-btn-secondary">
                Batal
            </button>
            <button type="button" onclick="submitRevisi()" class="btn-btn" style="background: #D97706; color: #FFFFFF; font-weight: 700; padding: 10px 22px; border: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-arrow-counterclockwise"></i> Kirim Revisi
            </button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL POPUP PERINGATAN TTD TU BELUM DIISI   -->
<!-- ========================================== -->
<div id="ttdWarningModal" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #FFFFFF; width: 100%; max-width: 440px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); text-align: center; padding: 28px 24px; border: 1px solid #F3F4F6; position: relative;">
        <div style="width: 60px; height: 60px; border-radius: 50%; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 28px; border: 2px solid #FDE68A;">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800; color: #0F172A; letter-spacing: -0.2px;">Tanda Tangan Belum Diisi</h3>
        <p style="margin: 0 0 24px 0; font-size: 13.5px; color: #64748B; line-height: 1.5;">
            Tanda Tangan Digital (TTD) Tata Usaha belum diisi.<br>Silakan masukkan TTD Anda terlebih dahulu!
        </p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button type="button" onclick="closeTtdWarningModal()" class="btn-btn btn-btn-secondary" style="flex: 1; justify-content: center; font-weight: 600; padding: 10px 16px;">
                Tutup
            </button>
            <button type="button" onclick="confirmOpenTtdFromWarning()" class="btn-btn btn-btn-primary" style="flex: 1.3; justify-content: center; font-weight: 700; padding: 10px 16px; background: #2563EB; border-color: #2563EB; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-pen-fill"></i> Isi TTD Sekarang
            </button>
        </div>
    </div>
</div>

<!-- REAL-TIME CANVAS SCRIPT -->
<script>
const isNotaDinas = <?= $isNotaDinas ? 'true' : 'false' ?>;
let currentModalType = 'ttd';
const modalCanvas = document.getElementById('modalCanvas');
const modalCtx = modalCanvas ? modalCanvas.getContext('2d') : null;
let drawingModal = false;

function setupModalCanvasDpi() {
    if (!modalCanvas) return;
    const rect = modalCanvas.getBoundingClientRect();
    const dpr = Math.max(window.devicePixelRatio || 1, 2);
    modalCanvas.width = rect.width * dpr;
    modalCanvas.height = rect.height * dpr;
    modalCtx.scale(dpr, dpr);
}

function getCanvasCoords(e) {
    if (!modalCanvas) return { x: 0, y: 0 };
    const rect = modalCanvas.getBoundingClientRect();
    const touch = (e.touches && e.touches[0]) || (e.changedTouches && e.changedTouches[0]);
    const clientX = touch ? touch.clientX : e.clientX;
    const clientY = touch ? touch.clientY : e.clientY;
    return {
        x: clientX - rect.left,
        y: clientY - rect.top
    };
}

let lastX = 0, lastY = 0;

function startDrawingModal(e) {
    if (e && e.cancelable) e.preventDefault();
    drawingModal = true;
    const watermark = document.querySelector('#signatureModalPopup .sig-canvas-watermark');
    if (watermark) watermark.style.display = 'none';
    const coords = getCanvasCoords(e);
    lastX = coords.x;
    lastY = coords.y;

    if (modalCtx) {
        modalCtx.strokeStyle = (currentModalType === 'paraf') ? (isNotaDinas ? '#000000' : '#7E22CE') : '#1E3A8A';
        modalCtx.lineWidth = (currentModalType === 'paraf') ? 5.5 : 3.5;
        modalCtx.lineCap = 'round';
        modalCtx.lineJoin = 'round';
        modalCtx.beginPath();
        modalCtx.arc(lastX, lastY, (currentModalType === 'paraf') ? 2.75 : 1.75, 0, Math.PI * 2);
        modalCtx.fillStyle = modalCtx.strokeStyle;
        modalCtx.fill();
        modalCtx.beginPath();
        modalCtx.moveTo(lastX, lastY);
    }
}

function drawModal(e) {
    if (!drawingModal || !modalCtx) return;
    if (e && e.cancelable) e.preventDefault();
    const coords = getCanvasCoords(e);

    modalCtx.strokeStyle = (currentModalType === 'paraf') ? (isNotaDinas ? '#000000' : '#7E22CE') : '#1E3A8A';
    modalCtx.lineWidth = (currentModalType === 'paraf') ? 5.5 : 3.5;
    modalCtx.lineCap = 'round';
    modalCtx.lineJoin = 'round';

    const midX = (lastX + coords.x) / 2;
    const midY = (lastY + coords.y) / 2;
    modalCtx.quadraticCurveTo(lastX, lastY, midX, midY);
    modalCtx.stroke();

    lastX = coords.x;
    lastY = coords.y;
}

function stopDrawingModal(e) {
    if (e && e.cancelable) e.preventDefault();
    drawingModal = false;
}

if (modalCanvas) {
    modalCanvas.addEventListener('mousedown', startDrawingModal);
    modalCanvas.addEventListener('mousemove', drawModal);
    modalCanvas.addEventListener('mouseup', stopDrawingModal);
    modalCanvas.addEventListener('mouseleave', stopDrawingModal);

    modalCanvas.addEventListener('touchstart', function(e) {
        if (e.cancelable) e.preventDefault();
        startDrawingModal(e);
    }, { passive: false });
    modalCanvas.addEventListener('touchmove', function(e) {
        if (e.cancelable) e.preventDefault();
        drawModal(e);
    }, { passive: false });
    modalCanvas.addEventListener('touchend', function(e) {
        if (e.cancelable) e.preventDefault();
        stopDrawingModal(e);
    }, { passive: false });
}

function openTtdChoiceModal() {
    document.body.style.overflow = 'hidden';
    const modal = document.getElementById('ttdChoiceModal');
    if (modal) modal.style.display = 'flex';
}

function closeTtdChoiceModal() {
    const modal = document.getElementById('ttdChoiceModal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
}

function applyTuSignatureToBox(dataUrl, isPath = false) {
    const pvImg = document.getElementById('pvTtdImg');
    const pvPlaceholder = document.getElementById('pvTtdPlaceholder');
    const pvBox = document.getElementById('pvTtdBox');

    const pvImgSingle = document.getElementById('pvTtdImgSingle');
    const pvPlaceholderSingle = document.getElementById('pvTtdPlaceholderSingle');
    const pvBoxSingle = document.getElementById('pvTtdBoxSingle');

    const fullSrc = isPath ? ('<?= BASE_URL ?>' + dataUrl) : dataUrl;

    if (pvImg) {
        pvImg.src = fullSrc;
        pvImg.style.display = 'block';
        pvImg.style.maxHeight = '55px';
        pvImg.style.height = '55px';
        pvImg.style.width = 'auto';
        pvImg.style.margin = '0';
    }
    if (pvPlaceholder) pvPlaceholder.style.display = 'none';
    if (pvBox) {
        pvBox.style.border = 'none';
        pvBox.style.background = 'transparent';
        pvBox.style.padding = '0';
        pvBox.style.width = '100%';
        pvBox.style.justifyContent = 'flex-start';
    }

    if (pvImgSingle) {
        pvImgSingle.src = fullSrc;
        pvImgSingle.style.display = 'block';
        pvImgSingle.style.maxHeight = '55px';
        pvImgSingle.style.height = '55px';
        pvImgSingle.style.width = 'auto';
        pvImgSingle.style.margin = '0';
    }
    if (pvPlaceholderSingle) pvPlaceholderSingle.style.display = 'none';
    if (pvBoxSingle) {
        pvBoxSingle.style.border = 'none';
        pvBoxSingle.style.background = 'transparent';
        pvBoxSingle.style.padding = '0';
        pvBoxSingle.style.width = '100%';
        pvBoxSingle.style.justifyContent = 'flex-start';
    }

    if (document.getElementById('tuSignatureData')) {
        document.getElementById('tuSignatureData').value = dataUrl;
    }
}

function selectTtdChoice(choice) {
    if (choice === 'upload') {
        const tteImgPath = '/assets/img/tte_stamp.png';
        applyTuSignatureToBox(tteImgPath, true);
        closeTtdChoiceModal();
        updateSubmitButtonState();
    } else if (choice === 'manual') {
        closeTtdChoiceModal();
        openManualSignatureModal('ttd');
    }
}

function openSignatureModal(type) {
    openManualSignatureModal(type);
}

function openManualSignatureModal(type) {
    currentModalType = type;
    document.body.style.overflow = 'hidden';
    const popup = document.getElementById('signatureModalPopup');
    if (popup) popup.style.display = 'flex';

    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const modalIcon = document.getElementById('modalIcon');
    const modalInstruction = document.getElementById('modalInstruction');
    const watermark = document.querySelector('#signatureModalPopup .sig-canvas-watermark');

    if (type === 'ttd') {
        if (modalTitle) modalTitle.textContent = 'Input Tanda Tangan Digital TU';
        if (modalSubtitle) modalSubtitle.textContent = 'Bubuhkan tanda tangan digital';
        if (modalIcon) {
            modalIcon.className = 'bi bi-pencil-square';
            modalIcon.style.color = '#2563EB';
        }
        if (modalInstruction) modalInstruction.textContent = 'Gunakan sentuhan jari atau mouse di area di bawah untuk membubuhkan TTD ' + <?= json_encode($tuNamaDisplay) ?> + ':';
        if (watermark) {
            watermark.innerHTML = '<i class="bi bi-pencil"></i> Sentuh atau goreskan tanda tangan di sini';
            watermark.style.display = 'flex';
        }
    } else {
        if (modalTitle) modalTitle.textContent = 'Input Paraf Digital TU';
        if (modalSubtitle) modalSubtitle.textContent = 'Bubuhkan paraf digital';
        if (modalIcon) {
            modalIcon.className = 'bi bi-pen-fill';
            modalIcon.style.color = isNotaDinas ? '#000000' : '#7E22CE';
        }
        if (modalInstruction) modalInstruction.textContent = 'Buat paraf singkat ' + <?= json_encode($tuNamaDisplay) ?> + ' (akan diletakkan di sebelah kiri Nama & NIP):';
        if (watermark) {
            watermark.innerHTML = '<i class="bi bi-pencil"></i> Sentuh atau goreskan paraf di sini';
            watermark.style.display = 'flex';
        }
    }

    requestAnimationFrame(() => {
        setupModalCanvasDpi();
        if (modalCtx && modalCanvas) {
            modalCtx.clearRect(0, 0, modalCanvas.width, modalCanvas.height);
            modalCtx.strokeStyle = (type === 'paraf') ? (isNotaDinas ? '#000000' : '#7E22CE') : '#1E3A8A';
            modalCtx.lineWidth = (type === 'paraf') ? 5.5 : 3.5;
            modalCtx.lineCap = 'round';
            modalCtx.lineJoin = 'round';
        }

        if (type === 'ttd') {
            const currentImg = document.getElementById('pvTtdImg') || document.getElementById('pvTtdImgSingle');
            if (currentImg && currentImg.src && currentImg.style.display !== 'none') {
                const img = new Image();
                img.onload = () => {
                    const rect = modalCanvas.getBoundingClientRect();
                    modalCtx.drawImage(img, 0, 0, rect.width, rect.height);
                    if (watermark) watermark.style.display = 'none';
                };
                img.src = currentImg.src;
            }
        } else {
            const currentImg = document.getElementById('pvParafImg');
            if (currentImg && currentImg.src && currentImg.style.display !== 'none') {
                const img = new Image();
                img.onload = () => {
                    const rect = modalCanvas.getBoundingClientRect();
                    modalCtx.drawImage(img, 0, 0, rect.width, rect.height);
                    if (watermark) watermark.style.display = 'none';
                };
                img.src = currentImg.src;
            }
        }
    });
}

function closeSignatureModal() {
    document.getElementById('signatureModalPopup').style.display = 'none';
    document.body.style.overflow = '';
}

function clearModalCanvas() {
    if (!modalCtx || !modalCanvas) return;
    modalCtx.clearRect(0, 0, modalCanvas.width, modalCanvas.height);
    modalCtx.strokeStyle = (currentModalType === 'paraf') ? (isNotaDinas ? '#000000' : '#7E22CE') : '#1E3A8A';
    modalCtx.lineWidth = (currentModalType === 'paraf') ? 5.5 : 3.5;
    modalCtx.lineCap = 'round';
    modalCtx.lineJoin = 'round';
    const watermark = document.querySelector('#signatureModalPopup .sig-canvas-watermark');
    if (watermark) watermark.style.display = 'flex';
}

function saveSignatureFromModal() {
    const dataUrl = modalCanvas.toDataURL();
    applyTuSignatureToBox(dataUrl, false);
    closeSignatureModal();
    updateSubmitButtonState();
}

function handleTuTteUpload(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            alert('File harus berupa dokumen PDF!');
            e.target.value = '';
            return;
        }
        const hasInput = document.getElementById('hasTuTteUploaded');
        if (hasInput) hasInput.value = '1';
        
        const btnUpload = document.getElementById('btnUploadTuTte');
        if (btnUpload) {
            btnUpload.style.background = '#ECFDF5';
            btnUpload.style.borderColor = '#10B981';
            btnUpload.style.color = '#047857';
            btnUpload.innerHTML = `<i class="bi bi-file-earmark-check-fill" style="color: #059669; font-size: 16px;"></i> Ganti Surat TTE`;
        }
        updateSubmitButtonState();
    }
}

function clearTuTteUpload() {
    const input = document.getElementById('inTuTtePdf');
    if (input) input.value = '';
    const hasInput = document.getElementById('hasTuTteUploaded');
    if (hasInput) hasInput.value = '0';
    
    const btnUpload = document.getElementById('btnUploadTuTte');
    if (btnUpload) {
        btnUpload.style.background = '#EFF6FF';
        btnUpload.style.borderColor = '#BFDBFE';
        btnUpload.style.color = '#1E40AF';
        btnUpload.innerHTML = `<i class="bi bi-file-earmark-pdf-fill" style="color: #2563EB; font-size: 16px;"></i> Upload Surat TTE (PDF)`;
    }
    updateSubmitButtonState();
}

function updateSubmitButtonState() {
    const sigData = document.getElementById('tuSignatureData') ? document.getElementById('tuSignatureData').value.trim() : '';
    const hasPdf = (document.getElementById('inTuTtePdf') && document.getElementById('inTuTtePdf').files && document.getElementById('inTuTtePdf').files.length > 0) || (document.getElementById('hasTuTteUploaded') && document.getElementById('hasTuTteUploaded').value === '1');
    const btn = document.getElementById('btnSubmitTu');
    if (btn) {
        btn.style.opacity = '1';
        if (!sigData && !hasPdf) {
            btn.title = 'Silakan isi TTD Tata Usaha atau Upload Surat TTE terlebih dahulu';
        } else {
            btn.title = 'Teruskan ke Sekretaris';
        }
    }
}

function showTtdWarningModal() {
    document.body.style.overflow = 'hidden';
    const modal = document.getElementById('ttdWarningModal');
    if (modal) modal.style.display = 'flex';
}

function closeTtdWarningModal() {
    const modal = document.getElementById('ttdWarningModal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
}

function confirmOpenTtdFromWarning() {
    closeTtdWarningModal();
    openSignatureModal('ttd');
}

function showLoadingOverlay() {
    document.getElementById('tuLoadingOverlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function prepareSubmitData(e) {
    document.getElementById('inActionType').value = 'submit';
    const sigData = document.getElementById('tuSignatureData') ? document.getElementById('tuSignatureData').value.trim() : '';
    const hasPdf = (document.getElementById('inTuTtePdf') && document.getElementById('inTuTtePdf').files && document.getElementById('inTuTtePdf').files.length > 0) || (document.getElementById('hasTuTteUploaded') && document.getElementById('hasTuTteUploaded').value === '1');
    if (!sigData && !hasPdf) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        showTtdWarningModal();
        return false;
    }
    showLoadingOverlay();
    return true;
}

function openRevisiModal() {
    document.body.style.overflow = 'hidden';
    document.getElementById('revisiModal').style.display = 'flex';
}

function closeRevisiModal() {
    document.body.style.overflow = '';
    document.getElementById('revisiModal').style.display = 'none';
}

function submitRevisi() {
    const note = document.getElementById('modalRevisiNote').value.trim();
    if (!note) {
        alert('Mohon ketikkan alasan atau catatan revisi!');
        return;
    }

    const form = document.getElementById('tuProcessForm');
    if (!form) return;

    const inNomor = document.getElementById('inNomorSurat');
    if (inNomor) inNomor.removeAttribute('required');

    document.getElementById('inActionType').value = 'revisi';
    document.getElementById('inCatatanRevisi').value = note;
    form.submit();
}

window.addEventListener('click', function(e) {
    const revModal = document.getElementById('revisiModal');
    const sigModal = document.getElementById('signatureModalPopup');
    const choiceModal = document.getElementById('ttdChoiceModal');
    const warnModal = document.getElementById('ttdWarningModal');
    if (e.target === revModal) closeRevisiModal();
    if (e.target === sigModal) closeSignatureModal();
    if (e.target === choiceModal) closeTtdChoiceModal();
    if (e.target === warnModal) closeTtdWarningModal();
});

async function printTuDocument() {
    try {
        if (typeof renderServerPdfCanvases === 'function') {
            await renderServerPdfCanvases();
        }
    } catch (e) {
        console.error('Error pre-rendering PDF canvases:', e);
    }

    const papers = document.querySelectorAll('.a4-paper-preview, .pdf-lampiran-card-preview');
    if (!papers || papers.length === 0) return;

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
                <img src="${lampiranImgSrc || ''}" class="pdf-img-item" style="width: 100% !important; height: 100% !important; max-width: 100% !important; max-height: 297mm !important; object-fit: contain !important; display: block !important; margin: 0 auto !important; border: none !important; box-shadow: none !important; transform: none !important;">
            </div>`;
        } else {
            const cloned = paper.cloneNode(true);
            cloned.querySelectorAll('.no-print').forEach(el => el.remove());

            const clonedCanvases = cloned.querySelectorAll('canvas');
            const origCanvases = paper.querySelectorAll('canvas');
            origCanvases.forEach((c, i) => {
                if (clonedCanvases[i]) {
                    if (c && c.width > 0 && c.style.display !== 'none') {
                        try {
                            const dataUrl = c.toDataURL('image/png');
                            const img = document.createElement('img');
                            img.src = dataUrl;
                            img.style.maxWidth = '100%';
                            img.style.height = 'auto';
                            img.style.display = 'block';
                            img.style.margin = '0 auto';
                            clonedCanvases[i].parentNode.replaceChild(img, clonedCanvases[i]);
                        } catch (e) {
                            clonedCanvases[i].remove();
                        }
                    } else {
                        clonedCanvases[i].remove();
                    }
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
        alert('Pop-up terblokir oleh browser. Harap izinkan pop-up untuk melakukan print.');
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
                img.kpp-paraf-img { display: block !important; visibility: visible !important; opacity: 1 !important; width: auto !important; max-width: 70px !important; max-height: 48px !important; object-fit: contain !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                .printable-a4-page { background: #FFFFFF !important; border: none !important; box-shadow: none !important; padding: 3cm !important; margin: 0 auto !important; width: 210mm !important; height: 297mm !important; max-height: 297mm !important; box-sizing: border-box !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; page-break-after: always !important; break-after: page !important; page-break-inside: avoid !important; break-inside: avoid !important; overflow: hidden !important; }
                .printable-a4-page.printable-lampiran-page { padding: 0 !important; border: none !important; box-shadow: none !important; overflow: hidden !important; width: 210mm !important; height: 297mm !important; background: #ffffff !important; display: block !important; }
                .printable-a4-page.printable-lampiran-page img,
                .printable-a4-page.printable-lampiran-page .pdf-img-item { width: 100% !important; height: 100% !important; max-width: 100% !important; max-height: 297mm !important; object-fit: contain !important; display: block !important; margin: 0 auto !important; border: none !important; box-shadow: none !important; transform: none !important; }
                .printable-a4-page:last-child { page-break-after: avoid !important; break-after: avoid !important; }
                .a4-paper-preview { background: #FFFFFF !important; border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; max-width: 100% !important; height: auto !important; min-height: 0 !important; box-sizing: border-box !important; }
                #pvNomorBox, #pvTtdBox, #pvTtdBoxSingle, #pvParafBox { border: none !important; background: transparent !important; box-shadow: none !important; padding: 0 !important; }
                #pvNomorPlaceholder, #pvTtdPlaceholder, #pvTtdPlaceholderSingle, #pvParafPlaceholder { display: none !important; }
                .no-print { display: none !important; }
                .pdf-canvas-item { display: none !important; }
                .pdf-img-item { display: block !important; visibility: visible !important; width: 100% !important; height: auto !important; }
                @media print {
                    body { background: #fff !important; }
                    img { display: inline-block !important; visibility: visible !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                    .printable-a4-page { border: none !important; box-shadow: none !important; padding: 3cm !important; margin: 0 auto !important; }
                    .printable-a4-page.printable-lampiran-page { padding: 0 !important; border: none !important; box-shadow: none !important; }
                    .a4-paper-preview { border: none !important; box-shadow: none !important; padding: 0 !important; }
                    #pvNomorBox, #pvTtdBox, #pvTtdBoxSingle, #pvParafBox { border: none !important; background: transparent !important; box-shadow: none !important; padding: 0 !important; }
                    #pvNomorPlaceholder, #pvTtdPlaceholder, #pvTtdPlaceholderSingle, #pvParafPlaceholder { display: none !important; }
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
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    renderServerPdfCanvases();
    updateSubmitButtonState();

    const form = document.getElementById('tuProcessForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const actionType = document.getElementById('inActionType') ? document.getElementById('inActionType').value : 'submit';
            if (actionType === 'submit') {
                const sigData = document.getElementById('tuSignatureData') ? document.getElementById('tuSignatureData').value.trim() : '';
                const hasPdf = (document.getElementById('inTuTtePdf') && document.getElementById('inTuTtePdf').files && document.getElementById('inTuTtePdf').files.length > 0) || (document.getElementById('hasTuTteUploaded') && document.getElementById('hasTuTteUploaded').value === '1');
                if (!sigData && !hasPdf) {
                    e.preventDefault();
                    e.stopPropagation();
                    showTtdWarningModal();
                    return false;
                }
            }
        });
    }
});
</script>

<?php 
$lampiranRotations = $meta['lampiran_page_rotations'] ?? [];
if (is_string($lampiranRotations)) {
    $lampiranRotations = json_decode($lampiranRotations, true) ?? [];
}
?>
<script>
window.lampiranRotations = <?= json_encode($lampiranRotations ?: new stdClass()) ?>;

async function renderServerPdfCanvases() {
    if (typeof pdfjsLib === 'undefined') return;
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    const pdfDocCache = {};
    const initialCanvases = Array.from(document.querySelectorAll('.pdf-canvas-item'));
    const processedPdfUrls = new Set();

    for (const canvas of initialCanvases) {
        const pdfUrl = canvas.getAttribute('data-pdf-url');
        if (!pdfUrl || processedPdfUrls.has(pdfUrl)) continue;
        processedPdfUrls.add(pdfUrl);

        try {
            if (!pdfDocCache[pdfUrl]) {
                pdfDocCache[pdfUrl] = await pdfjsLib.getDocument(pdfUrl).promise;
            }
            const pdf = pdfDocCache[pdfUrl];
            const numPages = pdf.numPages || 1;

            const firstCol = canvas.closest('.pdf-lampiran-wrapper-col');
            const parentGrid = firstCol ? firstCol.parentElement : null;

            for (let p = 1; p <= numPages; p++) {
                let targetCanvas = null;
                if (parentGrid) {
                    const foundCanvases = parentGrid.querySelectorAll('.pdf-canvas-item');
                    for (const fc of foundCanvases) {
                        if (fc.getAttribute('data-pdf-url') === pdfUrl && parseInt(fc.getAttribute('data-page-num'), 10) === p) {
                            targetCanvas = fc;
                            break;
                        }
                    }
                } else if (parseInt(canvas.getAttribute('data-page-num'), 10) === p) {
                    targetCanvas = canvas;
                }

                if (!targetCanvas && firstCol && parentGrid) {
                    const clonedCol = firstCol.cloneNode(true);
                    
                    const subTitleEl = clonedCol.querySelector('.no-print div div:last-child');
                    const badgeEl = clonedCol.querySelector('.no-print span');
                    if (subTitleEl) subTitleEl.textContent = numPages === 1 ? '1 Lembar' : `Halaman ${p} dari ${numPages}`;
                    if (badgeEl) badgeEl.textContent = `LEMBAR ${p}`;

                    targetCanvas = clonedCol.querySelector('.pdf-canvas-item');
                    const targetImg = clonedCol.querySelector('.pdf-img-item');

                    if (targetCanvas) {
                        targetCanvas.setAttribute('data-page-num', p);
                        targetCanvas.style.display = 'block';
                    }
                    if (targetImg) {
                        targetImg.src = '';
                        targetImg.style.display = 'none';
                    }

                    parentGrid.appendChild(clonedCol);
                }

                if (!targetCanvas) continue;

                const page = await pdf.getPage(p);
                const parentContainer = targetCanvas.parentElement;
                const availableWidth = parentContainer ? (parentContainer.clientWidth - 20) : 650;

                const nativeRot = page.rotate || 0;
                const rotKey = `${pdfUrl}_p${p}`;
                const userRot = (window.lampiranRotations && window.lampiranRotations[rotKey]) ? parseInt(window.lampiranRotations[rotKey], 10) : 0;
                const totalRot = (nativeRot + userRot) % 360;

                const unscaledViewport = page.getViewport({ scale: 1.0, rotation: totalRot });
                const baseScale = availableWidth > 0 ? Math.max(1.8, availableWidth / unscaledViewport.width) : 1.8;
                const dpr = Math.max(window.devicePixelRatio || 1, 2);
                const viewport = page.getViewport({ scale: baseScale * dpr, rotation: totalRot });

                targetCanvas.width = Math.floor(viewport.width);
                targetCanvas.height = Math.floor(viewport.height);
                targetCanvas.style.width = '100%';
                targetCanvas.style.height = 'auto';

                const ctx = targetCanvas.getContext('2d');
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, targetCanvas.width, targetCanvas.height);

                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                await page.render(renderContext).promise;

                const img = targetCanvas.nextElementSibling;
                if (img && img.classList.contains('pdf-img-item')) {
                    img.src = targetCanvas.toDataURL('image/png');
                    img.style.display = 'block';
                    img.style.width = '100%';
                    img.style.height = 'auto';
                    targetCanvas.style.display = 'none';
                }
            }
        } catch (err) {
            console.error('Error rendering PDF canvas for URL:', pdfUrl, err);
        }
    }
}

window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sigModal = document.getElementById('signatureModalPopup');
        if (sigModal && sigModal.style.display !== 'none') {
            closeSignatureModal();
            return;
        }
        const ttdModal = document.getElementById('ttdChoiceModal');
        if (ttdModal && ttdModal.style.display !== 'none') {
            closeTtdChoiceModal();
            return;
        }
        const revModal = document.getElementById('revisiModal');
        if (revModal && revModal.style.display !== 'none') {
            closeRevisiModal();
            return;
        }
        const warnModal = document.getElementById('tuWarningModal');
        if (warnModal && warnModal.style.display !== 'none') {
            closeTuWarningModal();
            return;
        }
    }
});
</script>

<!-- ========== TU LOADING OVERLAY ========== -->
<div id="tuLoadingOverlay" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(0,0,0,0.65); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); align-items: center; justify-content: center; flex-direction: column;">
    <div style="background: #FFFFFF; border-radius: 24px; padding: 44px 48px; text-align: center; box-shadow: 0 32px 80px rgba(0,0,0,0.3); max-width: 420px; width: 90%; animation: tuFadeSlideIn 0.35s cubic-bezier(.22,.68,0,1.2);">
        <!-- Spinner -->
        <div style="margin: 0 auto 24px auto; width: 72px; height: 72px; position: relative;">
            <svg width="72" height="72" viewBox="0 0 72 72" style="position: absolute; top: 0; left: 0; animation: tuSpin 1.2s linear infinite;">
                <circle cx="36" cy="36" r="30" fill="none" stroke="#E0F2FE" stroke-width="6"/>
                <path d="M 36 6 A 30 30 0 0 1 66 36" fill="none" stroke="#0284C7" stroke-width="6" stroke-linecap="round"/>
            </svg>
            <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 28px;">📄</div>
        </div>
        <!-- Title -->
        <div style="font-size: 18px; font-weight: 800; color: #0F172A; margin-bottom: 8px; letter-spacing: -0.3px;">Memproses Surat...</div>
        <!-- Subtitle -->
        <div style="font-size: 13.5px; color: #64748B; line-height: 1.6; margin-bottom: 20px;">Sedang membuat PDF dan mengupload<br>ke Google Drive. Mohon tunggu sebentar.</div>
        <!-- Progress dots -->
        <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
            <span style="width: 8px; height: 8px; border-radius: 50%; background: #0284C7; display: inline-block; animation: tuDot 1.4s ease-in-out infinite 0s;"></span>
            <span style="width: 8px; height: 8px; border-radius: 50%; background: #0284C7; display: inline-block; animation: tuDot 1.4s ease-in-out infinite 0.2s;"></span>
            <span style="width: 8px; height: 8px; border-radius: 50%; background: #0284C7; display: inline-block; animation: tuDot 1.4s ease-in-out infinite 0.4s;"></span>
        </div>
    </div>
</div>

<style>
@keyframes tuSpin { to { transform: rotate(360deg); } }
@keyframes tuFadeSlideIn {
    from { opacity: 0; transform: scale(0.88) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes tuDot {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
    40%           { transform: scale(1.2); opacity: 1; }
}
</style>
