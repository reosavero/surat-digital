<?php
$kategoriLabel = 'Surat Nota Dinas';
$currentUser = $currentUser ?? [];
$pemohonNama = !empty($currentUser['name']) ? $currentUser['name'] : 'Bagus Mahendra Putra';
$rawNip = $currentUser['nip'] ?? '';
$pemohonNopeg = !empty($currentUser['nopeg']) ? $currentUser['nopeg'] : '202205 29091996';
$pemohonJabatan = !empty($currentUser['jabatan']) ? $currentUser['jabatan'] : 'Staf Umum/Pengemudi';
$pemohonHp = !empty($currentUser['phone']) ? $currentUser['phone'] : '0856-4582-3054';
$pemohonSignature = $currentUser['signature'] ?? '';

$tuAccount = User::getSingleUserByRole('tata_usaha') ?: [];
$tuNama = $tuAccount['name'] ?? 'Maya Sulfiana, S.Kom';
$rawTuNip = $tuAccount['nip'] ?? '198612252011012016';
$tuNip = !empty($rawTuNip) ? (str_starts_with(strtoupper(trim($rawTuNip)), 'NIP') ? trim($rawTuNip) : 'NIP. ' . trim($rawTuNip)) : 'NIP. 198612252011012016';

$surat = $surat ?? null;
$isEdit = !empty($surat);
$meta = $surat['meta'] ?? [];
$catatanRevisi = $meta['catatan_revisi'] ?? '';

$valNomorSurat = $isEdit ? ($surat['nomor_surat'] ?? '') : '';
$valPerihal = $isEdit ? $surat['perihal'] : 'Permohonan Cuti Tahunan';
$valSifat = $isEdit ? ($meta['sifat_surat'] ?? 'Biasa') : 'Biasa';
$valLampiran = $isEdit ? $surat['lampiran'] : '1 (lembar)';
$valTanggal = $isEdit ? $surat['tanggal_surat'] : date('Y-m-d');

$valPemohonNama = $isEdit ? ($meta['pemohon_nama'] ?? $pemohonNama) : $pemohonNama;
$valPemohonNopeg = $isEdit ? ($meta['pemohon_nopeg'] ?? $pemohonNopeg) : $pemohonNopeg;
$valPemohonJabatan = $isEdit ? ($meta['pemohon_jabatan'] ?? $pemohonJabatan) : $pemohonJabatan;
$valPemohonHp = $isEdit ? ($meta['pemohon_hp'] ?? $pemohonHp) : $pemohonHp;

$valIsiSurat = $isEdit ? ($meta['isi_surat_dinas'] ?? $surat['isi_surat']) : "Dengan ini mengajukan Cuti Tahunan selama 2 (dua) hari kerja, terhitung mulai tanggal 26 sampai dengan 27 September 2026.\n\nSelama menjalankan cuti, pelaksanaan tugas dan pekerjaan yang menjadi tanggung jawab saya akan dikoordinasikan dengan Sdr. M. Faisal Mahardika agar pelayanan dan pelaksanaan pekerjaan tetap berjalan sebagaimana mestinya.";
$valPenutupSurat = $isEdit ? ($meta['penutup_surat'] ?? 'Demikian permohonan cuti ini saya sampaikan. Besar harapan saya kiranya Bapak Kepala TVRI Stasiun Jawa Timur Berkenan memberikan persetujuan atas pengajuan cuti tersebut. Atas perhatian dan persetujuannya, saya ucapkan terima kasih.') : 'Demikian permohonan cuti ini saya sampaikan. Besar harapan saya kiranya Bapak Kepala TVRI Stasiun Jawa Timur Berkenan memberikan persetujuan atas pengajuan cuti tersebut. Atas perhatian dan persetujuannya, saya ucapkan terima kasih.';

$valKppUserId = $isEdit ? ($surat['kpp_user_id'] ?? '') : '';
$valGDriveLink = $isEdit ? ($surat['gdrive_web_link'] ?? '') : '';

$existingPdfs = [];
if ($isEdit) {
    if (!empty($meta['lampiran_pdf_paths']) && is_array($meta['lampiran_pdf_paths'])) {
        $existingPdfs = array_values(array_filter($meta['lampiran_pdf_paths']));
    } elseif (!empty($meta['lampiran_pdf_path'])) {
        $existingPdfs = [$meta['lampiran_pdf_path']];
    }
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 0;
    }
    
    html, body {
        width: 100% !important;
        height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        overflow: visible !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    body > *:not(#reviewNotaDinasModal) {
        display: none !important;
    }

    #reviewNotaDinasModal,
    #reviewNotaDinasModal * {
        visibility: visible !important;
    }

    #reviewNotaDinasModal > div > div:first-child,
    #reviewNotaDinasModal > div > div:last-child,
    .no-print,
    button,
    .btn-btn {
        display: none !important;
        visibility: hidden !important;
    }

    #reviewNotaDinasModal {
        display: block !important;
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        height: auto !important;
        min-height: 0 !important;
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
        box-shadow: none !important;
        border: none !important;
        visibility: visible !important;
        overflow: visible !important;
        backdrop-filter: none !important;
        inset: auto !important;
    }

    #reviewNotaDinasModal > div,
    #reviewNotaDinasModal > div > div:nth-child(2),
    div[style*="flex-direction: row"],
    div[style*="min-width: 0"],
    #pvPdfListContainer,
    #pvPdfListContainer > div {
        display: block !important;
        position: static !important;
        float: none !important;
        width: 100% !important;
        max-width: 100% !important;
        height: auto !important;
        min-height: 0 !important;
        max-height: none !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
        background: #ffffff !important;
        gap: 0 !important;
        page-break-after: auto !important;
        break-after: auto !important;
        page-break-inside: auto !important;
        break-inside: auto !important;
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
        padding: 3cm !important;
        margin: 0 auto !important;
        box-shadow: none !important;
        border: none !important;
        background: #ffffff !important;
        box-sizing: border-box !important;
        page-break-before: auto !important;
        page-break-after: always !important;
        break-after: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        border-radius: 0 !important;
        overflow: hidden !important;
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
        page-break-after: always !important;
        break-after: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
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
        page-break-before: auto !important;
        page-break-after: always !important;
        break-after: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        border-radius: 0 !important;
        overflow: hidden !important;
        position: relative !important;
    }

    .pdf-lampiran-card-preview img,
    .pdf-lampiran-card-preview .pdf-img-item,
    .pdf-lampiran-card-preview .pdf-canvas-item {
        width: 100% !important;
        height: 100% !important;
        max-width: 100% !important;
        max-height: 297mm !important;
        object-fit: contain !important;
        display: block !important;
        margin: 0 auto !important;
        border: none !important;
        box-shadow: none !important;
        transform: none !important;
    }

    #reviewNotaDinasModal.has-no-lampiran .a4-paper-preview {
        page-break-after: avoid !important;
        break-after: avoid !important;
    }

    #reviewNotaDinasModal:not(.has-no-lampiran) #pvPdfListContainer > div:last-child .pdf-lampiran-card-preview {
        page-break-after: avoid !important;
        break-after: avoid !important;
    }

    .a4-paper-preview *, .pdf-lampiran-card-preview * {
        visibility: visible !important;
    }

    img {
        display: inline-block !important;
        visibility: visible !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        max-width: 100% !important;
    }
}

.pdf-lampiran-wrapper-col {
    flex: 0 0 calc(50% - 10px);
    max-width: calc(50% - 10px);
    min-width: 0;
    box-sizing: border-box;
    margin-bottom: 20px;
}

.review-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
@media (max-width: 640px) {
    .review-modal-footer {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 10px !important;
        padding: 12px 16px !important;
    }
    .review-footer-right {
        display: contents !important;
    }
    .btn-review-submit {
        grid-column: 1 / -1 !important;
        grid-row: 1 !important;
        width: 100% !important;
        min-width: 0 !important;
        padding: 12px 16px !important;
        font-size: 15px !important;
        font-weight: 700 !important;
        justify-content: center !important;
        box-sizing: border-box !important;
    }
    .btn-review-close {
        grid-column: 1 / 2 !important;
        grid-row: 2 !important;
        width: 100% !important;
        min-width: 0 !important;
        padding: 10px 14px !important;
        font-size: 13.5px !important;
        font-weight: 700 !important;
        justify-content: center !important;
        box-sizing: border-box !important;
    }
    .review-footer-left {
        grid-column: 2 / 3 !important;
        grid-row: 2 !important;
        width: 100% !important;
    }
    .btn-review-print {
        width: 100% !important;
        min-width: 0 !important;
        padding: 10px 14px !important;
        font-size: 13.5px !important;
        font-weight: 600 !important;
        justify-content: center !important;
        box-sizing: border-box !important;
    }
}
</style>

