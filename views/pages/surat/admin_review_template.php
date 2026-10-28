<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<?php
$meta = $surat['meta'] ?? [];
$isProduksi = (($surat['type'] ?? '') === 'izin_produksi' || str_contains(strtolower($meta['kategori_surat'] ?? ''), 'produksi'));
$isPrinsip = (($surat['type'] ?? '') === 'izin_prinsip' || str_contains(strtolower($meta['kategori_surat'] ?? ''), 'prinsip'));
$isNotaDinas = (($surat['type'] ?? '') === 'nota_dinas' || ($meta['kategori_surat'] ?? '') === 'Surat Nota Dinas');
$tglIndo = date('d F Y', strtotime($surat['tanggal_surat']));

$viewRole = $role ?? (class_exists('Session') ? Session::get('user_role', 'admin') : 'admin');

// Dynamic role account details lookup (needed before signature extraction):
$tuAccount = User::getSingleUserByRole('tata_usaha') ?: [];
$kepstaAccount = User::getSingleUserByRole('kepala_stasiun') ?: [];

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

// Signature & metadata extraction:
$pemohonTtd = $meta['pemohon_signature_data'] ?? $meta['pemohon_signature'] ?? '';

$tuTtdRaw = (!empty($surat['tu_signature']) ? $surat['tu_signature'] : ($meta['tu_signature'] ?? ''));
$tuTtd = ($tuTtdRaw !== '/assets/img/ttd_tu_stamp.png') ? $tuTtdRaw : '';

$kppTtdRaw = !empty($surat['kpp_signature']) ? $surat['kpp_signature'] 
    : (!empty($meta['kpp_signature']) ? $meta['kpp_signature'] 
    : (!empty($meta['kpp_signature_data']) ? $meta['kpp_signature_data'] : ''));
$kppTtd = ($kppTtdRaw !== '/assets/img/ttd_tu_stamp.png') ? $kppTtdRaw : '';

$getValidParaf = function(...$candidates) {
    foreach ($candidates as $c) {
        if (!empty($c) && $c !== '/assets/img/ttd_tu_stamp.png') {
            return $c;
        }
    }
    return '';
};

$kppParaf = $getValidParaf(
    $meta['kpp_paraf'] ?? null,
    $meta['paraf_kpp'] ?? null,
    $meta['kpp_paraf_data'] ?? null,
    $surat['kpp_paraf'] ?? null,
    $meta['paraf'] ?? null,
    $surat['paraf'] ?? null,
    $meta['tu_paraf'] ?? null,
    $kppAccount['paraf'] ?? null,
    $kppAccount['signature'] ?? null,
    $surat['kpp_signature'] ?? null,
    $meta['kpp_signature'] ?? null,
    $kppTtd ?? null
);

$nomorSuratDisplay = !empty($surat['nomor_surat']) ? $surat['nomor_surat'] : ($meta['nomor_surat'] ?? '');

$tuNamaDisplay = $tuAccount['name'] ?? ($meta['penandatangan_nama'] ?? 'Maya Sulfiana, S.Kom');
$tuNipRaw = $tuAccount['nip'] ?? ($meta['penandatangan_nip'] ?? '198612252011012016');
$tuNipDisplay = str_starts_with($tuNipRaw, 'NIP.') ? $tuNipRaw : ('NIP. ' . $tuNipRaw);

$kppNamaDisplay = $kppAccount['name'] ?? ($surat['kpp_nama'] ?? ($meta['pemohon_nama'] ?? 'Ir. Sepna Yuhana'));
$kppNipRaw = $kppAccount['nip'] ?? ($surat['kpp_nip'] ?? ($meta['pemohon_nip'] ?? '19680916 199303 2 006'));
$kppNipDisplay = str_starts_with($kppNipRaw, 'NIP.') ? $kppNipRaw : ('NIP. ' . $kppNipRaw);

$kepstaNamaDisplay = $kepstaAccount['name'] ?? ($meta['kepsta_nama'] ?? 'Akbar Sahidi, S.Sos., M.Si');
$kepstaNipRaw = $kepstaAccount['nip'] ?? ($meta['kepsta_nip'] ?? '197005121995031002');
$kepstaNipDisplay = str_starts_with($kepstaNipRaw, 'NIP.') ? $kepstaNipRaw : ('NIP. ' . $kepstaNipRaw);

$kepstaSigRaw = !empty($disposisi['kepsta_signature']) ? $disposisi['kepsta_signature'] 
    : (!empty($meta['kepsta_signature']) ? $meta['kepsta_signature'] 
    : (!empty($kepstaAccount['signature']) ? $kepstaAccount['signature'] : ''));
