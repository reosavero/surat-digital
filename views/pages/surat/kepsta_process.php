<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

<?php 
$meta = $surat['meta'] ?? [];
$nomorSuratDisplay = !empty($surat['nomor_surat']) ? $surat['nomor_surat'] : (!empty($meta['nomor_surat_disposisi']) ? $meta['nomor_surat_disposisi'] : (!empty($meta['nomor_surat']) ? $meta['nomor_surat'] : ''));
$isNotaDinas = (($surat['type'] ?? '') === 'nota_dinas' || ($meta['kategori_surat'] ?? '') === 'Surat Nota Dinas');

$tuAccount = User::getSingleUserByRole('tata_usaha') ?: [];
$kepstaAccount = User::getSingleUserByRole('kepala_stasiun') ?: [];

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

$kepstaNamaDisplay = $kepstaAccount['name'] ?? 'Akbar Sahidi, S.Sos., M.Si';
$kepstaNipRaw = $kepstaAccount['nip'] ?? '197005121995031002';
$kepstaNipDisplay = !empty($kepstaNipRaw) ? (str_starts_with(strtoupper(trim($kepstaNipRaw)), 'NIP') ? trim($kepstaNipRaw) : 'NIP. ' . trim($kepstaNipRaw)) : 'NIP. 197005121995031002';
?>

<div style="max-width: 1360px; margin: 0 auto; padding-bottom: 40px;">
    <!-- Top Action & Navigation Header -->
    <div class="page-header-banner" style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-check-square-fill" style="color: #003399;"></i> Disposisi & Tanda Tangan Kepala Stasiun (Kepsta)
            </h1>
        </div>
    </div>

    <?php 
    $dispoData = is_array($disposisi) ? $disposisi : [];
    $dispoChecklist = $dispoData['checklist'] ?? [];
    $sifatNotaDinas = !empty($meta['sifat_surat']) ? $meta['sifat_surat'] : 'Biasa';
    $rawSifat = (!empty($dispoData['kepsta_signed_at']) && !empty($dispoData['sifat_surat'])) ? $dispoData['sifat_surat'] : (!empty($dispoData['sifat_surat']) && !empty($surat['status']) && $surat['status'] === 'completed' ? $dispoData['sifat_surat'] : '');
    $sifatSelected = strtolower(trim($rawSifat));
    $katSurat = $meta['kategori_surat'] ?? 'Surat Dinas';
    $isDinas = ($katSurat === 'Surat Dinas');
    $isProduksi = (($surat['type'] ?? '') === 'izin_produksi');
    $tglIndo = !empty($surat['tanggal_surat']) ? date('d F Y', strtotime($surat['tanggal_surat'])) : date('d F Y');
    $pemohonTtd = $meta['pemohon_signature_data'] ?? $meta['pemohon_signature'] ?? '';
    $tuTtdRaw = !empty($surat['tu_signature']) ? $surat['tu_signature'] : (!empty($meta['tu_signature']) ? $meta['tu_signature'] : '');
    $tuTtd = ($tuTtdRaw === '/assets/img/ttd_tu_stamp.png') ? '' : $tuTtdRaw;
    $kppTtdRaw = !empty($surat['kpp_signature']) ? $surat['kpp_signature'] : (!empty($meta['kpp_signature']) ? $meta['kpp_signature'] : (!empty($meta['pemohon_signature']) ? $meta['pemohon_signature'] : ''));
    $kppTtd = ($kppTtdRaw === '/assets/img/ttd_tu_stamp.png') ? '' : $kppTtdRaw;
    $kppParafRaw = !empty($meta['kpp_paraf']) ? $meta['kpp_paraf'] 
        : (!empty($meta['paraf_kpp']) ? $meta['paraf_kpp'] 
        : (!empty($meta['tu_paraf']) ? $meta['tu_paraf'] 
        : (!empty($surat['kpp_paraf']) ? $surat['kpp_paraf'] 
        : (!empty($meta['paraf']) ? $meta['paraf'] 
        : (!empty($surat['paraf']) ? $surat['paraf'] 
        : (!empty($kppTtd) ? $kppTtd : ''))))));
    $kppParaf = ($kppParafRaw === '/assets/img/ttd_tu_stamp.png') ? '' : $kppParafRaw;
    $kepstaTtd = $dispoData['kepsta_signature'] ?? $meta['kepsta_signature'] ?? $surat['kepsta_signature'] ?? '';
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
    @media print {
        @page { size: A4 portrait; margin: 0; }
        html, body { width: 210mm !important; height: auto !important; margin: 0 !important; padding: 0 !important; background: #ffffff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .no-print, button, .btn-btn, header, aside, .app-topbar, .app-sidebar, .toast-container { display: none !important; }
        div[style*="max-width: 1360px"] {
            display: flex !important;
            flex-direction: column !important;
            width: 210mm !important;
            max-width: 210mm !important;
            margin: 0 auto !important;
            padding: 0 !important;
        }
        #kepstaForm {
            order: 1 !important;
            page-break-after: always !important;
            break-after: page !important;
        }
        .admin-review-grid-container {
            order: 2 !important;
            page-break-before: always !important;
            break-before: page !important;
        }
        div[style*="border-top: 2px dashed #CBD5E1"] {
            order: 3 !important;
            page-break-before: always !important;
            break-before: page !important;
        }
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
            visibility: visible !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; width: 210mm !important; max-width: 210mm !important; height: 297mm !important; max-height: 297mm !important; padding: 2.2cm !important; margin: 0 auto !important; box-shadow: none !important; border: none !important; background: #ffffff !important; box-sizing: border-box !important; page-break-after: always !important; break-after: page !important; overflow: hidden !important; flex: none !important;
        }
        .a4-paper-preview > div {
            border: 1.5px solid #000000 !important; padding: 12px 16px !important; box-sizing: border-box !important; background: #FFFFFF !important; height: 100% !important; min-height: 100% !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important;
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

    /* Signature Modal Kepsta */
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
    #modalKepstaCanvas {
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

    /* Responsiveness for Tablet & Mobile */
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
        .pdf-lampiran-wrapper-col {
            flex: 0 0 100% !important;
            max-width: 100% !important;
            width: 100% !important;
        }
    }

    @media screen and (max-width: 768px) {
        .a4-paper-preview {
            width: 100% !important;
            max-width: 100% !important;
            min-height: auto !important;
            height: auto !important;
            padding: 16px 12px !important;
            box-sizing: border-box !important;
            border-radius: 14px !important;
        }
        .kepsta-dispo-paper {
            padding: 8px 6px !important;
            margin-bottom: 20px !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06) !important;
        }
        .dispo-inner-frame {
            padding: 10px 8px !important;
            border-radius: 8px !important;
        }
        .dispo-header-title {
            font-size: 11.5px !important;
        }
        .dispo-header-title div {
            font-size: 11.5px !important;
        }
        .dispo-recipients-wrap {
            width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
            margin-bottom: 12px !important;
            border-radius: 6px !important;
        }
        .dispo-recipients-wrap table {
            min-width: 480px !important;
            width: 100% !important;
        }
        .dispo-mobile-scroll-hint {
            display: flex !important;
            align-items: center !important;
            gap: 4px !important;
        }
        .dispo-mid-section {
            flex-direction: column !important;
            gap: 12px !important;
        }
        .dispo-col-left {
            width: 100% !important;
        }
        .dispo-col-right {
            width: 100% !important;
        }
        .dispo-catatan-label {
            margin-left: 0 !important;
        }
        textarea[name="catatan_kepsta"] {
            min-height: 130px !important;
            height: auto !important;
        }
        .dispo-bottom-section {
            flex-direction: column !important;
            gap: 14px !important;
        }
        .dispo-sekre-box {
            width: 100% !important;
        }
        .dispo-kepsta-box {
            width: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-end !important;
            text-align: right !important;
        }
        #pvKepstaTtdBox {
            width: 100% !important;
            max-width: 200px !important;
            height: 52px !important;
        }
        .kepsta-bottom-actions {
            flex-direction: column-reverse !important;
            align-items: stretch !important;
            gap: 10px !important;
            width: 100% !important;
            margin-top: 14px !important;
            padding-top: 0 !important;
        }
        .kepsta-bottom-actions > * {
            width: 100% !important;
            justify-content: center !important;
            text-align: center !important;
        }
        .btn-action-teruskan {
            width: 100% !important;
            padding: 13px 20px !important;
            font-size: 15px !important;
            border-radius: 10px !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
        }
        .btn-action-batal {
            width: 100% !important;
            padding: 11px 16px !important;
            font-size: 14px !important;
            border-radius: 10px !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
        }

        /* Review Modal Responsive */
        #kepstaReviewModal {
            padding: 10px !important;
            align-items: flex-end !important;
        }
        #kepstaReviewModal > div {
            width: 100% !important;
            max-width: 100% !important;
            max-height: 94vh !important;
            border-radius: 18px 18px 14px 14px !important;
        }
        #kepstaReviewModal [style*="padding: 32px"] {
            padding: 10px 6px !important;
        }
        #kepstaReviewModal [style*="border-top: 1px solid #E2E8F0"] {
            flex-direction: column !important;
            gap: 10px !important;
            padding: 12px 14px !important;
        }
        #kepstaReviewModal [style*="border-top: 1px solid #E2E8F0"] > div {
            width: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 8px !important;
        }
        #kepstaReviewModal [style*="border-top: 1px solid #E2E8F0"] button {
            width: 100% !important;
            justify-content: center !important;
            padding: 11px !important;
        }
    }

    @media screen and (max-width: 576px) {
        .a4-paper-preview {
            padding: 12px 8px !important;
        }
        .kepsta-dispo-paper {
            padding: 6px 4px !important;
        }
        .dispo-inner-frame {
            padding: 8px 6px !important;
        }
        .dispo-meta-table {
            font-size: 11px !important;
        }
        .dispo-meta-table td {
            padding: 1px 0 !important;
        }
        .dispo-sifat-group {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 6px 12px !important;
            font-size: 11px !important;
        }

        /* Mobile Signature Modal Kepsta */
        #kepstaTtdModalPopup {
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
        #modalKepstaCanvas {
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
    }
</style>

