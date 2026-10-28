<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

<?php 
$meta = $surat['meta'] ?? [];
$defaultAgenda = $meta['nomor_agenda'] ?? '';
$dispoData = is_array($disposisi ?? null) ? $disposisi : [];
$sifatNotaDinas = !empty($meta['sifat_surat']) ? $meta['sifat_surat'] : 'Biasa';
$halDisposisi = $meta['hal_disposisi'] ?? $dispoData['hal'] ?? '';
$rawSifatDispo = !empty($dispoData['sifat_surat']) ? $dispoData['sifat_surat'] : (!empty($meta['sifat_disposisi']) ? $meta['sifat_disposisi'] : '');
$sifatDisposisiSelected = strtolower(trim($rawSifatDispo));
$katSurat = $meta['kategori_surat'] ?? 'Surat Dinas';
$isDinas = ($katSurat === 'Surat Dinas');
$isProduksi = (($surat['type'] ?? '') === 'izin_produksi' || str_contains(strtolower($meta['kategori_surat'] ?? ''), 'produksi'));
$isPrinsip = (($surat['type'] ?? '') === 'izin_prinsip' || str_contains(strtolower($meta['kategori_surat'] ?? ''), 'prinsip'));
$isNotaDinas = (($surat['type'] ?? '') === 'nota_dinas' || ($meta['kategori_surat'] ?? '') === 'Surat Nota Dinas');
if ($isNotaDinas || $isPrinsip || $isProduksi) {
    $sifatDisposisiSelected = '';
}
$tglIndo = !empty($surat['tanggal_surat']) ? date('d F Y', strtotime($surat['tanggal_surat'])) : date('d F Y');
$monthsIndo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$todayAgendaIndo = date('j') . ' ' . $monthsIndo[(int)date('n')] . ' ' . date('Y');
$defaultTanggalAgenda = $todayAgendaIndo;
$defaultTanggalAgendaYmd = date('Y-m-d');
$pemohonTtd = $meta['pemohon_signature_data'] ?? $meta['pemohon_signature'] ?? '';

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

$tuTtdRaw = !empty($surat['tu_signature']) ? $surat['tu_signature'] : (!empty($meta['tu_signature']) ? $meta['tu_signature'] : '');
$tuTtd = ($tuTtdRaw === '/assets/img/ttd_tu_stamp.png') ? '' : $tuTtdRaw;

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