?>
<style>
    img.kpp-paraf-img {
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
    .pdf-lampiran-wrapper-col {
        flex: 0 0 calc(50% - 10px);
        max-width: calc(50% - 10px);
        min-width: 0;
        box-sizing: border-box;
        margin-bottom: 20px;
    }

    /* Mobile & Tablet Responsiveness for Screen View */
    @media screen and (max-width: 992px) {
        .admin-review-grid-container {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            gap: 16px !important;
            padding: 4px 2px !important;
            width: 100% !important;
            box-sizing: border-box !important;
            margin-bottom: 16px !important;
        }

        .admin-review-col,
        .admin-review-col-page1,
        .admin-review-col-page2 {
            width: 100% !important;
            max-width: 100% !important;
            flex: 1 1 auto !important;
            display: block !important;
            box-sizing: border-box !important;
            margin-bottom: 16px !important;
        }

        .pdf-lampiran-wrapper-col {
            flex: 0 0 100% !important;
            max-width: 100% !important;
            width: 100% !important;
            margin-bottom: 16px !important;
        }

        .a4-paper-preview,
        .card-panel-variant.a4-paper-preview,
        .a4-paper-preview.dispo-paper-preview,
        .a4-paper-preview.surat-paper-preview {
            padding: 20px 14px !important;
            min-height: auto !important;
            width: 100% !important;
            max-width: 100% !important;
            border-radius: 12px !important;
            box-sizing: border-box !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05) !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }

        /* Inner frame of lembar disposisi on mobile */
        .dispo-paper-preview > div {
            padding: 10px 8px !important;
        }

        /* Disposisi Sifat flex wrap */
        .dispo-paper-preview table td div[style*="display: flex; gap: 24px"] {
            gap: 8px 14px !important;
            flex-wrap: wrap !important;
        }

        /* 3-column signature block in Surat */
        div[style*="grid-template-columns: 1.2fr 1.2fr 1fr"] {
            gap: 6px !important;
            font-size: 8px !important;
        }
    }

    @media screen and (max-width: 576px) {
        .a4-paper-preview,
        .card-panel-variant.a4-paper-preview,
        .a4-paper-preview.dispo-paper-preview,
        .a4-paper-preview.surat-paper-preview {
            padding: 14px 8px !important;
            border-radius: 8px !important;
        }

        .dispo-paper-preview > div {
            padding: 8px 6px !important;
        }

        .a4-paper-preview table {
            font-size: 10px !important;
        }

        .a4-paper-preview h2 {
            font-size: 13px !important;
        }

        /* Paraf image scaling */
        img.kpp-paraf-img {
            max-width: 48px !important;
            max-height: 34px !important;
        }
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 0;
        }

        :root {
            color-scheme: light !important;
        }

        html, body {
            width: 210mm !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            color: #000000 !important;
            color-scheme: light !important;
            overflow: visible !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .no-print,
        button,
        .btn-btn,
        header,
        aside,
        .app-topbar,
        .app-sidebar,
        .toast-container {
            display: none !important;
        }

        .app-main,
        .app-page,
        .printable-wrapper,
        .admin-review-grid-container,
        .admin-review-col {
            display: block !important;
            position: static !important;
            float: none !important;
            width: 210mm !important;
            max-width: 210mm !important;
            flex: none !important;
            height: auto !important;
            min-height: 0 !important;
            margin: 0 auto !important;
            padding: 0 !important;
            border: none !important;
            background: #ffffff !important;
            gap: 0 !important;
            page-break-after: auto !important;
            break-after: auto !important;
        }

        .admin-review-col-page1 {
            page-break-before: auto !important;
            break-before: auto !important;
            page-break-after: always !important;
            break-after: page !important;
        }

        .admin-review-col-page2 {
            page-break-before: always !important;
            break-before: page !important;
            page-break-after: always !important;
            break-after: page !important;
        }

        .a4-paper-preview {
            visibility: visible !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            width: 210mm !important;
            max-width: 210mm !important;
            height: 297mm !important;
            max-height: 297mm !important;
            margin: 0 auto !important;
            box-shadow: none !important;
            border: none !important;
            background: #ffffff !important;
            box-sizing: border-box !important;
            page-break-before: avoid !important;
            break-before: avoid !important;
            page-break-after: always !important;
            break-after: page !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            border-radius: 0 !important;
            overflow: hidden !important;
            flex: none !important;
        }

        .a4-paper-preview.dispo-paper-preview {
            padding: 2.2cm !important;
            overflow: visible !important;
        }

        .a4-paper-preview.surat-paper-preview {
            padding: 2.5cm !important;
        }

        .pdf-lampiran-wrapper-col {
            display: block !important;
            position: static !important;
            float: none !important;
            width: 100% !important;
            max-width: 100% !important;
            flex: none !important;
            height: 297mm !important;
            max-height: 297mm !important;
            margin: 0 auto !important;
            padding: 0 !important;
            border: none !important;
            background: transparent !important;
            gap: 0 !important;
            page-break-before: always !important;
            break-before: page !important;
            page-break-after: avoid !important;
            break-after: avoid !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            transform: none !important;
            -webkit-transform: none !important;
        }

        .pdf-lampiran-card-preview {
            visibility: visible !important;
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            height: 297mm !important;
            max-height: 297mm !important;
            padding: 0 !important;
            margin: 0 auto !important;
            box-shadow: none !important;
            border: none !important;
            background: #ffffff !important;
            box-sizing: border-box !important;
            page-break-before: avoid !important;
            break-before: avoid !important;
            page-break-after: avoid !important;
            break-after: avoid !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            border-radius: 0 !important;
            overflow: hidden !important;
            flex: none !important;
            transform: none !important;
            -webkit-transform: none !important;
        }

        .admin-review-grid-container:last-child,
        .admin-review-grid-container:last-child .a4-paper-preview,
        .admin-review-grid-container:last-child .admin-review-col:last-child,
        .admin-review-grid-container:last-child .admin-review-col:last-child .a4-paper-preview,
        .admin-review-grid-container:last-child .pdf-lampiran-wrapper-col:last-child,
        .admin-review-grid-container:last-child .pdf-lampiran-wrapper-col:last-child .pdf-lampiran-card-preview,
        .printable-wrapper > .admin-review-grid-container:last-child,
        .printable-wrapper > .admin-review-grid-container:last-child .a4-paper-preview {
            page-break-after: avoid !important;
            break-after: avoid !important;
            page-break-after: auto !important;
            break-after: auto !important;
        }

        .pdf-canvas-item {
            display: none !important;
        }

        .pdf-img-item,
        .pdf-lampiran-card-preview img {
            display: block !important;
            visibility: visible !important;
            width: 100% !important;
            max-width: 100% !important;
            height: 100% !important;
            max-height: 297mm !important;
            object-fit: contain !important;
            margin: 0 auto !important;
            padding: 0 !important;
            border: none !important;
        }

        img {
            display: inline-block !important;
            visibility: visible !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            max-width: 100% !important;
        }
    }