<div style="max-width: 950px; margin: 0 auto;">
    <!-- Top Header Banner -->
    <div class="page-header-banner" style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <h1 style="font-size: 22px; font-weight: 800; color: var(--text-heading); margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-file-earmark-text-fill" style="color: #2563EB;"></i> <?= $isEdit ? 'Revisi Surat Nota Dinas' : 'Form Pembuatan Surat Nota Dinas' ?>
            </h1>
        </div>
    </div>

    <?php if ($isEdit && (!empty($catatanRevisi) || ($surat['status'] ?? '') === 'revisi')): ?>
    <!-- Top Revision Note Banner Card -->
    <div style="background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%); border: 2px solid #F59E0B; border-radius: 16px; padding: 20px 24px; margin-bottom: 28px; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.12);">
        <div style="display: flex; align-items: flex-start; gap: 16px;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: #F59E0B; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 6px;">
                    <h3 style="font-size: 16px; font-weight: 800; color: #92400E; margin: 0; display: flex; align-items: center; gap: 8px;">
                        Catatan Revisi Surat
                    </h3>
                    <span style="background: #FEE2E2; color: #DC2626; font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 20px; border: 1px solid #FCA5A5; text-transform: uppercase;">
                        Status: Perlu Revisi
                    </span>
                </div>
                <?php if (!empty($catatanRevisi)): ?>
                <p style="font-size: 14px; font-weight: 600; color: #B45309; margin: 0; line-height: 1.5; background: #FFFFFF; padding: 12px 16px; border-radius: 10px; border: 1px solid #FCD34D;">
                    <i class="bi bi-chat-left-quote-fill" style="color: #F59E0B; margin-right: 6px;"></i>
                    <?= htmlspecialchars($catatanRevisi) ?>
                </p>
                <?php endif; ?>
                <div style="font-size: 12px; color: #92400E; margin-top: 8px; font-weight: 500;">
                    <i class="bi bi-info-circle-fill"></i> Silakan perbarui data surat di bawah ini sesuai catatan revisi lalu klik tombol Review dan Kirim.
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <form id="formNotaDinas" action="<?= BASE_URL ?>/surat/store-nota-dinas" method="POST" enctype="multipart/form-data" autocomplete="off" style="display: flex; flex-direction: column; gap: 28px;">
        <?= Session::csrfField() ?>
        <input type="hidden" name="kategori_surat" value="<?= $kategoriLabel ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="surat_id" value="<?= (int)$surat['id'] ?>">
        <?php endif; ?>

        <!-- ========================================================================= -->
        <!-- BAGIAN 1: FORM UTAMA SURAT NOTA DINAS -->
        <!-- ========================================================================= -->
        <div id="bagian1Card" class="card-panel-variant" style="border: 2px solid #BFDBFE; background: #FFFFFF; border-radius: 18px; padding: 28px; box-shadow: 0 4px 16px rgba(37, 99, 235, 0.05); position: relative; z-index: 30;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 2px dashed #DBEAFE;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 42px; height: 42px; border-radius: 12px; background: #EFF6FF; color: #1D4ED8; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800;">
                        1
                    </div>
                    <div>
                        <h2 style="font-size: 17px; font-weight: 800; color: #1E3A8A; margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="bi bi-file-earmark-text-fill" style="color: #2563EB;"></i> BAGIAN 1: SURAT NOTA DINAS
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Fixed Default Hidden Inputs -->
            <input type="hidden" name="kepada" id="inKepada" value="Kepala TVRI Stasiun Jawa Timur">
            <input type="hidden" name="dari" id="inDari" value="Kepala Sub Bagian Tata Usaha">

            <!-- Editable Inputs for Header Nota Dinas -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; margin-bottom: 18px;">
                <div class="form-group-block">
                    <label class="form-label-custom">Nomor Surat Nota Dinas</label>
                    <input type="text" name="nomor_surat" id="inNomorSurat" value="<?= htmlspecialchars($valNomorSurat) ?>" placeholder="Contoh: 1231/KP.09.02/I1.1.1/IX/2026" class="form-input-custom">
                </div>
                <div class="form-group-block">
                    <label class="form-label-custom">Hal / Perihal Nota Dinas</label>
                    <input type="text" name="perihal" id="inHal" value="<?= htmlspecialchars($valPerihal) ?>" required class="form-input-custom">
                </div>
                <div class="form-group-block">
                    <label class="form-label-custom">Sifat Surat</label>
                    <div class="input-wrapper" id="sifatSuratContainer" style="position: relative; width: 100%;">
                        <input type="hidden" name="sifat_surat" id="inSifat" value="<?= htmlspecialchars($valSifat) ?>" required>

                        <button type="button" class="custom-select-trigger" id="sifatSuratDropdownTrigger" onclick="toggleSifatSuratMenu(event)" style="width: 100%; display: flex; align-items: center; justify-content: space-between; height: 46px; padding: 10px 16px; background: #F9FAFB; border: 1.5px solid #E5E7EB; border-radius: 12px; font-size: 14px; color: #1F2937; font-weight: 500; cursor: pointer; transition: all 0.2s ease; outline: none; position: relative;">
                            <span id="sifatSuratSelectedText" style="color: #1F2937; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500;"><?= htmlspecialchars($valSifat) ?></span>
                            <i class="bi bi-chevron-down" id="sifatSuratChevronIcon" style="font-size: 13px; color: #6B7280; transition: transform 0.2s ease;"></i>
                        </button>

                        <!-- Custom Positioned Dropdown Popup Menu for Selecting Sifat Surat -->
                        <div id="sifatSuratDropdownMenu" style="display: none; position: absolute; top: calc(100% + 6px); left: 0; width: 100%; z-index: 99999; background: #FFFFFF; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.12), 0 4px 12px rgba(0,0,0,0.05); border: 1.5px solid #005BAC; padding: 6px;">
                            <div style="max-height: 250px; overflow-y: auto; padding-right: 2px;" id="sifatSuratOptionsList">
                                <div class="custom-sifat-option-item" data-val="Biasa" onclick="selectSifatSuratOption('Biasa', this)" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; cursor: pointer; transition: background 0.15s ease; margin-bottom: 2px;">
                                    <span style="font-size: 13.5px; font-weight: 600; color: #1F2937;">Biasa</span>
                                    <i class="bi bi-check2 check-icon-sifat" style="display: none; color: #005BAC; font-size: 18px; font-weight: 800;"></i>
                                </div>
                                <div class="custom-sifat-option-item" data-val="Segera" onclick="selectSifatSuratOption('Segera', this)" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; cursor: pointer; transition: background 0.15s ease; margin-bottom: 2px;">
                                    <span style="font-size: 13.5px; font-weight: 600; color: #1F2937;">Segera</span>
                                    <i class="bi bi-check2 check-icon-sifat" style="display: none; color: #005BAC; font-size: 18px; font-weight: 800;"></i>
                                </div>
                                <div class="custom-sifat-option-item" data-val="Sangat Segera" onclick="selectSifatSuratOption('Sangat Segera', this)" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; cursor: pointer; transition: background 0.15s ease; margin-bottom: 2px;">
                                    <span style="font-size: 13.5px; font-weight: 600; color: #1F2937;">Sangat Segera</span>
                                    <i class="bi bi-check2 check-icon-sifat" style="display: none; color: #005BAC; font-size: 18px; font-weight: 800;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 22px;">
                <div class="form-group-block">
                    <label class="form-label-custom">Lampiran</label>
                    <input type="text" name="lampiran" id="inLampiran" value="<?= htmlspecialchars($valLampiran) ?>" required class="form-input-custom">
                </div>
                <div class="form-group-block">
                    <label class="form-label-custom">Tanggal Nota Dinas</label>
                    <div class="modern-datepicker-wrapper">
                        <input type="text" name="tanggal_surat" id="inTanggal" value="<?= htmlspecialchars($valTanggal) ?>" required class="form-input-custom modern-datepicker" placeholder="Pilih tanggal nota dinas...">
                        <span class="datepicker-calendar-icon" title="Buka Kalender">
                            <i class="bi bi-calendar2-event-fill"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- BAGIAN DATA DIRI PEMOHON ("Saya yang Bertandatangan di bawah ini:") -->
            <div style="background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 14px; padding: 20px; margin-bottom: 22px;">
                <h3 style="font-size: 14.5px; font-weight: 800; color: #0F172A; margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-person-bounding-box" style="color: #2563EB;"></i> Saya yang Bertandatangan di bawah ini:
                </h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 14px;">
                    <div class="form-group-block">
                        <label class="form-label-custom">Nama Lengkap</label>
                        <input type="text" name="pemohon_nama" id="inPemohonNama" value="<?= htmlspecialchars($valPemohonNama) ?>" required class="form-input-custom">
                    </div>
                    <div class="form-group-block">
                        <label class="form-label-custom">NOPEG / NIP</label>
                        <input type="text" name="pemohon_nopeg" id="inPemohonNopeg" value="<?= htmlspecialchars($valPemohonNopeg) ?>" required class="form-input-custom">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group-block">
                        <label class="form-label-custom">Jabatan</label>
                        <input type="text" name="pemohon_jabatan" id="inPemohonJabatan" value="<?= htmlspecialchars($valPemohonJabatan) ?>" required class="form-input-custom">
                    </div>
                    <div class="form-group-block">
                        <label class="form-label-custom">Nomor HP</label>
                        <input type="text" name="pemohon_hp" id="inPemohonHp" value="<?= htmlspecialchars($valPemohonHp) ?>" required class="form-input-custom">
                    </div>
                </div>
            </div>

            <div class="form-group-block" style="margin-bottom: 18px;">
                <label class="form-label-custom">Isi Surat Nota Dinas</label>
                <textarea name="isi_surat" id="inIsiSurat" rows="6" class="form-input-custom" required><?= htmlspecialchars($valIsiSurat) ?></textarea>
            </div>

            <!-- Penutup Surat Default Hidden Input -->
            <input type="hidden" name="penutup_surat" id="inPenutupSurat" value="<?= htmlspecialchars($valPenutupSurat) ?>">

            <!-- GRID 2 KOLOM: TTD PEMOHON (KIRI) & PENANDATANGAN KPP BIDANG (KANAN) -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 22px; align-items: flex-start;">
                <!-- Tanda Tangan Digital Pemohon (Sisi Kiri) -->
                <div class="form-group-block">
                    <label class="form-label-custom" style="font-weight: 700; color: #1E3A8A; display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <i class="bi bi-pen-fill"></i> Tanda Tangan Digital Pemohon
                    </label>
                    
                    <div>
                        <div onclick="openSignatureModal()" title="Klik untuk membuat / menggambar TTD Digital Pemohon"
                             style="position: relative; width: 180px; height: 80px; background: #F8FAFC; border: 2px dashed #2563EB; border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; transition: background 0.2s;"
                             onmouseover="this.style.background='#EFF6FF';" onmouseout="this.style.background='#F8FAFC';">
                            <img id="formPemohonTtdImg" src="" style="max-height: 70px; object-fit: contain; display: none;">
                            <div id="formPemohonTtdPlaceholder" style="text-align: center; color: #64748B; font-size: 12px;">
                                <i class="bi bi-pencil-square" style="font-size: 20px; color: #2563EB; display: block; margin-bottom: 4px;"></i>
                                Klik untuk Isi TTD
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="pemohon_signature_data" id="pemohonSignatureData" value="">
                    <input type="hidden" name="penandatangan_nama" id="inPenandatanganNama" value="<?= htmlspecialchars($tuNama) ?>">
                    <input type="hidden" name="penandatangan_nip" id="inPenandatanganNip" value="<?= htmlspecialchars($tuNip) ?>">
                    <input type="hidden" name="penandatangan_jabatan" id="inPenandatanganJabatan" value="Kepala Sub Bagian Tata Usaha TVRI Stasiun Jawa Timur">
                </div>

                <!-- Penandatangan Surat (KPP Bidang) (Sisi Kanan) -->
            <style>
                #kppBidangDropdownMenu {
                    border: 1.5px solid #2563EB !important;
                    border-radius: 14px !important;
                    box-shadow: 0 20px 45px -8px rgba(0, 0, 0, 0.22), 0 8px 18px -4px rgba(0, 0, 0, 0.1) !important;
                    background: #FFFFFF !important;
                    transition: transform 0.15s ease, opacity 0.15s ease;
                }
                #kppBidangOptionsList::-webkit-scrollbar {
                    width: 6px;
                }
                #kppBidangOptionsList::-webkit-scrollbar-track {
                    background: #F8FAFC;
                    border-radius: 6px;
                }
                #kppBidangOptionsList::-webkit-scrollbar-thumb {
                    background: #CBD5E1;
                    border-radius: 6px;
                }
                #kppBidangOptionsList::-webkit-scrollbar-thumb:hover {
                    background: #94A3B8;
                }
                .custom-kpp-option-item:hover {
                    background: #EFF6FF !important;
                    border-color: #BFDBFE !important;
                }
                .custom-kpp-option-item:hover .kpp-option-title {
                    color: #1D4ED8 !important;
                }
            </style>
            <div class="form-group-block" style="position: relative;">
                <label class="form-label-custom" style="font-weight: 700; margin-bottom: 8px; color: #1E293B;">
                    Penandatangan Surat (KPP Bidang)
                </label>
                
                <div class="input-wrapper" id="kppBidangContainer" style="position: relative; max-width: 440px; width: 100%;">
                    <input type="hidden" name="kpp_user_id" id="inKppUserId" value="<?= htmlspecialchars($valKppUserId) ?>" required>

                    <button type="button" class="custom-select-trigger" id="kppBidangDropdownTrigger" onclick="toggleKppBidangMenu(event)" style="width: 100%; display: flex; align-items: center; justify-content: space-between; height: 46px; padding: 10px 16px; background: #F9FAFB; border: 1.5px solid #E5E7EB; border-radius: 12px; font-size: 14px; color: #1F2937; font-weight: normal; cursor: pointer; transition: all 0.2s ease; outline: none; position: relative;">
                        <span id="kppBidangSelectedText" style="color: #9CA3AF; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: normal;">-- Pilih KPP Bidang --</span>
                        <i class="bi bi-chevron-down" id="kppBidangChevronIcon" style="font-size: 13px; color: #6B7280; transition: transform 0.2s ease;"></i>
                    </button>

                    <!-- Custom Positioned Dropdown Popup Menu for Selecting KPP Bidang -->
                    <div id="kppBidangDropdownMenu" style="display: none; position: absolute; top: calc(100% + 6px); left: 0; min-width: 100%; width: 100%; z-index: 99999; background: #FFFFFF; border-radius: 14px; box-shadow: 0 20px 45px -8px rgba(0,0,0,0.22), 0 8px 18px -4px rgba(0,0,0,0.1); border: 1.5px solid #2563EB; padding: 8px; box-sizing: border-box;">
                        <div style="max-height: 250px; overflow-y: auto; padding-right: 2px;" id="kppBidangOptionsList">
                            <?php if (!empty($kppUsers)): ?>
                                <?php foreach ($kppUsers as $kpp): ?>
                                    <?php 
                                        $nipText = !empty($kpp['nip']) ? (str_starts_with(strtoupper($kpp['nip']), 'NIP') ? $kpp['nip'] : 'NIP. ' . $kpp['nip']) : '';
                                        $jabatanText = !empty($kpp['jabatan']) ? $kpp['jabatan'] : ('Ketua Perencana dan Pengendalian ' . ($kpp['divisi'] ?? ''));
                                        $divisiText = $kpp['divisi'] ?? '';
                                        $sigText = $kpp['signature'] ?? '';
                                        $displayName = 'KPP ' . htmlspecialchars($divisiText) . ' - ' . htmlspecialchars($kpp['name']);
                                        $isSelected = (!empty($valKppUserId) && $valKppUserId == $kpp['id']);
                                    ?>
                                    <div class="custom-kpp-option-item <?= $isSelected ? 'is-selected' : '' ?>" 
                                         data-val="<?= $kpp['id'] ?>"
                                         data-label="<?= $displayName ?>"
                                         data-nama="<?= htmlspecialchars($kpp['name']) ?>"
                                         data-nip="<?= htmlspecialchars($nipText) ?>"
                                         data-jabatan="<?= htmlspecialchars($jabatanText) ?>"
                                         data-divisi="<?= htmlspecialchars($divisiText) ?>"
                                         data-signature="<?= htmlspecialchars($sigText) ?>"
                                         onclick="selectKppBidangOption(<?= $kpp['id'] ?>, this)"
                                         style="display: flex; align-items: center; justify-content: space-between; padding: 9px 12px; border-radius: 10px; cursor: pointer; transition: all 0.15s ease; margin-bottom: 3px; <?= $isSelected ? 'background: #EFF6FF; border: 1px solid #BFDBFE;' : 'background: #FFFFFF; border: 1px solid transparent;' ?>">
                                        <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                                            <div class="kpp-avatar-wrap" style="width: 32px; height: 32px; border-radius: 8px; background: <?= $isSelected ? '#DBEAFE' : '#F1F5F9' ?>; color: <?= $isSelected ? '#1D4ED8' : '#475569' ?>; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; flex-shrink: 0;">
                                                <i class="bi bi-person-check-fill"></i>
                                            </div>
                                            <div style="display: flex; flex-direction: column; gap: 2px; min-width: 0;">
                                                <span class="kpp-option-title" style="font-size: 13px; font-weight: 700; color: <?= $isSelected ? '#1D4ED8' : '#1E293B' ?>; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= $displayName ?></span>
                                                <span style="font-size: 11.5px; color: #64748B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($jabatanText) ?></span>
                                            </div>
                                        </div>
                                        <div style="margin-left: 8px; flex-shrink: 0;">
                                            <i class="bi bi-check-circle-fill check-icon" style="<?= $isSelected ? 'display: block;' : 'display: none;' ?> color: #2563EB; font-size: 16px;"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <!-- ========================================================================= -->
        <!-- BAGIAN 2: UPLOAD BERKAS LAMPIRAN (OPSIONAL) -->
        <!-- ========================================================================= -->
        <div id="bagian2Card" class="card-panel-variant" style="border: 2px solid #FCA5A5; background: #FFFFFF; border-radius: 18px; padding: 28px; box-shadow: 0 4px 16px rgba(220, 38, 38, 0.05); position: relative; z-index: 10;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; padding-bottom: 14px; border-bottom: 2px dashed #FEE2E2;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 42px; height: 42px; border-radius: 12px; background: #FEF2F2; color: #DC2626; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800; border: 1px solid #FCA5A5;">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                    </div>
                    <div>
                        <h2 style="font-size: 17px; font-weight: 800; color: #991B1B; margin: 0; display: flex; align-items: center; gap: 8px;">
                            BAGIAN 2: UPLOAD BERKAS LAMPIRAN
                        </h2>
                    </div>
                </div>
            </div>

            <div>
                <input type="file" id="inUploadedPdf" name="lampiran_pdf[]" accept="application/pdf" multiple style="display: none;" onchange="handlePdfUpload(event)">
                <div id="existingPdfInputsContainer"></div>
                
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                    <button type="button" onclick="document.getElementById('inUploadedPdf').click();" class="btn-btn" style="background: #FEF2F2; color: #DC2626; border: 1.5px dashed #DC2626; font-weight: 700; padding: 10px 20px; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.2s ease;">
                        <i class="bi bi-cloud-arrow-up-fill" style="font-size: 18px; color: #DC2626;"></i> Pilih / Tambah Berkas PDF
                    </button>
                </div>

                <!-- Container Rendering List Berkas PDF Ter-upload -->
                <div id="pdfFileListContainer" style="display: flex; flex-direction: column; gap: 8px;">
                    <div id="emptyPdfMsg" style="font-size: 13px; color: #64748B; font-style: italic; background: #F8FAFC; padding: 12px 16px; border-radius: 10px; border: 1px dashed #CBD5E1;">
                        Belum ada berkas PDF lampiran dipilih.
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Bar Floating Card -->
        <div class="card-panel-variant" style="border-radius: 16px; padding: 18px 24px; background: #FFFFFF; border: 1.5px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            </div>

            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <a href="<?= $isEdit ? (BASE_URL . '/surat/revisi') : (BASE_URL . '/dashboard') ?>" class="btn-btn btn-btn-secondary">Batal</a>
                
                <button type="button" onclick="openReviewModal()" class="btn-btn btn-btn-primary" style="background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%); border: none; font-size: 14.5px; padding: 10px 22px; box-shadow: 0 4px 16px rgba(37, 99, 235, 0.35);">
                    <i class="bi bi-eye-fill"></i> Review dan Kirim
                </button>
            </div>
        </div>

        <input type="hidden" name="gdrive_web_link" id="inGdriveWebLink" value="">
    </form>
