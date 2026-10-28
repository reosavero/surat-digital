<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>GABUNGAN SURAT DISPOSISI & SURAT DIGITAL TVRI JAWA TIMUR</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }
        html, body {
            margin: 0;
            padding: 0;
            width: 210mm;
            background: #ffffff;
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        /* Page with exact 3cm margin on each side (30mm) */
        .pdf-page-surat {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            padding: 30mm;
            box-sizing: border-box;
            background: #ffffff;
            page-break-after: always !important;
            break-after: page !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            overflow: hidden;
            position: relative;
            margin: 0 auto;
        }
        /* Lampiran attachment pages */
        .pdf-page-lampiran {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            padding: 15mm;
            box-sizing: border-box;
            background: #ffffff;
            page-break-after: always !important;
            break-after: page !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            overflow: hidden;
            position: relative;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Surat TTE PDF pages */
        .pdf-page-tte {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            padding: 0;
            box-sizing: border-box;
            background: #ffffff;
            page-break-after: always !important;
            break-after: page !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            overflow: hidden;
            position: relative;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .no-print { display: none !important; }
        img { display: inline-block !important; visibility: visible !important; max-width: 100% !important; }
        img.kpp-paraf-img { display: block !important; visibility: visible !important; opacity: 1 !important; width: auto !important; max-width: 70px !important; max-height: 48px !important; object-fit: contain !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    </style>
</head>
<body>

    <?php 
    $meta = $surat['meta'] ?? [];
    $tuTtePdfPath = !empty($meta['tu_tte_pdf_path']) ? $meta['tu_tte_pdf_path'] : (!empty($meta['is_tu_tte_uploaded']) ? ($surat['pdf_path'] ?? '') : '');
    $hasTuTtePdf = !empty($tuTtePdfPath) && !empty($meta['is_tu_tte_uploaded']);
    $dispoChecklist = $disposisi['checklist'] ?? [];
    $rawSifat = !empty($meta['sifat_surat']) ? $meta['sifat_surat'] : (!empty($disposisi['sifat_surat']) ? $disposisi['sifat_surat'] : 'biasa');
    $sifatSelected = strtolower(trim($rawSifat));
    $isProduksi = (($surat['type'] ?? '') === 'izin_produksi');
    $isPrinsip = (($surat['type'] ?? '') === 'izin_prinsip');
    $isNotaDinas = (($surat['type'] ?? '') === 'nota_dinas' || ($meta['kategori_surat'] ?? '') === 'Surat Nota Dinas');
    $isFromUploadLampiran = !empty($meta['from_upload_lampiran']) || (($surat['type'] ?? '') === 'upload_lampiran');
    $tglIndo = !empty($surat['tanggal_surat']) ? date('d F Y', strtotime($surat['tanggal_surat'])) : date('d F Y');

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

    $tuNamaDisplay = $tuAccount['name'] ?? ($surat['meta']['penandatangan_nama'] ?? ($meta['penandatangan_nama'] ?? 'Maya Sulfiana, S.Kom'));
    $tuNipRaw = $tuAccount['nip'] ?? ($surat['meta']['penandatangan_nip'] ?? ($meta['penandatangan_nip'] ?? '198612252011012016'));
    $tuNipDisplay = !empty($tuNipRaw) ? (str_starts_with(strtoupper(trim($tuNipRaw)), 'NIP') ? trim($tuNipRaw) : 'NIP. ' . trim($tuNipRaw)) : 'NIP. 198612252011012016';

    $kppNamaDisplay = $kppAccount['name'] ?? ($surat['kpp_nama'] ?? ($meta['pemohon_nama'] ?? 'Ir. Sepna Yuhana'));
    $kppNipRaw = $kppAccount['nip'] ?? ($surat['kpp_nip'] ?? ($meta['pemohon_nip'] ?? '196809161993032006'));
    $kppNipDisplay = !empty($kppNipRaw) ? (str_starts_with(strtoupper(trim($kppNipRaw)), 'NIP') ? trim($kppNipRaw) : 'NIP. ' . trim($kppNipRaw)) : 'NIP. 19680916 199303 2 006';

    $kepstaNamaDisplay = $kepstaAccount['name'] ?? ($meta['kepsta_nama'] ?? 'Akbar Sahidi, S.Sos., M.Si');
    $kepstaNipRaw = $kepstaAccount['nip'] ?? ($meta['kepsta_nip'] ?? '197005121995031002');
    $kepstaNipDisplay = !empty($kepstaNipRaw) ? (str_starts_with(strtoupper(trim($kepstaNipRaw)), 'NIP') ? trim($kepstaNipRaw) : 'NIP. ' . trim($kepstaNipRaw)) : 'NIP. 197005121995031002';

    $getValidParaf = function(...$candidates) {
        foreach ($candidates as $c) {
            if (!empty($c) && is_string($c) && $c !== '/assets/img/ttd_tu_stamp.png') {
                return $c;
            }
        }
        return '';
    };

    $kppParafPath = $getValidParaf(
        $meta['kpp_paraf'] ?? null,
        $meta['paraf_kpp'] ?? null,
        $surat['kpp_paraf'] ?? null,
        $meta['paraf'] ?? null,
        $surat['paraf'] ?? null,
        $meta['tu_paraf'] ?? null,
        $kppAccount['paraf'] ?? null,
        $kppAccount['signature'] ?? null,
        $surat['kpp_signature'] ?? null
    );

    // Kru Table data
    $jsonRaw = $surat['meta']['kru_table_json'] ?? '';
    $jsonTable = !empty($jsonRaw) ? json_decode($jsonRaw, true) : null;
    ?>

    <?php if ($includeDisposisi ?? true): ?>
    <!-- ========================================== -->
    <!-- PAGE: SURAT DISPOSISI KEPALA STASIUN -->
    <!-- ========================================== -->
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

    <div class="pdf-page-surat">
        <!-- INNER RECTANGULAR BORDER FRAME -->
        <div style="border: 1.5px solid #000000; padding: 12px 14px; box-sizing: border-box; background: #FFFFFF; height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <!-- Logo Top Left -->
                <div style="margin-bottom: 6px;">
                    <img src="<?= getPdfImgSrc('/assets/img/tvri3.svg') ?>" style="height: 44px; width: auto;" alt="TVRI Logo">
                </div>

                <!-- Header Title Disposisi -->
                <div style="text-align: center; margin-bottom: 10px; color: #000000; font-family: Arial, sans-serif;">
                    <div style="font-size: 12px; font-weight: bold; letter-spacing: 0.2px;">DISPOSISI</div>
                    <div style="font-size: 12px; font-weight: bold; letter-spacing: 0.2px; margin-top: 1px;">KEPALA TVRI STASIUN JAWA TIMUR</div>
                    <div style="font-size: 12px; font-weight: bold; letter-spacing: 0.2px; margin-top: 1px;">LPP TELEVISI REPUBLIK INDONESIA</div>
                </div>

                <!-- Top Metadata Section -->
                <div style="padding: 2px 0; margin-bottom: 8px; font-size: 10.5px; font-family: Arial, sans-serif;">
                    <table style="width: 100%; border-collapse: collapse; line-height: 1.35; color: #000000;">
                        <tr>
                            <td style="width: 95px; vertical-align: top; padding: 1px 0;">Nomor Agenda</td>
                            <td style="width: 10px; vertical-align: top; padding: 1px 0;">:</td>
                            <td colspan="4" style="vertical-align: top; padding: 1px 0; font-weight: normal;"><?= htmlspecialchars($meta['nomor_agenda'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 1px 0;">Tanggal</td>
                            <td style="vertical-align: top; padding: 1px 0;">:</td>
                            <td colspan="4" style="vertical-align: top; padding: 1px 0;"><?= htmlspecialchars($meta['tanggal_agenda'] ?? date('d F Y', strtotime($surat['tanggal_surat']))) ?></td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 1px 0;">Dari</td>
                            <td style="vertical-align: top; padding: 1px 0;">:</td>
                            <td style="vertical-align: top; padding: 1px 0; width: 38%;">
                                <?= htmlspecialchars($meta['dari'] ?? 'Kepala Sub Bagian Tata Usaha') ?>
                            </td>
                            <td style="width: 85px; vertical-align: top; padding: 1px 0;">Nomor Surat</td>
                            <td style="width: 10px; vertical-align: top; padding: 1px 0;">:</td>
                            <td style="vertical-align: top; padding: 1px 0; font-weight: normal;">
                                <?= htmlspecialchars($surat['nomor_surat'] ?: '') ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 1px 0;">Hal</td>
                            <td style="vertical-align: top; padding: 1px 0;">:</td>
                            <td colspan="4" style="vertical-align: top; padding: 1px 0; font-weight: normal;"><?= htmlspecialchars($meta['hal_disposisi'] ?? $meta['perihal_disposisi'] ?? $disposisi['hal'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 2px 0;">Sifat</td>
                            <td style="vertical-align: top; padding: 2px 0;">:</td>
                            <td colspan="4" style="vertical-align: top; padding: 2px 0;">
                                <?php 
                                $svgCheckPdf = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block; position: absolute; top: -3px; left: -2px; pointer-events: none;"><path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/></svg>'; 
                                $rawSifat = !empty($disposisi['sifat_surat']) ? $disposisi['sifat_surat'] : (!empty($meta['sifat_disposisi']) ? $meta['sifat_disposisi'] : '');
                                $sifatSelected = strtolower(trim($rawSifat));
                                ?>
                                <div style="display: flex; gap: 20px; align-items: center; font-size: 10.5px; font-family: Arial, sans-serif;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px;">Biasa <span style="display: inline-flex; align-items: center; justify-content: center; width: 12px; height: 12px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatSelected === 'biasa') ? $svgCheckPdf : '' ?></span></span>
                                    <span style="display: inline-flex; align-items: center; gap: 4px;">Penting <span style="display: inline-flex; align-items: center; justify-content: center; width: 12px; height: 12px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatSelected === 'penting') ? $svgCheckPdf : '' ?></span></span>
                                    <span style="display: inline-flex; align-items: center; gap: 4px;">Segera <span style="display: inline-flex; align-items: center; justify-content: center; width: 12px; height: 12px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatSelected === 'segera') ? $svgCheckPdf : '' ?></span></span>
                                    <span style="display: inline-flex; align-items: center; gap: 4px;">Rahasia <span style="display: inline-flex; align-items: center; justify-content: center; width: 12px; height: 12px; border: 1.5px solid #000; position: relative; overflow: visible;"><?= ($sifatSelected === 'rahasia') ? $svgCheckPdf : '' ?></span></span>
                                </div>

                            </td>
                        </tr>
                    </table>
                </div>

                <!-- 3-Column Recipient Table with Integrated Checkbox Grid Cells -->
                <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt; color: #000000; margin-bottom: 8px;" border="1">
                    <?php 
                    for ($i = 0; $i < 10; $i++): 
                        $item1 = $recipientsCol1[$i] ?? null;
                        $item2 = $recipientsCol2[$i] ?? null;
                        $item3 = $recipientsCol3[$i] ?? null;
                    ?>
                        <tr>
                            <td style="width: 18px; text-align: center; vertical-align: middle; padding: 0;">
                                <?php if ($item1 && in_array($item1, $dispoChecklist)): ?>
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block; margin: auto;">
                                        <path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1.5px 3px; width: 30%; vertical-align: middle;">
                                <?= htmlspecialchars($item1 ?? '') ?>
                            </td>

                            <td style="width: 18px; text-align: center; vertical-align: middle; padding: 0;">
                                <?php if ($item2 && in_array($item2, $dispoChecklist)): ?>
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block; margin: auto;">
                                        <path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1.5px 3px; width: 30%; vertical-align: middle;">
                                <?= htmlspecialchars($item2 ?? '') ?>
                            </td>

                            <td style="width: 18px; text-align: center; vertical-align: middle; padding: 0;">
                                <?php if ($item3 && in_array($item3, $dispoChecklist)): ?>
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block; margin: auto;">
                                        <path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1.5px 3px; width: 30%; vertical-align: middle;">
                                <?= htmlspecialchars($item3 ?? '') ?>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </table>

                <!-- Section Disposisi & Catatan -->
                <div style="display: flex; gap: 10px; width: 100%; margin-bottom: 8px; font-size: 8.5pt; color: #000000; align-items: stretch;">
                    <!-- Left: Disposisi -->
                    <div style="width: 44%; display: flex; flex-direction: column;">
                        <div style="font-weight: normal; margin-bottom: 3px;">Disposisi :</div>
                        <table style="width: 100%; border-collapse: collapse; font-size: 8pt; color: #000000; background: #ffffff;" border="1">
                            <?php foreach ($disposisiItemsList as $dItem): ?>
                                <tr>
                                    <td style="width: 16px; text-align: center; vertical-align: middle; padding: 0;">
                                        <?php if (in_array($dItem, $dispoChecklist)): ?>
                                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block; margin: auto;">
                                                <path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 1px 3px; vertical-align: middle;">
                                        <?= htmlspecialchars($dItem) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>

                        <div style="margin-top: 6px;">
                            <div style="margin-bottom: 3px; font-weight: normal;">Sekretariat :</div>
                            <div style="font-size: 9pt; line-height: 1.25; white-space: pre-line; font-weight: normal; color: #003399; min-height: 30px;">
                                <?= htmlspecialchars(!empty($disposisi['catatan_sekre']) ? $disposisi['catatan_sekre'] : (!empty($meta['catatan_sekretariat']) ? $meta['catatan_sekretariat'] : '')) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Catatan -->
                    <div style="flex: 1; display: flex; flex-direction: column;">
                        <div style="font-weight: normal; margin-bottom: 3px; margin-left: 30px;">Catatan :</div>
                        <div style="font-size: 9pt; line-height: 1.35; white-space: pre-line; flex: 1; min-height: 170px; padding: 3px; color: #003399; font-weight: normal;">
                            <?= htmlspecialchars($disposisi['catatan_kepsta'] ?? '') ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Signature Block -->
            <div style="display: flex; justify-content: flex-end; align-items: flex-start; font-size: 9.5pt;">
                <div style="width: 240px; text-align: left; line-height: 1.25;">
                    <div>Surabaya, <?= date('d F Y', strtotime($disposisi['kepsta_signed_at'] ?? date('Y-m-d'))) ?></div>
                    <div>Kepala TVRI Stasiun Jawa Timur</div>
                    <div style="height: 48px; margin: 3px 0; display: flex; align-items: center; justify-content: flex-start;">
                        <?php if (!empty($disposisi['kepsta_signature'])): ?>
                            <img src="<?= getPdfImgSrc($disposisi['kepsta_signature']) ?>" style="max-height: 44px; width: auto;" alt="TTD Kepsta">
                        <?php else: ?>
                            <br><br>
                        <?php endif; ?>
                    </div>
                    <div style="text-decoration: underline; font-weight: normal;"><?= htmlspecialchars($kepstaNamaDisplay) ?></div>
                    <div><?= htmlspecialchars($kepstaNipDisplay) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!($disposisiOnly ?? false)): ?>
        <?php if ($hasTuTtePdf): ?>
        <!-- ========================================== -->
        <!-- SURAT TTE PDF (DIUNGGAH OLEH TATA USAHA) -->
        <!-- ========================================== -->
        <?php
        $fullTtePath = BASE_PATH . 'public' . '/' . ltrim($tuTtePdfPath, '/');
        if (!file_exists($fullTtePath)) {
            $fullTtePath = BASE_PATH . ltrim($tuTtePdfPath, '/');
        }
        $ttePdfBase64 = file_exists($fullTtePath) ? base64_encode(file_get_contents($fullTtePath)) : '';
        $ttePageCount = getPdfPageCount($tuTtePdfPath);
        ?>
        <?php if (!empty($ttePdfBase64)): ?>
            <div id="pdf_data_tu_tte" data-base64="<?= $ttePdfBase64 ?>" style="display: none;"></div>
            <?php for ($p = 1; $p <= $ttePageCount; $p++): ?>
                <div class="pdf-page-tte">
                    <div style="width: 210mm; height: 297mm; display: flex; align-items: center; justify-content: center; box-sizing: border-box; text-align: center; margin: 0; padding: 0;">
                        <canvas class="pdf-template-canvas-item" data-pdf-ref="pdf_data_tu_tte" data-page-num="<?= $p ?>" style="max-width: 210mm; max-height: 297mm; display: block; margin: 0 auto; border: none;"></canvas>
                        <img class="pdf-template-img-item" style="max-width: 210mm; max-height: 297mm; width: 100%; height: 100%; object-fit: contain; display: none; margin: 0 auto; border: none;">
                    </div>
                </div>
            <?php endfor; ?>
        <?php endif; ?>
        <?php else: ?>
        <?php if (!$isNotaDinas && !$isFromUploadLampiran): ?>
        <!-- ========================================== -->
        <!-- SURAT PERMOHONAN (1:1 SAMA PERSIS TU-PROCESS) -->
        <!-- ========================================== -->
    <div class="pdf-page-surat" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <!-- Logo Top Left -->
            <div style="margin-bottom: 14px;">
                <img src="<?= getPdfImgSrc('/assets/img/tvri3.svg') ?>" style="height: 48px; width: auto;" alt="TVRI Logo">
            </div>

            <!-- Header Line: Perihal Left, Tanggal Right -->
            <div style="font-size: 11px; line-height: 1.3; margin-bottom: 14px; color: #000000;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div>Perihal : <span style="font-weight: normal;"><?= htmlspecialchars($meta['perihal_permohonan'] ?? $surat['perihal']) ?></span></div>
                        <?php if (!empty($meta['lokasi_sub_permohonan']) || !empty($meta['sub_perihal'])): ?>
                            <div style="font-weight: normal; color: #000;"><?= htmlspecialchars($meta['lokasi_sub_permohonan'] ?? $meta['sub_perihal'] ?? '') ?></div>
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

            <!-- Kru Table on Surat Permohonan -->
            <?php if ($jsonTable && !empty($jsonTable['headers']) && !empty($jsonTable['rows'])): ?>
                <?php
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
                <div style="margin-bottom: 10px; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
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
                <div style="width: 220px; max-width: 100%; text-align: center; font-size: 10.5px; line-height: 1.25; color: #000000; word-wrap: break-word; overflow-wrap: break-word; box-sizing: border-box;">
                    <div style="white-space: normal; word-wrap: break-word; word-break: normal;"><?= htmlspecialchars($surat['kpp_jabatan'] ?? ($isProduksi ? 'Ketua Perencana dan Pengendalian Program' : 'Ketua Perencana dan Pengendalian Teknik')) ?></div>
                    <div>TVRI Stasiun Jawa Timur</div>
                    <div style="height: 48px; margin: 3px 0; display: flex; align-items: center; justify-content: center;">
                        <?php 
                        $kppSig = !empty($surat['kpp_signature']) ? $surat['kpp_signature'] : ($meta['kpp_signature'] ?? '');
                        if (!empty($kppSig) && $kppSig !== '/assets/img/ttd_tu_stamp.png'): 
                        ?>
                            <img src="<?= getPdfImgSrc($kppSig) ?>" style="max-height: 44px; width: auto; object-fit: contain;" alt="TTD KPP Bidang">
                        <?php else: ?>
                            <div style="height: 44px;"></div>
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
    <?php endif; ?>

    <?php if (!$isFromUploadLampiran): ?>
    <!-- ========================================== -->
    <!-- SURAT NOTA DINAS (untuk semua tipe surat) -->
    <!-- ========================================== -->
    <div class="pdf-page-surat" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <!-- Logo Top Left -->
            <div style="margin-bottom: 16px;">
                <img src="<?= getPdfImgSrc('/assets/img/tvri3.svg') ?>" style="height: 52px; width: auto;" alt="TVRI Logo">
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
                    <td style="vertical-align: top; padding: 2px 0; font-weight: normal;">
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

            <div style="border-top: 1.5px solid #000000; margin: 8px 0 12px 0;"></div>

            <?php if ($isNotaDinas && !empty($meta['pemohon_nama'])): ?>
            <!-- Table Data Diri Pemohon (Only for Single Nota Dinas) -->
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

            <!-- Tabel Kru HANYA untuk Single Nota Dinas, BUKAN untuk Produksi atau Prinsip -->
            <?php if ($isNotaDinas && $jsonTable && !empty($jsonTable['headers']) && !empty($jsonTable['rows'])): ?>
                <?php
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
                <div style="margin-bottom: 10px; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
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

            <!-- Signature Block -->
            <?php if ($isNotaDinas): ?>
                <!-- 2-COLUMN SIGNATURE FOR SINGLE NOTA DINAS -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 14px; margin-bottom: 14px;">
                    <!-- Left: Kasubag TU + Paraf KPP -->
                    <div style="width: 250px; text-align: left; font-size: 12px; line-height: 1.15; color: #000000;">
                        <div>Kepala Sub Bagian Tata Usaha</div>
                        <div>TVRI Stasiun Jawa Timur</div>
                        <div style="height: 55px; width: 100%; margin: 4px 0; display: flex; align-items: center; justify-content: flex-start;">
                            <?php 
                            $tuSig = !empty($surat['tu_signature']) ? $surat['tu_signature'] : ($meta['tu_signature'] ?? '');
                            if (!empty($tuSig) && $tuSig !== '/assets/img/ttd_tu_stamp.png'): 
                            ?>
                                <img src="<?= getPdfImgSrc($tuSig) ?>" style="max-height: 55px; width: auto; object-fit: contain; text-align: left; margin: 0;" alt="TTD TU">
                            <?php else: ?>
                                <div style="height: 55px;"></div>
                            <?php endif; ?>
                        </div>
                        <div style="position: relative; display: inline-block;">
                            <?php if (!empty($kppParafPath)): ?>
                                <div style="position: absolute; right: 100%; top: 50%; transform: translateY(-50%); margin-right: 8px; white-space: nowrap; display: flex; align-items: center; justify-content: center; z-index: 10;">
                                    <img src="<?= getPdfImgSrc($kppParafPath) ?>" class="kpp-paraf-img" style="max-height: 48px; width: auto; max-width: 70px; object-fit: contain; display: block; visibility: visible;" alt="Paraf KPP">
                                </div>
                            <?php endif; ?>
                            <div style="text-decoration: underline; font-weight: 500; white-space: nowrap;"><?= htmlspecialchars($tuNamaDisplay) ?></div>
                            <div style="white-space: nowrap;"><?= htmlspecialchars($tuNipDisplay) ?></div>
                        </div>
                    </div>

                    <!-- Right: Pemohon -->
                    <div style="width: 230px; text-align: center; font-size: 12px; line-height: 1.15; color: #000000;">
                        <div>Hormat Saya,</div>
                        <div style="min-height: 35px; margin: 3px 0; display: flex; align-items: center; justify-content: center;">
                            <?php 
                            $pemSig = !empty($meta['pemohon_signature_data']) ? $meta['pemohon_signature_data'] : (!empty($meta['pemohon_signature']) ? $meta['pemohon_signature'] : ($surat['pemohon_signature'] ?? ''));
                            if (!empty($pemSig)): 
                            ?>
                                <img src="<?= getPdfImgSrc($pemSig) ?>" style="max-height: 45px; width: auto; object-fit: contain;" alt="TTD Pemohon">
                            <?php else: ?>
                                <div style="height: 35px;"></div>
                            <?php endif; ?>
                        </div>
                        <div><?= htmlspecialchars($meta['pemohon_nama'] ?? '') ?></div>
                    </div>
                </div>
            <?php else: ?>
                <!-- SIGNATURE ON RIGHT WITH PARAF KPP BESIDE TU FOR 2-PAGE LETTER (PRODUKSI & PRINSIP) -->
                <div style="display: flex; justify-content: flex-end; margin-top: 16px; margin-bottom: 16px;">
                    <div style="width: 200px; text-align: left; font-size: 10.5px; line-height: 1.25; color: #000000;">
                        <div>Mengetahui,</div>
                        <div>Kepala Sub Bagian Tata Usaha</div>
                        <div style="height: 48px; width: 100%; margin: 3px 0; display: flex; align-items: center; justify-content: flex-start;">
                            <?php 
                            $tuSig = !empty($surat['tu_signature']) ? $surat['tu_signature'] : ($meta['tu_signature'] ?? '');
                            if (!empty($tuSig) && $tuSig !== '/assets/img/ttd_tu_stamp.png'): 
                            ?>
                                <img src="<?= getPdfImgSrc($tuSig) ?>" style="max-height: 48px; width: auto; object-fit: contain; text-align: left; margin: 0;" alt="TTD TU">
                            <?php else: ?>
                                <div style="height: 48px;"></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Name & NIP block with Paraf KPP Bidang displaying statically to the left -->
                        <div style="position: relative; display: inline-block;">
                            <?php if (!empty($kppParafPath)): ?>
                                <div style="position: absolute; right: 100%; top: 50%; transform: translateY(-50%); margin-right: 8px; white-space: nowrap; display: flex; align-items: center; justify-content: center; z-index: 10;">
                                    <img src="<?= getPdfImgSrc($kppParafPath) ?>" class="kpp-paraf-img" style="max-height: 48px; width: auto; max-width: 70px; object-fit: contain; display: block; visibility: visible;" alt="Paraf KPP">
                                </div>
                            <?php endif; ?>
                            <div style="text-decoration: underline; font-weight: 500; white-space: nowrap;"><?= htmlspecialchars($tuNamaDisplay) ?></div>
                            <div style="white-space: nowrap;"><?= htmlspecialchars($tuNipDisplay) ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <!-- Footer -->
            <div style="padding-top: 10px;">
                <?php if (!$isNotaDinas): ?>
                <?php 
                // Barcode QR konsisten dengan yang dibuat oleh Admin pada tu-process dan kepsta-process (bukan langsung masuk ke file PDF Google Drive)
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
                            <img src="<?= getPdfImgSrc($qrSrcTu) ?>" style="width: 48px; height: 48px; border: 1.5px solid #000000; background: #FFFFFF; padding: 2px;" alt="Barcode QR Google Drive">
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
    <?php endif; // end !$isFromUploadLampiran ?>
    <?php endif; // end !$hasTuTtePdf ?>
    <?php endif; // end !$disposisiOnly ?>

    <!-- ========================================== -->
    <!-- DOKUMEN PDF LAMPIRAN TER-UPLOAD -->
    <!-- ========================================== -->
    <?php 
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
    if (empty($pdfLampirans) && !empty($surat['pdf_path']) && $isFromUploadLampiran) {
        $pdfLampirans = [$surat['pdf_path']];
    }
    $pdfLampirans = array_values(array_unique(array_filter($pdfLampirans)));
    ?>

    <?php if (!empty($pdfLampirans) && empty($hasTuTtePdf)): ?>
        <?php foreach ($pdfLampirans as $idx => $relPdfPath): 
            $fullLampiranPath = BASE_PATH . 'public' . '/' . ltrim($relPdfPath, '/');
            if (!file_exists($fullLampiranPath)) {
                $fullLampiranPath = BASE_PATH . ltrim($relPdfPath, '/');
            }
            if (!file_exists($fullLampiranPath)) continue;

            $pdfBase64 = base64_encode(file_get_contents($fullLampiranPath));
            $pageCount = getPdfPageCount($relPdfPath);
        ?>
            <!-- Hidden container storing single base64 data for this PDF -->
            <div id="pdf_data_<?= $idx ?>" data-base64="<?= $pdfBase64 ?>" style="display: none;"></div>

            <?php for ($p = 1; $p <= $pageCount; $p++): ?>
                <div class="pdf-page-lampiran">
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; box-sizing: border-box; text-align: center;">
                        <canvas class="pdf-template-canvas-item" data-pdf-ref="pdf_data_<?= $idx ?>" data-page-num="<?= $p ?>" style="max-width: 100%; max-height: 267mm; display: block; margin: 0 auto; border: 1px solid #CBD5E1;"></canvas>
                        <img class="pdf-template-img-item" style="max-width: 100%; max-height: 267mm; object-fit: contain; display: none; margin: 0 auto;">
                    </div>
                </div>
            <?php endfor; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php
    $workerCode = file_exists(BASE_PATH . 'assets/js/pdf.worker.min.js') ? file_get_contents(BASE_PATH . 'assets/js/pdf.worker.min.js') : '';
    $pdfJsCode = file_exists(BASE_PATH . 'assets/js/pdf.min.js') ? file_get_contents(BASE_PATH . 'assets/js/pdf.min.js') : '';
    ?>
    <!-- Inlining worker before pdf.js enables synchronous fake-worker mode without Web Worker / CORS blocking in Chromium -->
    <script>
    <?= $workerCode ?>
    </script>
    <script>
    <?= $pdfJsCode ?>
    </script>
    <script>
    async function renderPdfTemplateCanvases() {
        if (typeof pdfjsLib === 'undefined') return;

        const pdfDocCache = {};
        const canvases = document.querySelectorAll('.pdf-template-canvas-item');
        for (const canvas of canvases) {
            const refId = canvas.getAttribute('data-pdf-ref');
            const pageNum = parseInt(canvas.getAttribute('data-page-num'), 10) || 1;
            const dataEl = document.getElementById(refId);
            if (!dataEl) continue;
            const b64Data = dataEl.getAttribute('data-base64');
            if (!b64Data) continue;

            try {
                if (!pdfDocCache[refId]) {
                    const binaryStr = atob(b64Data);
                    const bytes = new Uint8Array(binaryStr.length);
                    for (let i = 0; i < binaryStr.length; i++) {
                        bytes[i] = binaryStr.charCodeAt(i);
                    }
                    pdfDocCache[refId] = await pdfjsLib.getDocument({ data: bytes }).promise;
                }
                const pdf = pdfDocCache[refId];
                const page = await pdf.getPage(pageNum);

                const viewport = page.getViewport({ scale: 2.0 });

                canvas.width = viewport.width;
                canvas.height = viewport.height;

                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                await page.render(renderContext).promise;

                const img = canvas.nextElementSibling;
                if (img && img.classList.contains('pdf-template-img-item')) {
                    img.src = canvas.toDataURL('image/png');
                    img.style.display = 'block';
                    canvas.style.display = 'none';
                }
            } catch (err) {
                console.error('Error rendering PDF canvas in template:', err);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderPdfTemplateCanvases);
    } else {
        renderPdfTemplateCanvases();
    }
    </script>

</body>
</html>