</style>

<?php 
$tuTtePdfPath = !empty($meta['tu_tte_pdf_path']) ? $meta['tu_tte_pdf_path'] : (!empty($meta['is_tu_tte_uploaded']) ? ($surat['pdf_path'] ?? '') : '');
$hasTuTtePdf = !empty($tuTtePdfPath) && !empty($meta['is_tu_tte_uploaded']);

$pdfLampirans = [];
if (!empty($meta['lampiran_pdf_paths']) && is_array($meta['lampiran_pdf_paths'])) {
    $pdfLampirans = $meta['lampiran_pdf_paths'];
} elseif (!empty($meta['lampiran_pdf_path'])) {
    $pdfLampirans = [$meta['lampiran_pdf_path']];
} elseif (!empty($surat['pdf_path']) && empty($meta['is_tu_tte_uploaded'])) {
    $pdfLampirans = [$surat['pdf_path']];
}
$isFromUploadLampiran = !empty($meta['from_upload_lampiran']) || (($surat['type'] ?? '') === 'upload_lampiran');

if (!isset($disposisi) || empty($disposisi)) {
    if (class_exists('Disposisi')) {
        $disposisi = (new Disposisi())->getBySuratId($surat['id'] ?? 0) ?: [];
    } else {
        $disposisi = [];
    }
}

$dispoChecklist = $disposisi['checklist'] ?? ($meta['dispo_checklist'] ?? []);
if (is_string($dispoChecklist)) {
    $dispoChecklist = json_decode($dispoChecklist, true) ?? [];
}
if (!is_array($dispoChecklist)) {
    $dispoChecklist = [];
}

$hasDisposisi = !empty($disposisi['id']) || 
                !empty($disposisi['catatan_sekre']) || 
                !empty($meta['catatan_sekretariat']) || 
                !empty($meta['nomor_agenda']) || 
                !empty($meta['hal_disposisi']) || 
                in_array($surat['status'] ?? '', ['sekre_approved', 'completed']);

// Jika diakses via popup modal (klik card surat):
// - Role Admin/Superadmin melihat draft murni buatan admin.
// - Role KPP Bidang melihat TTD & Paraf KPP jika sudah di-TTD & diparaf.
// - Role KTU melihat TTD/TTE KTU jika sudah di-TTD oleh KTU & diteruskan.
// - Role Sekretaris & Kepsta melihat Lembar Disposisi jika sudah diinputkan oleh Sekre.
if (!empty($isModal)) {
    $isAdminRole  = in_array($viewRole, ['admin', 'superadmin']);
    $isKppRole    = in_array($viewRole, ['kpp_bidang', 'kpp']);
    $isTuRole     = in_array($viewRole, ['tata_usaha', 'tu', 'ktu']);
    $isSekreRole  = in_array($viewRole, ['sekretaris', 'sekre']);
    $isKepstaRole = in_array($viewRole, ['kepala_stasiun', 'kepsta']);

    $hasDisposisiInput = !empty($disposisi['id']) || 
                         !empty($disposisi['catatan_sekre']) || 
                         !empty($meta['catatan_sekretariat']) || 
                         !empty($meta['nomor_agenda']) || 
                         !empty($meta['hal_disposisi']) || 
                         in_array($surat['status'] ?? '', ['sekre_approved', 'completed']);

    if (($isSekreRole || $isKepstaRole) && $hasDisposisiInput) {
        $hasDisposisi = true;
    } else {
        $hasDisposisi = false;
    }

    $isKppSigned = !empty($surat['kpp_signed_at']) || 
                   (!empty($surat['kpp_signature']) && $surat['kpp_signature'] !== '/assets/img/ttd_tu_stamp.png') || 
                   (!empty($meta['kpp_signature']) && $meta['kpp_signature'] !== '/assets/img/ttd_tu_stamp.png') || 
                   in_array($surat['status'] ?? '', ['kpp_signed', 'submitted_to_tu', 'tu_signed', 'sekre_approved', 'completed']);

    $isTuSigned  = !empty($surat['tu_signed_at']) || 
                   (!empty($surat['tu_signature']) && $surat['tu_signature'] !== '/assets/img/ttd_tu_stamp.png') || 
                   (!empty($meta['tu_signature']) && $meta['tu_signature'] !== '/assets/img/ttd_tu_stamp.png') || 
                   !empty($meta['is_tu_tte_uploaded']) || 
                   in_array($surat['status'] ?? '', ['tu_signed', 'sekre_approved', 'completed']);

    if ($isAdminRole || $isKppRole || !$isTuSigned) {
        $tuTtd = '';
        $hasTuTtePdf = false;
    } else {
        $tuTtePdfPath = !empty($meta['tu_tte_pdf_path']) ? $meta['tu_tte_pdf_path'] : (!empty($meta['is_tu_tte_uploaded']) ? ($surat['pdf_path'] ?? '') : '');
        $hasTuTtePdf = !empty($tuTtePdfPath) && !empty($meta['is_tu_tte_uploaded']);
    }

    if ($isAdminRole || !$isKppSigned) {
        $kppTtd = '';
        $kppParaf = '';
    }
}
?>