</div>

<!-- Modal Generate Barcode / Input Link Google Drive -->
<div id="barcodePreviewModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #FFFFFF; width: 100%; max-width: 500px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); overflow: hidden; display: flex; flex-direction: column;">
        <div style="padding: 18px 24px; background: #F0FDF4; border-bottom: 1px solid #DCFCE7; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: #DCFCE7; color: #15803D; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="bi bi-qr-code-scan"></i>
                </div>
                <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #14532D;">Generate Barcode QR Nota Dinas</h3>
            </div>
            <button type="button" onclick="closeBarcodeModal()" style="background: transparent; border: none; font-size: 24px; color: #64748B; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <div style="padding: 24px;">
            <p style="margin: 0 0 16px 0; font-size: 13.5px; color: #475569; line-height: 1.5;">
                Tempelkan / Paste link URL Google Drive berkas atau dokumen di bawah ini. URL ini akan di-generate menjadi <strong>Barcode QR</strong> pada footer Nota Dinas.
            </p>

            <div class="form-group-block" style="margin-bottom: 18px;">
                <label class="form-label-custom" style="font-weight: 700; color: #15803D; display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                    <i class="bi bi-link-45deg" style="font-size: 18px;"></i> Link URL Google Drive:
                </label>
                <div style="display: flex; gap: 8px;">
                    <input type="url" id="modalGdriveInput" placeholder="https://drive.google.com/file/d/..." class="form-input-custom" style="flex: 1; border-color: #16A34A;" oninput="updateLiveBarcodePreview(this.value)">
                    <button type="button" onclick="pasteGdriveFromClipboard()" class="btn-btn" style="background: #F0FDF4; color: #15803D; border: 1.5px solid #16A34A; font-weight: 700; white-space: nowrap; padding: 0 14px; font-size: 13px;" title="Tempel dari Clipboard">
                        <i class="bi bi-clipboard-check"></i> Paste
                    </button>
                </div>
            </div>

            <div style="background: #F8FAFC; border: 1.5px dashed #16A34A; border-radius: 14px; padding: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
                <div style="border: 2px solid #000; padding: 6px; background: #FFF; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 12px; width: 140px; height: 140px; display: flex; align-items: center; justify-content: center;">
                    <img id="modalQrPreviewImg" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https%3A%2F%2Fdrive.google.com" style="width: 128px; height: 128px; object-fit: contain;" alt="QR Code Preview">
                </div>
                <div id="modalQrStatusText" style="font-size: 12.5px; font-weight: 800; color: #15803D; display: flex; align-items: center; gap: 6px;">
                    <i class="bi bi-patch-check-fill" style="font-size: 16px;"></i> VERIFIED DIGITAL QR GOOGLE DRIVE
                </div>
                <div id="modalQrLinkPreview" style="font-size: 11px; color: #64748B; margin-top: 4px; word-break: break-all; max-width: 100%;">
                    https://drive.google.com
                </div>
            </div>
        </div>

        <div style="padding: 16px 24px; background: #F8FAFC; border-top: 1px solid #E2E8F0; display: flex; justify-content: flex-end; gap: 12px;">
            <button type="button" onclick="closeBarcodeModal()" class="btn-btn btn-btn-secondary" style="font-weight: 600;">Batal</button>
            <button type="button" onclick="saveBarcodeFromModal()" class="btn-btn" style="background: #16A34A; color: #FFFFFF; border: 1.5px solid #16A34A; font-weight: 700; padding: 10px 20px;">
                <i class="bi bi-check-lg"></i> Simpan &amp; Generate Barcode
            </button>
        </div>
    </div>
