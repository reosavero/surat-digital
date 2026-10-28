<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

<?php 
$meta = $surat['meta'] ?? [];
$katSurat = $meta['kategori_surat'] ?? 'Surat Dinas';
$isDinas = ($katSurat === 'Surat Dinas');
$isProduksi = (($surat['type'] ?? '') === 'izin_produksi');
$isNotaDinas = (($surat['type'] ?? '') === 'nota_dinas' || ($meta['kategori_surat'] ?? '') === 'Surat Nota Dinas');
$tglIndo = !empty($surat['tanggal_surat']) ? date('d F Y', strtotime($surat['tanggal_surat'])) : date('d F Y');
$isSubmittedToKpp = (($surat['status'] ?? '') === 'submitted_to_kpp');
$kppTtd = $isSubmittedToKpp ? '' : ($surat['kpp_signature'] ?? '');
$kppParaf = $isSubmittedToKpp ? '' : ($meta['kpp_paraf'] ?? $meta['paraf_kpp'] ?? '');

$pemohonTtd = $meta['pemohon_signature_data'] ?? $meta['pemohon_signature'] ?? '';
$tuTtd = $surat['tu_signature'] ?? $meta['tu_signature'] ?? '';
$nomorSuratDisplay = $surat['nomor_surat'] ?? '';

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

    /* KPP Bottom Form Action Bar */
    .kpp-bottom-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
        gap: 14px;
        flex-wrap: wrap;
    }
    .kpp-bottom-left {
        display: flex;
        align-items: center;
    }
    .kpp-bottom-right {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .kpp-sub-actions {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .btn-print-kpp {
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
    .btn-print-kpp:hover {
        border-color: #94A3B8;
        background: #F8FAFC;
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
    #sigCanvas {
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
        box-shadow: 0 4px 12px rgba(37,99,235,0.22);
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
        .kpp-bottom-actions {
            flex-direction: column-reverse !important;
            align-items: stretch !important;
            gap: 12px !important;
            margin-bottom: 24px !important;
            width: 100% !important;
        }
        .kpp-bottom-right {
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
        .kpp-sub-actions {
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
        .kpp-bottom-left {
            width: 100% !important;
        }
        .btn-print-kpp {
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
        #signatureModal {
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
        #sigCanvas {
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

        /* Mobile Revisi & Warning Modals */
        #revisiModal > div, #kppWarningModal > div {
            width: 100% !important;
            max-width: 100% !important;
            padding: 20px 16px !important;
            border-radius: 16px !important;
        }
    }

    @media print {
        @page { size: A4 portrait; margin: 0; }
        html, body { width: 210mm !important; height: auto !important; margin: 0 !important; padding: 0 !important; background: #ffffff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .no-print, button, .btn-btn, #pvKppTtdPlaceholder, #pvKppParafPlaceholder, .tu-ttd-placeholder-text { display: none !important; visibility: hidden !important; opacity: 0 !important; }
        #pvKppTtdBox, #pvKppParafBox { border: none !important; background: transparent !important; box-shadow: none !important; padding: 0 !important; }
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
            display: block !important; position: static !important; float: none !important; width: 100% !important; max-width: 100% !important; flex: none !important; height: 297mm !important; max-height: 297mm !important; margin: 0 auto !important; padding: 0 !important; border: none !important; background: transparent !important; page-break-before: always !important; break-before: page !important; page-break-after: always !important; break-after: page !important; page-break-inside: avoid !important; break-inside: avoid !important;
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
                <i class="bi bi-pen-fill" style="color: #4338CA;"></i> TTD & Paraf KPP Bidang
            </h1>
        </div>
    </div>

    <?php if (!empty($meta['catatan_revisi'])): ?>
        <div style="background: #FEF2F2; border: 1.5px solid #FCA5A5; border-radius: 14px; padding: 16px 20px; margin-bottom: 24px; color: #991B1B; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.08);">
            <div style="font-weight: 800; font-size: 14px; margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-exclamation-triangle-fill" style="color: #DC2626; font-size: 18px;"></i> Catatan Revisi:
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
                    <?php if (!empty($meta['pemohon_nopeg']) || !empty($meta['pemohon_nip'])): ?>
                    <tr>
                        <td style="vertical-align: top; padding: 1.5px 0; padding-left: 10px;">NOPEG / NIP</td>
                        <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                        <td style="vertical-align: top; padding: 1.5px 0;"><?= htmlspecialchars($meta['pemohon_nopeg'] ?? $meta['pemohon_nip'] ?? '') ?></td>
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
                    <div style="margin-bottom: 20px; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
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
                                        <th style="padding: <?= $tPaddingTh ?>; border: 1px solid #000; font-weight: bold; font-family: Arial, sans-serif; <?= $wStyle ?> <?= $alignStyle ?> word-break: break-word; overflow-wrap: break-word; hyphens: auto; box-sizing: border-box;"><?= htmlspecialchars($h) ?></th>
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
                                            <td style="padding: <?= $tPaddingTd ?>; border: 1px solid #000; font-family: Arial, sans-serif; <?= $wStyle ?> <?= $alignStyle ?> word-break: break-word; overflow-wrap: break-word; box-sizing: border-box;"><?= htmlspecialchars($cell) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php 
                $isiTextDinas = trim($meta['isi_surat_dinas'] ?? $surat['isi_surat'] ?? '');
                $penutupDinas = trim($meta['penutup_surat'] ?? $meta['penutup_dinas'] ?? '');
                if (!empty($penutupDinas) && (str_contains($isiTextDinas, 'Demikian permohonan') || str_contains($isiTextDinas, $penutupDinas))) {
                    $penutupDinas = '';
                }
                if (!empty($penutupDinas)):
                ?>
                    <div style="line-height: 1.15; text-align: justify; font-size: 13px; font-family: Arial, sans-serif; color: #000000; margin-bottom: 24px; white-space: pre-line;"><?= htmlspecialchars($penutupDinas) ?></div>
                <?php endif; ?>

                <!-- Signature Block (2 columns if pemohon present, else single column) -->
                <?php if (!empty($pemohonTtd) || !empty($meta['pemohon_nama'])): ?>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 14px; margin-bottom: 14px;">
                        <div style="width: 250px; text-align: left; font-size: 12px; line-height: 1.15; color: #000000;">
                            <div>Kepala Sub Bagian Tata Usaha</div>
                            <div>TVRI Stasiun Jawa Timur</div>
                            <div style="height: 55px; margin: 4px 0; display: flex; align-items: center; justify-content: flex-start;">
                                <?php if (!empty($tuTtd)): ?>
                                    <img src="<?= getImgUrl($tuTtd) ?>" style="max-height: 50px; object-fit: contain;">
                                <?php endif; ?>
                            </div>

                            <div style="position: relative; display: inline-block;">
                                <div id="pvKppParafBox" onclick="openSignatureModal('paraf')" title="Klik untuk membuat / menggambar Paraf KPP Bidang"
                                     style="position: absolute; right: 100%; top: 50%; transform: translateY(-50%); margin-right: 8px; width: 56px; height: 42px; border: 2px dashed #2563EB; background: #EFF6FF; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;"
                                     onmouseover="this.style.background='#DBEAFE';" onmouseout="this.style.background='#EFF6FF';">
                                    <img id="pvKppParafImg" src="<?= !empty($kppParaf) ? getImgUrl($kppParaf) : '' ?>" style="max-height: 48px; width: auto; max-width: 70px; object-fit: contain; <?= empty($kppParaf) ? 'display: none;' : '' ?>" alt="Paraf KPP">
                                    <span id="pvKppParafPlaceholder" style="<?= !empty($kppParaf) ? 'display: none;' : '' ?> font-size: 8.5px; font-weight: 800; color: #2563EB; text-align: center;">
                                        Paraf
                                    </span>
                                </div>

                                <div style="text-decoration: underline; font-weight: 500;"><?= htmlspecialchars($tuNamaDisplay) ?></div>
                                <div><?= htmlspecialchars($tuNipDisplay) ?></div>
                            </div>
                        </div>

                        <div style="width: 230px; text-align: center; font-size: 12px; line-height: 1.15; color: #000000;">
                            <div>Hormat Saya,</div>
                            <div style="min-height: 35px; margin: 3px 0; display: flex; align-items: center; justify-content: center;">
                                <?php if (!empty($pemohonTtd)): ?>
                                    <img src="<?= getImgUrl($pemohonTtd) ?>" style="max-height: 45px; object-fit: contain;">
                                <?php else: ?>
                                    <div style="height: 35px;"></div>
                                <?php endif; ?>
                            </div>
                            <div><?= htmlspecialchars($meta['pemohon_nama'] ?? '') ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="display: flex; justify-content: flex-end; margin-top: 24px; margin-bottom: 24px;">
                        <div style="width: 240px; text-align: left; font-size: 12.5px; line-height: 1.15; color: #000000;">
                            <div>Mengetahui,</div>
                            <div>Kepala Sub Bagian Tata Usaha</div>
                            <div style="height: 55px; margin: 4px 0; display: flex; align-items: center; justify-content: flex-start;">
                                <?php if (!empty($tuTtd)): ?>
                                    <img src="<?= getImgUrl($tuTtd) ?>" style="max-height: 50px; object-fit: contain;">
                                <?php endif; ?>
                            </div>

                            <div style="position: relative; display: inline-block;">
                                <div id="pvKppParafBox" onclick="openSignatureModal('paraf')" title="Klik untuk membuat / menggambar Paraf KPP Bidang"
                                     style="position: absolute; right: 100%; top: 50%; transform: translateY(-50%); margin-right: 8px; width: 56px; height: 42px; border: 2px dashed #2563EB; background: #EFF6FF; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;"
                                     onmouseover="this.style.background='#DBEAFE';" onmouseout="this.style.background='#EFF6FF';">
                                    <img id="pvKppParafImg" src="<?= !empty($kppParaf) ? getImgUrl($kppParaf) : '' ?>" style="max-height: 48px; width: auto; max-width: 70px; object-fit: contain; <?= empty($kppParaf) ? 'display: none;' : '' ?>" alt="Paraf KPP">
                                    <span id="pvKppParafPlaceholder" style="<?= !empty($kppParaf) ? 'display: none;' : '' ?> font-size: 8.5px; font-weight: 800; color: #2563EB; text-align: center;">
                                        Paraf
                                    </span>
                                </div>
                                <div style="text-decoration: underline; font-weight: 500;"><?= htmlspecialchars($tuNamaDisplay) ?></div>
                                <div><?= htmlspecialchars($tuNipDisplay) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div style="padding-top: 12px;">
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
    <!-- SIDE-BY-SIDE 2-COLUMN DOCUMENT PREVIEW (KANAN & KIRI - SAME AS TU PROCESS) -->
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
                            <div id="pvKppTtdBox" onclick="openSignatureModal('ttd')" title="Klik untuk membuat / menggambar TTD Digital KPP Bidang" 
                                 style="height: 48px; width: 160px; max-width: 100%; margin: 4px auto; display: flex; align-items: center; justify-content: center; border: 2px dashed #2563EB; background: #EFF6FF; border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                                 onmouseover="this.style.background='#DBEAFE';" onmouseout="this.style.background='#EFF6FF';">
                                <img id="pvKppTtdImg" src="<?= !empty($kppTtd) ? getImgUrl($kppTtd) : '' ?>" style="max-height: 44px; width: auto; object-fit: contain; <?= empty($kppTtd) ? 'display: none;' : '' ?>" alt="TTD KPP Bidang">
                                <span id="pvKppTtdPlaceholder" style="<?= !empty($kppTtd) ? 'display: none;' : '' ?> font-size: 10px; font-weight: 700; color: #2563EB; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="bi bi-pen"></i> Klik untuk Isi TTD KPP
                                </span>
                            </div>
                            <div style="text-decoration: underline; font-weight: 500;"><?= htmlspecialchars($kppNamaDisplay) ?></div>
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

                    <?php 
                    $isiTextDinas = trim($meta['isi_surat_dinas'] ?? $surat['isi_surat'] ?? '');
                    $penutupDinas = trim($meta['penutup_surat'] ?? $meta['penutup_dinas'] ?? '');
                    if (!empty($penutupDinas) && (str_contains($isiTextDinas, 'Demikian permohonan') || str_contains($isiTextDinas, $penutupDinas))) {
                        $penutupDinas = '';
                    }
                    if (!empty($penutupDinas)):
                    ?>
                        <div style="line-height: 1.35; text-align: justify; font-size: 11px; font-family: Arial, sans-serif; color: #000000; margin-bottom: 12px; white-space: pre-line;"><?= htmlspecialchars($penutupDinas) ?></div>
                    <?php endif; ?>

                    <!-- Signature Block directly under content -->
                    <div style="display: flex; justify-content: flex-end; margin-top: 16px; margin-bottom: 16px;">
                        <div style="width: 200px; text-align: left; font-size: 10.5px; line-height: 1.25; color: #000000;">
                            <div>Mengetahui,</div>
                            <div>Kepala Sub Bagian Tata Usaha</div>
                            <div style="height: 48px; margin: 3px 0; display: flex; align-items: center; justify-content: flex-start;">
                                <?php if (!empty($tuTtd)): ?>
                                    <img src="<?= getImgUrl($tuTtd) ?>" style="max-height: 44px; object-fit: contain;">
                                <?php endif; ?>
                            </div>

                            <!-- Name & NIP block with Paraf KPP Bidang displaying statically to the left -->
                            <div style="position: relative; display: inline-block;">
                                <div id="pvKppParafBox" onclick="openSignatureModal('paraf')" title="Klik untuk membuat / menggambar Paraf KPP Bidang"
                                     style="position: absolute; right: 100%; top: 50%; transform: translateY(-50%); margin-right: 8px; width: 56px; height: 42px; border: 2px dashed #2563EB; background: #EFF6FF; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;"
                                     onmouseover="this.style.background='#DBEAFE';" onmouseout="this.style.background='#EFF6FF';">
                                    <img id="pvKppParafImg" src="<?= !empty($kppParaf) ? getImgUrl($kppParaf) : '' ?>" style="max-height: 48px; width: auto; max-width: 70px; object-fit: contain; <?= empty($kppParaf) ? 'display: none;' : '' ?>" alt="Paraf KPP">
                                    <span id="pvKppParafPlaceholder" style="<?= !empty($kppParaf) ? 'display: none;' : '' ?> font-size: 8.5px; font-weight: 800; color: #2563EB; text-align: center;">
                                        Paraf
                                    </span>
                                </div>
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

                    $qrUrlKpp = !empty($adminBarcodeLink) ? $adminBarcodeLink : 'https://drive.google.com';
                    $qrSrcKpp = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrUrlKpp);
                    ?>
                    <div style="display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 6px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <a href="<?= htmlspecialchars($qrUrlKpp) ?>" target="_blank" title="Scan / Klik untuk membuka URL Google Drive">
                                <img src="<?= $qrSrcKpp ?>" style="width: 48px; height: 48px; border: 1.5px solid #000000; background: #FFFFFF; padding: 2px;" alt="Barcode QR Google Drive">
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
    ?>

    <?php if (!empty($pdfLampirans)): ?>
        <div style="width: 100%; margin-top: 24px; margin-bottom: 32px; padding-top: 24px; border-top: 2px dashed #CBD5E1;">
            <div class="admin-review-grid-container" style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 20px; align-items: stretch; width: 100%;">
                <?php 
                $totalCardCount = 0;
                foreach ($pdfLampirans as $idx => $pdfPath): 
                    $pageCount = getPdfPageCount($pdfPath);
                    for ($p = 1; $p <= $pageCount; $p++):
                        $totalCardCount++;
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

    <!-- ACTION BUTTONS FORM FOR KPP BIDANG -->
    <form id="formKppSubmit" action="<?= BASE_URL ?>/surat/kpp-submit/<?= $surat['id'] ?>" method="POST" autocomplete="off">
        <?= Session::csrfField() ?>
        <input type="hidden" name="action_type" id="inKppActionType" value="submit">
        <input type="hidden" name="kpp_signature_data" id="inKppSignatureData" value="<?= htmlspecialchars($kppTtd) ?>">
        <input type="hidden" name="kpp_paraf_data" id="inKppParafData" value="<?= htmlspecialchars($kppParaf) ?>">
        <input type="hidden" name="catatan_revisi" id="inKppCatatanRevisi" value="">

        <!-- FORM ACTIONS BAR -->
        <div class="kpp-bottom-actions">
            <div class="kpp-bottom-left">
                <button type="button" onclick="printKppDocument()" class="btn-btn btn-print-kpp">
                    <i class="bi bi-printer-fill" style="color: #2563EB; font-size: 16px;"></i> Print Surat
                </button>
            </div>
            <div class="kpp-bottom-right">
                <div class="kpp-sub-actions">
                    <a href="<?= BASE_URL ?>/dashboard" class="btn-btn btn-btn-secondary btn-action-batal">
                        Batal
                    </a>

                    <button type="button" onclick="openRevisionModal()" class="btn-btn btn-action-revisi">
                        <i class="bi bi-arrow-counterclockwise"></i> Revisi
                    </button>
                </div>

                <button type="submit" id="btnSubmitKpp" class="btn-btn btn-btn-primary btn-action-teruskan">
                    <i class="bi bi-send-fill"></i> Teruskan
                </button>
            </div>
        </div>
    </form>
</div>

<!-- MODAL SIGNATURE & PARAF CANVAS DRAWING -->
<div id="signatureModal" onclick="if(event.target === this) closeSignatureModal()" class="kpp-signature-modal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(6px); align-items: center; justify-content: center; padding: 20px;">
    <div class="sig-modal-dialog">
        <div class="sig-modal-header">
            <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                <div class="sig-header-icon">
                    <i class="bi bi-pen-fill"></i>
                </div>
                <div style="min-width: 0;">
                    <h3 id="modalSigTitle" class="sig-header-title">Input Tanda Tangan Digital KPP Bidang</h3>
                    <div class="sig-header-subtitle">Bubuhkan tanda tangan atau paraf digital</div>
                </div>
            </div>
        </div>

        <div class="sig-modal-body">
            <p id="modalSigSub" class="sig-instructions-text">
                Gunakan mouse atau layar sentuh di bawah untuk membubuhkan TTD KPP Bidang:
            </p>

            <div class="sig-canvas-wrapper">
                <canvas id="sigCanvas"></canvas>
                <div class="sig-canvas-watermark">
                    <i class="bi bi-pencil"></i> Sentuh atau goreskan tanda tangan di sini
                </div>
            </div>

            <div class="sig-modal-footer">
                <div class="sig-footer-subgroup">
                    <button type="button" onclick="clearSigCanvas()" class="btn-btn btn-sig-clear">
                        <i class="bi bi-eraser-fill"></i> Hapus
                    </button>
                    <button type="button" onclick="closeSignatureModal()" class="btn-btn btn-btn-secondary btn-sig-cancel">
                        Batal
                    </button>
                </div>

                <div class="sig-footer-main">
                    <button type="button" onclick="saveSigCanvas()" class="btn-btn btn-btn-primary btn-sig-save">
                        <i class="bi bi-check-lg"></i> Simpan &amp; Tempel
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
<!-- MODAL POPUP PERINGATAN KPP BELUM DIISI     -->
<!-- ========================================== -->
<div id="kppWarningModal" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #FFFFFF; width: 100%; max-width: 440px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); text-align: center; padding: 28px 24px; border: 1px solid #F3F4F6; position: relative;">
        <div style="width: 60px; height: 60px; border-radius: 50%; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 28px; border: 2px solid #FDE68A;">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <h3 id="kppWarningTitle" style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800; color: #0F172A; letter-spacing: -0.2px;">Tanda Tangan Belum Diisi</h3>
        <p id="kppWarningMsg" style="margin: 0 0 24px 0; font-size: 13.5px; color: #64748B; line-height: 1.5;">
            Tanda Tangan Digital (TTD) KPP Bidang belum diisi.<br>Silakan masukkan TTD Anda terlebih dahulu!
        </p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button type="button" onclick="closeKppWarningModal()" class="btn-btn btn-btn-secondary" style="flex: 1; justify-content: center; font-weight: 600; padding: 10px 16px;">
                Tutup
            </button>
            <button type="button" id="btnKppWarningAction" onclick="confirmOpenKppFromWarning()" class="btn-btn btn-btn-primary" style="flex: 1.3; justify-content: center; font-weight: 700; padding: 10px 16px; background: #2563EB; border-color: #2563EB; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-pen-fill"></i> Isi TTD Sekarang
            </button>
        </div>
    </div>
</div>

<script>
const isNotaDinas = <?= $isNotaDinas ? 'true' : 'false' ?>;
let currentSigMode = 'ttd'; // 'ttd' or 'paraf'
const sigCanvas = document.getElementById('sigCanvas');
const sigCtx = sigCanvas ? sigCanvas.getContext('2d') : null;
let drawingSig = false;
let hasDrawnSig = false;
let currentStrokes = [];

function setupSigCanvasDpi() {
    if (!sigCanvas) return;
    const rect = sigCanvas.getBoundingClientRect();
    if (rect.width === 0 || rect.height === 0) return;
    const dpr = Math.max(window.devicePixelRatio || 1, 2);
    sigCanvas.width = rect.width * dpr;
    sigCanvas.height = rect.height * dpr;
    if (sigCtx) {
        sigCtx.scale(dpr, dpr);
    }
}

function getSigCanvasCoords(e) {
    if (!sigCanvas) return { x: 0, y: 0 };
    const rect = sigCanvas.getBoundingClientRect();
    let clientX = e.clientX;
    let clientY = e.clientY;
    if (e.touches && e.touches.length > 0) {
        clientX = e.touches[0].clientX;
        clientY = e.touches[0].clientY;
    } else if (e.changedTouches && e.changedTouches.length > 0) {
        clientX = e.changedTouches[0].clientX;
        clientY = e.changedTouches[0].clientY;
    }
    return {
        x: clientX - rect.left,
        y: clientY - rect.top
    };
}

let lastSigX = 0, lastSigY = 0;

function getStrokeColor() {
    if (currentSigMode === 'paraf' && isNotaDinas) {
        return '#000000';
    }
    return '#1E3A8A';
}

function startDrawingSig(e) {
    if (e.cancelable) e.preventDefault();
    drawingSig = true;
    hasDrawnSig = true;

    const watermark = document.querySelector('.sig-canvas-watermark');
    if (watermark) watermark.style.display = 'none';

    const coords = getSigCanvasCoords(e);
    lastSigX = coords.x;
    lastSigY = coords.y;

    currentStrokes.push({ type: 'start', x: lastSigX, y: lastSigY });

    if (sigCtx) {
        sigCtx.strokeStyle = getStrokeColor();
        sigCtx.lineWidth = (currentSigMode === 'paraf') ? 5.5 : 3.5;
        sigCtx.lineCap = 'round';
        sigCtx.lineJoin = 'round';
        sigCtx.beginPath();
        sigCtx.arc(lastSigX, lastSigY, (currentSigMode === 'paraf') ? 2.75 : 1.75, 0, Math.PI * 2);
        sigCtx.fillStyle = sigCtx.strokeStyle;
        sigCtx.fill();
        sigCtx.beginPath();
        sigCtx.moveTo(lastSigX, lastSigY);
    }
}

function drawSig(e) {
    if (!drawingSig || !sigCtx) return;
    if (e.cancelable) e.preventDefault();
    const coords = getSigCanvasCoords(e);

    const midX = (lastSigX + coords.x) / 2;
    const midY = (lastSigY + coords.y) / 2;

    currentStrokes.push({ type: 'line', lastX: lastSigX, lastY: lastSigY, midX: midX, midY: midY, x: coords.x, y: coords.y });

    sigCtx.strokeStyle = getStrokeColor();
    sigCtx.lineWidth = (currentSigMode === 'paraf') ? 5.5 : 3.5;
    sigCtx.lineCap = 'round';
    sigCtx.lineJoin = 'round';
    sigCtx.quadraticCurveTo(lastSigX, lastSigY, midX, midY);
    sigCtx.stroke();

    lastSigX = coords.x;
    lastSigY = coords.y;
}

function stopDrawingSig(e) {
    if (e && e.cancelable) e.preventDefault();
    drawingSig = false;
}

if (sigCanvas) {
    sigCanvas.addEventListener('mousedown', startDrawingSig);
    sigCanvas.addEventListener('mousemove', drawSig);
    sigCanvas.addEventListener('mouseup', stopDrawingSig);
    sigCanvas.addEventListener('mouseleave', stopDrawingSig);

    sigCanvas.addEventListener('touchstart', function(e) {
        if (e.cancelable) e.preventDefault();
        startDrawingSig(e);
    }, { passive: false });
    sigCanvas.addEventListener('touchmove', function(e) {
        if (e.cancelable) e.preventDefault();
        drawSig(e);
    }, { passive: false });
    sigCanvas.addEventListener('touchend', function(e) {
        if (e.cancelable) e.preventDefault();
        stopDrawingSig(e);
    }, { passive: false });
}

function openSignatureModal(mode) {
    currentSigMode = mode;
    document.body.style.overflow = 'hidden';
    const modal = document.getElementById('signatureModal');
    const title = document.getElementById('modalSigTitle');
    const sub = document.getElementById('modalSigSub');

    if (mode === 'paraf') {
        if (title) title.innerText = 'Input Paraf Digital KPP Bidang';
        if (sub) sub.innerText = 'Gunakan sentuhan jari atau stylus di bawah untuk membubuhkan paraf:';
    } else {
        if (title) title.innerText = 'Input Tanda Tangan Digital KPP Bidang';
        if (sub) sub.innerText = 'Gunakan sentuhan jari atau stylus di bawah untuk membubuhkan tanda tangan:';
    }

    if (modal) modal.style.display = 'flex';
    requestAnimationFrame(() => {
        setupSigCanvasDpi();
        clearSigCanvas();
    });
}

function closeSignatureModal() {
    document.body.style.overflow = '';
    const modal = document.getElementById('signatureModal');
    if (modal) modal.style.display = 'none';
}

function clearSigCanvas() {
    if (sigCtx && sigCanvas) {
        sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        sigCtx.strokeStyle = getStrokeColor();
        sigCtx.lineWidth = (currentSigMode === 'paraf') ? 5.5 : 3.5;
        sigCtx.lineCap = 'round';
        sigCtx.lineJoin = 'round';
    }
    const watermark = document.querySelector('.sig-canvas-watermark');
    if (watermark) watermark.style.display = 'flex';
    currentStrokes = [];
    hasDrawnSig = false;
}

function saveSigCanvas() {
    if (!hasDrawnSig) {
        alert('Silakan gambar tanda tangan / paraf terlebih dahulu pada canvas.');
        return;
    }

    const dataUrl = sigCanvas.toDataURL('image/png');

    if (currentSigMode === 'paraf') {
        document.getElementById('inKppParafData').value = dataUrl;
        const imgs = document.querySelectorAll('#pvKppParafImg');
        const phs = document.querySelectorAll('#pvKppParafPlaceholder');
        imgs.forEach(img => {
            img.src = dataUrl;
            img.style.display = 'block';
        });
        phs.forEach(ph => ph.style.display = 'none');
    } else {
        document.getElementById('inKppSignatureData').value = dataUrl;
        const imgs = document.querySelectorAll('#pvKppTtdImg');
        const phs = document.querySelectorAll('#pvKppTtdPlaceholder');
        imgs.forEach(img => {
            img.src = dataUrl;
            img.style.display = 'block';
        });
        phs.forEach(ph => ph.style.display = 'none');
    }

    closeSignatureModal();
}

function openRevisiModal() {
    const m = document.getElementById('revisiModal') || document.getElementById('revisionModal');
    if (m) m.style.display = 'flex';
}
function openRevisionModal() { openRevisiModal(); }

function closeRevisiModal() {
    const m = document.getElementById('revisiModal') || document.getElementById('revisionModal');
    if (m) m.style.display = 'none';
}
function closeRevisionModal() { closeRevisiModal(); }

function submitRevisi() {
    const inp = document.getElementById('modalRevisiNote') || document.getElementById('modalCatatanRevisiInp');
    const note = inp ? inp.value.trim() : '';
    if (!note) {
        alert('Mohon ketikkan alasan atau catatan revisi!');
        return;
    }
    document.getElementById('inKppActionType').value = 'revisi';
    document.getElementById('inKppCatatanRevisi').value = note;
    document.getElementById('formKppSubmit').submit();
}
function submitKppRevision() { submitRevisi(); }

let currentWarningType = 'ttd';

function showKppWarningModal(isParaf) {
    document.body.style.overflow = 'hidden';
    const modal = document.getElementById('kppWarningModal');
    const title = document.getElementById('kppWarningTitle');
    const msg = document.getElementById('kppWarningMsg');
    const btn = document.getElementById('btnKppWarningAction');

    currentWarningType = isParaf ? 'paraf' : 'ttd';

    if (isParaf) {
        if (title) title.textContent = 'Paraf Belum Diisi';
        if (msg) msg.innerHTML = 'Paraf Digital KPP Bidang belum diisi.<br>Silakan masukkan Paraf Anda terlebih dahulu!';
        if (btn) btn.innerHTML = '<i class="bi bi-pen-fill"></i> Isi Paraf Sekarang';
    } else {
        if (title) title.textContent = 'Tanda Tangan Belum Diisi';
        if (msg) msg.innerHTML = 'Tanda Tangan Digital (TTD) KPP Bidang belum diisi.<br>Silakan masukkan TTD Anda terlebih dahulu!';
        if (btn) btn.innerHTML = '<i class="bi bi-pen-fill"></i> Isi TTD Sekarang';
    }

    if (modal) modal.style.display = 'flex';
}

function closeKppWarningModal() {
    const modal = document.getElementById('kppWarningModal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
}

function confirmOpenKppFromWarning() {
    closeKppWarningModal();
    openSignatureModal(currentWarningType);
}

window.addEventListener('click', function(e) {
    const revModal = document.getElementById('revisiModal') || document.getElementById('revisionModal');
    const sigModal = document.getElementById('signatureModal');
    const warnModal = document.getElementById('kppWarningModal');
    if (e.target === revModal) closeRevisiModal();
    if (e.target === sigModal) closeSignatureModal();
    if (e.target === warnModal) closeKppWarningModal();
});

async function printKppDocument() {
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
            cloned.querySelectorAll('.no-print, #pvKppTtdPlaceholder, #pvKppParafPlaceholder, .tu-ttd-placeholder-text').forEach(el => el.remove());
            cloned.querySelectorAll('img').forEach(img => {
                if (!img.src || img.src === '' || img.src.includes('about:blank') || img.style.display === 'none') {
                    img.remove();
                }
            });

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
                .printable-a4-page { background: #FFFFFF !important; border: none !important; box-shadow: none !important; padding: 3cm !important; margin: 0 auto !important; width: 210mm !important; height: 297mm !important; max-height: 297mm !important; box-sizing: border-box !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; page-break-after: always !important; break-after: page !important; page-break-inside: avoid !important; break-inside: avoid !important; overflow: hidden !important; }
                .printable-a4-page.printable-lampiran-page { padding: 0 !important; border: none !important; box-shadow: none !important; overflow: hidden !important; width: 210mm !important; height: 297mm !important; background: #ffffff !important; display: block !important; }
                .printable-a4-page.printable-lampiran-page img,
                .printable-a4-page.printable-lampiran-page .pdf-img-item { width: 100% !important; height: 100% !important; max-width: 100% !important; max-height: 297mm !important; object-fit: contain !important; display: block !important; margin: 0 auto !important; border: none !important; box-shadow: none !important; transform: none !important; }
                .printable-a4-page:last-child { page-break-after: avoid !important; break-after: avoid !important; }
                .a4-paper-preview { background: #FFFFFF !important; border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; max-width: 100% !important; height: auto !important; min-height: 0 !important; box-sizing: border-box !important; }
                #pvKppTtdBox, #pvKppParafBox { border: none !important; background: transparent !important; box-shadow: none !important; padding: 0 !important; }
                #pvKppTtdPlaceholder, #pvKppParafPlaceholder, .tu-ttd-placeholder-text { display: none !important; visibility: hidden !important; opacity: 0 !important; }
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
                    .a4-paper-preview { border: none !important; box-shadow: none !important; padding: 0 !important; }
                    #pvKppTtdBox, #pvKppParafBox { border: none !important; background: transparent !important; box-shadow: none !important; padding: 0 !important; }
                    #pvKppTtdPlaceholder, #pvKppParafPlaceholder, .tu-ttd-placeholder-text { display: none !important; visibility: hidden !important; opacity: 0 !important; }
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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        renderServerPdfCanvases();
        setupKppSubmitValidation();
    });
} else {
    renderServerPdfCanvases();
    setupKppSubmitValidation();
}

function setupKppSubmitValidation() {
    const formKpp = document.getElementById('formKppSubmit');
    if (formKpp) {
        formKpp.addEventListener('submit', function(e) {
            const actionType = document.getElementById('inKppActionType') ? document.getElementById('inKppActionType').value : 'submit';
            if (actionType === 'submit') {
                const isNotaDinas = <?= json_encode($isNotaDinas) ?>;
                const sigVal = document.getElementById('inKppSignatureData') ? document.getElementById('inKppSignatureData').value.trim() : '';
                const parafVal = document.getElementById('inKppParafData') ? document.getElementById('inKppParafData').value.trim() : '';

                if (isNotaDinas) {
                    if (!parafVal) {
                        e.preventDefault();
                        e.stopPropagation();
                        showKppWarningModal(true);
                        return false;
                    }
                } else {
                    if (!sigVal) {
                        e.preventDefault();
                        e.stopPropagation();
                        showKppWarningModal(false);
                        return false;
                    }
                    if (!parafVal) {
                        e.preventDefault();
                        e.stopPropagation();
                        showKppWarningModal(true);
                        return false;
                    }
                }
            }
        });
    }
}

window.addEventListener('resize', function() {
    const modal = document.getElementById('signatureModal');
    if (modal && modal.style.display === 'flex' && !hasDrawnSig) {
        setupSigCanvasDpi();
    }
});

window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sigModal = document.getElementById('signatureModal');
        if (sigModal && sigModal.style.display !== 'none') {
            closeSignatureModal();
            return;
        }
        const revModal = document.getElementById('revisiModal');
        if (revModal && revModal.style.display !== 'none') {
            closeRevisiModal();
            return;
        }
        const warnModal = document.getElementById('kppWarningModal');
        if (warnModal && warnModal.style.display !== 'none') {
            closeKppWarningModal();
            return;
        }
    }
});
</script>