<?php if ($hasDisposisi): ?>
<!-- LEMBAR DISPOSISI SEKRETARIS & KEPALA STASIUN (1:1 SAMA PERSIS SEKRE-PROCESS) -->
<div class="admin-review-grid-container" style="display: flex; justify-content: center; background: #F8FAFC; padding: 20px; box-sizing: border-box; margin-bottom: 24px;">
    <div class="admin-review-col" style="width: 100%; max-width: 800px;">

        <div class="a4-paper-preview dispo-paper-preview" style="background: #FFFFFF; border: 1px solid #CBD5E1; padding: 2.2cm; box-shadow: 0 4px 20px rgba(0,0,0,0.08); font-family: Arial, sans-serif; color: #000000; position: relative; min-height: 297mm; max-width: 210mm; margin: 0 auto; box-sizing: border-box;">
            <!-- INNER RECTANGULAR BORDER FRAME (GARIS KOTAK SISI SURAT, BUKAN DI UJUNG KERTAS) -->
            <div style="border: 1.5px solid #000000; padding: 14px 18px; box-sizing: border-box; background: #FFFFFF; min-height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <!-- Logo Top Left -->
                    <div style="margin-bottom: 8px; display: flex; align-items: flex-start; justify-content: space-between;">
                        <img src="<?= BASE_URL ?>/assets/img/tvri3.svg" style="height: 38px; width: auto;" alt="TVRI Logo">
                    </div>

                    <!-- Header Title Disposisi -->
                    <div style="text-align: center; margin-bottom: 12px; color: #000000; font-family: Arial, sans-serif;">
                        <div style="font-size: 12.5px; font-weight: 700; letter-spacing: 0.2px;">DISPOSISI</div>
                        <div style="font-size: 12.5px; font-weight: 700; letter-spacing: 0.2px; margin-top: 1px;">KEPALA TVRI STASIUN JAWA TIMUR</div>
                        <div style="font-size: 12.5px; font-weight: 700; letter-spacing: 0.2px; margin-top: 1px;">LPP TELEVISI REPUBLIK INDONESIA</div>
                    </div>

                    <!-- Top Metadata Section -->
                    <div style="padding: 2px 0; margin-bottom: 10px; background: transparent;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 11.5px; line-height: 1.4; color: #000000; font-family: Arial, sans-serif;">
                            <tr>
                                <td style="width: 100px; vertical-align: top; padding: 1.5px 0;">Nomor Agenda</td>
                                <td style="width: 12px; vertical-align: top; padding: 1.5px 0;">:</td>
                                <td colspan="4" style="vertical-align: top; padding: 1.5px 0; font-weight: normal;"><?= htmlspecialchars($meta['nomor_agenda'] ?? ($disposisi['nomor_agenda'] ?? '')) ?></td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 1.5px 0;">Tanggal</td>
                                <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                                <td colspan="4" style="vertical-align: top; padding: 1.5px 0;"><?= htmlspecialchars($meta['tanggal_agenda'] ?? ($disposisi['tanggal_agenda'] ?? date('d F Y', strtotime($surat['tanggal_surat'])))) ?></td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 1.5px 0;">Dari</td>
                                <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                                <td style="vertical-align: top; padding: 1.5px 0; width: 38%;">
                                    <?= htmlspecialchars($meta['dari'] ?? ($disposisi['dari'] ?? 'Kepala Sub Bagian Tata Usaha')) ?>
                                </td>
                                
                                <td style="width: 90px; vertical-align: top; padding: 1.5px 0;">Nomor Surat</td>
                                <td style="width: 12px; vertical-align: top; padding: 1.5px 0;">:</td>
                                <td style="vertical-align: top; padding: 1.5px 0; font-weight: normal;">
                                    <?= htmlspecialchars($surat['nomor_surat'] ?: ($meta['nomor_surat_disposisi'] ?? ($meta['nomor_surat'] ?? ''))) ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 1.5px 0;">Hal</td>
                                <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                                <td colspan="4" style="vertical-align: top; padding: 1.5px 0; font-weight: normal;"><?= htmlspecialchars($meta['hal_disposisi'] ?? ($meta['perihal_disposisi'] ?? ($disposisi['hal'] ?? ($surat['perihal'] ?? '')))) ?></td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 2px 0;">Sifat</td>
                                <td style="vertical-align: top; padding: 2px 0;">:</td>
                                <td colspan="4" style="vertical-align: top; padding: 2px 0;">
                                    <?php 
                                    $svgCheckModal = '<span style="display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; position: relative;"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block;"><path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
                                    $isSekreOrKepstaRole = in_array($viewRole, ['sekretaris', 'sekre', 'kepala_stasiun', 'kepsta']);
                                    $isTargetSurat = ($isNotaDinas || $isPrinsip || $isProduksi);
                                    if ($isSekreOrKepstaRole && $isTargetSurat) {
                                        $rawSifatDispo = (!empty($disposisi['kepsta_signed_at']) && !empty($disposisi['sifat_surat']))
                                            ? $disposisi['sifat_surat']
                                            : (!empty($surat['status']) && $surat['status'] === 'completed' && !empty($disposisi['sifat_surat']) ? $disposisi['sifat_surat'] : '');
                                    } else {
                                        $rawSifatDispo = !empty($disposisi['sifat_surat']) ? $disposisi['sifat_surat'] : (!empty($meta['sifat_disposisi']) ? $meta['sifat_disposisi'] : (!empty($meta['sifat_surat']) ? $meta['sifat_surat'] : 'biasa'));
                                    }
                                    $sifatSelectedDispo = strtolower(trim($rawSifatDispo));
                                    
                                    $isItemChecked = function($item, $checklist) {
                                        if (empty($item) || empty($checklist)) return false;
                                        if (!is_array($checklist)) return false;
                                        $itemTrim = strtolower(trim($item));
                                        foreach ($checklist as $c) {
                                            if (strtolower(trim($c)) === $itemTrim) return true;
                                        }
                                        return false;
                                    };
                                    ?>
                                    <div style="display: flex; gap: 24px; align-items: center; font-size: 11.5px; font-family: Arial, sans-serif;">
                                        <label style="display: flex; align-items: center; gap: 5px;">Biasa <span style="display: inline-flex; align-items: center; justify-content: center; width: 13px; height: 13px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatSelectedDispo === 'biasa') ? $svgCheckModal : '' ?></span></label>
                                        <label style="display: flex; align-items: center; gap: 5px;">Penting <span style="display: inline-flex; align-items: center; justify-content: center; width: 13px; height: 13px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatSelectedDispo === 'penting') ? $svgCheckModal : '' ?></span></label>
                                        <label style="display: flex; align-items: center; gap: 5px;">Segera <span style="display: inline-flex; align-items: center; justify-content: center; width: 13px; height: 13px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatSelectedDispo === 'segera') ? $svgCheckModal : '' ?></span></label>
                                        <label style="display: flex; align-items: center; gap: 5px;">Rahasia <span style="display: inline-flex; align-items: center; justify-content: center; width: 13px; height: 13px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatSelectedDispo === 'rahasia') ? $svgCheckModal : '' ?></span></label>
                                    </div>

                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- 3-Column Recipient Checklist Table (28 items with integrated square checkbox cells matching photo 1:1) -->
                    <?php 
                    $recipientsCol1 = [
                        'Kasubbag Tata Usaha', 'KPP Program', 'KPP Berita', 'KPP PU', 'KPP Teknik',
                        'KPP Keuangan', 'KPP Umum', 'KPP Media Baru', 'Kapel Produksi Program', 'Kapel Promo'
                    ];
                    $recipientsCol2 = [
                        'Kapel Monev Program', 'Kapel Dokumentasi & Distribusi Audio Video', 'Kapel CA & OR',
                        'Kapel Berita', 'Kapel Monev Berita', 'Kapel Penjualan & Pemasaran', 'Kapel IT',
                        'Kapel Produksi & Penyiaran Teknik', 'Kapel Transmisi', 'Kapel Evaluasi & Pelaporan Keuangan'
                    ];
                    $recipientsCol3 = [
                        'Kapel Pengelola SDM & RB', 'Kapel Perlengkapan & Sarpras', 'Kapel Pengelola BMN',
                        'Kapel Humas & Protokol', 'Kapel Konten Kreatif Digital', 'Kapel Analitik & Distribusi Konten',
                        'PPK I', 'PPK II'
                    ];
                    $disposisiItemsList = [
                        'Diteliti / Dis', 'Dipertimbangkan', 'Untuk Diketahui / Diperhatikan',
                        'Mewakili / Menghadiri / Mengikuti', 'Dikoordinasikan', 'Ditampung Permasalahannya',
                        'Peringatkan / Pendekatan', 'Pendapat / Analisa / Saran', 'Konsep Jawaban / Sambutan',
                        'Konsep Laporan', 'Data Diolah', 'Ditindaklanjuti', 'Diagendakan / Dijadwalkan',
                        'Harap Dibantu', 'File'
                    ];
                    ?>
                    <table style="width: 100%; border-collapse: collapse; font-size: 10.5px; color: #000000; margin-bottom: 10px;" border="1">
                        <?php 
                        for ($i = 0; $i < 10; $i++): 
                            $item1 = $recipientsCol1[$i] ?? null;
                            $item2 = $recipientsCol2[$i] ?? null;
                            $item3 = $recipientsCol3[$i] ?? null;
                        ?>
                            <tr>
                                <td style="width: 22px; text-align: center; vertical-align: middle; padding: 1px; position: relative;">
                                    <?php if ($item1 && $isItemChecked($item1, $dispoChecklist)): ?>
                                        <?= $svgCheckModal ?>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 2px 5px; width: 30%; vertical-align: middle;"><?= htmlspecialchars($item1 ?? '') ?></td>

                                <td style="width: 22px; text-align: center; vertical-align: middle; padding: 1px; position: relative;">
                                    <?php if ($item2 && $isItemChecked($item2, $dispoChecklist)): ?>
                                        <?= $svgCheckModal ?>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 2px 5px; width: 30%; vertical-align: middle;"><?= htmlspecialchars($item2 ?? '') ?></td>

                                <td style="width: 22px; text-align: center; vertical-align: middle; padding: 1px; position: relative;">
                                    <?php if ($item3 && $isItemChecked($item3, $dispoChecklist)): ?>
                                        <?= $svgCheckModal ?>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 2px 5px; width: 30%; vertical-align: middle;"><?= htmlspecialchars($item3 ?? '') ?></td>
                            </tr>
                        <?php endfor; ?>
                    </table>

                    <!-- Section Disposisi & Catatan -->
                    <div style="display: flex; gap: 12px; width: 100%; margin-bottom: 10px; font-size: 10.5px; color: #000000; align-items: stretch;">
                        <!-- Left: Disposisi (Tabel Grid Thin Border) -->
                        <div style="width: 44%; display: flex; flex-direction: column;">
                            <div style="font-weight: normal; margin-bottom: 4px;">Disposisi :</div>
                            <table style="width: 100%; border-collapse: collapse; font-size: 10.5px; color: #000000; background: #ffffff;" border="1">
                                <?php foreach ($disposisiItemsList as $dItem): ?>
                                    <tr>
                                        <td style="width: 22px; text-align: center; vertical-align: middle; padding: 1px; position: relative;">
                                            <?php if ($isItemChecked($dItem, $dispoChecklist)): ?>
                                                <?= $svgCheckModal ?>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1.5px 5px;"><?= htmlspecialchars($dItem) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>

                            <div style="margin-top: 6px;">
                                <div style="font-weight: normal; margin-bottom: 2px;">Sekretariat :</div>
                                <div style="font-size: 9.5pt; line-height: 1.3; white-space: pre-line; font-weight: normal; color: #003399; min-height: 30px;">
                                    <?= htmlspecialchars(!empty($disposisi['catatan_sekre']) ? $disposisi['catatan_sekre'] : (!empty($meta['catatan_sekretariat']) ? $meta['catatan_sekretariat'] : '')) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Catatan -->
                        <div style="flex: 1; display: flex; flex-direction: column;">
                            <div style="font-weight: normal; margin-bottom: 4px; margin-left: 40px;">Catatan :</div>
                            <div style="font-size: 9.5pt; line-height: 1.4; white-space: pre-line; flex: 1; min-height: 100px; max-height: 220px; padding: 4px; color: #003399; font-weight: normal; overflow: hidden;">
                                <?= htmlspecialchars($disposisi['catatan_kepsta'] ?? '') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Signature Section -->
                <div>
                    <div style="display: flex; justify-content: flex-end; align-items: flex-start; font-size: 11.5px; color: #000000; margin-top: 6px;">
                        <div style="width: 280px; text-align: left; line-height: 1.35;">
                            <div>Surabaya, <?= date('d F Y', strtotime($disposisi['kepsta_signed_at'] ?? date('Y-m-d'))) ?></div>
                            <div>Kepala TVRI Stasiun Jawa Timur</div>
                            <div style="height: 48px; margin: 4px 0; display: flex; align-items: center; justify-content: flex-start;">
                                <?php if (!empty($kepstaSigRaw)): ?>
                                    <img src="<?= getImgUrl($kepstaSigRaw) ?>" style="max-height: 45px; width: auto; object-fit: contain; display: block;" alt="TTD Kepsta">
                                <?php else: ?>
                                    <div style="height: 45px;"></div>
                                <?php endif; ?>
                            </div>
                            <div style="text-decoration: underline; font-weight: normal; color: #000000;"><?= htmlspecialchars($kepstaNamaDisplay) ?></div>
                            <div style="color: #000000;"><?= htmlspecialchars($kepstaNipDisplay) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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
            <?php if (!$isMainPage): ?>
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
<?php elseif ($isFromUploadLampiran): ?>
<!-- PREVIEW SURAT UPLOAD LAMPIRAN (DIUNGGAH OLEH SEKRETARIS) -->
<div class="admin-review-grid-container" style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 20px; align-items: stretch; width: 100%; margin-bottom: 28px; <?= (!empty($pdfLampirans) && count($pdfLampirans) === 1 && getPdfPageCount($pdfLampirans[0]) === 1) ? 'justify-content: center;' : '' ?>">
    <?php if (!empty($pdfLampirans)): ?>
        <?php foreach ($pdfLampirans as $idx => $pdfPath): 
            $pageCount = getPdfPageCount($pdfPath);
            $pdfUrl = getImgUrl($pdfPath);
            for ($p = 1; $p <= $pageCount; $p++):
                $subTitle = $pageCount === 1 ? '1 Lembar' : "Halaman $p dari $pageCount";
        ?>
            <div class="pdf-lampiran-wrapper-col" style="<?= ($pageCount === 1 && count($pdfLampirans) === 1) ? 'max-width: 800px; width: 100%; flex: 0 0 800px;' : '' ?>">
                <div style="padding: 10px 14px; background: #EFF6FF; border: 1px solid #93C5FD; border-radius: 10px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;" class="no-print">
                    <div style="display: flex; align-items: center; gap: 10px; min-width: 0; overflow: hidden;">
                        <i class="bi bi-file-earmark-pdf-fill" style="color: #0284C7; font-size: 22px; flex-shrink: 0;"></i>
                        <div style="min-width: 0; overflow: hidden;">
                            <div style="font-size: 13px; font-weight: 800; color: #1E40AF; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Berkas Upload Lampiran <?= count($pdfLampirans) > 1 ? ($idx + 1) : '' ?></div>
                            <div style="font-size: 11px; color: #1D4ED8;"><?= $subTitle ?></div>
                        </div>
                    </div>
                    <span style="font-size: 10.5px; font-weight: 800; background: #0284C7; color: #FFFFFF; padding: 3px 10px; border-radius: 20px; flex-shrink: 0;">HALAMAN <?= $p ?></span>
                </div>

                <div class="pdf-lampiran-card-preview" style="background: #FFFFFF; border: 1.5px solid #CBD5E1; padding: 0 !important; box-shadow: 0 4px 20px rgba(0,0,0,0.06); position: relative; aspect-ratio: 210 / 297; margin: 0 auto; box-sizing: border-box; border-radius: 8px; width: 100%; overflow: hidden;">
                    <canvas class="pdf-canvas-item" data-pdf-url="<?= $pdfUrl ?>" data-page-num="<?= $p ?>" style="width: 100%; height: auto; object-fit: contain; display: block; background: #FFFFFF; border: none; margin: 0; padding: 0;"></canvas>
                    <img class="pdf-img-item" style="width: 100%; height: auto; object-fit: contain; display: none; margin: 0 auto; border: none; padding: 0;">
                </div>
            </div>
        <?php endfor; endforeach; ?>
    <?php endif; ?>