$kepstaAccount = User::getSingleUserByRole('kepala_stasiun') ?: [];
$kepstaNamaDisplay = $kepstaAccount['name'] ?? 'Akbar Sahidi, S.Sos., M.Si';
$kepstaNipRaw = $kepstaAccount['nip'] ?? '197005121995031002';
$kepstaNipDisplay = !empty($kepstaNipRaw) ? (str_starts_with(strtoupper(trim($kepstaNipRaw)), 'NIP') ? trim($kepstaNipRaw) : 'NIP. ' . trim($kepstaNipRaw)) : 'NIP. 197005121995031002';
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

    /* SEKRE Agenda Inputs Grid */
    .sekre-agenda-inputs-grid {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 24px;
        width: 100%;
        box-sizing: border-box;
    }
    .sekre-agenda-col-agenda {
        flex: 1;
        min-width: 180px;
        max-width: 220px;
    }
    .sekre-agenda-col-tanggal {
        flex: 1;
        min-width: 200px;
        max-width: 240px;
    }
    .sekre-agenda-col-hal {
        flex: 2;
        min-width: 220px;
        max-width: 400px;
    }

    /* SEKRE Bottom Action Bar */
    .sekre-bottom-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        border-top: 1px solid #E2E8F0;
        padding-top: 20px;
        margin-top: 8px;
    }
    .sekre-bottom-left {
        display: flex;
        align-items: center;
    }
    .sekre-bottom-right {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .sekre-sub-actions {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .btn-action-hapus {
        background: #FEF2F2;
        color: #DC2626;
        border: 1.5px solid #FCA5A5;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 11px 20px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-action-hapus:hover {
        background: #FEE2E2;
        border-color: #EF4444;
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
    .btn-action-teruskan-kepsta {
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
    .btn-action-teruskan-kepsta:hover {
        background-color: #1D4ED8;
        border-color: #1D4ED8;
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
        /* Input nomor agenda, tanggal dan hal sama panjangnya di mobile */
        .sekre-agenda-inputs-grid {
            flex-direction: column !important;
            gap: 12px !important;
            width: 100% !important;
        }
        .sekre-agenda-col-agenda,
        .sekre-agenda-col-tanggal,
        .sekre-agenda-col-hal {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 100% !important;
            flex: 1 1 100% !important;
            box-sizing: border-box !important;
        }
        .sekre-agenda-inputs-grid input[type="text"] {
            width: 100% !important;
            box-sizing: border-box !important;
        }

        /* Bottom Action Buttons Responsiveness */
        .sekre-bottom-actions {
            flex-direction: column-reverse !important;
            align-items: stretch !important;
            gap: 12px !important;
            padding-top: 16px !important;
            width: 100% !important;
        }
        .sekre-bottom-right {
            display: flex !important;
            flex-direction: column !important;
            gap: 10px !important;
            width: 100% !important;
        }
        .btn-action-teruskan-kepsta {
            order: -1 !important;
            width: 100% !important;
            padding: 14px 20px !important;
            font-size: 15.5px !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 14px rgba(37,99,235,0.28) !important;
        }
        .sekre-sub-actions {
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
        .sekre-bottom-left {
            width: 100% !important;
        }
        .btn-action-hapus {
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
        #disposisiReviewModal > div,
        #revisiModal > div,
        #agendaWarningModal > div,
        #deleteSuratModal > div {
            width: 100% !important;
            max-width: 100% !important;
            border-radius: 18px !important;
        }
        #disposisiReviewModal .a4-paper-preview {
            padding: 12px !important;
        }
    }

    @media print {
        @page { size: A4 portrait; margin: 0; }
        html, body { width: 210mm !important; height: auto !important; margin: 0 !important; padding: 0 !important; background: #ffffff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .no-print, button, .btn-btn { display: none !important; }
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
                <i class="bi bi-file-earmark-plus-fill" style="color: #D97706;"></i> Review & Disposisi Sekretaris
            </h1>
        </div>
    </div>

    <?php 
    $tuTtePdfPath = !empty($meta['tu_tte_pdf_path']) ? $meta['tu_tte_pdf_path'] : (!empty($meta['is_tu_tte_uploaded']) ? ($surat['pdf_path'] ?? '') : '');
    $hasTuTtePdf = !empty($tuTtePdfPath) && !empty($meta['is_tu_tte_uploaded']);
    ?>

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
                        Nomor : <?= htmlspecialchars($surat['nomor_surat'] ?: '          /UM.03.02/I1.1.1/IX/2026') ?>
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
                            <td style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($sifatNotaDinas) ?></td>
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

                    $qrUrlSekre = !empty($adminBarcodeLink) ? $adminBarcodeLink : 'https://drive.google.com';
                    $qrSrcSekre = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrUrlSekre);
                    ?>
                    <div style="display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 6px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <a href="<?= htmlspecialchars($qrUrlSekre) ?>" target="_blank" title="Scan / Klik untuk membuka URL Google Drive">
                                <img src="<?= $qrSrcSekre ?>" style="width: 48px; height: 48px; border: 1.5px solid #000000; background: #FFFFFF; padding: 2px;" alt="Barcode QR Google Drive">
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
        '<div class="a4-paper-preview" style="background: #FFFFFF; border: none; padding: 3cm; font-family: Arial, sans-serif; color: #000000; position: relative; min-height: 270mm; margin: 0 auto; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between;">' .
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

    <?php if (!empty($pdfLampirans) && empty($hasTuTtePdf)): ?>
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

    <!-- CARD 2: INPUT FORM DISPOSISI SEKRETARIS -->
    <div class="card-panel-variant" style="padding: 32px; border-radius: 18px; background: #FFFFFF; border: 1.5px solid #D97706; box-shadow: 0 10px 25px -5px rgba(217, 119, 6, 0.1);">
        <div style="margin-bottom: 24px; padding-bottom: 14px; border-bottom: 1px solid #E2E8F0;">
            <h2 style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-pencil-square" style="color: #D97706;"></i> Form Input Lembar Disposisi Sekretaris
            </h2>
        </div>

        <form action="<?= BASE_URL ?>/surat/sekre-submit/<?= $surat['id'] ?>" method="POST" enctype="multipart/form-data" id="sekreSubmitForm" autocomplete="off">
            <?= Session::csrfField() ?>
            <input type="hidden" name="action_type" id="inActionType" value="approve">
            <input type="hidden" name="nomor_surat" value="<?= htmlspecialchars($surat['nomor_surat'] ?? '') ?>">
            <input type="hidden" name="dari" value="<?= htmlspecialchars($meta['dari'] ?? 'Kepala Sub Bagian Tata Usaha TVRI Jawa Timur') ?>">
            <input type="hidden" name="perihal" value="<?= htmlspecialchars($surat['perihal']) ?>">



            <!-- INPUT 1, 2, 3 HORIZONTAL IN 1 ROW (LEFT TO RIGHT, RESPONSIVE 100% ON MOBILE) -->
            <div class="sekre-agenda-inputs-grid">
                <!-- 1. Nomor Agenda -->
                <div class="sekre-agenda-col-agenda">
                    <label style="display: block; font-size: 13.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">
                        1. Nomor Agenda
                    </label>
                    <input type="text" name="nomor_agenda" id="inNomorAgenda" value="<?= htmlspecialchars($defaultAgenda) ?>" placeholder="Ketik nomor agenda..." required
                           oninput="syncDisposisiPreview()"
                           style="width: 100%; padding: 9px 12px; border-radius: 10px; border: 1.5px solid #CBD5E1; font-size: 13.5px; font-weight: normal; color: #1E293B; background: #FFFFFF; box-sizing: border-box;">
                </div>

                <!-- 2. Tanggal -->
                <div class="sekre-agenda-col-tanggal">
                    <label style="display: block; font-size: 13.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">
                        2. Tanggal
                    </label>
                    <div class="modern-datepicker-wrapper">
                        <input type="text" name="tanggal_agenda" id="inTanggalAgenda"
                               value="<?= htmlspecialchars($defaultTanggalAgenda) ?>"
                               data-default-date="<?= htmlspecialchars($defaultTanggalAgendaYmd) ?>"
                               data-date-format="j F Y"
                               required
                               class="form-input-custom modern-datepicker"
                               placeholder="Pilih tanggal agenda..."
                               oninput="syncDisposisiPreview()"
                               onchange="syncDisposisiPreview()">
                        <span class="datepicker-calendar-icon" title="Buka Kalender">
                            <i class="bi bi-calendar2-event-fill"></i>
                        </span>
                    </div>
                </div>

                <!-- 3. Hal Disposisi -->
                <div class="sekre-agenda-col-hal">
                    <label style="display: block; font-size: 13.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">
                        3. Hal Disposisi
                    </label>
                    <input type="text" name="hal_disposisi" id="inHalDisposisi" value="<?= htmlspecialchars($halDisposisi) ?>" placeholder="Ketik hal disposisi..."
                           oninput="syncDisposisiPreview()"
                           style="width: 100%; padding: 9px 12px; border-radius: 10px; border: 1.5px solid #CBD5E1; font-size: 13.5px; font-weight: normal; color: #1E293B; background: #FFFFFF; box-sizing: border-box;">
                </div>
            </div>

            <!-- FORM ACTIONS -->
            <div class="sekre-bottom-actions">
                <div class="sekre-bottom-left">
                    <button type="button" onclick="openDeleteSuratModal()" class="btn-btn btn-action-hapus">
                        <i class="bi bi-trash3-fill"></i> Hapus Surat
                    </button>
                </div>

                <div class="sekre-bottom-right">
                    <div class="sekre-sub-actions">
                        <a href="<?= BASE_URL ?>/dashboard" class="btn-btn btn-btn-secondary btn-action-batal">
                            Batal
                        </a>

                        <button type="button" onclick="openRevisiModal()" class="btn-btn btn-action-revisi">
                            <i class="bi bi-arrow-counterclockwise"></i> Revisi
                        </button>
                    </div>

                    <button type="button" onclick="handleTeruskanKepsta()" class="btn-btn btn-btn-primary btn-action-teruskan-kepsta">
                        <i class="bi bi-send-fill"></i> Teruskan ke Kepsta
                    </button>
                </div>
            </div>

            <!-- Hidden Catatan Revisi Field -->
            <input type="hidden" name="catatan_revisi" id="inCatatanRevisi">
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 1: REVIEW DISPOSISI SHEET (EXACT LAYOUT 1:1 MATCHING PHOTO) -->
<!-- ========================================== -->
<div id="disposisiReviewModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px; overflow-y: auto;">
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
            <div class="a4-paper-preview" style="background: #FFFFFF; border: 1px solid #CBD5E1; padding: 1.2cm; box-shadow: 0 4px 20px rgba(0,0,0,0.08); font-family: Arial, sans-serif; color: #000000; position: relative; min-height: 297mm; max-width: 210mm; margin: 0 auto; box-sizing: border-box;">
                <!-- INNER RECTANGULAR BORDER FRAME (GARIS KOTAK SISI SURAT, BUKAN DI UJUNG KERTAS) -->
                <div style="border: 1.5px solid #000000; padding: 24px; box-sizing: border-box; background: #FFFFFF; min-height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <!-- Logo Top Left -->
                    <div style="margin-bottom: 12px; display: flex; align-items: flex-start; justify-content: space-between;">
                        <img src="<?= BASE_URL ?>/assets/img/tvri3.svg" style="height: 48px; width: auto;" alt="TVRI Logo">
                    </div>

                    <!-- Header Title Disposisi -->
                    <div style="text-align: center; margin-bottom: 20px; color: #000000; font-family: Arial, sans-serif;">
                        <div style="font-size: 13px; font-weight: 600; letter-spacing: 0.2px;">DISPOSISI</div>
                        <div style="font-size: 13px; font-weight: 600; letter-spacing: 0.2px; margin-top: 1px;">KEPALA TVRI STASIUN JAWA TIMUR</div>
                        <div style="font-size: 13px; font-weight: 600; letter-spacing: 0.2px; margin-top: 1px;">LPP TELEVISI REPUBLIK INDONESIA</div>
                    </div>

                    <!-- Top Metadata Section -->
                    <div style="padding: 4px 0; margin-bottom: 16px; background: transparent;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; line-height: 1.6; color: #000000; font-family: Arial, sans-serif;">
                            <tr>
                                <td style="width: 105px; vertical-align: top; padding: 2px 0;">Nomor Agenda</td>
                                <td style="width: 12px; vertical-align: top; padding: 2px 0;">:</td>
                                <td colspan="4" id="modalAgendaText" style="vertical-align: top; padding: 2px 0; font-weight: normal;"><?= htmlspecialchars($defaultAgenda) ?></td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 2px 0;">Tanggal</td>
                                <td style="vertical-align: top; padding: 2px 0;">:</td>
                                <td colspan="4" id="modalTanggalText" style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($defaultTanggalAgenda) ?></td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 2px 0;">Dari</td>
                                <td style="vertical-align: top; padding: 2px 0;">:</td>
                                <td style="vertical-align: top; padding: 2px 0; width: 38%;">
                                    <?= htmlspecialchars($meta['dari'] ?? 'Kepala Sub Bagian Tata Usaha') ?>
                                </td>
                                
                                <td style="width: 95px; vertical-align: top; padding: 2px 0;">Nomor Surat</td>
                                <td style="width: 12px; vertical-align: top; padding: 2px 0;">:</td>
                                 <td style="vertical-align: top; padding: 2px 0; font-weight: normal;">
                                     <?= htmlspecialchars($surat['nomor_surat'] ?: '') ?>
                                 </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 2px 0;">Hal</td>
                                <td style="vertical-align: top; padding: 2px 0;">:</td>
                                <td colspan="4" id="modalHalText" style="vertical-align: top; padding: 2px 0; font-weight: normal;"><?= htmlspecialchars($halDisposisi) ?></td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 4px 0;">Sifat</td>
                                <td style="vertical-align: top; padding: 4px 0;">:</td>
                                <td colspan="4" style="vertical-align: top; padding: 4px 0;">
                                     <?php $svgCheckModal = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block; position: absolute; top: -5px; left: -2px; pointer-events: none;"><path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/></svg>'; ?>
                                     <div style="display: flex; gap: 30px; align-items: center; font-size: 12.5px; font-family: Arial, sans-serif;">
                                         <label style="display: flex; align-items: center; gap: 6px;">Biasa <span id="modalChkBiasa" style="display: inline-flex; align-items: center; justify-content: center; width: 14px; height: 14px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatDisposisiSelected === 'biasa') ? $svgCheckModal : '' ?></span></label>
                                         <label style="display: flex; align-items: center; gap: 6px;">Penting <span id="modalChkPenting" style="display: inline-flex; align-items: center; justify-content: center; width: 14px; height: 14px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatDisposisiSelected === 'penting') ? $svgCheckModal : '' ?></span></label>
                                         <label style="display: flex; align-items: center; gap: 6px;">Segera <span id="modalChkSegera" style="display: inline-flex; align-items: center; justify-content: center; width: 14px; height: 14px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatDisposisiSelected === 'segera') ? $svgCheckModal : '' ?></span></label>
                                         <label style="display: flex; align-items: center; gap: 6px;">Rahasia <span id="modalChkRahasia" style="display: inline-flex; align-items: center; justify-content: center; width: 14px; height: 14px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatDisposisiSelected === 'rahasia') ? $svgCheckModal : '' ?></span></label>
                                     </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- 3-Column Recipient Checklist Table (28 items with integrated square checkbox cells matching photo 1:1) -->
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px; color: #000000; margin-bottom: 16px;" border="1">
                        <tr>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px; width: 30%; vertical-align: middle;">Kasubbag Tata Usaha</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px; width: 30%; vertical-align: middle;">Kapel Monev Program</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px; width: 30%; vertical-align: middle;">Kapel Pengelola SDM & RB</td>
                        </tr>
                        <tr>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">KPP Program</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel Dokumentasi & Distribusi Audio Video</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel Perlengkapan & Sarpras</td>
                        </tr>
                        <tr>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">KPP Berita</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel CA & OR</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel Pengelola BMN</td>
                        </tr>
                        <tr>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">KPP PU</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel Berita</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel Humas & Protokol</td>
                        </tr>
                        <tr>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">KPP Teknik</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel Monev Berita</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel Konten Kreatif Digital</td>
                        </tr>
                        <tr>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">KPP Keuangan</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel Penjualan & Pemasaran</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel Analitik & Distribusi Konten</td>
                        </tr>
                        <tr>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">KPP Umum</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel IT</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">PPK I</td>
                        </tr>
                        <tr>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">KPP Media Baru</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel Produksi & Penyiaran Teknik</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">PPK II</td>
                        </tr>
                        <tr>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel Produksi Program</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel Transmisi</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;"></td>
                        </tr>
                        <tr>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel Promo</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;">Kapel Evaluasi & Pelaporan Keuangan</td>
                            <td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td>
                            <td style="padding: 4px 6px;"></td>
                        </tr>
                    </table>

                    <!-- Section Disposisi (Tabel Thin Grid) & Catatan (Terpisah tanpa garis kotak terhubung) -->
                    <div style="display: flex; gap: 16px; width: 100%; margin-bottom: 20px; font-size: 11px; color: #000000; align-items: stretch;">
                        <!-- Left: Disposisi (Tabel Grid Thin Border) -->
                        <div style="width: 44%; display: flex; flex-direction: column;">
                            <div style="font-weight: normal; margin-bottom: 6px;">Disposisi :</div>
                            <table style="width: 100%; border-collapse: collapse; font-size: 11px; color: #000000; background: #ffffff;" border="1">
                                <tr><td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td><td style="padding: 3px 6px;">Diteliti / Dis</td></tr>
                                <tr><td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td><td style="padding: 3px 6px;">Dipertimbangkan</td></tr>
                                <tr><td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td><td style="padding: 3px 6px;">Untuk Diketahui / Diperhatikan</td></tr>
                                <tr><td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td><td style="padding: 3px 6px;">Mewakili / Menghadiri / Mengikuti</td></tr>
                                <tr><td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td><td style="padding: 3px 6px;">Dikoordinasikan</td></tr>
                                <tr><td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td><td style="padding: 3px 6px;">Ditampung Permasalahannya</td></tr>
                                <tr><td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td><td style="padding: 3px 6px;">Peringatkan / Pendekatan</td></tr>
                                <tr><td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td><td style="padding: 3px 6px;">Pendapat / Analisa / Saran</td></tr>
                                <tr><td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td><td style="padding: 3px 6px;">Konsep Jawaban / Sambutan</td></tr>
                                <tr><td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td><td style="padding: 3px 6px;">Konsep Laporan</td></tr>
                                <tr><td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td><td style="padding: 3px 6px;">Data Diolah</td></tr>
                                <tr><td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td><td style="padding: 3px 6px;">Ditindaklanjuti</td></tr>
                                <tr><td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td><td style="padding: 3px 6px;">Diagendakan / Dijadwalkan</td></tr>
                                <tr><td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td><td style="padding: 3px 6px;">Harap Dibantu</td></tr>
                                <tr><td style="width: 24px; text-align: center; vertical-align: middle; padding: 2px;"></td><td style="padding: 3px 6px;">File</td></tr>
                            </table>

                            <div style="margin-top: 10px;">
                                <div style="font-weight: normal;">Sekretariat :</div>
                            </div>
                        </div>

                        <!-- Right: Catatan (Terpisah tanpa garis kotak terhubung dengan disposisi) -->
                        <div style="flex: 1; display: flex; flex-direction: column;">
                            <div style="font-weight: normal; margin-bottom: 6px; margin-left: 60px;">Catatan :</div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Signature Section -->
                <div>
                    <div style="display: flex; justify-content: flex-end; align-items: flex-start; font-size: 12px; color: #000000; margin-top: -35px;">
                        <div style="width: 280px; text-align: left; line-height: 1.4;">
                            <div>Surabaya, <?= date('d F Y') ?></div>
                            <div>Kepala TVRI Stasiun Jawa Timur</div>
                            <div style="height: 65px; margin: 6px 0;"></div>
                            <div style="text-decoration: underline; font-weight: normal;"><?= htmlspecialchars($kepstaNamaDisplay) ?></div>
                            <div><?= htmlspecialchars($kepstaNipDisplay) ?></div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
        </div>

        <!-- Modal Footer -->
        <div style="padding: 14px 24px; background: #F8FAFC; border-top: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <button type="button" onclick="printReviewModal('disposisiReviewModal')" class="btn-btn" style="background: #FFFFFF; color: #1E293B; border: 1.5px solid #CBD5E1; font-weight: 700; box-shadow: 0 1px 2px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-printer-fill" style="color: #2563EB; font-size: 16px;"></i> Print Surat
                </button>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <button type="button" onclick="closeDisposisiReviewModal()" class="btn-btn btn-btn-secondary" style="padding: 10px 20px;">
                    Batal
                </button>
                <button type="button" onclick="confirmAndSubmitToKepsta()" class="btn-btn btn-btn-primary" style="padding: 10px 24px; font-weight: 700; background-color: #2563EB; border-color: #2563EB;">
                    <i class="bi bi-send-fill"></i> Ya, Teruskan ke Kepsta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 2: REVISI MODAL                      -->
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
<!-- MODAL 3: PERINGATAN NOMOR AGENDA KOSONG    -->
<!-- ========================================== -->
<div id="agendaWarningModal" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #FFFFFF; width: 100%; max-width: 440px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); text-align: center; padding: 28px 24px; border: 1px solid #F3F4F6; position: relative;">
        <div style="width: 60px; height: 60px; border-radius: 50%; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 28px; border: 2px solid #FDE68A;">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800; color: #0F172A; letter-spacing: -0.2px;">Nomor Agenda Belum Diisi</h3>
        <p style="margin: 0 0 24px 0; font-size: 13.5px; color: #64748B; line-height: 1.5;">
            Nomor Agenda belum diisi.<br>Silakan masukkan Nomor Agenda terlebih dahulu!
        </p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button type="button" onclick="closeAgendaWarningModal()" class="btn-btn btn-btn-secondary" style="flex: 1; justify-content: center; font-weight: 600; padding: 10px 16px;">
                Tutup
            </button>
            <button type="button" onclick="confirmFocusAgendaFromWarning()" class="btn-btn btn-btn-primary" style="flex: 1.3; justify-content: center; font-weight: 700; padding: 10px 16px; background: #2563EB; border-color: #2563EB; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-pencil-fill"></i> Isi Agenda Sekarang
            </button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 4: HAPUS SURAT MODAL                 -->
<!-- ========================================== -->
<div id="deleteSuratModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #FFFFFF; width: 100%; max-width: 480px; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); padding: 24px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
            <i class="bi bi-trash3-fill" style="color: #DC2626; font-size: 24px;"></i>
            <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #991B1B;">Hapus Surat dari TU</h3>
        </div>

        <p style="font-size: 14px; color: #475569; margin-bottom: 24px; line-height: 1.5;">
            Apakah Anda yakin ingin menghapus surat yang dikirim dari Tata Usaha (TU) ini? Dokumen surat akan ditolak dari alur proses.
        </p>

        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <button type="button" onclick="closeDeleteSuratModal()" class="btn-btn btn-btn-secondary">
                Batal
            </button>
            <button type="button" onclick="submitDeleteSurat()" class="btn-btn" style="background: #DC2626; color: #FFFFFF; font-weight: 700; padding: 10px 22px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-trash3-fill"></i> Ya, Hapus Surat
            </button>
        </div>
    </div>
</div>

<script>
function handleSekreFileSelect(input) {
    const list = document.getElementById('sekreFilesList');
    const noFileText = document.getElementById('sekreNoFileText');
    if (!list) return;

    list.innerHTML = '';
    if (input.files && input.files.length > 0) {
        if (noFileText) noFileText.style.display = 'none';
        list.style.display = 'block';
        for (let i = 0; i < input.files.length; i++) {
            const li = document.createElement('li');
            li.style.marginBottom = '6px';
            li.innerHTML = '<i class="bi bi-file-earmark-pdf-fill" style="color: #DC2626; margin-right: 6px;"></i> ' + 
                input.files[i].name + ' <span style="font-weight: normal; color: #64748B;">(' + (input.files[i].size / 1024 / 1024).toFixed(2) + ' MB)</span>';
            list.appendChild(li);
        }
    } else {
        if (noFileText) noFileText.style.display = 'block';
        list.style.display = 'none';
    }
}

// 0. Calendar Popup & Date Formatting Controller
function openDatePicker() {
    const agendaInput = document.getElementById('inTanggalAgenda');
    if (agendaInput && agendaInput._flatpickr) {
        agendaInput._flatpickr.open();
    }
}

function handleDateSelect(dateStr) {
    if (!dateStr) return;
    const parts = dateStr.split('-');
    if (parts.length !== 3) return;

    const year = parts[0];
    const monthIndex = parseInt(parts[1], 10) - 1;
    const day = parseInt(parts[2], 10);

    const monthsIndo = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    const formatted = `${day} ${monthsIndo[monthIndex]} ${year}`;
    const agendaInput = document.getElementById('inTanggalAgenda');
    if (agendaInput) {
        agendaInput.value = formatted;
        syncDisposisiPreview();
    }
}

// 1. Sync Form Inputs to Disposisi Review Modal Live
function syncDisposisiPreview() {
    const agendaEl = document.getElementById('inNomorAgenda');
    const tanggalEl = document.getElementById('inTanggalAgenda');
    const halEl = document.getElementById('inHalDisposisi');

    const modalAgenda = document.getElementById('modalAgendaText');
    const modalTanggal = document.getElementById('modalTanggalText');
    const modalHal = document.getElementById('modalHalText');

    if (modalAgenda && agendaEl) modalAgenda.textContent = agendaEl.value || '';
    if (modalTanggal && tanggalEl) modalTanggal.textContent = tanggalEl.value || '';
    if (modalHal && halEl) modalHal.textContent = halEl.value || '';
}

// 2. Open / Close Review Disposisi Modal & Handle Button Click
function handleTeruskanKepsta() {
    const agendaInput = document.getElementById('inNomorAgenda');
    if (!agendaInput || !agendaInput.value.trim()) {
        openAgendaWarningModal();
        return;
    }
    openDisposisiReviewModal();
}

function openAgendaWarningModal() {
    document.body.style.overflow = 'hidden';
    const modal = document.getElementById('agendaWarningModal');
    if (modal) modal.style.display = 'flex';
}

function closeAgendaWarningModal() {
    const modal = document.getElementById('agendaWarningModal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
}

function confirmFocusAgendaFromWarning() {
    closeAgendaWarningModal();
    const agendaInput = document.getElementById('inNomorAgenda');
    if (agendaInput) agendaInput.focus();
}

function openDisposisiReviewModal() {
    syncDisposisiPreview();
    document.body.style.overflow = 'hidden';
    document.getElementById('disposisiReviewModal').style.display = 'flex';
}

function closeDisposisiReviewModal() {
    document.body.style.overflow = '';
    document.getElementById('disposisiReviewModal').style.display = 'none';
}

function confirmAndSubmitToKepsta() {
    document.body.style.overflow = '';
    document.getElementById('inActionType').value = 'approve';
    document.getElementById('sekreSubmitForm').submit();
}

// 3. Open / Close Revisi Modal & Submit Actions
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

    document.getElementById('inActionType').value = 'revisi';
    document.getElementById('inCatatanRevisi').value = note;
    document.getElementById('sekreSubmitForm').submit();
}

function submitApprove() {
    document.getElementById('inActionType').value = 'approve';
}

// 4. Open / Close Delete Surat Modal
function openDeleteSuratModal() {
    document.body.style.overflow = 'hidden';
    document.getElementById('deleteSuratModal').style.display = 'flex';
}

function closeDeleteSuratModal() {
    document.body.style.overflow = '';
    document.getElementById('deleteSuratModal').style.display = 'none';
}

function submitDeleteSurat() {
    const form = document.getElementById('sekreSubmitForm');
    if (!form) return;

    const inAgenda = document.getElementById('inNomorAgenda');
    const inTanggal = document.getElementById('inTanggalAgenda');
    if (inAgenda) inAgenda.removeAttribute('required');
    if (inTanggal) inTanggal.removeAttribute('required');

    document.getElementById('inActionType').value = 'delete';
    form.submit();
}

window.addEventListener('click', function(e) {
    const revModal = document.getElementById('revisiModal');
    const delModal = document.getElementById('deleteSuratModal');
    const warnModal = document.getElementById('agendaWarningModal');
    const dispoModal = document.getElementById('disposisiReviewModal');
    if (e.target === revModal) closeRevisiModal();
    if (e.target === delModal) closeDeleteSuratModal();
    if (e.target === warnModal) closeAgendaWarningModal();
    if (e.target === dispoModal) closeDisposisiReviewModal();
});

async function printReviewModal(modalId) {
    if (modalId === 'disposisiReviewModal') {
        syncDisposisiPreview();
    }

    try {
        if (typeof renderServerPdfCanvases === 'function') {
            await renderServerPdfCanvases();
        }
    } catch (e) {
        console.error('Error pre-rendering PDF canvases:', e);
    }

    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    const isDispoModal = (modalId === 'disposisiReviewModal');
    const papers = modal.querySelectorAll('.a4-paper-preview, .pdf-lampiran-card-preview');
    if (!papers || papers.length === 0) return;

    let papersHtml = '';
    papers.forEach((paper, idx) => {
        const isLampiran = paper.classList.contains('pdf-lampiran-card-preview');
        if (isLampiran) {
            if (isDispoModal) return;
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

    const extraLampiranHtml = <?= json_encode($printableLampiranHtml) ?>;
    if (!isDispoModal && extraLampiranHtml && !papersHtml.includes('printable-lampiran-page')) {
        papersHtml += extraLampiranHtml;
    }

    const printWin = window.open('', '_blank', 'width=950,height=800');
    if (!printWin) {
        alert('Pop-up terblokir oleh browser. Harap izinkan pop-up untuk melakukan print.');
        return;
    }

    const pagePaddingCss = isDispoModal ? '1.2cm' : '3cm';

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
                .printable-a4-page { background: #FFFFFF !important; border: none !important; box-shadow: none !important; padding: ${pagePaddingCss} !important; margin: 0 auto !important; width: 210mm !important; height: 297mm !important; max-height: 297mm !important; box-sizing: border-box !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; page-break-after: always !important; break-after: page !important; page-break-inside: avoid !important; break-inside: avoid !important; overflow: hidden !important; }
                .printable-a4-page.printable-lampiran-page { padding: 0 !important; border: none !important; box-shadow: none !important; overflow: hidden !important; width: 210mm !important; height: 297mm !important; background: #ffffff !important; display: block !important; }
                .printable-a4-page.printable-lampiran-page img,
                .printable-a4-page.printable-lampiran-page .pdf-img-item { width: 100% !important; height: 100% !important; max-width: 100% !important; max-height: 297mm !important; object-fit: contain !important; display: block !important; margin: 0 auto !important; border: none !important; box-shadow: none !important; transform: none !important; }
                .printable-a4-page:last-child { page-break-after: avoid !important; break-after: avoid !important; }
                .a4-paper-preview { background: #FFFFFF !important; border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; max-width: 100% !important; height: auto !important; min-height: 0 !important; box-sizing: border-box !important; }
                .a4-paper-preview > div { border: 1.5px solid #000000 !important; padding: 16px 20px !important; min-height: 0 !important; box-sizing: border-box !important; background: #FFFFFF !important; }
                #pvTtdBox, #pvParafBox, #pvKepstaTtdBox, #pvTtdBoxSingle { border: none !important; background: transparent !important; box-shadow: none !important; }
                #pvTtdPlaceholder, #pvParafPlaceholder, #pvTtdPlaceholderSingle, #revKepstaTtdPlaceholder { display: none !important; }
                .no-print { display: none !important; }
                .pdf-canvas-item { display: none !important; }
                .pdf-img-item { display: block !important; visibility: visible !important; width: 100% !important; height: auto !important; }
                @media print {
                    body { background: #fff !important; }
                    img { display: inline-block !important; visibility: visible !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                    .printable-a4-page { border: none !important; box-shadow: none !important; padding: ${pagePaddingCss} !important; margin: 0 auto !important; }
                    .printable-a4-page.printable-lampiran-page { padding: 0 !important; border: none !important; box-shadow: none !important; }
                    .a4-paper-preview { border: none !important; box-shadow: none !important; padding: 0 !important; }
                    .a4-paper-preview > div { border: 1.5px solid #000000 !important; padding: 16px 20px !important; }
                    #pvTtdBox, #pvParafBox, #pvKepstaTtdBox, #pvTtdBoxSingle { border: none !important; background: transparent !important; box-shadow: none !important; }
                    #pvTtdPlaceholder, #pvParafPlaceholder, #pvTtdPlaceholderSingle, #revKepstaTtdPlaceholder { display: none !important; }
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
        const dispoModal = document.getElementById('disposisiReviewModal');
        if (dispoModal && dispoModal.style.display !== 'none') {
            closeDisposisiReviewModal();
            return;
        }
        const revModal = document.getElementById('revisiModal');
        if (revModal && revModal.style.display !== 'none') {
            closeRevisiModal();
            return;
        }
        const warnModal = document.getElementById('agendaWarningModal');
        if (warnModal && warnModal.style.display !== 'none') {
            closeAgendaWarningModal();
            return;
        }
        const delModal = document.getElementById('deleteSuratModal');
        if (delModal && delModal.style.display !== 'none') {
            closeDeleteSuratModal();
            return;
        }
    }
});

document.addEventListener('DOMContentLoaded', function () {
    syncDisposisiPreview();
});
</script>