<?php
    // 28 Recipient Items matching photo in 3 columns
    $recipientsCol1 = [
        'Kasubbag Tata Usaha',
        'KPP Program',
        'KPP Berita',
        'KPP PU',
        'KPP Teknik',
        'KPP Keuangan',
        'KPP Umum',
        'KPP Media Baru',
        'Kapel Produksi Program',
        'Kapel Promo'
    ];

    $recipientsCol2 = [
        'Kapel Monev Program',
        'Kapel Dokumentasi & Distribusi Audio Video',
        'Kapel CA & OR',
        'Kapel Berita',
        'Kapel Monev Berita',
        'Kapel Penjualan & Pemasaran',
        'Kapel IT',
        'Kapel Produksi & Penyiaran Teknik',
        'Kapel Transmisi',
        'Kapel Evaluasi & Pelaporan Keuangan'
    ];

    $recipientsCol3 = [
        'Kapel Pengelola SDM & RB',
        'Kapel Perlengkapan & Sarpras',
        'Kapel Pengelola BMN',
        'Kapel Humas & Protokol',
        'Kapel Konten Kreatif Digital',
        'Kapel Analitik & Distribusi Konten',
        'PPK I',
        'PPK II'
    ];

    // 15 Disposisi Items matching photo
    $disposisiItemsList = [
        'Diteliti / Dis',
        'Dipertimbangkan',
        'Untuk Diketahui / Diperhatikan',
        'Mewakili / Menghadiri / Mengikuti',
        'Dikoordinasikan',
        'Ditampung Permasalahannya',
        'Peringatkan / Pendekatan',
        'Pendapat / Analisa / Saran',
        'Konsep Jawaban / Sambutan',
        'Konsep Laporan',
        'Data Diolah',
        'Ditindaklanjuti',
        'Diagendakan / Dijadwalkan',
        'Harap Dibantu',
        'File'
    ];
    $isFromUploadLampiran = !empty($meta['from_upload_lampiran']);

    $pdfLampirans = [];
    if (!empty($meta['lampiran_pdf_paths'])) {
        if (is_array($meta['lampiran_pdf_paths'])) {
            $pdfLampirans = $meta['lampiran_pdf_paths'];
        } elseif (is_string($meta['lampiran_pdf_paths'])) {
            $pdfLampirans = json_decode($meta['lampiran_pdf_paths'], true) ?? [];
        }
    }
    if (empty($pdfLampirans) && !empty($meta['lampiran_pdf_path'])) {
        $pdfLampirans = [$meta['lampiran_pdf_path']];
    }
    $pdfLampirans = array_values(array_unique(array_filter($pdfLampirans)));
    $hasAdminLampiran = !empty($pdfLampirans);
    ?>

    <?php 
    $tuTtePdfPath = !empty($meta['tu_tte_pdf_path']) ? $meta['tu_tte_pdf_path'] : (!empty($meta['is_tu_tte_uploaded']) ? ($surat['pdf_path'] ?? '') : '');
    $hasTuTtePdf = !empty($tuTtePdfPath) && !empty($meta['is_tu_tte_uploaded']);
    ?>

    <?php if (!$isFromUploadLampiran): ?>
    <?php if ($hasTuTtePdf): ?>
    <!-- PREVIEW SURAT TTE PDF (DIUNGGAH OLEH TATA USAHA) - 1 BARIS 2 SURAT -->
    <div class="admin-review-grid-container" style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 20px; align-items: stretch; width: 100%; margin-bottom: 28px; <?= getPdfPageCount($tuTtePdfPath) === 1 ? 'justify-content: center;' : '' ?>">
        <?php 
        $pdfUrlTte = getImgUrl($tuTtePdfPath);
        $pageCountTte = getPdfPageCount($tuTtePdfPath);
        $mainDocPages = $isNotaDinas ? 1 : 2;
        $totalLampiranPages = max(0, $pageCountTte - $mainDocPages);

        for ($p = 1; $p <= $pageCountTte; $p++):
            $isMainPage = ($p <= $mainDocPages);
            $lampiranPageNum = $p - $mainDocPages;
            $subTitle = $totalLampiranPages <= 1 ? '1 Lembar' : "Halaman $lampiranPageNum dari $totalLampiranPages";
        ?>
            <div class="pdf-lampiran-wrapper-col" style="<?= ($pageCountTte === 1) ? 'max-width: 800px; width: 100%; flex: 0 0 800px;' : '' ?>">
                <?php if (!$isMainPage && $hasAdminLampiran): ?>
                <div style="padding: 10px 14px; background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: 10px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;" class="no-print">
                    <div style="display: flex; align-items: center; gap: 10px; min-width: 0; overflow: hidden;">
                        <i class="bi bi-file-earmark-pdf-fill" style="color: #DC2626; font-size: 22px; flex-shrink: 0;"></i>
                        <div style="min-width: 0; overflow: hidden;">
                            <div style="font-size: 13px; font-weight: 800; color: #991B1B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Lampiran PDF 1</div>
                            <div style="font-size: 11px; color: #7F1D1D;"><?= $subTitle ?></div>
                        </div>
                    </div>
                    <span style="font-size: 10.5px; font-weight: 800; background: #DC2626; color: #FFFFFF; padding: 3px 10px; border-radius: 20px; flex-shrink: 0;">LEMBAR <?= $lampiranPageNum ?></span>
                </div>
                <?php endif; ?>

                <div class="pdf-lampiran-card-preview" style="background: #FFFFFF; border: 1.5px solid #CBD5E1; padding: 0 !important; box-shadow: 0 4px 20px rgba(0,0,0,0.06); position: relative; aspect-ratio: 210 / 297; margin: 0 auto; box-sizing: border-box; border-radius: 8px; width: 100%; overflow: hidden;">
                    <canvas class="pdf-canvas-item" data-pdf-url="<?= $pdfUrlTte ?>" data-page-num="<?= $p ?>" style="width: 100%; height: auto; object-fit: contain; display: block; background: #FFFFFF; border: none; margin: 0; padding: 0;"></canvas>
                    <img class="pdf-img-item" style="width: 100%; height: auto; object-fit: contain; display: none; margin: 0 auto; border: none; padding: 0;">
                </div>
            </div>
        <?php endfor; ?>
    </div>
    <?php elseif ($isNotaDinas): ?>
    <!-- SINGLE PAGE NOTA DINAS PREVIEW FOR ADMIN-CREATED NOTA DINAS -->
    <div style="display: flex; justify-content: center; margin-bottom: 28px; width: 100%;">
        <div class="card-panel-variant a4-paper-preview" style="width: 100%; max-width: 800px; padding: 2.5cm; border-radius: 18px; background: #FFFFFF; border: 1.5px solid #CBD5E1; box-shadow: 0 4px 20px rgba(0,0,0,0.06); font-family: Arial, sans-serif; color: #000000; position: relative; min-height: 270mm; margin: 0 auto; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <!-- Logo Top Left -->
                <div style="margin-bottom: 18px;">
                    <img src="<?= BASE_URL ?>/assets/img/tvri3.svg" style="height: 58px; width: auto;" alt="TVRI Logo">
                </div>

                <!-- Title Centered -->
                <div style="text-align: center; margin-bottom: 18px;">
                    <h2 style="font-size: 14.5px; font-weight: normal; letter-spacing: 0.5px; margin: 0; color: #000000; text-transform: uppercase; text-decoration: underline; font-family: Arial, sans-serif;">NOTA DINAS</h2>
                    <div style="font-size: 13px; color: #111111; margin-top: 4px; font-family: Arial, sans-serif;">
                        Nomor : <?= htmlspecialchars($nomorSuratDisplay ?: '          /UM.03.02/I1.1.1/IX/2026') ?>
                    </div>
                </div>

                <!-- Metadata Table -->
                <table style="width: 100%; border-collapse: collapse; font-size: 13px; line-height: 1.15; margin-bottom: 10px; color: #000000;">
                    <tr>
                        <td style="width: 100px; vertical-align: top; padding: 2px 0;">Kepada Yth</td>
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
                        <td style="vertical-align: top; padding: 2px 0; font-weight: 500;"><?= htmlspecialchars($surat['perihal']) ?></td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">Sifat</td>
                        <td style="vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($sifatNotaDinas) ?></td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">Lampiran</td>
                        <td style="vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($surat['lampiran'] ?? '1 (lembar)') ?></td>
                    </tr>
                    <tr>
                        <td style="width: 100px; vertical-align: top; padding: 2px 0;">Tanggal</td>
                        <td style="width: 12px; vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;"><?= $tglIndo ?></td>
                    </tr>
                </table>

                <div style="border-top: 1.5px solid #000000; margin: 10px 0 16px 0;"></div>

                <?php if (!empty($meta['pemohon_nama'])): ?>
                <!-- Table Data Diri Pemohon -->
                <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; line-height: 1.15; margin-bottom: 12px; color: #000000;">
                    <tr>
                        <td colspan="3" style="padding: 2px 0; font-weight: normal;">Saya yang bertanda tangan dibawah ini :</td>
                    </tr>
                    <tr>
                        <td style="width: 120px; vertical-align: top; padding: 1.5px 0; padding-left: 12px;">Nama</td>
                        <td style="width: 12px; vertical-align: top; padding: 1.5px 0;">:</td>
                        <td style="vertical-align: top; padding: 1.5px 0; font-weight: 500;"><?= htmlspecialchars($meta['pemohon_nama']) ?></td>
                    </tr>
                    <?php if (!empty($meta['pemohon_nopeg']) || !empty($meta['pemohon_nip'])): ?>
                    <tr>
                        <td style="vertical-align: top; padding: 1.5px 0; padding-left: 12px;">NOPEG / NIP</td>
                        <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                        <td style="vertical-align: top; padding: 1.5px 0;"><?= htmlspecialchars($meta['pemohon_nopeg'] ?? $meta['pemohon_nip'] ?? '-') ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($meta['pemohon_jabatan'])): ?>
                    <tr>
                        <td style="vertical-align: top; padding: 1.5px 0; padding-left: 12px;">Jabatan</td>
                        <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                        <td style="vertical-align: top; padding: 1.5px 0;"><?= htmlspecialchars($meta['pemohon_jabatan']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($meta['pemohon_hp'])): ?>
                    <tr>
                        <td style="vertical-align: top; padding: 1.5px 0; padding-left: 12px;">Nomor HP.</td>
                        <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                        <td style="vertical-align: top; padding: 1.5px 0;"><?= htmlspecialchars($meta['pemohon_hp']) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php endif; ?>

                <div style="font-size: 13px; color: #000000; margin-bottom: 8px; line-height: 1.15;">Dengan hormat,</div>
                <div style="line-height: 1.15; text-align: justify; font-size: 13px; font-family: Arial, sans-serif; color: #000000; margin-bottom: 24px; white-space: pre-line;"><?= htmlspecialchars($meta['isi_surat_dinas'] ?? $surat['isi_surat']) ?></div>

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

                <!-- Signature Block (2 columns: Left for TU TTD + Paraf KPP, Right for Pemohon) -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 14px; margin-bottom: 14px;">
                    <!-- Left Column: Kepala Sub Bagian Tata Usaha -->
                    <div style="width: 250px; text-align: left; font-size: 12px; line-height: 1.15; color: #000000;">
                        <div>Kepala Sub Bagian Tata Usaha</div>
                        <div>TVRI Stasiun Jawa Timur</div>
                        <div style="height: 60px; margin: 3px 0; display: flex; align-items: center; justify-content: flex-start;">
                            <?php if (!empty($tuTtd)): ?>
                                <img src="<?= getImgUrl($tuTtd) ?>" style="max-height: 55px; width: auto; object-fit: contain;" alt="TTD TU">
                            <?php else: ?>
                                <span style="font-size: 11px; color: #666; font-style: italic; border: 1px dashed #ccc; padding: 4px 8px; border-radius: 4px;">(Belum ditandatangani Kasubag TU)</span>
                            <?php endif; ?>
                        </div>

                        <!-- Name & NIP block with Paraf KPP Bidang displaying overlay to the left -->
                        <div style="position: relative; display: inline-block;">
                            <?php if (!empty($kppParaf)): ?>
                                <div style="position: absolute; right: 100%; top: 50%; transform: translateY(-50%); margin-right: 8px; display: flex; align-items: center; justify-content: center;">
                                    <img src="<?= getImgUrl($kppParaf) ?>" style="max-height: 48px; width: auto; max-width: 70px; object-fit: contain;" alt="Paraf KPP">
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
                            <?php if (!empty($pemohonTtd)): ?>
                                <img src="<?= getImgUrl($pemohonTtd) ?>" style="max-height: 45px; object-fit: contain;" alt="TTD Pemohon">
                            <?php else: ?>
                                <span style="font-size: 11px; color: #666; font-style: italic; border: 1px dashed #ccc; padding: 4px 8px; border-radius: 4px;">(TTD Pemohon)</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-weight: 500; text-decoration: underline;"><?= htmlspecialchars($meta['pemohon_nama'] ?? '') ?></div>
                        <?php if (!empty($meta['pemohon_nopeg']) || !empty($meta['pemohon_nip'])): ?>
                            <div><?= htmlspecialchars($meta['pemohon_nopeg'] ?? $meta['pemohon_nip']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
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
                            <div style="height: 48px; margin: 3px 0; display: flex; align-items: center; justify-content: center;">
                                <?php if (!empty($kppTtd)): ?>
                                    <img src="<?= getImgUrl($kppTtd) ?>" style="max-height: 44px; width: auto; object-fit: contain;" alt="TTD KPP Bidang">
                                <?php else: ?>
                                    <span style="font-size: 9.5px; color: #666666; font-style: italic; border: 1px dashed #999999; padding: 3px 8px; border-radius: 4px;">(TTD KPP Bidang)</span>
                                <?php endif; ?>
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
                            Nomor : <?= htmlspecialchars($nomorSuratDisplay ?: '          /UM.03.02/I1.1.1/IX/2026') ?>
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
                            <td style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars(!empty($meta['sifat_surat']) ? $meta['sifat_surat'] : 'Biasa') ?></td>
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
                                    <img src="<?= getImgUrl($tuTtd) ?>" style="max-height: 44px; object-fit: contain;" alt="TTD TU">
                                <?php else: ?>
                                    <span style="font-size: 9.5px; color: #666666; font-style: italic; border: 1px dashed #999999; padding: 3px 8px; border-radius: 4px;">(TTD Kepala TU)</span>
                                <?php endif; ?>
                            </div>

                            <!-- Name & NIP block with Paraf KPP Bidang displaying statically to the left -->
                            <div style="position: relative; display: inline-block;">
                                <?php if (!empty($kppParaf)): ?>
                                    <div style="position: absolute; right: 100%; top: 50%; transform: translateY(-50%); margin-right: 8px; white-space: nowrap; display: flex; align-items: center; justify-content: center; z-index: 10;">
                                        <img src="<?= getImgUrl($kppParaf) ?>" style="max-height: 48px; width: auto; max-width: 70px; object-fit: contain;" alt="Paraf KPP">
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

                    $qrUrlKepsta = !empty($adminBarcodeLink) ? $adminBarcodeLink : 'https://drive.google.com';
                    $qrSrcKepsta = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrUrlKepsta);
                    ?>
                    <div style="display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 6px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <a href="<?= htmlspecialchars($qrUrlKepsta) ?>" target="_blank" title="Scan / Klik untuk membuka URL Google Drive">
                                <img src="<?= $qrSrcKepsta ?>" style="width: 48px; height: 48px; border: 1.5px solid #000000; background: #FFFFFF; padding: 2px;" alt="Barcode QR Google Drive">
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
    <?php endif; // !$isFromUploadLampiran ?>

    <!-- DOKUMEN PDF LAMPIRAN TER-UPLOAD -->
    <?php 
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
        '<div class="a4-paper-preview" style="background: #FFFFFF; border: none; padding: 3cm; font-family: Arial, sans-serif; color: #000000; position: relative; min-height: 270mm; margin: 0 auto; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between;">' .
            '<div>' .
                '<div style="margin-bottom: 20px;"><img src="' . BASE_URL . '/assets/img/tvri3.svg" style="height: 58px; width: auto;" alt="TVRI Logo"></div>' .
                '<div style="font-size: 13px; line-height: 1.15; margin-bottom: 24px; color: #000000; border-bottom: 1.5px solid #000; padding-bottom: 12px;">' .
                    '<h2 style="font-size: 15px; font-weight: bold; margin: 0 0 6px 0; text-transform: uppercase;">DAFTAR LAMPIRAN BERKAS PENDUKUNG</h2>' .
                    '<div style="font-size: 12px; color: #333;">Dokumen Lampiran Resmi TVRI Stasiun Jawa Timur</div>' .
                '</div>' .
                '<p style="font-size: 13px; line-height: 1.4; margin-bottom: 16px;">Berikut adalah daftar berkas PDF pendukung yang diunggah dan dilampirkan bersama surat ini:</p>' .
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

    <?php if (!empty($pdfLampirans) && empty($hasTuTtePdf)): ?>
        <div style="width: 100%; margin-top: 24px; margin-bottom: 32px; padding-top: 24px; border-top: 2px dashed #CBD5E1;">
            <div style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 20px; align-items: stretch; width: 100%;">
                <?php 
                $totalCardCount = 0;
                foreach ($pdfLampirans as $idx => $pdfPath): 
                    $pageCount = getPdfPageCount($pdfPath);
                    $cleanPdfPath = '/' . ltrim($pdfPath, '/');
                    $pdfUrl = (str_starts_with($pdfPath, 'http://') || str_starts_with($pdfPath, 'https://')) ? $pdfPath : (BASE_URL . $cleanPdfPath);
                    for ($p = 1; $p <= $pageCount; $p++):
                        $totalCardCount++;
                        $subTitle = $pageCount === 1 ? '1 Lembar' : "Halaman $p dari $pageCount";
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
                            <canvas class="pdf-canvas-item" data-pdf-url="<?= htmlspecialchars($pdfUrl) ?>" data-page-num="<?= $p ?>" style="width: 100%; height: auto; object-fit: contain; display: block; background: #FFFFFF; border: none; margin: 0; padding: 0;"></canvas>
                            <img class="pdf-img-item" style="width: 100%; height: auto; object-fit: contain; display: none; margin: 0 auto; border: none; padding: 0;">
                        </div>
                    </div>
                <?php endfor; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- FORM UTAMA KEPSTA: LEMBAR DISPOSISI 1:1 SAMA PERSIS FOTO -->
    <form action="<?= BASE_URL ?>/surat/kepsta-submit/<?= $surat['id'] ?>" method="POST" id="kepstaForm" onsubmit="return validateKepstaFormSubmit(event)" autocomplete="off">
        <?= Session::csrfField() ?>

        <div class="a4-paper-preview kepsta-dispo-paper" style="background: #FFFFFF; border: 1px solid #CBD5E1; padding: 1cm 1.2cm; box-shadow: 0 10px 30px rgba(0,0,0,0.08); font-family: Arial, sans-serif; color: #000000; max-width: 210mm; min-height: 297mm; margin: 0 auto 28px auto; box-sizing: border-box; border-radius: 12px;">
            <!-- INNER RECTANGULAR BORDER FRAME (GARIS KOTAK SISI SURAT, BUKAN DI UJUNG KERTAS) -->
            <div class="dispo-inner-frame" style="border: 1.5px solid #000000; padding: 14px 18px; box-sizing: border-box; background: #FFFFFF; width: 100%; min-height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <!-- Logo Top Left -->
                <div style="margin-bottom: 8px; display: flex; align-items: flex-start; justify-content: space-between;">
                    <img src="<?= BASE_URL ?>/assets/img/tvri3.svg" style="height: 42px; width: auto;" alt="TVRI Logo">
                </div>

                <!-- Header Title Disposisi -->
                <div class="dispo-header-title" style="text-align: center; margin-bottom: 12px; color: #000000; font-family: Arial, sans-serif;">
                    <div style="font-size: 13px; font-weight: 700; letter-spacing: 0.2px;">DISPOSISI</div>
                    <div style="font-size: 13px; font-weight: 700; letter-spacing: 0.2px; margin-top: 1px;">KEPALA TVRI STASIUN JAWA TIMUR</div>
                    <div style="font-size: 13px; font-weight: 700; letter-spacing: 0.2px; margin-top: 1px;">LPP TELEVISI REPUBLIK INDONESIA</div>
                </div>

                <!-- Top Metadata Section (Filled in by Sekre & TU) -->
                <div style="padding: 2px 0; margin-bottom: 10px; background: transparent;">
                    <table class="dispo-meta-table" style="width: 100%; border-collapse: collapse; font-size: 12px; line-height: 1.4; color: #000000; font-family: Arial, sans-serif;">
                        <tr>
                            <td style="width: 105px; vertical-align: top; padding: 1.5px 0;">Nomor Agenda</td>
                            <td style="width: 12px; vertical-align: top; padding: 1.5px 0;">:</td>
                            <td colspan="4" style="vertical-align: top; padding: 1.5px 0; font-weight: normal;">
                                <?= htmlspecialchars($meta['nomor_agenda'] ?? '') ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 1.5px 0;">Tanggal</td>
                            <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                            <td colspan="4" style="vertical-align: top; padding: 1.5px 0;">
                                <?= htmlspecialchars($meta['tanggal_agenda'] ?? date('d F Y', strtotime($surat['tanggal_surat']))) ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 1.5px 0;">Dari</td>
                            <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                            <td style="vertical-align: top; padding: 1.5px 0; width: 38%;">
                                <?= htmlspecialchars($meta['dari'] ?? 'Kepala Sub Bagian Tata Usaha') ?>
                            </td>
                            
                            <td style="width: 95px; vertical-align: top; padding: 1.5px 0;">Nomor Surat</td>
                            <td style="width: 12px; vertical-align: top; padding: 1.5px 0;">:</td>
                            <td style="vertical-align: top; padding: 1.5px 0; font-weight: normal;">
                                <?= htmlspecialchars($nomorSuratDisplay) ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 1.5px 0;">Hal</td>
                            <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                            <td colspan="4" style="vertical-align: top; padding: 1.5px 0; font-weight: normal;">
                                <?= htmlspecialchars($meta['hal_disposisi'] ?? $meta['perihal_disposisi'] ?? $disposisi['hal'] ?? '') ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 2px 0;">Sifat</td>
                            <td style="vertical-align: top; padding: 2px 0;">:</td>
                            <td colspan="4" style="vertical-align: top; padding: 2px 0;">
                                <?php 
                                $svgCheckDispo = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block; position: absolute; top: -5px; left: -2px; pointer-events: none;"><path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/></svg>'; 
                                $rawSifat = (!empty($disposisi['kepsta_signed_at']) && !empty($disposisi['sifat_surat'])) ? $disposisi['sifat_surat'] : (!empty($disposisi['sifat_surat']) && !empty($surat['status']) && $surat['status'] === 'completed' ? $disposisi['sifat_surat'] : '');
                                $sifatSelected = strtolower(trim($rawSifat));
                                ?>
                                <div class="dispo-sifat-group" style="display: flex; gap: 24px; align-items: center; font-size: 12px; font-family: Arial, sans-serif;">
                                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                        <input type="radio" name="sifat_surat" value="biasa" <?= ($sifatSelected === 'biasa') ? 'checked data-checked="true"' : 'data-checked="false"' ?> style="display: none;" onclick="toggleSifatRadio(this)" onchange="if(this.checked){ document.querySelectorAll('input[name=\'sifat_surat\']').forEach(r => r.dataset.checked = 'false'); this.dataset.checked = 'true'; updateSifatCheckmarks(this.value); }">
                                        Biasa <span id="spanChkBiasa" style="display: inline-flex; align-items: center; justify-content: center; width: 13px; height: 13px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatSelected === 'biasa') ? $svgCheckDispo : '' ?></span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                        <input type="radio" name="sifat_surat" value="penting" <?= ($sifatSelected === 'penting') ? 'checked data-checked="true"' : 'data-checked="false"' ?> style="display: none;" onclick="toggleSifatRadio(this)" onchange="if(this.checked){ document.querySelectorAll('input[name=\'sifat_surat\']').forEach(r => r.dataset.checked = 'false'); this.dataset.checked = 'true'; updateSifatCheckmarks(this.value); }">
                                        Penting <span id="spanChkPenting" style="display: inline-flex; align-items: center; justify-content: center; width: 13px; height: 13px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatSelected === 'penting') ? $svgCheckDispo : '' ?></span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                        <input type="radio" name="sifat_surat" value="segera" <?= ($sifatSelected === 'segera') ? 'checked data-checked="true"' : 'data-checked="false"' ?> style="display: none;" onclick="toggleSifatRadio(this)" onchange="if(this.checked){ document.querySelectorAll('input[name=\'sifat_surat\']').forEach(r => r.dataset.checked = 'false'); this.dataset.checked = 'true'; updateSifatCheckmarks(this.value); }">
                                        Segera <span id="spanChkSegera" style="display: inline-flex; align-items: center; justify-content: center; width: 13px; height: 13px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatSelected === 'segera') ? $svgCheckDispo : '' ?></span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                        <input type="radio" name="sifat_surat" value="rahasia" <?= ($sifatSelected === 'rahasia') ? 'checked data-checked="true"' : 'data-checked="false"' ?> style="display: none;" onclick="toggleSifatRadio(this)" onchange="if(this.checked){ document.querySelectorAll('input[name=\'sifat_surat\']').forEach(r => r.dataset.checked = 'false'); this.dataset.checked = 'true'; updateSifatCheckmarks(this.value); }">
                                        Rahasia <span id="spanChkRahasia" style="display: inline-flex; align-items: center; justify-content: center; width: 13px; height: 13px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatSelected === 'rahasia') ? $svgCheckDispo : '' ?></span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- 3-Column Recipient Checklist Table (28 items with dedicated square grid cells & custom SVG checkmark) -->
                <div class="dispo-recipients-wrap" style="margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px; flex-wrap: wrap; gap: 4px;">
                        <div style="font-size: 11px; font-weight: bold; color: #0f172a;">
                            Pilih / Centang Penerima Disposisi:
                        </div>
                        <div class="dispo-mobile-scroll-hint" style="display: none; font-size: 10px; color: #64748B; font-style: italic;">
                            <i class="bi bi-arrows-expand"></i> Geser ke samping untuk melihat seluruh daftar
                        </div>
                    </div>
                    <table style="width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 10.5px; color: #000000; background: #FFFFFF;" border="1">
                        <colgroup>
                            <col style="width: 24px;">
                            <col style="width: 30%;">
                            <col style="width: 24px;">
                            <col style="width: 30%;">
                            <col style="width: 24px;">
                            <col style="width: 30%;">
                        </colgroup>
                        <?php 
                        $maxRows = 10; 
                        for ($i = 0; $i < $maxRows; $i++): 
                            $item1 = $recipientsCol1[$i] ?? null;
                            $item2 = $recipientsCol2[$i] ?? null;
                            $item3 = $recipientsCol3[$i] ?? null;
                        ?>
                            <tr style="height: 20px;">
                                <!-- Column 1 Pair -->
                                <?php 
                                $isChk1 = $item1 && in_array($item1, $dispoChecklist);
                                $val1 = htmlspecialchars($item1 ?? '');
                                ?>
                                <td class="dispo-chk-cell" onclick="toggleDispoCell(this)" style="width: 24px; min-width: 24px; max-width: 24px; height: 20px; text-align: center; vertical-align: middle; padding: 0; cursor: pointer; user-select: none;">
                                    <?php if ($item1): ?>
                                        <input type="checkbox" name="checklist[]" value="<?= $val1 ?>" <?= $isChk1 ? 'checked' : '' ?> style="display: none;">
                                        <span class="chk-svg-wrap" style="visibility: <?= $isChk1 ? 'visible' : 'hidden' ?>; display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px;">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block;">
                                                <path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td onclick="toggleDispoCell(this)" style="padding: 1px 4px; width: 30%; vertical-align: middle; cursor: pointer; user-select: none;">
                                    <?php if ($item1): ?>
                                        <span style="font-weight: normal;"><?= $val1 ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- Column 2 Pair -->
                                <?php 
                                $isChk2 = $item2 && in_array($item2, $dispoChecklist);
                                $val2 = htmlspecialchars($item2 ?? '');
                                ?>
                                <td class="dispo-chk-cell" onclick="toggleDispoCell(this)" style="width: 24px; min-width: 24px; max-width: 24px; height: 20px; text-align: center; vertical-align: middle; padding: 0; cursor: pointer; user-select: none;">
                                    <?php if ($item2): ?>
                                        <input type="checkbox" name="checklist[]" value="<?= $val2 ?>" <?= $isChk2 ? 'checked' : '' ?> style="display: none;">
                                        <span class="chk-svg-wrap" style="visibility: <?= $isChk2 ? 'visible' : 'hidden' ?>; display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px;">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block;">
                                                <path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td onclick="toggleDispoCell(this)" style="padding: 1px 4px; width: 30%; vertical-align: middle; cursor: pointer; user-select: none;">
                                    <?php if ($item2): ?>
                                        <span style="font-weight: normal;"><?= $val2 ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- Column 3 Pair -->
                                <?php 
                                $isChk3 = $item3 && in_array($item3, $dispoChecklist);
                                $val3 = htmlspecialchars($item3 ?? '');
                                ?>
                                <td class="dispo-chk-cell" onclick="toggleDispoCell(this)" style="width: 24px; min-width: 24px; max-width: 24px; height: 20px; text-align: center; vertical-align: middle; padding: 0; cursor: pointer; user-select: none;">
                                    <?php if ($item3): ?>
                                        <input type="checkbox" name="checklist[]" value="<?= $val3 ?>" <?= $isChk3 ? 'checked' : '' ?> style="display: none;">
                                        <span class="chk-svg-wrap" style="visibility: <?= $isChk3 ? 'visible' : 'hidden' ?>; display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px;">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block;">
                                                <path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td onclick="toggleDispoCell(this)" style="padding: 1px 4px; width: 30%; vertical-align: middle; cursor: pointer; user-select: none;">
                                    <?php if ($item3): ?>
                                        <span style="font-weight: normal;"><?= $val3 ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </table>
                </div>

                <!-- Section Disposisi & Catatan -->
                <div class="dispo-mid-section" style="display: flex; gap: 16px; width: 100%; margin-bottom: 10px; align-items: stretch;">
                    <!-- Left: Disposisi -->
                    <div class="dispo-col-left" style="width: 44%; display: flex; flex-direction: column;">
                        <div style="font-size: 11px; font-weight: normal; margin-bottom: 4px;">Disposisi :</div>
                        <table style="width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 10.5px; color: #000000; background: #FFFFFF;" border="1">
                            <colgroup>
                                <col style="width: 24px;">
                                <col>
                            </colgroup>
                            <?php foreach ($disposisiItemsList as $dItem): ?>
                                <?php 
                                $isChkD = in_array($dItem, $dispoChecklist);
                                $valD = htmlspecialchars($dItem);
                                ?>
                                <tr style="height: 19px;">
                                    <td class="dispo-chk-cell" onclick="toggleDispoCell(this)" style="width: 24px; min-width: 24px; max-width: 24px; height: 19px; text-align: center; vertical-align: middle; padding: 0; cursor: pointer; user-select: none;">
                                        <input type="checkbox" name="checklist[]" value="<?= $valD ?>" <?= $isChkD ? 'checked' : '' ?> style="display: none;">
                                        <span class="chk-svg-wrap" style="visibility: <?= $isChkD ? 'visible' : 'hidden' ?>; display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px;">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block;">
                                                <path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                    </td>
                                    <td onclick="toggleDispoCell(this)" style="padding: 1px 4px; vertical-align: middle; cursor: pointer; user-select: none;">
                                        <span style="font-weight: normal;"><?= $valD ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                    <!-- Right: Catatan -->
                    <div class="dispo-col-right" style="flex: 1; font-size: 10.5px; color: #000000; display: flex; flex-direction: column;">
                        <div class="dispo-catatan-label" style="font-size: 11px; font-weight: normal; display: block; margin-bottom: 4px; margin-left: 50px;">Catatan :</div>
                        <textarea name="catatan_kepsta" rows="15" placeholder="Ketikkan catatan atau instruksi khusus Kepala Stasiun di sini..."
                                  style="width: 100%; flex: 1; min-height: 240px; border: 1.5px solid #003399; padding: 8px; font-family: Arial, sans-serif; font-size: 11.5px; line-height: 1.4; border-radius: 6px; box-sizing: border-box; outline: none; background: #F8FAFC; resize: vertical; color: #003399; font-weight: normal;"><?= htmlspecialchars($disposisi['catatan_kepsta'] ?? '') ?></textarea>
                    </div>
                </div>
            </div><!-- END Top content section -->

            <!-- Bottom Signature Section -->
            <div>
                <div class="dispo-bottom-section" style="display: flex; justify-content: space-between; align-items: flex-start; font-size: 11.5px; color: #000000; margin-top: 6px; gap: 16px;">
                    <!-- Sekretariat Input Textarea -->
                    <div class="dispo-sekre-box" style="width: 44%; vertical-align: top;">
                        <div style="font-size: 11px; color: #000000; font-weight: normal; display: block; margin-bottom: 4px;">Sekretariat :</div>
                        <textarea name="catatan_sekretariat" rows="4" placeholder="Ketik catatan / keterangan sekretariat di sini..."
                                  style="width: 100%; min-height: 85px; height: 85px; border: 1.5px solid #003399; padding: 6px; font-family: Arial, sans-serif; font-size: 11.5px; line-height: 1.4; border-radius: 6px; box-sizing: border-box; outline: none; background: #F8FAFC; resize: vertical; color: #003399; font-weight: normal;"><?= htmlspecialchars(!empty($disposisi['catatan_sekre']) ? $disposisi['catatan_sekre'] : (!empty($meta['catatan_sekretariat']) ? $meta['catatan_sekretariat'] : '')) ?></textarea>
                    </div>

                    <!-- Right Side: TTD Kepala Stasiun -->
                    <div class="dispo-kepsta-box" style="width: 250px; text-align: left; line-height: 1.35; font-size: 11.5px;">
                        <div>Surabaya, <?= date('d F Y') ?></div>
                        <div>Kepala TVRI Stasiun Jawa Timur</div>

                        <!-- Clickable TTD Area for Kepsta -->
                        <div id="pvKepstaTtdBox" onclick="openKepstaSignatureModal()" title="Klik di sini untuk membuat / menggambar TTD Digital Kepala Stasiun"
                             style="height: 50px; width: 180px; max-width: 100%; margin: 4px 0 6px 0; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 1.5px dashed #3B82F6; border-radius: 6px; background: #F8FAFC; transition: all 0.2s;"
                             onmouseover="this.style.background='#EFF6FF'; this.style.borderColor='#1D4ED8';"
                             onmouseout="this.style.background='#F8FAFC'; this.style.borderColor='#3B82F6';">
                            <img id="pvKepstaTtdImg" src="<?= !empty($kepstaTtd) ? getImgUrl($kepstaTtd) : '' ?>" style="max-height: 46px; object-fit: contain; <?= empty($kepstaTtd) ? 'display: none;' : '' ?>">
                            <span id="pvKepstaTtdPlaceholder" style="font-size: 10.5px; color: #2563EB; font-weight: 700; <?= !empty($kepstaTtd) ? 'display: none;' : '' ?>">
                                <i class="bi bi-pencil-square"></i> Klik untuk isi TTD
                            </span>
                        </div>

                        <div style="text-decoration: underline; font-weight: normal;"><?= htmlspecialchars($kepstaNamaDisplay) ?></div>
                        <div><?= htmlspecialchars($kepstaNipDisplay) ?></div>
                    </div>
                </div>
            </div><!-- END Bottom signature section -->
            </div>
            <input type="hidden" name="kepsta_signature_data" id="kepstaSignatureData" value="<?= htmlspecialchars($kepstaTtd) ?>">
        </div>

        <!-- FORM ACTION BUTTONS -->
        <div class="kepsta-bottom-actions" style="display: flex; justify-content: flex-end; align-items: center; flex-wrap: wrap; gap: 12px; max-width: 210mm; margin: 0 auto; padding-top: 10px;">
            <a href="<?= BASE_URL ?>/dashboard" class="btn-btn btn-btn-secondary btn-action-batal">
                Batal
            </a>

            <button type="button" onclick="handleKepstaReviewClick()" class="btn-btn btn-btn-primary btn-action-teruskan" style="padding: 12px 28px; font-size: 15px; font-weight: 700; background-color: #2563EB; border-color: #2563EB;">
                <i class="bi bi-send-fill"></i> Teruskan Disposisi
            </button>
        </div></form>