</div>

<!-- Modal Review Container (Preview Surat Nota Dinas) -->
<div id="reviewNotaDinasModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 16px; overflow-y: auto;">
    <div style="background: #FFFFFF; width: 98vw; max-width: 900px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(37, 99, 235, 0.25); overflow: hidden; display: flex; flex-direction: column; max-height: 95vh; border: 1px solid #BFDBFE;">
        <div style="padding: 16px 24px; background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); border-bottom: 1px solid #BFDBFE; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #FFFFFF; color: #1D4ED8; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15); border: 1px solid #BFDBFE;">
                    <i class="bi bi-file-earmark-check-fill"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #1E3A8A; letter-spacing: -0.2px;">Review &amp; Konfirmasi Dokumen Surat Nota Dinas</h3>
                </div>
            </div>
        </div>

        <div style="padding: 20px 24px; overflow-y: auto; background: #F8FAFC; flex: 1; display: flex; flex-direction: column; gap: 20px;">
            <div class="a4-paper-preview" style="background: #FFFFFF; border: 1px solid #CBD5E1; padding: 3cm; box-shadow: 0 4px 20px rgba(0,0,0,0.06); font-family: Arial, sans-serif; color: #000000; position: relative; width: 210mm; max-width: 100%; min-height: 270mm; margin: 0 auto; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; border-radius: 4px;">
                <div>
                    <!-- Logo Top Left -->
                    <div style="margin-bottom: 10px;">
                        <img src="<?= BASE_URL ?>/assets/img/tvri3.svg" style="height: 52px; width: auto;" alt="TVRI Logo">
                    </div>

                    <!-- Title Centered -->
                    <div style="text-align: center; margin-bottom: 10px;">
                        <h2 style="font-size: 16px; font-weight: normal; letter-spacing: normal; margin: 0; color: #000000; text-transform: uppercase; text-decoration: underline;">NOTA DINAS</h2>
                        <div style="font-size: 12.5px; color: #111111; margin-top: 3px;">Nomor : <span id="pvNomorSurat"></span></div>
                    </div>

                    <!-- Metadata Table -->
                    <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; line-height: 1.15; margin-bottom: 6px; color: #000000;">
                        <tr>
                            <td style="width: 100px; vertical-align: top; padding: 1.5px 0;">Kepada Yth.</td>
                            <td style="width: 12px; vertical-align: top; padding: 1.5px 0;">:</td>
                            <td style="vertical-align: top; padding: 1.5px 0;">
                                <span id="pvKepada" style="font-weight: 500;">Kepala TVRI Stasiun Jawa Timur</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 1.5px 0;">Dari</td>
                            <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                            <td style="vertical-align: top; padding: 1.5px 0;">Kepala Sub Bagian Tata Usaha</td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 1.5px 0;">Hal</td>
                            <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                            <td style="vertical-align: top; padding: 1.5px 0; font-weight: 500;">
                                <span id="pvHal">Permohonan Cuti Tahunan</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 1.5px 0;">Sifat</td>
                            <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                            <td id="pvSifat" style="vertical-align: top; padding: 1.5px 0;">Biasa</td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 1.5px 0;">Lampiran</td>
                            <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                            <td id="pvLampiran" style="vertical-align: top; padding: 1.5px 0;">1 (lembar)</td>
                        </tr>
                        <tr>
                            <td style="width: 100px; vertical-align: top; padding: 1.5px 0;">Tanggal</td>
                            <td style="width: 12px; vertical-align: top; padding: 1.5px 0;">:</td>
                            <td id="pvTanggal" style="vertical-align: top; padding: 1.5px 0;"><?= date('d F Y') ?></td>
                        </tr>
                    </table>

                    <div style="border-top: 1.5px solid #000000; margin: 6px 0 8px 0;"></div>
                    <div style="font-size: 12.5px; color: #000000; margin-bottom: 4px; line-height: 1.15;">Dengan Hormat,</div>
                    
                    <div style="font-size: 12.5px; color: #000000; margin-bottom: 4px; line-height: 1.15;">Saya yang Bertandatangan di bawah ini :</div>
                    
                    <!-- Section Pemohon Detail Table -->
                    <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; line-height: 1.15; margin-left: 15px; margin-bottom: 8px; color: #000000;">
                        <tr>
                            <td style="width: 100px; vertical-align: top; padding: 1.5px 0;">Nama</td>
                            <td style="width: 12px; vertical-align: top; padding: 1.5px 0;">:</td>
                            <td id="pvNamaPemohon" style="vertical-align: top; padding: 1.5px 0;">Bagus Mahendra Putra</td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 1.5px 0;">NOPEG</td>
                            <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                            <td id="pvNopegPemohon" style="vertical-align: top; padding: 1.5px 0;">202205 29091996</td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 1.5px 0;">Jabatan</td>
                            <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                            <td id="pvJabatanPemohon" style="vertical-align: top; padding: 1.5px 0;">Staf Umum/Pengemudi</td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top; padding: 1.5px 0;">Nomor HP.</td>
                            <td style="vertical-align: top; padding: 1.5px 0;">:</td>
                            <td id="pvHpPemohon" style="vertical-align: top; padding: 1.5px 0;">0856-4582-3054</td>
                        </tr>
                    </table>

                    <div id="pvIsiSurat" style="line-height: 1.15; text-align: justify; font-size: 12.5px; font-family: Arial, sans-serif; color: #000000; margin-bottom: 8px; white-space: pre-line;"></div>
                    <div id="pvPenutupSurat" style="line-height: 1.15; text-align: justify; font-size: 12.5px; font-family: Arial, sans-serif; color: #000000; margin-bottom: 10px; white-space: pre-line;"></div>

                    <!-- Signature Block (2 columns: Left for TU default, Right for Hormat Saya / Pemohon) -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 10px; margin-bottom: 10px;">
                        <!-- Left Column: Kepala Sub Bagian Tata Usaha -->
                        <div style="width: 250px; text-align: left; font-size: 12px; line-height: 1.15; color: #000000;">
                            <div>Kepala Sub Bagian Tata Usaha</div>
                            <div>TVRI Stasiun Jawa Timur</div>
                            <div style="height: 35px; margin: 3px 0;"></div>
                            <div style="text-decoration: underline;"><?= htmlspecialchars($tuNama) ?></div>
                            <div><?= htmlspecialchars($tuNip) ?></div>
                        </div>

                        <!-- Right Column: Hormat Saya (Pemohon) -->
                        <div style="width: 230px; text-align: center; font-size: 12px; line-height: 1.15; color: #000000;">
                            <div>Hormat Saya,</div>
                            <div style="min-height: 35px; margin: 3px 0; display: flex; align-items: center; justify-content: center;">
                                <img id="pvPTtdImg" src="" style="max-height: 35px; object-fit: contain; display: none;">
                                <div id="pvPTtdPlaceholder" style="height: 35px;"></div>
                            </div>
                            <div id="pvPNamaPenandatangan">Bagus Mahendra Putra</div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div style="padding-top: 12px; border-top: 1px solid #E2E8F0;">
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

            <!-- ROW 2: DOKUMEN PDF LAMPIRAN -->
            <div id="pvPdfPreviewWrapper" style="width: 100%; display: none; margin-top: 10px; padding-top: 24px; border-top: 2px dashed #CBD5E1;">
                <div id="pvPdfListContainer" style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 20px; align-items: stretch; width: 100%;"></div>
            </div>
        </div>

        <!-- Modal Bottom Bar Actions -->
        <div class="review-modal-footer" style="padding: 16px 24px; background: #FFFFFF; border-top: 1px solid #E2E8F0; gap: 12px; box-shadow: 0 -4px 12px rgba(0,0,0,0.03);">
            <div class="review-footer-left">
                <button type="button" onclick="printNotaDinasDocument()" class="btn-btn btn-review-print" style="background: #FFFFFF; color: #1E293B; border: 1.5px solid #CBD5E1; font-weight: 700; box-shadow: 0 1px 2px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-printer-fill" style="color: #2563EB; font-size: 16px;"></i> Print Surat
                </button>
            </div>
            <div class="review-footer-right" style="display: flex; gap: 12px; align-items: center;">
                <button type="button" onclick="closeReviewModal()" class="btn-btn btn-btn-secondary btn-review-close" style="min-width: 120px; justify-content: center; font-weight: 700;">Tutup</button>
                <button type="button" onclick="submitFormFromReview()" class="btn-btn btn-btn-primary btn-review-submit" style="background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%); border: none; min-width: 200px; justify-content: center; font-weight: 400; font-size: 14.5px; box-shadow: 0 4px 16px rgba(37, 99, 235, 0.35); transition: all 0.2s ease;" onmouseover="this.style.transform='translateY(-1px)';" onmouseout="this.style.transform='translateY(0)';">
                    <i class="bi bi-send-fill" style="font-size: 16px;"></i> Konfirmasi &amp; Kirim
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal TTD Digital Pemohon -->
<!-- ================================================== -->
<!-- MODAL NOTIFIKASI PERINGATAN PENANDATANGAN BELUM DIISI -->
<!-- ================================================== -->
<div id="penandatanganWarningNotificationModal" style="display: none; position: fixed; inset: 0; z-index: 10010; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #FFFFFF; width: 100%; max-width: 460px; border-radius: 20px; padding: 28px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35); text-align: center; position: relative;">
        <div style="width: 64px; height: 64px; border-radius: 50%; background: #FEF2F2; color: #DC2626; display: inline-flex; align-items: center; justify-content: center; font-size: 32px; margin-bottom: 16px; border: 2px solid #FCA5A5;">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800; color: #991B1B;">Penandatangan Surat Belum Dipilih!</h3>
        <p style="margin: 0 0 24px 0; font-size: 13.5px; color: #64748B; line-height: 1.5;">
            Anda belum memilih <strong>KPP Bidang Penandatangan Surat</strong>. Silakan pilih penandatangan surat terlebih dahulu sebelum melakukan konfirmasi dan mengirim dokumen.
        </p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button type="button" onclick="closePenandatanganWarningModal()" class="btn-btn btn-btn-secondary" style="flex: 1; justify-content: center; font-weight: 600;">Tutup</button>
            <button type="button" onclick="goToFillPenandatangan()" class="btn-btn btn-btn-primary" style="flex: 1.4; justify-content: center; background-color: #2563EB; border-color: #2563EB; font-weight: 700;">
                <i class="bi bi-person-check-fill"></i> Pilih Penandatangan
            </button>
        </div>
    </div>
</div>