</div>
<?php elseif ($isNotaDinas): ?>
<!-- TAMPILAN POPUP UNTUK SURAT NOTA DINAS (SINGLE A4 CENTERED) -->
<div class="admin-review-grid-container" style="display: flex; justify-content: center; background: #F8FAFC; padding: 20px; box-sizing: border-box;">
    <div class="admin-review-col" style="width: 100%; max-width: 800px;">
        <div class="a4-paper-preview surat-paper-preview" style="background: #FFFFFF; border: 1px solid #CBD5E1; padding: 2.5cm; box-shadow: 0 4px 20px rgba(0,0,0,0.06); font-family: Arial, sans-serif; color: #000000; position: relative; min-height: 270mm; margin: 0 auto; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <!-- Logo Top Left -->
                <div style="margin-bottom: 18px;">
                    <img src="<?= BASE_URL ?>/assets/img/tvri3.svg" style="height: 58px; width: auto;" alt="TVRI Logo">
                </div>

                <!-- Title Centered -->
                <div style="text-align: center; margin-bottom: 18px;">
                    <h2 style="font-size: 14.5px; font-weight: normal; letter-spacing: 0.5px; margin: 0; color: #000000; text-transform: uppercase; text-decoration: underline; font-family: Arial, sans-serif;">NOTA DINAS</h2>
                    <div style="font-size: 13px; color: #111111; margin-top: 4px;">Nomor : <?= htmlspecialchars($nomorSuratDisplay ?: '          /UM.03.02/I1.1.1/IX/2026') ?></div>
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
                        <td style="vertical-align: top; padding: 1.5px 0;"><?= htmlspecialchars($meta['pemohon_nopeg'] ?? $meta['pemohon_nip'] ?? '') ?></td>
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

                <!-- Signature Block -->
                <?php if (!empty($pemohonTtd) || !empty($meta['pemohon_nama'])): ?>
                    <!-- 2 Column Signature Block -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 24px; margin-bottom: 24px;">
                        <div style="width: 250px; text-align: left; font-size: 12.5px; line-height: 1.15; color: #000000;">
                            <div>Kepala Sub Bagian Tata Usaha</div>
                            <div>TVRI Stasiun Jawa Timur</div>
                            <div style="height: 55px; margin: 4px 0; display: flex; align-items: center; justify-content: flex-start;">
                                <?php if (!empty($tuTtd)): ?>
                                    <img src="<?= getImgUrl($tuTtd) ?>" style="max-height: 50px; object-fit: contain;">
                                <?php endif; ?>
                            </div>

                            <div style="position: relative; display: inline-block;">
                                <?php if (!empty($kppParaf)): ?>
                                    <div style="position: absolute; right: 100%; top: 50%; transform: translateY(-50%); margin-right: 8px; width: 56px; height: 42px; white-space: nowrap; display: flex; align-items: center; justify-content: center; z-index: 10;">
                                        <img src="<?= getImgUrl($kppParaf) ?>" class="kpp-paraf-img" style="max-height: 48px; width: auto; max-width: 70px; object-fit: contain; display: block; visibility: visible;" alt="Paraf KPP">
                                    </div>
                                <?php endif; ?>
                                <div style="text-decoration: underline; font-weight: 500;"><?= htmlspecialchars($tuNamaDisplay) ?></div>
                                <div><?= htmlspecialchars($tuNipDisplay) ?></div>
                            </div>
                        </div>

                        <div style="width: 230px; text-align: center; font-size: 12.5px; line-height: 1.15; color: #000000;">
                            <div>Hormat Saya,</div>
                            <div style="height: 55px; margin: 4px 0; display: flex; align-items: center; justify-content: center;">
                                <?php if (!empty($pemohonTtd)): ?>
                                    <img src="<?= getImgUrl($pemohonTtd) ?>" style="max-height: 50px; object-fit: contain;">
                                <?php endif; ?>
                            </div>
                            <div style="text-decoration: underline; font-weight: 500;"><?= htmlspecialchars($meta['pemohon_nama'] ?? '') ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Single Column Signature Block -->
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
                                <?php if (!empty($kppParaf)): ?>
                                    <div style="position: absolute; right: 100%; top: 50%; transform: translateY(-50%); margin-right: 8px; width: 56px; height: 42px; white-space: nowrap; display: flex; align-items: center; justify-content: center; z-index: 10;">
                                        <img src="<?= getImgUrl($kppParaf) ?>" class="kpp-paraf-img" style="max-height: 48px; width: auto; max-width: 70px; object-fit: contain; display: block; visibility: visible;" alt="Paraf KPP">
                                    </div>
                                <?php endif; ?>
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
</div>