</div>

<!-- ========================================== -->
<!-- MODAL POPUP PENGERJAAN TTD KEPALA STASIUN -->
<!-- ========================================== -->
<div id="kepstaTtdModalPopup" onclick="if(event.target === this) closeKepstaSignatureModal()" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px;">
    <div class="sig-modal-dialog">
        <div class="sig-modal-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="sig-header-icon">
                    <i class="bi bi-pen-fill"></i>
                </div>
                <div>
                    <h3 class="sig-header-title">Input Tanda Tangan Digital Kepala Stasiun</h3>
                    <div class="sig-header-subtitle">Bubuhkan tanda tangan digital</div>
                </div>
            </div>
        </div>

        <div class="sig-modal-body">
            <p class="sig-instructions-text">
                Gunakan sentuhan jari atau mouse di bawah untuk membubuhkan TTD Kepala Stasiun:
            </p>

            <div class="sig-canvas-wrapper">
                <canvas id="modalKepstaCanvas"></canvas>
                <div class="sig-canvas-watermark">
                    <i class="bi bi-pencil"></i> Sentuh atau goreskan tanda tangan di sini
                </div>
            </div>

            <div class="sig-modal-footer">
                <div class="sig-footer-subgroup">
                    <button type="button" onclick="clearModalKepstaCanvas()" class="btn-btn btn-sig-clear">
                        <i class="bi bi-eraser-fill"></i> Hapus
                    </button>
                    <button type="button" onclick="closeKepstaSignatureModal()" class="btn-btn btn-btn-secondary btn-sig-cancel">
                        Batal
                    </button>
                </div>

                <div class="sig-footer-main">
                    <button type="button" onclick="saveKepstaSignatureFromModal()" class="btn-btn btn-btn-primary btn-sig-save">
                        <i class="bi bi-check-lg"></i> Simpan & Tempel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: REVIEW DISPOSISI KEPALA STASIUN -->