<!-- Modal TTD Digital Pemohon -->
<div id="signatureModalPopup" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #FFFFFF; width: 100%; max-width: 520px; border-radius: 18px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); overflow: hidden; display: flex; flex-direction: column;">
        <div style="padding: 16px 24px; background: #EFF6FF; border-bottom: 1px solid #BFDBFE; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i id="modalIcon" class="bi bi-pen-fill" style="color: #2563EB; font-size: 20px;"></i>
                <h3 id="modalTitle" style="margin: 0; font-size: 17px; font-weight: 800; color: #1E3A8A;">Input Tanda Tangan Digital Pemohon</h3>
            </div>
        </div>

        <div style="padding: 24px;">
            <p id="modalInstruction" style="font-size: 13.5px; color: #64748B; margin-top: 0; margin-bottom: 14px;">
                Gunakan mouse atau layar sentuh di area di bawah untuk membubuhkan tanda tangan pemohon:
            </p>

            <div style="position: relative; width: 100%; height: 180px; background: #FFFFFF; border: 2px dashed #2563EB; border-radius: 12px; margin-bottom: 16px; overflow: hidden;">
                <canvas id="modalCanvas" style="width: 100%; height: 180px; cursor: crosshair; touch-action: none;"></canvas>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; gap: 8px;">
                    <button type="button" onclick="clearModalCanvas()" class="btn-btn btn-btn-secondary" style="padding: 6px 12px; font-size: 12.5px;">
                        <i class="bi bi-eraser-fill"></i> Hapus
                    </button>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="button" onclick="closeSignatureModal()" class="btn-btn btn-btn-secondary" style="padding: 8px 16px;">
                        Batal
                    </button>
                    <button type="button" onclick="saveSignatureFromModal()" class="btn-btn btn-btn-primary" style="padding: 8px 20px; font-weight: 700; background-color: #2563EB; border-color: #2563EB;">
                        <i class="bi bi-check-lg"></i> Simpan &amp; Tempel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function formatDateIndo(dateStr) {
    if (!dateStr) return '<?= date('d F Y') ?>';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const mIdx = parseInt(parts[1], 10) - 1;
        return parseInt(parts[2], 10) + ' ' + (months[mIdx] || parts[1]) + ' ' + parts[0];
    }
    return dateStr;
}

function toggleKppBidangMenu(e) {
    if (e) e.stopPropagation();
    var menu = document.getElementById('kppBidangDropdownMenu');
    var trigger = document.getElementById('kppBidangDropdownTrigger');
    var chevron = document.getElementById('kppBidangChevronIcon');
    var optionsList = document.getElementById('kppBidangOptionsList');
    var b1 = document.getElementById('bagian1Card');
    var cont = document.getElementById('kppBidangContainer');
    if (!menu || !trigger) return;

    var isOpen = menu.style.display === 'block';
    if (isOpen) {
        closeKppBidangMenu();
    } else {
        if (typeof closeSifatSuratMenu === 'function') closeSifatSuratMenu();
        if (b1) b1.style.zIndex = '500';
        if (cont) cont.style.zIndex = '1000';

        var rect = trigger.getBoundingClientRect();
        var spaceBelow = window.innerHeight - rect.bottom;
        var spaceAbove = rect.top;

        if (spaceBelow < 260 && spaceAbove > 200) {
            menu.style.top = 'auto';
            menu.style.bottom = 'calc(100% + 6px)';
            if (optionsList) optionsList.style.maxHeight = Math.min(260, spaceAbove - 30) + 'px';
        } else {
            menu.style.bottom = 'auto';
            menu.style.top = 'calc(100% + 6px)';
            if (optionsList) optionsList.style.maxHeight = Math.min(260, Math.max(160, spaceBelow - 30)) + 'px';
        }

        menu.style.display = 'block';
        if (chevron) chevron.style.transform = 'rotate(180deg)';
        if (trigger) {
            trigger.style.borderColor = '#2563EB';
            trigger.style.boxShadow = '0 0 0 4px rgba(37, 99, 235, 0.15)';
        }
    }
}

function closeKppBidangMenu() {
    var menu = document.getElementById('kppBidangDropdownMenu');
    var trigger = document.getElementById('kppBidangDropdownTrigger');
    var chevron = document.getElementById('kppBidangChevronIcon');
    var b1 = document.getElementById('bagian1Card');
    var cont = document.getElementById('kppBidangContainer');
    if (menu) menu.style.display = 'none';
    if (chevron) chevron.style.transform = 'rotate(0deg)';
    if (trigger) {
        trigger.style.borderColor = '#E5E7EB';
        trigger.style.boxShadow = 'none';
    }
    if (b1) b1.style.zIndex = '30';
    if (cont) cont.style.zIndex = '';
}

function toggleSifatSuratMenu(e) {
    if (e) e.stopPropagation();
    var menu = document.getElementById('sifatSuratDropdownMenu');
    var trigger = document.getElementById('sifatSuratDropdownTrigger');
    var chevron = document.getElementById('sifatSuratChevronIcon');
    var optionsList = document.getElementById('sifatSuratOptionsList');
    if (!menu || !trigger) return;

    var isOpen = menu.style.display === 'block';
    if (isOpen) {
        closeSifatSuratMenu();
    } else {
        if (typeof closeKppBidangMenu === 'function') closeKppBidangMenu();

        var rect = trigger.getBoundingClientRect();
        var spaceBelow = window.innerHeight - rect.bottom;
        var spaceAbove = rect.top;

        if (spaceBelow < 200 && spaceAbove > 150) {
            menu.style.top = 'auto';
            menu.style.bottom = 'calc(100% + 6px)';
            if (optionsList) optionsList.style.maxHeight = Math.min(200, spaceAbove - 20) + 'px';
        } else {
            menu.style.bottom = 'auto';
            menu.style.top = 'calc(100% + 6px)';
            if (optionsList) optionsList.style.maxHeight = Math.min(200, spaceBelow - 20) + 'px';
        }

        menu.style.display = 'block';
        if (chevron) chevron.style.transform = 'rotate(180deg)';
        if (trigger) {
            trigger.style.borderColor = '#005BAC';
            trigger.style.boxShadow = '0 0 0 4px rgba(0, 91, 172, 0.15)';
        }
    }
}

function closeSifatSuratMenu() {
    var menu = document.getElementById('sifatSuratDropdownMenu');
    var trigger = document.getElementById('sifatSuratDropdownTrigger');
    var chevron = document.getElementById('sifatSuratChevronIcon');
    if (menu) menu.style.display = 'none';
    if (chevron) chevron.style.transform = 'rotate(0deg)';
    if (trigger) {
        trigger.style.borderColor = '#E5E7EB';
        trigger.style.boxShadow = 'none';
    }
}

function selectSifatSuratOption(val, elem) {
    var hiddenInput = document.getElementById('inSifat');
    var textEl = document.getElementById('sifatSuratSelectedText');

    if (hiddenInput) hiddenInput.value = val;
    if (textEl) {
        textEl.textContent = val;
        textEl.style.color = '#1F2937';
        textEl.style.fontWeight = '500';
    }

    var items = document.querySelectorAll('#sifatSuratDropdownMenu .custom-sifat-option-item');
    items.forEach(function(item) {
        var isSelected = item.getAttribute('data-val') === val;
        if (isSelected) {
            item.style.background = '#EFF6FF';
            var check = item.querySelector('.check-icon-sifat');
            if (check) check.style.display = 'inline-block';
        } else {
            item.style.background = 'transparent';
            var check = item.querySelector('.check-icon-sifat');
            if (check) check.style.display = 'none';
        }
    });

    closeSifatSuratMenu();

    const pvSifat = document.getElementById('pvSifat');
    if (pvSifat) pvSifat.textContent = val;
}

function selectKppBidangOption(id, elem) {
    var hiddenInput = document.getElementById('inKppUserId');
    var textEl = document.getElementById('kppBidangSelectedText');

    if (hiddenInput) hiddenInput.value = id;
    if (textEl && elem) {
        textEl.textContent = elem.getAttribute('data-label') || '';
        textEl.style.color = '#1F2937';
        textEl.style.fontWeight = 'normal';
    }

    var items = document.querySelectorAll('#kppBidangDropdownMenu .custom-kpp-option-item');
    items.forEach(function(item) {
        var isSelected = item.getAttribute('data-val') == id;
        var check = item.querySelector('.check-icon');
        var avatar = item.querySelector('.kpp-avatar-wrap');
        var title = item.querySelector('.kpp-option-title');
        if (isSelected) {
            item.classList.add('is-selected');
            item.style.background = '#EFF6FF';
            item.style.borderColor = '#BFDBFE';
            if (check) check.style.display = 'block';
            if (avatar) {
                avatar.style.background = '#DBEAFE';
                avatar.style.color = '#1D4ED8';
            }
            if (title) title.style.color = '#1D4ED8';
        } else {
            item.classList.remove('is-selected');
            item.style.background = '#FFFFFF';
            item.style.borderColor = 'transparent';
            if (check) check.style.display = 'none';
            if (avatar) {
                avatar.style.background = '#F1F5F9';
                avatar.style.color = '#475569';
            }
            if (title) title.style.color = '#1E293B';
        }
    });

    closeKppBidangMenu();
}