<?php else: ?>
<!-- TAMPILAN POPUP UNTUK SURAT PERMOHONAN & NOTA DINAS (2 COLUMN GRID SIDE-BY-SIDE - SAME AS TU PROCESS) -->
<div class="admin-review-grid-container" style="display: flex; flex-direction: row; gap: 24px; align-items: stretch; flex-wrap: nowrap; margin-bottom: 28px; width: 100%;">
    
    <!-- SURAT 1 (SISI KIRI): SURAT PERMOHONAN -->
    <div class="admin-review-col admin-review-col-page1" style="flex: 1 1 0px; min-width: 0; width: 50%; max-width: 50%; box-sizing: border-box; display: flex; flex-direction: column;">
        <div class="card-panel-variant a4-paper-preview surat-paper-preview" style="padding: 2.2cm 2.5cm; border-radius: 18px; background: #FFFFFF; border: 1.5px solid #CBD5E1; box-shadow: 0 4px 20px rgba(0,0,0,0.06); font-family: Arial, sans-serif; color: #000000; position: relative; min-height: 270mm; margin: 0 auto; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; width: 100%;">
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
        <div class="card-panel-variant a4-paper-preview surat-paper-preview" style="padding: 2.5cm; border-radius: 18px; background: #FFFFFF; border: 1.5px solid #CBD5E1; box-shadow: 0 4px 20px rgba(0,0,0,0.06); font-family: Arial, sans-serif; color: #000000; position: relative; min-height: 270mm; margin: 0 auto; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; width: 100%;">
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
                            <?php if (!empty($kppParaf)): ?>
                                <div style="position: absolute; right: 100%; top: 50%; transform: translateY(-50%); margin-right: 8px; width: 56px; height: 42px; white-space: nowrap; display: flex; align-items: center; justify-content: center; z-index: 10;">
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
                <?php 
                $adminBarcodeLink = !empty($meta['admin_gdrive_web_link']) 
                    ? $meta['admin_gdrive_web_link'] 
                    : (!empty($meta['gdrive_web_link']) 
                        ? $meta['gdrive_web_link'] 
                        : (!empty($surat['gdrive_web_link']) && !str_contains($surat['gdrive_web_link'], '/file/d/') 
                            ? $surat['gdrive_web_link'] 
                            : ''));

                $qrUrlRev = !empty($adminBarcodeLink) ? $adminBarcodeLink : 'https://drive.google.com';
                $qrSrcRev = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrUrlRev);
                ?>
                <div style="display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 6px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <a href="<?= htmlspecialchars($qrUrlRev) ?>" target="_blank" title="Scan / Klik untuk membuka URL Google Drive">
                            <img src="<?= $qrSrcRev ?>" style="width: 48px; height: 48px; border: 1.5px solid #000000; background: #FFFFFF; padding: 2px;" alt="Barcode QR Google Drive">
                        </a>
                    </div>
                </div>

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
$lampiranRotations = $meta['lampiran_page_rotations'] ?? [];
if (is_string($lampiranRotations)) {
    $lampiranRotations = json_decode($lampiranRotations, true) ?? [];
}
$suratIdForRot = (int)($surat['id'] ?? 0);
?>