<!-- ========================================== -->
<div id="kepstaReviewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px; overflow-y: auto;">
    <div style="background: #FFFFFF; width: 100%; max-width: 860px; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); overflow: hidden; display: flex; flex-direction: column; max-height: 94vh;">
        <!-- Modal Top Bar -->
        <div style="padding: 14px 24px; background: #EFF6FF; border-bottom: 1px solid #BFDBFE; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-eye-fill" style="color: #1D4ED8; font-size: 20px;"></i>
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #1E3A8A;">Review Lembar Disposisi Kepala Stasiun</h3>
            </div>
        </div>

        <!-- Document Canvas Container -->
        <div style="padding: 32px; overflow-y: auto; background: #F8FAFC; flex: 1;">
            <div class="a4-paper-preview kepsta-dispo-paper" style="background: #FFFFFF; border: 1px solid #CBD5E1; padding: 1cm 1.2cm; box-shadow: 0 4px 20px rgba(0,0,0,0.08); font-family: Arial, sans-serif; color: #000000; position: relative; min-height: 297mm; max-width: 210mm; margin: 0 auto; box-sizing: border-box;">
                <!-- INNER RECTANGULAR BORDER FRAME (GARIS KOTAK SISI SURAT, BUKAN DI UJUNG KERTAS) -->
                <div class="dispo-inner-frame" style="border: 1.5px solid #000000; padding: 14px 18px; box-sizing: border-box; background: #FFFFFF; min-height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <!-- Logo Top Left -->
                    <div style="margin-bottom: 8px; display: flex; align-items: flex-start; justify-content: space-between;">
                        <img src="<?= BASE_URL ?>/assets/img/tvri3.svg" style="height: 42px; width: auto;" alt="TVRI Logo">
                    </div>

                    <!-- Header Title Disposisi -->
                    <div class="dispo-header-title" style="text-align: center; margin-bottom: 12px; color: #000000;">
                        <div style="font-size: 13px; font-weight: bold; letter-spacing: normal;">DISPOSISI</div>
                        <div style="font-size: 13px; font-weight: bold; letter-spacing: normal; margin-top: 1px;">KEPALA TVRI STASIUN JAWA TIMUR</div>
                        <div style="font-size: 13px; font-weight: bold; letter-spacing: normal; margin-top: 1px;">LPP TELEVISI REPUBLIK INDONESIA</div>
                    </div>

                    <!-- Top Metadata Section -->
                    <div style="padding: 2px 0; margin-bottom: 10px; background: transparent;">
                        <table class="dispo-meta-table" style="width: 100%; border-collapse: collapse; font-size: 12px; line-height: 1.4; color: #000000;">
                            <tr>
                                <td style="width: 105px; vertical-align: top; padding: 1.5px 0;">Nomor Agenda</td>
                                <td style="width: 12px; vertical-align: top; padding: 1.5px 0;">:</td>
                                <td style="width: 220px; vertical-align: top; padding: 1.5px 0; font-weight: normal;"><?= htmlspecialchars($meta['nomor_agenda'] ?? '') ?></td>
                                
                                <td style="width: 95px; vertical-align: top; padding: 1.5px 0;">Nomor Surat</td>
                                <td style="width: 12px; vertical-align: top; padding: 1.5px 0;">:</td>
                                <td style="vertical-align: top; padding: 1.5px 0; font-weight: normal;"><?= htmlspecialchars($nomorSuratDisplay) ?></td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 1.5px 0;">Tanggal</td>
                                <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                                <td colspan="4" style="vertical-align: top; padding: 1.5px 0;"><?= htmlspecialchars($meta['tanggal_agenda'] ?? date('d F Y', strtotime($surat['tanggal_surat']))) ?></td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 1.5px 0;">Dari</td>
                                <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                                <td colspan="4" style="vertical-align: top; padding: 1.5px 0;"><?= htmlspecialchars($meta['dari'] ?? 'Kepala Sub Bagian Tata Usaha') ?></td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 1.5px 0;">Hal</td>
                                <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                                <td colspan="4" style="vertical-align: top; padding: 1.5px 0; font-weight: normal;"><?= htmlspecialchars($meta['hal_disposisi'] ?? $meta['perihal_disposisi'] ?? $disposisi['hal'] ?? $surat['perihal'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 2px 0;">Sifat</td>
                                <td style="vertical-align: top; padding: 2px 0;">:</td>
                                <td colspan="4" style="vertical-align: top; padding: 2px 0;">
                                     <div class="dispo-sifat-group" style="display: flex; gap: 20px; align-items: center; font-size: 12px;">
                                         <label style="display: flex; align-items: center; gap: 6px;">Biasa <span id="revChkBiasa" style="display: inline-flex; align-items: center; justify-content: center; width: 13px; height: 13px; border: 1.5px solid #000; position: relative; overflow: visible;"></span></label>
                                         <label style="display: flex; align-items: center; gap: 6px;">Penting <span id="revChkPenting" style="display: inline-flex; align-items: center; justify-content: center; width: 13px; height: 13px; border: 1.5px solid #000; position: relative; overflow: visible;"></span></label>
                                         <label style="display: flex; align-items: center; gap: 6px;">Segera <span id="revChkSegera" style="display: inline-flex; align-items: center; justify-content: center; width: 13px; height: 13px; border: 1.5px solid #000; position: relative; overflow: visible;"></span></label>
                                         <label style="display: flex; align-items: center; gap: 6px;">Rahasia <span id="revChkRahasia" style="display: inline-flex; align-items: center; justify-content: center; width: 13px; height: 13px; border: 1.5px solid #000; position: relative; overflow: visible;"></span></label>
                                     </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- 3-Column Recipient Checklist Table (28 items) -->
                    <div class="dispo-recipients-wrap" style="margin-bottom: 10px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 10.5px; color: #000000; margin-bottom: 0;" border="1">
                        <?php 
                        $maxRows = 10; 
                        for ($i = 0; $i < $maxRows; $i++): 
                            $item1 = $recipientsCol1[$i] ?? null;
                            $item2 = $recipientsCol2[$i] ?? null;
                            $item3 = $recipientsCol3[$i] ?? null;
                        ?>
                            <tr style="height: 20px;">
                                <td style="width: 24px; text-align: center; vertical-align: middle; padding: 1px;">
                                    <?php if ($item1): ?>
                                        <span class="rev-chk-cell" data-val="<?= htmlspecialchars($item1) ?>"></span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1px 4px; width: 30%; vertical-align: middle;"><?= htmlspecialchars($item1 ?? '') ?></td>
                                
                                <td style="width: 24px; text-align: center; vertical-align: middle; padding: 1px;">
                                    <?php if ($item2): ?>
                                        <span class="rev-chk-cell" data-val="<?= htmlspecialchars($item2) ?>"></span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1px 4px; width: 30%; vertical-align: middle;"><?= htmlspecialchars($item2 ?? '') ?></td>
                                
                                <td style="width: 24px; text-align: center; vertical-align: middle; padding: 1px;">
                                    <?php if ($item3): ?>
                                        <span class="rev-chk-cell" data-val="<?= htmlspecialchars($item3) ?>"></span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1px 4px; width: 30%; vertical-align: middle;"><?= htmlspecialchars($item3 ?? '') ?></td>
                            </tr>
                        <?php endfor; ?>
                    </table>
                    </div>

                    <!-- Section Disposisi & Catatan -->
                    <div class="dispo-mid-section" style="display: flex; gap: 16px; width: 100%; margin-bottom: 10px; font-size: 10.5px; color: #000000; align-items: stretch;">
                        <!-- Left: Disposisi Table -->
                        <div class="dispo-col-left" style="width: 44%; display: flex; flex-direction: column;">
                            <div style="font-weight: normal; margin-bottom: 4px; font-size: 11px;">Disposisi :</div>
                            <table style="width: 100%; border-collapse: collapse; font-size: 10.5px; color: #000000; background: #ffffff;" border="1">
                                <?php foreach ($disposisiItemsList as $dItem): ?>
                                    <tr style="height: 19px;">
                                        <td style="width: 24px; text-align: center; vertical-align: middle; padding: 1px;">
                                            <span class="rev-chk-cell" data-val="<?= htmlspecialchars($dItem) ?>"></span>
                                        </td>
                                        <td style="padding: 1px 4px;"><?= htmlspecialchars($dItem) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>

                            <div class="dispo-sekre-box" style="margin-top: 10px;">
                                <div style="font-weight: normal; margin-bottom: 4px; font-size: 11px;">Sekretariat :</div>
                                <div id="revSekreText" style="white-space: pre-line; line-height: 1.35; font-size: 11.5px; color: #003399; font-weight: normal; padding: 2px 0; min-height: 70px; box-sizing: border-box;"></div>
                            </div>
                        </div>

                        <!-- Right: Catatan Text -->
                        <div class="dispo-col-right" style="flex: 1; display: flex; flex-direction: column;">
                            <div class="dispo-catatan-label" style="font-weight: normal; margin-bottom: 4px; margin-left: 50px; font-size: 11px;">Catatan :</div>
                            <div id="revCatatanText" style="white-space: pre-line; line-height: 1.4; font-size: 11.5px; color: #003399; font-weight: normal; padding: 2px 0; flex: 1; min-height: 240px; box-sizing: border-box;"></div>
                        </div>
                    </div>
                </div><!-- END Top content section -->

                <!-- Bottom Signature Section -->
                <div>
                    <div class="dispo-bottom-section" style="display: flex; justify-content: flex-end; align-items: flex-start; font-size: 11.5px; color: #000000; margin-top: -50px; gap: 16px;">
                        <div class="dispo-kepsta-box" style="width: 250px; text-align: left; line-height: 1.35; font-size: 11.5px;">
                            <div>Surabaya, <?= date('d F Y') ?></div>
                            <div>Kepala TVRI Stasiun Jawa Timur</div>
                            <div style="height: 50px; margin: 4px 0 6px 0; display: flex; align-items: center; justify-content: flex-start;">
                                <img id="revKepstaTtdImg" style="max-height: 46px; width: auto; display: none;">
                                <span id="revKepstaTtdPlaceholder" style="font-size: 10.5px; color: #64748B; font-style: italic; border: 1px dashed #94A3B8; padding: 4px 10px; border-radius: 4px;">(TTD Kepala Stasiun)</span>
                            </div>
                            <div style="text-decoration: underline; font-weight: normal;"><?= htmlspecialchars($kepstaNamaDisplay) ?></div>
                            <div><?= htmlspecialchars($kepstaNipDisplay) ?></div>
                        </div>
                    </div>
                </div><!-- END Bottom signature section -->
            </div>
        </div>
    </div>

        <!-- Modal Footer -->
        <div style="padding: 14px 24px; background: #F8FAFC; border-top: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <button type="button" onclick="printReviewModal('kepstaReviewModal')" class="btn-btn" style="background: #FFFFFF; color: #1E293B; border: 1.5px solid #CBD5E1; font-weight: 700; box-shadow: 0 1px 2px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-printer-fill" style="color: #2563EB; font-size: 16px;"></i> Print Surat
                </button>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <button type="button" onclick="closeKepstaReviewModal()" class="btn-btn btn-btn-secondary" style="padding: 10px 20px;">
                    Batal
                </button>
                <button type="button" onclick="confirmAndSubmitKepstaForm()" class="btn-btn btn-btn-primary" style="padding: 10px 24px; font-weight: 700; background-color: #2563EB; border-color: #2563EB;">
                    <i class="bi bi-check-circle-fill"></i> Ya, Teruskan Disposisi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL POPUP PERINGATAN KEPSTA (BELUM ISI DISPOSISI / TTD) -->
<!-- ========================================== -->
<div id="kepstaWarningModal" onclick="if(event.target === this) closeKepstaWarningModal()" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; inset: 0; z-index: 999999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #FFFFFF; width: 100%; max-width: 440px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); text-align: center; padding: 28px 24px; border: 1px solid #F3F4F6; position: relative;">
        <div style="width: 60px; height: 60px; border-radius: 50%; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 28px; border: 2px solid #FDE68A;">
            <i id="warnModalIcon" class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <h3 id="warnModalTitle" style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800; color: #0F172A; letter-spacing: -0.2px;">Belum Ada Input Disposisi & TTD</h3>
        <p id="warnModalDesc" style="margin: 0 0 24px 0; font-size: 13.5px; color: #64748B; line-height: 1.5;">
            Anda belum melakukan input apapun pada lembar disposisi maupun Tanda Tangan Digital.<br>Silakan lengkapi disposisi dan TTD Anda terlebih dahulu!
        </p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button type="button" onclick="closeKepstaWarningModal()" class="btn-btn btn-btn-secondary" style="flex: 1; justify-content: center; font-weight: 600; padding: 10px 16px;">
                Tutup
            </button>
            <button type="button" id="warnModalActionBtn" onclick="confirmOpenKepstaTtdFromWarning()" class="btn-btn btn-btn-primary" style="flex: 1.4; justify-content: center; font-weight: 700; padding: 10px 16px; background: #2563EB; border-color: #2563EB; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-pen-fill"></i> Isi TTD Sekarang
            </button>
        </div>
    </div>
</div>

<script>
function clearCanvasKepsta() {
    clearModalKepstaCanvas();
}

function saveSignatureKepsta() {
    // Handled via modal
}

// MODAL POPUP FOR KEPSTA TTD
const modalKepstaCanvas = document.getElementById('modalKepstaCanvas');
const modalKepstaCtx = modalKepstaCanvas ? modalKepstaCanvas.getContext('2d') : null;
let drawingModalKepsta = false;

function setupModalKepstaCanvasDpi() {
    if (!modalKepstaCanvas) return;
    const rect = modalKepstaCanvas.getBoundingClientRect();
    const dpr = Math.max(window.devicePixelRatio || 1, 2);
    modalKepstaCanvas.width = rect.width * dpr;
    modalKepstaCanvas.height = rect.height * dpr;
    if (modalKepstaCtx) {
        modalKepstaCtx.scale(dpr, dpr);
    }
}

function getKepstaCanvasCoords(e) {
    if (!modalKepstaCanvas) return { x: 0, y: 0 };
    const rect = modalKepstaCanvas.getBoundingClientRect();
    const touch = (e.touches && e.touches[0]) || (e.changedTouches && e.changedTouches[0]);
    const clientX = touch ? touch.clientX : e.clientX;
    const clientY = touch ? touch.clientY : e.clientY;
    return {
        x: clientX - rect.left,
        y: clientY - rect.top
    };
}

let lastKepstaX = 0, lastKepstaY = 0;

function startDrawingKepstaModal(e) {
    if (e && e.cancelable) e.preventDefault();
    drawingModalKepsta = true;
    const watermark = document.querySelector('#kepstaTtdModalPopup .sig-canvas-watermark');
    if (watermark) watermark.style.display = 'none';
    const coords = getKepstaCanvasCoords(e);
    lastKepstaX = coords.x;
    lastKepstaY = coords.y;

    if (modalKepstaCtx) {
        modalKepstaCtx.strokeStyle = '#1E3A8A';
        modalKepstaCtx.lineWidth = 3.5;
        modalKepstaCtx.lineCap = 'round';
        modalKepstaCtx.lineJoin = 'round';
        modalKepstaCtx.beginPath();
        modalKepstaCtx.arc(lastKepstaX, lastKepstaY, 1.75, 0, Math.PI * 2);
        modalKepstaCtx.fillStyle = modalKepstaCtx.strokeStyle;
        modalKepstaCtx.fill();
        modalKepstaCtx.beginPath();
        modalKepstaCtx.moveTo(lastKepstaX, lastKepstaY);
    }
}

function drawKepstaModal(e) {
    if (!drawingModalKepsta || !modalKepstaCtx) return;
    if (e && e.cancelable) e.preventDefault();
    const coords = getKepstaCanvasCoords(e);

    modalKepstaCtx.strokeStyle = '#1E3A8A';
    modalKepstaCtx.lineWidth = 3.5;
    modalKepstaCtx.lineCap = 'round';
    modalKepstaCtx.lineJoin = 'round';

    const midX = (lastKepstaX + coords.x) / 2;
    const midY = (lastKepstaY + coords.y) / 2;
    modalKepstaCtx.quadraticCurveTo(lastKepstaX, lastKepstaY, midX, midY);
    modalKepstaCtx.stroke();

    lastKepstaX = coords.x;
    lastKepstaY = coords.y;
}

function stopDrawingKepstaModal(e) {
    if (e && e.cancelable) e.preventDefault();
    drawingModalKepsta = false;
}

if (modalKepstaCanvas) {
    modalKepstaCanvas.addEventListener('mousedown', startDrawingKepstaModal);
    modalKepstaCanvas.addEventListener('mousemove', drawKepstaModal);
    modalKepstaCanvas.addEventListener('mouseup', stopDrawingKepstaModal);
    modalKepstaCanvas.addEventListener('mouseleave', stopDrawingKepstaModal);

    modalKepstaCanvas.addEventListener('touchstart', function(e) {
        if (e.cancelable) e.preventDefault();
        startDrawingKepstaModal(e);
    }, { passive: false });
    modalKepstaCanvas.addEventListener('touchmove', function(e) {
        if (e.cancelable) e.preventDefault();
        drawKepstaModal(e);
    }, { passive: false });
    modalKepstaCanvas.addEventListener('touchend', function(e) {
        if (e.cancelable) e.preventDefault();
        stopDrawingKepstaModal(e);
    }, { passive: false });
}

function openKepstaSignatureModal() {
    document.body.style.overflow = 'hidden';
    const popup = document.getElementById('kepstaTtdModalPopup');
    if (popup) popup.style.display = 'flex';

    const watermark = document.querySelector('#kepstaTtdModalPopup .sig-canvas-watermark');
    if (watermark) watermark.style.display = 'flex';

    requestAnimationFrame(() => {
        setupModalKepstaCanvasDpi();

        if (modalKepstaCtx && modalKepstaCanvas) {
            modalKepstaCtx.clearRect(0, 0, modalKepstaCanvas.width, modalKepstaCanvas.height);
            modalKepstaCtx.strokeStyle = '#1E3A8A';
            modalKepstaCtx.lineWidth = 3.5;
            modalKepstaCtx.lineCap = 'round';
            modalKepstaCtx.lineJoin = 'round';
        }

        const currentImg = document.getElementById('pvKepstaTtdImg');
        if (currentImg && currentImg.getAttribute('src') && currentImg.style.display !== 'none' && currentImg.getAttribute('src').length > 5) {
            const img = new Image();
            img.onload = () => {
                if (modalKepstaCtx && modalKepstaCanvas) {
                    const rect = modalKepstaCanvas.getBoundingClientRect();
                    modalKepstaCtx.drawImage(img, 0, 0, rect.width, rect.height);
                    if (watermark) watermark.style.display = 'none';
                }
            };
            img.src = currentImg.getAttribute('src');
        }
    });
}

function closeKepstaSignatureModal() {
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
    const popup = document.getElementById('kepstaTtdModalPopup');
    if (popup) popup.style.display = 'none';
}

function clearModalKepstaCanvas() {
    if (modalKepstaCtx && modalKepstaCanvas) {
        modalKepstaCtx.clearRect(0, 0, modalKepstaCanvas.width, modalKepstaCanvas.height);
        modalKepstaCtx.strokeStyle = '#1E3A8A';
        modalKepstaCtx.lineWidth = 3.5;
        modalKepstaCtx.lineCap = 'round';
        modalKepstaCtx.lineJoin = 'round';
    }
    const watermark = document.querySelector('#kepstaTtdModalPopup .sig-canvas-watermark');
    if (watermark) watermark.style.display = 'flex';

    const inputData = document.getElementById('kepstaSignatureData');
    if (inputData) inputData.value = '';

    const pvImg = document.getElementById('pvKepstaTtdImg');
    const pvPlaceholder = document.getElementById('pvKepstaTtdPlaceholder');
    if (pvImg) {
        pvImg.style.display = 'none';
        pvImg.setAttribute('src', '');
    }
    if (pvPlaceholder) {
        pvPlaceholder.style.display = 'inline-block';
    }
}

function useDefaultKepstaSignature() {
    clearModalKepstaCanvas();
    if (modalKepstaCtx) {
        modalKepstaCtx.strokeStyle = '#003399';
        modalKepstaCtx.lineWidth = 5.5;
        modalKepstaCtx.lineCap = 'round';
        modalKepstaCtx.lineJoin = 'round';
        modalKepstaCtx.font = '28px cursive';
        modalKepstaCtx.fillStyle = '#003399';
        modalKepstaCtx.fillText(<?= json_encode($kepstaNamaDisplay) ?>, 80, 90);
    }
}

function saveKepstaSignatureFromModal() {
    if (!modalKepstaCanvas) return;
    const dataUrl = modalKepstaCanvas.toDataURL();

    const inputData = document.getElementById('kepstaSignatureData');
    if (inputData) inputData.value = dataUrl;

    const pvImg = document.getElementById('pvKepstaTtdImg');
    const pvPlaceholder = document.getElementById('pvKepstaTtdPlaceholder');
    if (pvImg) {
        pvImg.setAttribute('src', dataUrl);
        pvImg.style.display = 'block';
    }
    if (pvPlaceholder) {
        pvPlaceholder.style.display = 'none';
    }

    closeKepstaSignatureModal();
}

function toggleDispoCell(target) {
    if (!target) return;
    const td = target.closest('td');
    if (!td) return;
    let cell = td.classList.contains('dispo-chk-cell') ? td : td.previousElementSibling;
    if (!cell || !cell.classList.contains('dispo-chk-cell')) return;

    const input = cell.querySelector('input[type="checkbox"]');
    const svgWrap = cell.querySelector('.chk-svg-wrap');
    if (input) {
        input.checked = !input.checked;
        if (svgWrap) {
            svgWrap.style.visibility = input.checked ? 'visible' : 'hidden';
        }
    }
}

// KEPSTA REVIEW MODAL CONTROLLER & SYNC
const svgCheckBlue = `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block;"><path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`;

function showKepstaWarningModal(reason = 'empty_all') {
    try {
        const modal = document.getElementById('kepstaWarningModal');
        if (!modal) return;

        const titleEl = document.getElementById('warnModalTitle');
        const descEl = document.getElementById('warnModalDesc');
        const iconEl = document.getElementById('warnModalIcon');
        const actionBtn = document.getElementById('warnModalActionBtn');

        if (reason === 'empty_all') {
            if (titleEl) titleEl.textContent = 'Centang Disposisi & TTD Belum Diisi';
            if (descEl) descEl.innerHTML = 'Silakan melakukan centang pada tabel penerima surat disposisi dan isi Tanda Tangan Digital (TTD) terlebih dahulu!';
            if (iconEl) iconEl.className = 'bi bi-exclamation-triangle-fill';
            if (actionBtn) {
                actionBtn.innerHTML = '<i class="bi bi-pencil-square"></i> Centang Disposisi & TTD';
                actionBtn.onclick = function() {
                    scrollToDispoForm();
                };
            }
        } else if (reason === 'empty_dispo') {
            if (titleEl) titleEl.textContent = 'Penerima Disposisi Belum Dicentang';
            if (descEl) descEl.innerHTML = 'Silakan melakukan centang pada tabel penerima atau instruksi surat disposisi terlebih dahulu!';
            if (iconEl) iconEl.className = 'bi bi-card-checklist';
            if (actionBtn) {
                actionBtn.innerHTML = '<i class="bi bi-check2-square"></i> Centang Disposisi';
                actionBtn.onclick = function() {
                    scrollToDispoForm();
                };
            }
        } else { // 'empty_ttd'
            if (titleEl) titleEl.textContent = 'Tanda Tangan Belum Diisi';
            if (descEl) descEl.innerHTML = 'Tanda Tangan Digital (TTD) Kepala Stasiun belum diisi.<br>Silakan masukkan TTD Anda terlebih dahulu!';
            if (iconEl) iconEl.className = 'bi bi-pen-fill';
            if (actionBtn) {
                actionBtn.innerHTML = '<i class="bi bi-pen-fill"></i> Isi TTD Sekarang';
                actionBtn.onclick = function() {
                    confirmOpenKepstaTtdFromWarning();
                };
            }
        }

        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
        modal.style.display = 'flex';
    } catch (err) {
        console.error('Error in showKepstaWarningModal:', err);
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
    }
}

function scrollToDispoForm() {
    closeKepstaWarningModal();
    setTimeout(function() {
        const dispoForm = document.getElementById('kepstaForm');
        if (dispoForm) {
            dispoForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }, 50);
}

function closeKepstaWarningModal() {
    const modal = document.getElementById('kepstaWarningModal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
}

function confirmOpenKepstaTtdFromWarning() {
    closeKepstaWarningModal();
    setTimeout(function() {
        openKepstaSignatureModal();
    }, 50);
}

function checkKepstaInputs() {
    const checkedBoxes = document.querySelectorAll('#kepstaForm input[name="checklist[]"]:checked');
    const sigData = document.getElementById('kepstaSignatureData') ? document.getElementById('kepstaSignatureData').value.trim() : '';

    const hasChecklist = checkedBoxes.length > 0;
    const hasSig = sigData.length > 0 && sigData !== '/assets/img/ttd_kepsta_stamp.png' && !sigData.includes('ttd_tu_stamp') && !sigData.includes('ttd_pemohon');

    if (!hasChecklist && !hasSig) {
        return 'empty_all';
    }
    if (!hasChecklist) {
        return 'empty_dispo';
    }
    if (!hasSig) {
        return 'empty_ttd';
    }
    return 'ok';
}

function validateKepstaFormSubmit(e) {
    const status = checkKepstaInputs();
    if (status !== 'ok') {
        if (e && e.preventDefault) e.preventDefault();
        showKepstaWarningModal(status);
        return false;
    }
    return true;
}

function handleKepstaReviewClick() {
    const status = checkKepstaInputs();
    if (status !== 'ok') {
        showKepstaWarningModal(status);
        return false;
    }
    saveSignatureKepsta();
    syncKepstaReviewModal();
    document.body.style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';
    document.getElementById('kepstaReviewModal').style.display = 'flex';
}

function closeKepstaReviewModal() {
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
    const popup = document.getElementById('kepstaReviewModal');
    if (popup) popup.style.display = 'none';
}

function confirmAndSubmitKepstaForm() {
    const status = checkKepstaInputs();
    if (status !== 'ok') {
        closeKepstaReviewModal();
        showKepstaWarningModal(status);
        return false;
    }
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
    // Show loading overlay before submitting
    const overlay = document.getElementById('kepstaLoadingOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    document.getElementById('kepstaForm').submit();
}

function syncKepstaReviewModal() {
    const selectedSifat = document.querySelector('input[name="sifat_surat"]:checked')?.value || '';
    const svgAbsHtml = `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block; position: absolute; top: -5px; left: -2px; pointer-events: none;"><path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
    
    const elBiasa = document.getElementById('revChkBiasa');
    const elPenting = document.getElementById('revChkPenting');
    const elSegera = document.getElementById('revChkSegera');
    const elRahasia = document.getElementById('revChkRahasia');

    if (elBiasa) elBiasa.innerHTML = (selectedSifat === 'biasa') ? svgAbsHtml : '';
    if (elPenting) elPenting.innerHTML = (selectedSifat === 'penting') ? svgAbsHtml : '';
    if (elSegera) elSegera.innerHTML = (selectedSifat === 'segera') ? svgAbsHtml : '';
    if (elRahasia) elRahasia.innerHTML = (selectedSifat === 'rahasia') ? svgAbsHtml : '';

    const checkedValues = Array.from(document.querySelectorAll('#kepstaForm input[name="checklist[]"]:checked')).map(el => el.value);

    document.querySelectorAll('.rev-chk-cell').forEach(cell => {
        const itemVal = cell.getAttribute('data-val');
        if (itemVal && checkedValues.includes(itemVal)) {
            cell.innerHTML = svgCheckBlue;
        } else {
            cell.innerHTML = '';
        }
    });

    const catKepsta = document.querySelector('textarea[name="catatan_kepsta"]')?.value || '';
    const catSekre = document.querySelector('textarea[name="catatan_sekretariat"]')?.value || '';
    
    const elCat = document.getElementById('revCatatanText');
    const elSekre = document.getElementById('revSekreText');
    if (elCat) elCat.textContent = catKepsta || '';
    if (elSekre) elSekre.textContent = catSekre || '';

    const sigData = document.getElementById('kepstaSignatureData')?.value || '';
    const imgEl = document.getElementById('revKepstaTtdImg');
    const placeholderEl = document.getElementById('revKepstaTtdPlaceholder');
    if (sigData && sigData.length > 100) {
        if (imgEl) {
            imgEl.src = sigData;
            imgEl.style.display = 'block';
        }
        if (placeholderEl) placeholderEl.style.display = 'none';
    } else {
        if (imgEl) imgEl.style.display = 'none';
        if (placeholderEl) placeholderEl.style.display = 'inline-block';
    }
}

async function printReviewModal(modalId) {
    try {
        if (typeof renderServerPdfCanvases === 'function') {
            await renderServerPdfCanvases();
        }
    } catch (e) {
        console.error('Error pre-rendering PDF canvases:', e);
    }

    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    let papersHtml = '';

    function processPaperNode(paper, isDispo = false) {
        if (!paper) return '';
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

            return `
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

            if (isDispo) {
                return `<div class="printable-a4-page printable-dispo-page">${cloned.innerHTML}</div>`;
            } else {
                return `<div class="printable-a4-page">${cloned.innerHTML}</div>`;
            }
        }
    }

    // 1. PAGE 1: LEMBAR DISPOSISI KEPALA STASIUN (FROM REVIEW MODAL)
    const dispoPaper = modal.querySelector('.a4-paper-preview');
    if (dispoPaper) {
        papersHtml += processPaperNode(dispoPaper, true);
    }

    // 2. PAGE 2+: SURAT PERMOHONAN / NOTA DINAS / TU TTE PDF (FROM MAIN PAGE GRID)
    const mainDocsContainer = document.querySelector('.admin-review-grid-container');
    if (mainDocsContainer) {
        const mainPapers = mainDocsContainer.querySelectorAll('.a4-paper-preview, .pdf-lampiran-card-preview');
        mainPapers.forEach(p => {
            papersHtml += processPaperNode(p);
        });
    }

    // 3. PAGE 4+: UPLOADED PDF LAMPIRAN CARDS FROM MAIN PAGE
    const extraPdfCards = document.querySelectorAll('div[style*="border-top: 2px dashed #CBD5E1"] .pdf-lampiran-card-preview');
    if (extraPdfCards && extraPdfCards.length > 0) {
        extraPdfCards.forEach(p => {
            papersHtml += processPaperNode(p);
        });
    }

    const extraLampiranHtml = <?= json_encode($printableLampiranHtml) ?>;
    if (extraLampiranHtml && !papersHtml.includes('printable-lampiran-page')) {
        papersHtml += extraLampiranHtml;
    }

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
                .printable-a4-page { background: #FFFFFF !important; border: none !important; box-shadow: none !important; padding: 2.5cm 2cm !important; margin: 0 auto !important; width: 210mm !important; height: 297mm !important; max-height: 297mm !important; box-sizing: border-box !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; page-break-after: always !important; break-after: page !important; page-break-inside: avoid !important; break-inside: avoid !important; overflow: hidden !important; }
                .printable-a4-page.printable-dispo-page { padding: 2.2cm !important; display: block !important; }
                .printable-a4-page.printable-dispo-page > div { border: 1.5px solid #000000 !important; padding: 12px 16px !important; box-sizing: border-box !important; background: #FFFFFF !important; height: 100% !important; min-height: 100% !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; }
                .printable-a4-page.printable-lampiran-page { padding: 0 !important; border: none !important; box-shadow: none !important; overflow: hidden !important; width: 210mm !important; height: 297mm !important; background: #ffffff !important; display: block !important; }
                .printable-a4-page.printable-lampiran-page img,
                .printable-a4-page.printable-lampiran-page .pdf-img-item { width: 100% !important; height: 100% !important; max-width: 100% !important; max-height: 297mm !important; object-fit: contain !important; display: block !important; margin: 0 auto !important; border: none !important; box-shadow: none !important; transform: none !important; }
                .printable-a4-page:last-child { page-break-after: avoid !important; break-after: avoid !important; }
                .a4-paper-preview { background: #FFFFFF !important; border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; max-width: 100% !important; height: auto !important; min-height: 0 !important; box-sizing: border-box !important; }
                .a4-paper-preview > div { border: 1.5px solid #000000 !important; padding: 12px 16px !important; min-height: 0 !important; box-sizing: border-box !important; background: #FFFFFF !important; }
                #pvTtdBox, #pvParafBox, #pvKepstaTtdBox { border: none !important; background: transparent !important; box-shadow: none !important; }
                #pvTtdPlaceholder, #pvParafPlaceholder, #revKepstaTtdPlaceholder { display: none !important; }
                .no-print { display: none !important; }
                .pdf-canvas-item { display: none !important; }
                .pdf-img-item { display: block !important; visibility: visible !important; width: 100% !important; height: auto !important; }
                @media print {
                    body { background: #fff !important; }
                    img { display: inline-block !important; visibility: visible !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                    .printable-a4-page { border: none !important; box-shadow: none !important; padding: 2.5cm 2cm !important; margin: 0 auto !important; }
                    .printable-a4-page.printable-dispo-page { padding: 2.2cm !important; }
                    .printable-a4-page.printable-dispo-page > div { border: 1.5px solid #000000 !important; padding: 12px 16px !important; }
                    .a4-paper-preview { border: none !important; box-shadow: none !important; padding: 0 !important; }
                    .a4-paper-preview > div { border: 1.5px solid #000000 !important; padding: 14px 18px !important; }
                    #pvTtdBox, #pvParafBox, #pvKepstaTtdBox { border: none !important; background: transparent !important; box-shadow: none !important; }
                    #pvTtdPlaceholder, #pvParafPlaceholder, #revKepstaTtdPlaceholder { display: none !important; }
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
    window.addEventListener('click', function(e) {
        const sigModal = document.getElementById('kepstaTtdModalPopup');
        const revModal = document.getElementById('kepstaReviewModal');
        const warnModal = document.getElementById('kepstaWarningModal');
        if (e.target === sigModal) closeKepstaSignatureModal();
        if (e.target === revModal) closeKepstaReviewModal();
        if (e.target === warnModal) closeKepstaWarningModal();
    });
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeKepstaSignatureModal();
            closeKepstaReviewModal();
            closeKepstaWarningModal();
        }
    });
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
                try {
                    const res = await fetch(pdfUrl);
                    if (res.ok) {
                        const buffer = await res.arrayBuffer();
                        pdfDocCache[pdfUrl] = await pdfjsLib.getDocument({ data: buffer.slice(0) }).promise;
                    } else {
                        throw new Error(`Fetch status ${res.status}`);
                    }
                } catch (fetchErr) {
                    console.warn('Fetch ArrayBuffer failed, attempting direct pdfjsLib.getDocument:', fetchErr);
                    pdfDocCache[pdfUrl] = await pdfjsLib.getDocument(pdfUrl).promise;
                }
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
                let availableWidth = 650;
                if (parentContainer && parentContainer.clientWidth > 40) {
                    availableWidth = parentContainer.clientWidth;
                } else {
                    const wrapper = targetCanvas.closest('.pdf-lampiran-wrapper-col');
                    if (wrapper && wrapper.clientWidth > 40) availableWidth = wrapper.clientWidth;
                }

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
                try {
                    const dataUrl = targetCanvas.toDataURL('image/png');
                    if (img && img.classList.contains('pdf-img-item') && dataUrl && dataUrl.length > 100) {
                        img.src = dataUrl;
                        img.style.display = 'block';
                        img.style.width = '100%';
                        img.style.height = 'auto';
                        targetCanvas.style.display = 'none';
                    } else {
                        targetCanvas.style.display = 'block';
                    }
                } catch (e) {
                    console.warn('toDataURL conversion error, keeping canvas visible:', e);
                    targetCanvas.style.display = 'block';
                }
            }
        } catch (err) {
            console.error('Error rendering PDF canvas for URL:', pdfUrl, err);
        }
    }
}

function toggleSifatRadio(radio) {
    if (radio.dataset.checked === 'true') {
        radio.checked = false;
        radio.dataset.checked = 'false';
        updateSifatCheckmarks('');
    } else {
        document.querySelectorAll('input[name="sifat_surat"]').forEach(r => r.dataset.checked = 'false');
        radio.dataset.checked = 'true';
        updateSifatCheckmarks(radio.value);
    }
}

function updateSifatCheckmarks(val) {
    const svgHtml = `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block; position: absolute; top: -5px; left: -2px; pointer-events: none;"><path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
    const elB = document.getElementById('spanChkBiasa');
    const elS = document.getElementById('spanChkSegera');
    const elP = document.getElementById('spanChkPenting');
    const elR = document.getElementById('spanChkRahasia');

    if (elB) elB.innerHTML = (val === 'biasa') ? svgHtml : '';
    if (elS) elS.innerHTML = (val === 'segera') ? svgHtml : '';
    if (elP) elP.innerHTML = (val === 'penting') ? svgHtml : '';
    if (elR) elR.innerHTML = (val === 'rahasia') ? svgHtml : '';
}
</script>

<!-- ========== KEPSTA LOADING OVERLAY ========== -->
<div id="kepstaLoadingOverlay" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(0,0,0,0.65); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); align-items: center; justify-content: center; flex-direction: column;">
    <div style="background: #FFFFFF; border-radius: 24px; padding: 44px 48px; text-align: center; box-shadow: 0 32px 80px rgba(0,0,0,0.3); max-width: 420px; width: 90%; animation: kpFadeIn 0.35s cubic-bezier(.22,.68,0,1.2);">
        <!-- Spinner -->
        <div style="margin: 0 auto 24px auto; width: 72px; height: 72px; position: relative;">
            <svg width="72" height="72" viewBox="0 0 72 72" style="position: absolute; top: 0; left: 0; animation: kpSpin 1.2s linear infinite;">
                <circle cx="36" cy="36" r="30" fill="none" stroke="#DBEAFE" stroke-width="6"/>
                <path d="M 36 6 A 30 30 0 0 1 66 36" fill="none" stroke="#2563EB" stroke-width="6" stroke-linecap="round"/>
            </svg>
            <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 28px;">✍️</div>
        </div>
        <!-- Title -->
        <div style="font-size: 18px; font-weight: 800; color: #0F172A; margin-bottom: 8px; letter-spacing: -0.3px;">Menyimpan Disposisi & TTD...</div>
        <!-- Subtitle -->
        <div style="font-size: 13.5px; color: #64748B; line-height: 1.6; margin-bottom: 20px;">Sedang membuat PDF gabungan dan<br>mengupload ke Google Drive. Mohon tunggu.</div>
        <!-- Progress dots -->
        <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
            <span style="width: 8px; height: 8px; border-radius: 50%; background: #2563EB; display: inline-block; animation: kpDot 1.4s ease-in-out infinite 0s;"></span>
            <span style="width: 8px; height: 8px; border-radius: 50%; background: #2563EB; display: inline-block; animation: kpDot 1.4s ease-in-out infinite 0.2s;"></span>
            <span style="width: 8px; height: 8px; border-radius: 50%; background: #2563EB; display: inline-block; animation: kpDot 1.4s ease-in-out infinite 0.4s;"></span>
        </div>
    </div>
</div>

<style>
@keyframes kpSpin { to { transform: rotate(360deg); } }
@keyframes kpFadeIn {
    from { opacity: 0; transform: scale(0.88) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes kpDot {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
    40%           { transform: scale(1.2); opacity: 1; }
}
</style>