function openReviewModal(showModal = true) {
    const nomorDVal = document.getElementById('inNomorSurat') ? document.getElementById('inNomorSurat').value.trim() : '';
    const perihalD = document.getElementById('inHal') ? document.getElementById('inHal').value : '';
    const sifatD = document.getElementById('inSifat') ? document.getElementById('inSifat').value : '';
    const tglDVal = document.getElementById('inTanggal') ? document.getElementById('inTanggal').value : '';
    const lampiranD = document.getElementById('inLampiran') ? document.getElementById('inLampiran').value : '';
    const isiD = document.getElementById('inIsiSurat') ? document.getElementById('inIsiSurat').value : '';
    const penutupD = document.getElementById('inPenutupSurat') ? document.getElementById('inPenutupSurat').value : '';

    const namaP = document.getElementById('inPemohonNama') ? document.getElementById('inPemohonNama').value : '';
    const nopegP = document.getElementById('inPemohonNopeg') ? document.getElementById('inPemohonNopeg').value : '';
    const jabatanP = document.getElementById('inPemohonJabatan') ? document.getElementById('inPemohonJabatan').value : '';
    const hpP = document.getElementById('inPemohonHp') ? document.getElementById('inPemohonHp').value : '';

    if (document.getElementById('pvNomorSurat')) {
        document.getElementById('pvNomorSurat').textContent = nomorDVal ? nomorDVal : '';
    }
    if (document.getElementById('pvHal')) document.getElementById('pvHal').textContent = perihalD;
    if (document.getElementById('pvSifat')) document.getElementById('pvSifat').textContent = sifatD;
    if (document.getElementById('pvLampiran')) document.getElementById('pvLampiran').textContent = lampiranD;
    if (document.getElementById('pvTanggal')) document.getElementById('pvTanggal').textContent = formatDateIndo(tglDVal);
    if (document.getElementById('pvIsiSurat')) document.getElementById('pvIsiSurat').textContent = isiD;
    if (document.getElementById('pvPenutupSurat')) document.getElementById('pvPenutupSurat').textContent = penutupD;

    if (document.getElementById('pvNamaPemohon')) document.getElementById('pvNamaPemohon').textContent = namaP;
    if (document.getElementById('pvNopegPemohon')) document.getElementById('pvNopegPemohon').textContent = nopegP;
    if (document.getElementById('pvJabatanPemohon')) document.getElementById('pvJabatanPemohon').textContent = jabatanP;
    if (document.getElementById('pvHpPemohon')) document.getElementById('pvHpPemohon').textContent = hpP;
    if (document.getElementById('pvPNamaPenandatangan')) document.getElementById('pvPNamaPenandatangan').textContent = namaP;

    // Sync TTD Pemohon preview
    const sigValP = document.getElementById('pemohonSignatureData') ? document.getElementById('pemohonSignatureData').value : '';
    const pvTtdImgP = document.getElementById('pvPTtdImg');
    const pvTtdPlaceholderP = document.getElementById('pvPTtdPlaceholder');
    if (sigValP && pvTtdImgP) {
        pvTtdImgP.src = sigValP;
        pvTtdImgP.style.display = 'block';
        if (pvTtdPlaceholderP) pvTtdPlaceholderP.style.display = 'none';
    } else if (pvTtdImgP) {
        pvTtdImgP.style.display = 'none';
        if (pvTtdPlaceholderP) {
            pvTtdPlaceholderP.style.display = 'block';
            pvTtdPlaceholderP.innerHTML = '';
        }
    }

    // Sync Multi-PDF Upload previews in Review Modal
    const pdfWrapper = document.getElementById('pvPdfPreviewWrapper');
    const pdfContainer = document.getElementById('pvPdfListContainer');
    if (pdfWrapper && pdfContainer) {
        if (uploadedPdfs.length > 0) {
            pdfWrapper.style.display = 'block';
            let pdfsHtml = '';
            uploadedPdfs.forEach((pdfItem, index) => {
                const pageCount = pdfItem.pageCount || 1;
                for (let p = 1; p <= pageCount; p++) {
                    const sideText = pageCount === 1 
                        ? `1 Lembar - ${pdfItem.sizeFormatted}` 
                        : `Halaman ${p} dari ${pageCount} - ${pdfItem.sizeFormatted}`;

                    const hasRendered = pdfItem.renderedPages && pdfItem.renderedPages[p];
                    const imgSrc = hasRendered ? pdfItem.renderedPages[p] : '';
                    const imgStyle = hasRendered ? 'display: block;' : 'display: none;';
                    const canvasStyle = hasRendered ? 'display: none;' : 'display: block;';

                    pdfsHtml += `
                    <div class="pdf-lampiran-wrapper-col">
                        <div style="padding: 10px 14px; background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: 10px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;" class="no-print">
                            <div style="display: flex; align-items: center; gap: 10px; min-width: 0; overflow: hidden;">
                                <i class="bi bi-file-earmark-pdf-fill" style="color: #DC2626; font-size: 22px; flex-shrink: 0;"></i>
                                <div style="min-width: 0; overflow: hidden;">
                                    <div style="font-size: 13px; font-weight: 800; color: #991B1B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Lampiran PDF ${index + 1}</div>
                                    <div style="font-size: 11px; color: #7F1D1D;">${sideText}</div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 10.5px; font-weight: 800; background: #DC2626; color: #FFFFFF; padding: 3px 10px; border-radius: 20px; flex-shrink: 0;">LEMBAR ${p}</span>
                            </div>
                        </div>

                        <div class="pdf-lampiran-card-preview" style="background: #FFFFFF; border: none; padding: 0 !important; box-shadow: 0 4px 20px rgba(0,0,0,0.06); font-family: Arial, sans-serif; color: #000000; position: relative; aspect-ratio: 210 / 297; min-height: 0; margin: 0 auto; box-sizing: border-box; border-radius: 4px; width: 100%; overflow: hidden;">
                            <canvas id="pdfCanvas_${pdfItem.id}_${p}" class="pdf-canvas-item" style="width: 100%; height: auto; object-fit: contain; ${canvasStyle} background: #FFFFFF; border: none; margin: 0; padding: 0;"></canvas>
                            <img id="pdfImg_${pdfItem.id}_${p}" class="pdf-img-item" src="${imgSrc}" style="width: 100%; height: auto; object-fit: contain; ${imgStyle} margin: 0 auto; border: none; padding: 0;">
                        </div>
                    </div>`;
                }
            });
            pdfContainer.innerHTML = pdfsHtml;

            setTimeout(() => {
                uploadedPdfs.forEach(pdfItem => {
                    renderPdfItemCanvases(pdfItem);
                });
            }, 50);
        } else {
            pdfWrapper.style.display = 'none';
            pdfContainer.innerHTML = '';
        }
    }

    // Sync Barcode QR Preview
    const gdriveVal = document.getElementById('inGdriveWebLink') ? document.getElementById('inGdriveWebLink').value : '';
    updateReviewBarcodePreview(gdriveVal);

    if (showModal) {
        document.body.style.overflow = 'hidden';
        document.getElementById('reviewNotaDinasModal').style.display = 'flex';
    }
}

async function renderPdfItemCanvases(pdfItem) {
    if (typeof pdfjsLib === 'undefined') return;
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    try {
        let loadingTask;
        if (pdfItem.arrayBuffer) {
            loadingTask = pdfjsLib.getDocument({ data: pdfItem.arrayBuffer.slice(0) });
        } else if (pdfItem.url || pdfItem.objectUrl) {
            loadingTask = pdfjsLib.getDocument(pdfItem.url || pdfItem.objectUrl);
        } else {
            return;
        }
        const pdf = await loadingTask.promise;
        const numPages = pdf.numPages;

        if (pdfItem.pageCount !== numPages) {
            pdfItem.pageCount = numPages;
            if (typeof openReviewModal === 'function') {
                openReviewModal(false);
                return;
            }
        }

        if (!pdfItem.renderedPages) pdfItem.renderedPages = {};

        for (let p = 1; p <= numPages; p++) {
            const canvasId = `pdfCanvas_${pdfItem.id}_${p}`;
            const imgId = `pdfImg_${pdfItem.id}_${p}`;
            const canvas = document.getElementById(canvasId);
            const img = document.getElementById(imgId);
            if (!canvas) continue;

            const page = await pdf.getPage(p);
            const parentContainer = canvas.parentElement;
            let availableWidth = 650;
            if (parentContainer && parentContainer.clientWidth > 0) {
                availableWidth = parentContainer.clientWidth;
            } else {
                const wrapper = canvas.closest('[style*="calc(50%"]') || canvas.closest('[style*="width: 210mm"]');
                if (wrapper && wrapper.clientWidth > 0) availableWidth = wrapper.clientWidth;
            }

            const userRot = lampiranPageRotations[`${pdfItem.id}_${p}`] || 0;
            const totalRot = (page.rotate + userRot) % 360;

            const unscaledViewport = page.getViewport({ scale: 1.0, rotation: totalRot });
            const baseScale = availableWidth > 0 ? Math.max(1.8, availableWidth / unscaledViewport.width) : 1.8;
            const dpr = Math.max(window.devicePixelRatio || 1, 2);
            const viewport = page.getViewport({ scale: baseScale * dpr, rotation: totalRot });

            canvas.width = Math.floor(viewport.width);
            canvas.height = Math.floor(viewport.height);
            canvas.style.width = '100%';
            canvas.style.height = 'auto';

            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };
            await page.render(renderContext).promise;

            const dataUrl = canvas.toDataURL('image/png');
            pdfItem.renderedPages[p] = dataUrl;

            if (img) {
                img.src = dataUrl;
                img.style.display = 'block';
                img.style.width = '100%';
                img.style.height = 'auto';
                canvas.style.display = 'none';
            }
        }
    } catch (err) {
        console.error('Error rendering PDF canvas:', err);
    }
}

let uploadedPdfs = [];

function getPdfPageCountFromBuffer(buffer) {
    try {
        const text = new TextDecoder('latin1').decode(new Uint8Array(buffer));
        const pagesObjMatch = text.match(/\/Type\s*\/Pages\b[\s\S]*?\/Count\s+(\d+)/);
        if (pagesObjMatch) {
            const count = parseInt(pagesObjMatch[1], 10);
            if (!isNaN(count) && count > 0) return count;
        }
        const countMatches = text.match(/\/Count\s+(\d+)/g);
        if (countMatches && countMatches.length > 0) {
            let maxCount = 1;
            countMatches.forEach(m => {
                const num = parseInt(m.replace(/\/Count\s+/, ''), 10);
                if (!isNaN(num) && num > maxCount) maxCount = num;
            });
            if (maxCount > 1) return maxCount;
        }
        const pageMatches = text.match(/\/Type\s*\/Page\b/g);
        if (pageMatches && pageMatches.length > 0) {
            return pageMatches.length;
        }
    } catch(e) {
        console.log('Error parsing PDF page count:', e);
    }
    return 1;
}

function handlePdfUpload(event) {
    const files = Array.from(event.target.files);
    if (!files || files.length === 0) return;

    let hasInvalid = false;
    let pendingCount = files.length;

    files.forEach(file => {
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            hasInvalid = true;
            pendingCount--;
            if (pendingCount === 0) {
                renderPdfFileList();
                syncPdfFileInput();
            }
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const buffer = e.target.result;
            const pageCount = getPdfPageCountFromBuffer(buffer);
            const id = 'pdf_' + Date.now() + '_' + Math.floor(Math.random() * 100000);
            const objectUrl = URL.createObjectURL(file);
            const sizeFormatted = (file.size / 1024).toFixed(1) + ' KB';

            const item = {
                id: id,
                file: file,
                fileName: file.name,
                objectUrl: objectUrl,
                arrayBuffer: buffer,
                sizeFormatted: sizeFormatted,
                pageCount: pageCount
            };
            uploadedPdfs.push(item);

            if (typeof pdfjsLib !== 'undefined') {
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                pdfjsLib.getDocument({ data: buffer.slice(0) }).promise.then(pdfDoc => {
                    if (pdfDoc && pdfDoc.numPages && item.pageCount !== pdfDoc.numPages) {
                        item.pageCount = pdfDoc.numPages;
                        renderPdfFileList();
                    }
                }).catch(err => {
                    console.log('pdfjsLib count error:', err);
                });
            }

            pendingCount--;
            if (pendingCount === 0) {
                renderPdfFileList();
                syncPdfFileInput();
            }
        };
        reader.onerror = function() {
            pendingCount--;
            if (pendingCount === 0) {
                renderPdfFileList();
                syncPdfFileInput();
            }
        };
        reader.readAsArrayBuffer(file);
    });

    if (hasInvalid) {
        alert('Beberapa berkas dilewati karena bukan format PDF (.pdf).');
    }
    event.target.value = '';
}