<?php if (!empty($pdfLampirans) && empty($hasTuTtePdf) && !$isFromUploadLampiran): ?>
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
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="font-size: 10.5px; font-weight: 800; background: #DC2626; color: #FFFFFF; padding: 3px 10px; border-radius: 20px; flex-shrink: 0;">LEMBAR <?= $p ?></span>
                        </div>
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

                await renderSingleServerCanvas(targetCanvas, pdf, p, pdfUrl);
            }
        } catch (err) {
            console.error('Error rendering PDF canvas for URL:', pdfUrl, err);
        }
    }
}

async function renderSingleServerCanvas(targetCanvas, pdfDoc, pageNum, pdfUrl) {
    try {
        const page = await pdfDoc.getPage(pageNum);
        const parentContainer = targetCanvas.parentElement;
        const availableWidth = parentContainer ? (parentContainer.clientWidth - 20) : 650;

        const nativeRot = page.rotate || 0;
        const rotKey = `${pdfUrl}_p${pageNum}`;
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
    } catch (err) {
        console.error('Error rendering single server canvas:', err);
    }
}

async function rotateAdminLampiranPage(pdfUrl, pageNum, suratId) {
    const rotKey = `${pdfUrl}_p${pageNum}`;
    const currentRot = (window.lampiranRotations && window.lampiranRotations[rotKey]) ? parseInt(window.lampiranRotations[rotKey], 10) : 0;
    const newRot = (currentRot + 180) % 360;

    if (!window.lampiranRotations) window.lampiranRotations = {};
    window.lampiranRotations[rotKey] = newRot;

    // Find the canvas for this page and re-render
    const foundCanvases = document.querySelectorAll('.pdf-canvas-item');
    for (const fc of foundCanvases) {
        if (fc.getAttribute('data-pdf-url') === pdfUrl && parseInt(fc.getAttribute('data-page-num'), 10) === pageNum) {
            fc.style.display = 'block';
            const img = fc.nextElementSibling;
            if (img && img.classList.contains('pdf-img-item')) {
                img.style.display = 'none';
            }
            if (typeof pdfjsLib !== 'undefined') {
                try {
                    const pdfDoc = await pdfjsLib.getDocument(pdfUrl).promise;
                    await renderSingleServerCanvas(fc, pdfDoc, pageNum, pdfUrl);
                } catch (e) {
                    console.error('Error re-rendering rotated canvas:', e);
                }
            }
            break;
        }
    }

    // Also update pdf_template canvases if loaded in background
    if (typeof renderPdfTemplateCanvases === 'function') {
        renderPdfTemplateCanvases();
    }

    // Optionally save to database if suratId is valid
    if (suratId > 0) {
        try {
            const formData = new FormData();
            formData.append('surat_id', suratId);
            formData.append('lampiran_page_rotations', JSON.stringify(window.lampiranRotations));
            await fetch('<?= defined("BASE_URL") ? BASE_URL : "" ?>/surat/update-rotation', {
                method: 'POST',
                body: formData
            });
        } catch (e) {
            console.warn('Could not save rotation to database:', e);
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderServerPdfCanvases);
} else {
    renderServerPdfCanvases();
}
</script>