function renderPdfFileList() {
    const container = document.getElementById('pdfFileListContainer');
    if (!container) return;

    if (uploadedPdfs.length === 0) {
        container.innerHTML = `<div id="emptyPdfMsg" style="font-size: 13px; color: #64748B; font-style: italic; background: #F8FAFC; padding: 12px 16px; border-radius: 10px; border: 1px dashed #CBD5E1;">Belum ada berkas PDF lampiran dipilih.</div>`;
        return;
    }

    let html = '';
    uploadedPdfs.forEach((item, index) => {
        html += `
        <div style="background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: 10px; padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                <i class="bi bi-file-earmark-pdf-fill" style="color: #DC2626; font-size: 20px; flex-shrink: 0;"></i>
                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <div style="font-size: 13px; font-weight: 700; color: #991B1B; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${index + 1}. ${escapeHtml(item.fileName)}</div>
                    <div style="font-size: 11.5px; color: #7F1D1D;">Ukuran: ${item.sizeFormatted}</div>
                </div>
            </div>
            <button type="button" onclick="removePdfItem('${item.id}')" style="background: #FFFFFF; color: #DC2626; border: 1px solid #FCA5A5; border-radius: 8px; padding: 4px 10px; font-size: 12px; cursor: pointer; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;">
                <i class="bi bi-trash-fill"></i> Hapus
            </button>
        </div>`;
    });

    container.innerHTML = html;
}

function removePdfItem(id) {
    const idx = uploadedPdfs.findIndex(item => item.id === id);
    if (idx !== -1) {
        if (uploadedPdfs[idx].objectUrl) {
            URL.revokeObjectURL(uploadedPdfs[idx].objectUrl);
        }
        uploadedPdfs.splice(idx, 1);
    }
    renderPdfFileList();
    syncPdfFileInput();
}

function syncPdfFileInput() {
    try {
        const dt = new DataTransfer();
        uploadedPdfs.forEach(item => {
            if (item.file) {
                dt.items.add(item.file);
            }
        });
        const fileInp = document.getElementById('inUploadedPdf');
        if (fileInp) {
            fileInp.files = dt.files;
            fileInp.name = 'lampiran_pdf[]';
        }
        syncLampiranPageRotationsInput();
    } catch(e) {
        console.log('DataTransfer sync notice:', e);
    }

    const existingContainer = document.getElementById('existingPdfInputsContainer');
    if (existingContainer) {
        existingContainer.innerHTML = '';
        uploadedPdfs.forEach(item => {
            if (item.isExisting && item.path) {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'existing_pdf_paths[]';
                inp.value = item.path;
                existingContainer.appendChild(inp);
            }
        });
    }
}

let lampiranPageRotations = {};

function rotateLampiranPage(pdfId, pageNum) {
    const key = pdfId + '_' + pageNum;
    const currentRot = lampiranPageRotations[key] || 0;
    lampiranPageRotations[key] = (currentRot + 180) % 360;
    syncLampiranPageRotationsInput();

    const pdfItem = uploadedPdfs.find(item => item.id === pdfId);
    if (pdfItem) {
        renderPdfItemSinglePage(pdfItem, pageNum);
    }
}

function syncLampiranPageRotationsInput() {
    let inp = document.getElementById('inLampiranPageRotations');
    if (!inp) {
        inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'lampiran_page_rotations';
        inp.id = 'inLampiranPageRotations';
        const form = document.querySelector('form');
        if (form) form.appendChild(inp);
    }
    inp.value = JSON.stringify(lampiranPageRotations);
}

async function renderPdfItemSinglePage(pdfItem, pageNum) {
    if (typeof pdfjsLib === 'undefined') return;
    try {
        let loadingTask;
        if (pdfItem.arrayBuffer) {
            loadingTask = pdfjsLib.getDocument({ data: pdfItem.arrayBuffer.slice(0) });
        } else if (pdfItem.url || pdfItem.objectUrl) {
            loadingTask = pdfjsLib.getDocument(pdfItem.url || pdfItem.objectUrl);
        } else {
            return;
        }
        const pdf = await loadingTask.promise;
        const canvasId = `pdfCanvas_${pdfItem.id}_${pageNum}`;
        const imgId = `pdfImg_${pdfItem.id}_${pageNum}`;
        const canvas = document.getElementById(canvasId);
        const img = document.getElementById(imgId);
        if (!canvas) return;

        const page = await pdf.getPage(pageNum);
        const parentContainer = canvas.parentElement;
        let availableWidth = 650;
        if (parentContainer && parentContainer.clientWidth > 0) {
            availableWidth = parentContainer.clientWidth;
        }

        const userRot = lampiranPageRotations[`${pdfItem.id}_${pageNum}`] || 0;
        const totalRot = (page.rotate + userRot) % 360;

        const unscaledViewport = page.getViewport({ scale: 1.0, rotation: totalRot });
        const baseScale = availableWidth > 0 ? Math.max(1.8, availableWidth / unscaledViewport.width) : 1.8;
        const dpr = Math.max(window.devicePixelRatio || 1, 2);
        const viewport = page.getViewport({ scale: baseScale * dpr, rotation: totalRot });

        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);
        canvas.style.width = '100%';
        canvas.style.height = 'auto';

        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        const renderContext = {
            canvasContext: ctx,
            viewport: viewport
        };
        await page.render(renderContext).promise;

        const dataUrl = canvas.toDataURL('image/png');
        if (!pdfItem.renderedPages) pdfItem.renderedPages = {};
        pdfItem.renderedPages[pageNum] = dataUrl;

        if (img) {
            img.src = dataUrl;
            img.style.display = 'block';
            img.style.width = '100%';
            img.style.height = 'auto';
            canvas.style.display = 'none';
        }
    } catch (err) {
        console.error('Error rendering single page rotation:', err);
    }
}

async function printNotaDinasDocument() {
    openReviewModal(false);
    const modal = document.getElementById('reviewNotaDinasModal');
    if (modal) {
        if (!uploadedPdfs || uploadedPdfs.length === 0) {
            modal.classList.add('has-no-lampiran');
        } else {
            modal.classList.remove('has-no-lampiran');
        }
    }

    if (uploadedPdfs && uploadedPdfs.length > 0) {
        for (const item of uploadedPdfs) {
            if (!item.renderedPages || Object.keys(item.renderedPages).length === 0) {
                await renderPdfItemCanvases(item);
            }
        }
    }

    setTimeout(() => {
        printReviewModal('reviewNotaDinasModal');
    }, 150);
}

function printReviewModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    let papersHtml = '';

    // 1. Build main letter papers (Surat Permohonan / Nota Dinas)
    const mainPapers = modal.querySelectorAll('.a4-paper-preview');
    mainPapers.forEach((paper) => {
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
    });

    // 2. Build printable pages for PDF lampirans using upload_lampiran.php exact logic
    if (uploadedPdfs && uploadedPdfs.length > 0) {
        uploadedPdfs.forEach((pdfItem, index) => {
            const pageCount = pdfItem.pageCount || 1;
            for (let p = 1; p <= pageCount; p++) {
                let lampiranImgSrc = null;
                if (pdfItem.renderedPages && pdfItem.renderedPages[p]) {
                    lampiranImgSrc = pdfItem.renderedPages[p];
                } else {
                    const imgEl = document.getElementById(`pdfImg_${pdfItem.id}_${p}`);
                    const canvasEl = document.getElementById(`pdfCanvas_${pdfItem.id}_${p}`);
                    if (imgEl && imgEl.src && imgEl.src.length > 10 && !imgEl.src.includes('about:blank')) {
                        lampiranImgSrc = imgEl.src;
                    } else if (canvasEl && canvasEl.width > 0) {
                        try {
                            lampiranImgSrc = canvasEl.toDataURL('image/png');
                        } catch(e) {}
                    }
                }

                if (lampiranImgSrc) {
                    papersHtml += `
                    <div class="printable-a4-page printable-lampiran-page" style="padding: 0 !important; margin: 0 auto !important; width: 210mm !important; height: 297mm !important; max-height: 297mm !important; box-sizing: border-box !important; display: block !important; overflow: hidden !important; page-break-after: always !important; break-after: page !important; border: none !important; box-shadow: none !important; background: #ffffff !important;">
                        <img src="${lampiranImgSrc}" class="pdf-img-item" style="width: 100% !important; height: 100% !important; max-width: 100% !important; max-height: 297mm !important; object-fit: contain !important; display: block !important; margin: 0 auto !important; border: none !important; box-shadow: none !important; transform: none !important;">
                    </div>`;
                }
            }
        });
    }

    if (!papersHtml.trim()) {
        alert('Tidak ada dokumen untuk dicetak.');
        return;
    }

    const printWin = window.open('', '_blank', 'width=950,height=800');
    if (!printWin) {
        alert('Pop-up terblokir oleh browser. Harap izinkan pop-up untuk melakukan print.');
        return;
    }

    const doPrintScript = `
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
    `;

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
                .no-print { display: none !important; }
                .pdf-canvas-item { display: none !important; }
                .pdf-img-item { display: block !important; visibility: visible !important; width: 100% !important; height: auto !important; }
                @media print {
                    body { background: #fff !important; }
                    img { display: inline-block !important; visibility: visible !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                    .printable-a4-page { border: none !important; box-shadow: none !important; padding: 3cm !important; }
                    .printable-a4-page.printable-lampiran-page { padding: 0 !important; border: none !important; box-shadow: none !important; }
                }
            </style>
        </head>
        <body>
            ${papersHtml}
            <` + `script>${doPrintScript}<` + `/script>
        </body>
        </html>
    `);
    printWin.document.close();
}

function closeReviewModal() {
    const modal = document.getElementById('reviewNotaDinasModal');
    if (modal) {
        modal.style.display = 'none';
    }
    document.body.style.overflow = '';
}

function openBarcodeModal() {
    const hiddenVal = document.getElementById('inGdriveWebLink') ? document.getElementById('inGdriveWebLink').value : '';
    const inputElem = document.getElementById('modalGdriveInput');
    if (inputElem) {
        inputElem.value = hiddenVal;
        updateLiveBarcodePreview(hiddenVal);
    }
    document.body.style.overflow = 'hidden';
    document.getElementById('barcodePreviewModal').style.display = 'flex';
}

function closeBarcodeModal() {
    document.getElementById('barcodePreviewModal').style.display = 'none';
    document.body.style.overflow = '';
}

function updateLiveBarcodePreview(url) {
    const trimmed = (url || '').trim();
    const img = document.getElementById('modalQrPreviewImg');
    const statusText = document.getElementById('modalQrStatusText');
    const linkPreview = document.getElementById('modalQrLinkPreview');
    
    if (img && statusText && linkPreview) {
        if (trimmed) {
            const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(trimmed);
            img.src = qrUrl;
            statusText.innerHTML = '<i class="bi bi-patch-check-fill" style="font-size: 16px; color: #16A34A;"></i> BARCODE QR GOOGLE DRIVE AKTIF';
            linkPreview.innerText = trimmed;
            linkPreview.style.color = '#15803D';
        } else {
            const defaultUrl = 'https://drive.google.com';
            img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(defaultUrl);
            statusText.innerHTML = '<i class="bi bi-info-circle-fill" style="font-size: 16px; color: #EAB308;"></i> SIAP GENERATE BARCODE';
            linkPreview.innerText = 'Silakan paste / tempel URL Google Drive di atas';
            linkPreview.style.color = '#64748B';
        }
    }
}

async function pasteGdriveFromClipboard() {
    try {
        const text = await navigator.clipboard.readText();
        if (text) {
            const inputElem = document.getElementById('modalGdriveInput');
            if (inputElem) {
                inputElem.value = text.trim();
                updateLiveBarcodePreview(text.trim());
            }
        }
    } catch (err) {
        alert('Tidak dapat mengakses clipboard secara otomatis. Silakan tempelkan link (Ctrl+V) secara manual.');
    }
}

function saveBarcodeFromModal() {
    const inputVal = (document.getElementById('modalGdriveInput').value || '').trim();
    const hiddenInp = document.getElementById('inGdriveWebLink');
    if (hiddenInp) hiddenInp.value = inputVal;

    const btn = document.getElementById('btnBarcodeModal');
    const btnLabel = document.getElementById('btnBarcodeLabel');
    
    if (inputVal) {
        if (btn) {
            btn.style.background = '#DCFCE7';
            btn.style.color = '#15803D';
            btn.style.borderColor = '#16A34A';
        }
        if (btnLabel) {
            btnLabel.innerHTML = 'Barcode Ter-generate <i class="bi bi-check-circle-fill" style="margin-left: 4px;"></i>';
        }
    } else {
        if (btn) {
            btn.style.background = '#F0FDF4';
            btn.style.color = '#16A34A';
            btn.style.borderColor = '#16A34A';
        }
        if (btnLabel) {
            btnLabel.innerText = 'Generate Barcode';
        }
    }

    updateReviewBarcodePreview(inputVal);
    closeBarcodeModal();
}

function updateReviewBarcodePreview(url) {
    const pvBarcodeImg = document.getElementById('pvBarcodeImg');
    if (pvBarcodeImg) {
        const targetUrl = url || 'https://drive.google.com';
        pvBarcodeImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(targetUrl);
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function submitFormFromReview() {
    const kppSelect = document.getElementById('inKppUserId');
    if (!kppSelect || !kppSelect.value) {
        showPenandatanganWarningModal();
        return;
    }
    closeReviewModal();
    document.body.style.overflow = '';
    document.getElementById('formNotaDinas').submit();
}

function showPenandatanganWarningModal() {
    const modal = document.getElementById('penandatanganWarningNotificationModal');
    if (modal) modal.style.display = 'flex';
}

function closePenandatanganWarningModal() {
    const modal = document.getElementById('penandatanganWarningNotificationModal');
    if (modal) modal.style.display = 'none';
}

function goToFillPenandatangan() {
    closePenandatanganWarningModal();
    if (typeof closeReviewModal === 'function') {
        closeReviewModal();
    }
    const container = document.getElementById('kppBidangContainer') || document.getElementById('inKppUserId');
    if (container) {
        container.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    const trigger = document.getElementById('kppBidangDropdownTrigger');
    if (trigger) {
        setTimeout(function() {
            if (typeof toggleKppBidangMenu === 'function') {
                toggleKppBidangMenu();
            }
        }, 300);
    }
}

// Modal Canvas TTD Digital Pemohon
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
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return {
        x: clientX - rect.left,
        y: clientY - rect.top
    };
}

let lastX = 0, lastY = 0;

function startDrawingModal(e) {
    e.preventDefault();
    drawingModal = true;
    const coords = getCanvasCoords(e);
    lastX = coords.x;
    lastY = coords.y;

    if (modalCtx) {
        modalCtx.strokeStyle = '#000000';
        modalCtx.lineWidth = 3.2;
        modalCtx.lineCap = 'round';
        modalCtx.lineJoin = 'round';
        modalCtx.beginPath();
        modalCtx.arc(lastX, lastY, 1.6, 0, Math.PI * 2);
        modalCtx.fillStyle = modalCtx.strokeStyle;
        modalCtx.fill();
        modalCtx.beginPath();
        modalCtx.moveTo(lastX, lastY);
    }
}

function drawModal(e) {
    if (!drawingModal || !modalCtx) return;
    e.preventDefault();
    const coords = getCanvasCoords(e);

    modalCtx.strokeStyle = '#000000';
    modalCtx.lineWidth = 3.2;
    modalCtx.lineCap = 'round';
    modalCtx.lineJoin = 'round';

    const midX = (lastX + coords.x) / 2;
    const midY = (lastY + coords.y) / 2;
    modalCtx.quadraticCurveTo(lastX, lastY, midX, midY);
    modalCtx.stroke();

    lastX = coords.x;
    lastY = coords.y;
}

function stopDrawingModal() {
    drawingModal = false;
}

if (modalCanvas) {
    modalCanvas.addEventListener('mousedown', startDrawingModal);
    modalCanvas.addEventListener('mousemove', drawModal);
    modalCanvas.addEventListener('mouseup', stopDrawingModal);
    modalCanvas.addEventListener('mouseleave', stopDrawingModal);

    modalCanvas.addEventListener('touchstart', startDrawingModal, { passive: false });
    modalCanvas.addEventListener('touchmove', drawModal, { passive: false });
    modalCanvas.addEventListener('touchend', stopDrawingModal);
}

function openSignatureModal() {
    if (!modalCanvas || !modalCtx) return;
    document.body.style.overflow = 'hidden';
    document.getElementById('signatureModalPopup').style.display = 'flex';

    setupModalCanvasDpi();

    modalCtx.clearRect(0, 0, modalCanvas.width, modalCanvas.height);
    modalCtx.strokeStyle = '#000000';
    modalCtx.lineWidth = 5.5;
    modalCtx.lineCap = 'round';
    modalCtx.lineJoin = 'round';

    const modalInstruction = document.getElementById('modalInstruction');
    const namaPemohon = document.getElementById('inPemohonNama') ? document.getElementById('inPemohonNama').value : 'Pemohon';
    if (modalInstruction) {
        modalInstruction.textContent = 'Gunakan mouse atau layar sentuh di bawah untuk membubuhkan TTD ' + namaPemohon + ':';
    }

    const currentData = document.getElementById('pemohonSignatureData') ? document.getElementById('pemohonSignatureData').value : '';
    if (currentData) {
        const img = new Image();
        img.onload = () => {
            const rect = modalCanvas.getBoundingClientRect();
            modalCtx.drawImage(img, 0, 0, rect.width, rect.height);
        };
        img.src = currentData;
    } else {
        clearModalCanvas();
    }
}

function closeSignatureModal() {
    document.getElementById('signatureModalPopup').style.display = 'none';
    document.body.style.overflow = '';
}

function clearModalCanvas() {
    if (!modalCtx || !modalCanvas) return;
    modalCtx.clearRect(0, 0, modalCanvas.width, modalCanvas.height);
}

function saveSignatureFromModal() {
    if (!modalCanvas) return;
    const dataUrl = modalCanvas.toDataURL();
    
    const formImg = document.getElementById('formPemohonTtdImg');
    const formPlaceholder = document.getElementById('formPemohonTtdPlaceholder');
    const pvImg = document.getElementById('pvPTtdImg');
    const pvPlaceholder = document.getElementById('pvPTtdPlaceholder');

    if (formImg) {
        formImg.src = dataUrl;
        formImg.style.display = 'block';
    }
    if (formPlaceholder) formPlaceholder.style.display = 'none';

    if (pvImg) {
        pvImg.src = dataUrl;
        pvImg.style.display = 'block';
    }
    if (pvPlaceholder) pvPlaceholder.style.display = 'none';

    const sigInput = document.getElementById('pemohonSignatureData');
    if (sigInput) sigInput.value = dataUrl;

    closeSignatureModal();
}

window.addEventListener('click', function(e) {
    const modal = document.getElementById('reviewNotaDinasModal');
    const bcModal = document.getElementById('barcodePreviewModal');
    const sigModal = document.getElementById('signatureModalPopup');
    const warnPenandatanganModal = document.getElementById('penandatanganWarningNotificationModal');
    if (e.target === modal) {
        closeReviewModal();
    }
    if (e.target === bcModal) {
        closeBarcodeModal();
    }
    if (e.target === sigModal) {
        closeSignatureModal();
    }
    if (e.target === warnPenandatanganModal) {
        closePenandatanganWarningModal();
    }

    var container = document.getElementById('kppBidangContainer');
    if (container && !container.contains(e.target)) {
        closeKppBidangMenu();
    }

    var sifatContainer = document.getElementById('sifatSuratContainer');
    if (sifatContainer && !sifatContainer.contains(e.target)) {
        closeSifatSuratMenu();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    var initSifatVal = document.getElementById('inSifat') ? document.getElementById('inSifat').value : 'Biasa';
    var sifatItem = document.querySelector('.custom-sifat-option-item[data-val="' + initSifatVal + '"]');
    if (!sifatItem) {
        initSifatVal = 'Biasa';
        sifatItem = document.querySelector('.custom-sifat-option-item[data-val="Biasa"]');
    }
    if (sifatItem) {
        selectSifatSuratOption(initSifatVal, sifatItem);
    }
    const existingPdfPaths = <?= json_encode($existingPdfs) ?>;
    if (existingPdfPaths && existingPdfPaths.length > 0) {
        let pendingExisting = existingPdfPaths.length;
        existingPdfPaths.forEach((path, index) => {
            if (!path) {
                pendingExisting--;
                return;
            }
            const fileUrl = (path.startsWith('http') || path.startsWith('/')) 
                ? (path.startsWith('/') ? ('<?= BASE_URL ?>' + path) : path) 
                : ('<?= BASE_URL ?>/' + path);
            const fileName = path.split('/').pop() || ('Lampiran_' + (index + 1) + '.pdf');

            fetch(fileUrl)
                .then(res => res.arrayBuffer())
                .then(buffer => {
                    const pageCount = getPdfPageCountFromBuffer(buffer);
                    uploadedPdfs.push({
                        id: 'existing_pdf_' + index + '_' + Date.now(),
                        fileName: fileName,
                        url: fileUrl,
                        arrayBuffer: buffer,
                        sizeFormatted: (buffer.byteLength / 1024).toFixed(1) + ' KB',
                        pageCount: pageCount,
                        isExisting: true
                    });
                })
                .catch(err => {
                    console.error('Error fetching existing PDF:', err);
                    uploadedPdfs.push({
                        id: 'existing_pdf_' + index + '_' + Date.now(),
                        fileName: fileName,
                        url: fileUrl,
                        arrayBuffer: null,
                        sizeFormatted: 'PDF Lampiran',
                        pageCount: 1,
                        isExisting: true
                    });
                })
                .finally(() => {
                    pendingExisting--;
                    if (pendingExisting === 0) {
                        renderPdfFileList();
                    }
                });
        });
    }

    <?php if (!empty($valKppUserId)): ?>
    var kppItem = document.querySelector('.custom-kpp-option-item[data-val="<?= $valKppUserId ?>"]');
    if (kppItem) {
        selectKppBidangOption(<?= (int)$valKppUserId ?>, kppItem);
    }
    <?php endif; ?>

    const formEl = document.getElementById('formNotaDinas');
    if (formEl) {
        formEl.addEventListener('submit', function(e) {
            const kppSelect = document.getElementById('inKppUserId');
            if (!kppSelect || !kppSelect.value) {
                e.preventDefault();
                showPenandatanganWarningModal();
                return false;
            }
        });
    }
});
</script>
