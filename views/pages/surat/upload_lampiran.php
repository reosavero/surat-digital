<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

<?php 
$surat = $selectedSurat ?? null;
$meta = $surat['meta'] ?? [];
$defaultAgenda = $meta['nomor_agenda'] ?? '';
$monthsIndo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$todayAgendaIndo = date('j') . ' ' . $monthsIndo[(int)date('n')] . ' ' . date('Y');
$dispoData = is_array($disposisi ?? null) ? $disposisi : [];
$rawSifat = !empty($meta['sifat_surat']) ? $meta['sifat_surat'] : (!empty($dispoData['sifat_surat']) ? $dispoData['sifat_surat'] : '');
$sifatSelected = strtolower(trim($rawSifat));

$kepstaAccount = User::getSingleUserByRole('kepala_stasiun') ?: [];
$kepstaNamaDisplay = $kepstaAccount['name'] ?? 'Akbar Sahidi, S.Sos., M.Si';
$kepstaNipRaw = $kepstaAccount['nip'] ?? '197005121995031002';
$kepstaNipDisplay = !empty($kepstaNipRaw) ? (str_starts_with(strtoupper(trim($kepstaNipRaw)), 'NIP') ? trim($kepstaNipRaw) : 'NIP. ' . trim($kepstaNipRaw)) : 'NIP. 197005121995031002';

$tuAccount = User::getSingleUserByRole('tata_usaha') ?: [];
$tuNamaDisplay = $tuAccount['name'] ?? ($meta['penandatangan_nama'] ?? 'Maya Sulfiana, S.Kom');

$pdfLampirans = [];
if (!empty($meta['lampiran_pdf_paths']) && is_array($meta['lampiran_pdf_paths'])) {
    $pdfLampirans = $meta['lampiran_pdf_paths'];
} elseif (!empty($meta['lampiran_pdf_path'])) {
    $pdfLampirans = [$meta['lampiran_pdf_path']];
}
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
    .dropzone-upload-box {
        border: 2px dashed #CBD5E1;
        border-radius: 14px;
        padding: 24px;
        text-align: center;
        background: #F8FAFC;
        cursor: pointer;
        transition: all 0.25s ease;
    }
    .dropzone-upload-box:hover {
        border-color: #D97706;
        background: #FFFBEB;
    }

    /* Upload Disposisi Inputs Grid */
    .upload-disposisi-inputs-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 24px;
        width: 100%;
        box-sizing: border-box;
    }
    .upload-disposisi-input-col {
        min-width: 0;
        box-sizing: border-box;
    }
    .upload-disposisi-input-col input[type="text"] {
        width: 100%;
        padding: 9px 12px;
        border-radius: 10px;
        border: 1.5px solid #CBD5E1;
        font-size: 13.5px;
        font-weight: normal;
        color: #1E293B;
        background: #FFFFFF;
        box-sizing: border-box;
    }

    /* Upload Bottom Actions Bar */
    .upload-bottom-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        border-top: 1px solid #E2E8F0;
        padding-top: 20px;
        margin-top: 8px;
    }
    .btn-action-kembali {
        padding: 12px 24px;
        font-size: 15px;
        font-weight: 600;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
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
        .upload-disposisi-inputs-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }
        .admin-review-grid-container {
            flex-direction: column !important;
            gap: 14px !important;
        }
        .pdf-lampiran-wrapper-col {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
    }

    @media screen and (max-width: 768px) {
        /* All inputs identical 100% width on mobile */
        .upload-disposisi-inputs-grid {
            grid-template-columns: 1fr !important;
            gap: 12px !important;
            width: 100% !important;
        }
        .upload-disposisi-input-col {
            width: 100% !important;
            box-sizing: border-box !important;
        }

        /* Bottom Action Buttons */
        .upload-bottom-actions {
            flex-direction: column !important;
            gap: 10px !important;
            width: 100% !important;
            padding-top: 16px !important;
        }
        .btn-action-teruskan-kepsta {
            order: -1 !important;
            width: 100% !important;
            padding: 14px 20px !important;
            font-size: 15.5px !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 14px rgba(37,99,235,0.28) !important;
        }
        .btn-action-kembali {
            width: 100% !important;
            padding: 12px 14px !important;
            font-size: 14px !important;
            border-radius: 10px !important;
            justify-content: center !important;
            text-align: center !important;
        }
    }

    @media screen and (max-width: 576px) {
        .card-panel-variant {
            padding: 18px 14px !important;
            border-radius: 16px !important;
        }
        .dropzone-upload-box {
            padding: 18px 12px !important;
        }
        #disposisiReviewModal > div,
        #revisiModal > div,
        #validationWarningModal > div {
            width: 100% !important;
            max-width: 100% !important;
            border-radius: 18px !important;
        }
        #disposisiReviewModal .a4-paper-preview {
            padding: 12px !important;
        }
        .disposisi-modal-footer {
            flex-direction: column-reverse !important;
            gap: 10px !important;
            padding: 12px 16px !important;
            width: 100% !important;
        }
        .disposisi-modal-footer > * {
            width: 100% !important;
            justify-content: center !important;
        }
        .disposisi-modal-footer div {
            flex-direction: column !important;
            gap: 8px !important;
        }
        .disposisi-modal-footer .btn-btn {
            width: 100% !important;
            justify-content: center !important;
        }
    }
</style>

<div style="max-width: 1200px; margin: 0 auto; padding-bottom: 40px;">
    <!-- Page Header Header Navigation -->
    <div class="page-header-banner" style="margin-bottom: 24px;">
        <h1 style="font-size: 24px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 10px;">
            <i class="bi bi-file-earmark-arrow-up-fill" style="color: #D97706;"></i> Upload Lampiran & Lembar Disposisi Sekretaris
        </h1>
    </div>

    <!-- MAIN SINGLE CARD PANEL FOR UPLOAD LAMPIRAN & DISPOSISI -->
    <div class="card-panel-variant" style="padding: 32px; border-radius: 18px; background: #FFFFFF; border: 1.5px solid #D97706; box-shadow: 0 10px 25px -5px rgba(217, 119, 6, 0.12);">
        <!-- Card Header Badge & Title -->
        <div style="margin-bottom: 24px; padding-bottom: 18px; border-bottom: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 52px; height: 52px; border-radius: 14px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; font-size: 26px;">
                    <i class="bi bi-cloud-arrow-up-fill"></i>
                </div>
                <div>
                    <h2 style="font-size: 20px; font-weight: 800; color: #0F172A; margin: 0;">
                        Upload Lampiran & Lembar Disposisi Sekretaris
                    </h2>
                </div>
            </div>
        </div>

            <form action="<?= BASE_URL ?>/surat/upload-lampiran" method="POST" enctype="multipart/form-data" id="uploadLampiranForm" autocomplete="off">
                <?= Session::csrfField() ?>
                <input type="hidden" name="surat_id" value="<?= $surat['id'] ?? 0 ?>">
                <input type="hidden" name="action_type" id="inActionType" value="approve">
                <input type="hidden" name="nomor_surat" value="<?= htmlspecialchars($surat['nomor_surat'] ?? '') ?>">
                <input type="hidden" name="perihal" value="<?= htmlspecialchars($surat['perihal'] ?? '') ?>">

                <!-- BAGIAN 1: UPLOAD BERKAS LAMPIRAN PDF -->
                <div style="background: #FFFFFF; border: 1.5px solid #FDA4AF; border-radius: 20px; padding: 24px; margin-bottom: 28px; box-shadow: 0 4px 14px rgba(244, 63, 94, 0.04);">
                    <!-- Header Row -->
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <!-- PDF Icon Badge -->
                            <div style="width: 48px; height: 48px; border-radius: 12px; background: #FFE4E6; border: 1px solid #FECDD3; color: #DC2626; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                                <i class="bi bi-file-earmark-pdf-fill"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 16.5px; font-weight: 800; color: #881337; margin: 0; letter-spacing: 0.01em;">
                                    1. Fitur Upload Berkas Lampiran PDF
                                </h3>
                            </div>
                        </div>
                    </div>

                    <!-- Dashed Divider Line -->
                    <div style="border-top: 1px dashed #FECDD3; margin-bottom: 20px;"></div>

                    <!-- Upload Button & Format Info Row -->
                    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 16px;">
                        <button type="button" onclick="document.getElementById('inUploadedPdf').click()" 
                                style="display: inline-flex; align-items: center; gap: 8px; padding: 9px 20px; background: #FFF1F2; border: 1.5px dashed #F87171; border-radius: 25px; color: #BE123C; font-size: 13.5px; font-weight: 800; cursor: pointer; transition: all 0.2s ease;"
                                onmouseover="this.style.background='#FFE4E6'; this.style.borderColor='#E11D48';"
                                onmouseout="this.style.background='#FFF1F2'; this.style.borderColor='#F87171';">
                            <span style="width: 22px; height: 22px; border-radius: 50%; background: #E11D48; color: #FFFFFF; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">
                                <i class="bi bi-cloud-arrow-up-fill"></i>
                            </span>
                            <span>Pilih / Tambah Berkas PDF</span>
                        </button>

                        <input type="file" name="lampiran_pdf[]" id="inUploadedPdf" accept="application/pdf" multiple style="display: none;" onchange="handlePdfUpload(event)">
                        <div id="existingPdfInputsContainer"></div>
                    </div>

                    <!-- Files List / Status Box -->
                    <div id="pdfFileListContainer" style="display: flex; flex-direction: column; gap: 8px;">
                        <div id="emptyPdfMsg" style="font-size: 13px; color: #64748B; font-style: italic; background: #F8FAFC; padding: 12px 16px; border-radius: 10px; border: 1px dashed #CBD5E1;">
                            Belum ada berkas PDF lampiran dipilih.
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: FORM INPUT LEMBAR DISPOSISI SEKRETARIS -->
                <div style="margin-bottom: 24px; background: #FFFFFF; border: 1.5px solid #CBD5E1; border-radius: 14px; padding: 24px;">
                    <div style="margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #E2E8F0;">
                        <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="bi bi-pencil-square" style="color: #D97706;"></i> 2. Form Input Lembar Disposisi Sekretaris
                        </h3>
                    </div>

                    <!-- INPUT 1, 2, 3, 4, 5 (UNIFORM RESPONSIVE GRID) -->
                    <div class="upload-disposisi-inputs-grid">
                        <!-- 1. Nomor Agenda -->
                        <div class="upload-disposisi-input-col">
                            <label style="display: block; font-size: 13.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">
                                1. Nomor Agenda
                            </label>
                            <input type="text" name="nomor_agenda" id="inNomorAgenda" value="<?= htmlspecialchars($defaultAgenda) ?>" placeholder="Ketik nomor agenda..." required
                                   oninput="syncDisposisiPreview()">
                        </div>

                        <!-- 2. Tanggal Agenda -->
                        <div class="upload-disposisi-input-col">
                            <label style="display: block; font-size: 13.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">
                                2. Tanggal
                            </label>
                            <div class="modern-datepicker-wrapper">
                                <input type="text" name="tanggal_agenda" id="inTanggalAgenda"
                                       value="<?= htmlspecialchars($todayAgendaIndo) ?>"
                                       data-default-date="<?= date('Y-m-d') ?>"
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

                        <!-- 3. Nomor Surat -->
                        <div class="upload-disposisi-input-col">
                            <label style="display: block; font-size: 13.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">
                                3. Nomor Surat
                            </label>
                            <input type="text" name="nomor_surat" id="inNomorSurat" value="<?= htmlspecialchars($meta['nomor_surat_disposisi'] ?? $surat['nomor_surat'] ?? '') ?>" placeholder="Ketik nomor surat..."
                                   oninput="syncDisposisiPreview()">
                        </div>

                        <!-- 4. Dari -->
                        <div class="upload-disposisi-input-col">
                            <label style="display: block; font-size: 13.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">
                                4. Dari
                            </label>
                            <input type="text" name="dari" id="inDari" value="<?= htmlspecialchars($meta['dari'] ?? '') ?>" placeholder="Ketik pengirim (dari)..."
                                   oninput="syncDisposisiPreview()">
                        </div>

                        <!-- 5. Hal -->
                        <div class="upload-disposisi-input-col">
                            <label style="display: block; font-size: 13.5px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">
                                5. Hal
                            </label>
                            <input type="text" name="hal_disposisi" id="inHalDisposisi" value="<?= htmlspecialchars($meta['hal_disposisi'] ?? $dispoData['hal'] ?? '') ?>" placeholder="Ketik hal / perihal disposisi..."
                                   oninput="syncDisposisiPreview()">
                        </div>
                    </div>
                </div>

                <!-- FORM ACTIONS -->
                <div class="upload-bottom-actions">
                    <a href="<?= BASE_URL ?>/dashboard" class="btn-btn btn-btn-secondary btn-action-kembali">
                        Kembali
                    </a>

                    <button type="button" onclick="handleTeruskanKepsta()" class="btn-btn btn-btn-primary btn-action-teruskan-kepsta">
                        <i class="bi bi-send-fill"></i> Teruskan ke Kepsta
                    </button>
                </div>

                <!-- Hidden Catatan Revisi Field -->
                <input type="hidden" name="catatan_revisi" id="inCatatanRevisi">
            </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 1: REVIEW DISPOSISI & LAMPIRAN SHEET  -->
<!-- ========================================== -->
<div id="disposisiReviewModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px; overflow-y: auto; overscroll-behavior: contain;">
    <div style="background: #FFFFFF; width: 100%; max-width: 1100px; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); overflow: hidden; display: flex; flex-direction: column; max-height: 94vh; overscroll-behavior: contain;">
        <!-- Modal Top Bar -->
        <div style="padding: 14px 24px; background: #EFF6FF; border-bottom: 1px solid #BFDBFE; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-eye-fill" style="color: #1D4ED8; font-size: 20px;"></i>
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #1E3A8A;">Review Lembar Disposisi Sekretaris & Lampiran PDF</h3>
            </div>
        </div>

        <!-- Document Canvas Container -->
        <div style="padding: 24px; overflow-y: auto; background: #F8FAFC; flex: 1; overscroll-behavior: contain;">
            <!-- CONTAINER DOKUMEN PDF LAMPIRAN TER-UPLOAD (DI ATAS LEMBAR DISPOSISI) -->
            <div id="pvPdfPreviewWrapper" style="display: none; margin-bottom: 30px; width: 100%;">
                <div style="margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between;">
                    <h4 style="font-size: 15px; font-weight: 800; color: #991B1B; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-file-earmark-pdf-fill" style="color: #DC2626;"></i> Pratinjau Berkas Lampiran PDF Ter-upload
                    </h4>
                    <span style="font-size: 11px; font-weight: 700; color: #BE123C; background: #FFF1F2; padding: 4px 12px; border-radius: 20px; border: 1px solid #FECDD3;">
                        DOKUMEN LAMPIRAN TERKIRIM
                    </span>
                </div>
                <div id="pvPdfListContainer" class="admin-review-grid-container" style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 20px; align-items: stretch; width: 100%;"></div>
            </div>

            <!-- LEMBAR DISPOSISI SEKRETARIS (DI BAWAH DOKUMEN LAMPIRAN) -->
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
                                <td colspan="4" id="modalTanggalText" style="vertical-align: top; padding: 2px 0;"><?= htmlspecialchars($todayAgendaIndo) ?></td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 2px 0;">Dari</td>
                                <td style="vertical-align: top; padding: 2px 0;">:</td>
                                <td style="vertical-align: top; padding: 2px 0; width: 38%; font-weight: normal;" id="modalDariText">
                                    <?= htmlspecialchars($meta['dari'] ?? '') ?>
                                </td>
                                
                                <td style="width: 95px; vertical-align: top; padding: 2px 0;">Nomor Surat</td>
                                <td style="width: 12px; vertical-align: top; padding: 2px 0;">:</td>
                                <td style="vertical-align: top; padding: 2px 0; font-weight: normal;" id="modalNomorSuratText">
                                    <?= htmlspecialchars($meta['nomor_surat_disposisi'] ?? $surat['nomor_surat'] ?? '') ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 2px 0;">Hal</td>
                                <td style="vertical-align: top; padding: 2px 0;">:</td>
                                <td colspan="4" id="modalHalText" style="vertical-align: top; padding: 2px 0; font-weight: normal;"><?= htmlspecialchars($meta['hal_disposisi'] ?? $dispoData['hal'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 4px 0;">Sifat</td>
                                <td style="vertical-align: top; padding: 4px 0;">:</td>
                                <td colspan="4" style="vertical-align: top; padding: 4px 0;">
                                     <div style="display: flex; gap: 30px; align-items: center; font-size: 12.5px; font-family: Arial, sans-serif;">
                                         <label style="display: flex; align-items: center; gap: 6px;">Biasa <span id="modalChkBiasa" style="display: inline-flex; align-items: center; justify-content: center; width: 14px; height: 14px; border: 1.5px solid #000; position: relative; overflow: visible;"></span></label>
                                         <label style="display: flex; align-items: center; gap: 6px;">Penting <span id="modalChkPenting" style="display: inline-flex; align-items: center; justify-content: center; width: 14px; height: 14px; border: 1.5px solid #000; position: relative; overflow: visible;"></span></label>
                                         <label style="display: flex; align-items: center; gap: 6px;">Segera <span id="modalChkSegera" style="display: inline-flex; align-items: center; justify-content: center; width: 14px; height: 14px; border: 1.5px solid #000; position: relative; overflow: visible;"></span></label>
                                         <label style="display: flex; align-items: center; gap: 6px;">Rahasia <span id="modalChkRahasia" style="display: inline-flex; align-items: center; justify-content: center; width: 14px; height: 14px; border: 1.5px solid #000; position: relative; overflow: visible;"></span></label>
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
                                <div style="font-weight: normal;">Sekretariat : </div>
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
                    <div style="display: flex; justify-content: flex-end; align-items: flex-start; font-size: 12px; color: #000000; margin-top: 10px;">
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
            <button type="button" onclick="printDisposisiReviewDocument()" class="btn-btn" style="background: #FFFFFF; color: #1E293B; border: 1.5px solid #CBD5E1; font-weight: 700; padding: 10px 20px; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                <i class="bi bi-printer-fill" style="color: #2563EB; font-size: 16px;"></i> Print Surat
            </button>
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
<!-- MODAL 3: PERINGATAN KELENGKAPAN DATA       -->
<!-- ========================================== -->
<div id="validationWarningModal" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #FFFFFF; width: 100%; max-width: 460px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); text-align: center; padding: 28px 24px; border: 1px solid #F3F4F6; position: relative;">
        <div style="width: 60px; height: 60px; border-radius: 50%; background: #FEF2F2; color: #DC2626; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 28px; border: 2px solid #FCA5A5;">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800; color: #0F172A;">Data Belum Lengkap</h3>
        <p style="margin: 0 0 14px 0; font-size: 13.5px; color: #64748B; line-height: 1.5;">
            Mohon lengkapi berkas lampiran & form berikut sebelum meneruskan ke Kepala Stasiun:
        </p>
        <div id="validationWarningList" style="text-align: left; background: #FFF1F2; border: 1px solid #FECDD3; border-radius: 12px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #991B1B; line-height: 1.6;">
        </div>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button type="button" onclick="closeValidationWarningModal()" class="btn-btn btn-btn-primary" style="flex: 1; justify-content: center; font-weight: 700; padding: 10px 20px; background: #2563EB; border-color: #2563EB;">
                Tutup & Lengkapi Data
            </button>
        </div>
    </div>
</div>

<script>
window.existingPdfLampirans = <?= json_encode($pdfLampirans) ?>;
let uploadedPdfs = [];

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getPdfPageCountFromBuffer(buffer) {
    try {
        const text = new TextDecoder('ascii').decode(new Uint8Array(buffer));
        const matches = text.match(/\/Type\s*\/Page\b/g);
        if (matches && matches.length > 0) {
            return matches.length;
        }
        const countMatch = text.match(/\/Count\s+(\d+)/);
        if (countMatch && countMatch[1]) {
            return parseInt(countMatch[1], 10);
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

function renderPdfFileList() {
    const container = document.getElementById('pdfFileListContainer');
    if (!container) return;

    if (uploadedPdfs.length === 0) {
        container.innerHTML = `<div id="emptyPdfMsg" style="font-size: 13px; color: #64748B; font-style: italic; background: #F8FAFC; padding: 12px 16px; border-radius: 10px; border: 1px dashed #CBD5E1;">Belum ada berkas PDF lampiran dipilih.</div>`;
    } else {
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

    const modalPdfBadge = document.getElementById('modalPdfCountBadge');
    if (modalPdfBadge) {
        modalPdfBadge.textContent = uploadedPdfs.length > 0 ? (uploadedPdfs.length + ' File PDF') : 'Dokumen PDF Ready';
    }
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

function syncDisposisiPreview() {
    const agendaEl = document.getElementById('inNomorAgenda');
    const tanggalEl = document.getElementById('inTanggalAgenda');
    const nomorSuratEl = document.getElementById('inNomorSurat');
    const dariEl = document.getElementById('inDari');
    const halEl = document.getElementById('inHalDisposisi');

    const modalAgenda = document.getElementById('modalAgendaText');
    const modalTanggal = document.getElementById('modalTanggalText');
    const modalNomorSurat = document.getElementById('modalNomorSuratText');
    const modalDari = document.getElementById('modalDariText');
    const modalHal = document.getElementById('modalHalText');

    if (modalAgenda && agendaEl) modalAgenda.textContent = agendaEl.value || '';
    if (modalTanggal && tanggalEl) modalTanggal.textContent = tanggalEl.value || '';
    if (modalNomorSurat && nomorSuratEl) modalNomorSurat.textContent = nomorSuratEl.value || '';
    if (modalDari && dariEl) modalDari.textContent = dariEl.value || '';
    if (modalHal && halEl) modalHal.textContent = halEl.value || '';

    const rawSifat = '<?= strtolower(trim($rawSifat)) ?>';
    const svgCheckHtml = `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block; position: absolute; top: -5px; left: -2px; pointer-events: none;"><path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
    
    const elBiasa = document.getElementById('modalChkBiasa');
    const elSegera = document.getElementById('modalChkSegera');
    const elPenting = document.getElementById('modalChkPenting');
    const elRahasia = document.getElementById('modalChkRahasia');

    if (elBiasa) elBiasa.innerHTML = (rawSifat === 'biasa') ? svgCheckHtml : '';
    if (elSegera) elSegera.innerHTML = (rawSifat === 'segera') ? svgCheckHtml : '';
    if (elPenting) elPenting.innerHTML = (rawSifat === 'penting') ? svgCheckHtml : '';
    if (elRahasia) elRahasia.innerHTML = (rawSifat === 'rahasia') ? svgCheckHtml : '';

    const modalPdfBadge = document.getElementById('modalPdfCountBadge');
    if (modalPdfBadge) {
        modalPdfBadge.textContent = uploadedPdfs.length > 0 ? (uploadedPdfs.length + ' File PDF') : 'Dokumen PDF Ready';
    }

    syncDisposisiReviewModalPdfPreview();
}

function syncDisposisiReviewModalPdfPreview() {
    const pdfWrapper = document.getElementById('pvPdfPreviewWrapper');
    const pdfContainer = document.getElementById('pvPdfListContainer');
    if (!pdfWrapper || !pdfContainer) return;

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
            syncDisposisiReviewModalPdfPreview();
            return;
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

            const unscaledViewport = page.getViewport({ scale: 1.0 });
            const baseScale = availableWidth > 0 ? Math.max(1.8, availableWidth / unscaledViewport.width) : 1.8;
            const dpr = Math.max(window.devicePixelRatio || 1, 2);
            const viewport = page.getViewport({ scale: baseScale * dpr });

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

function handleTeruskanKepsta() {
    openDisposisiReviewModal();
}

function openDisposisiReviewModal() {
    syncDisposisiPreview();
    document.body.style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';
    const modal = document.getElementById('disposisiReviewModal');
    if (modal) modal.style.display = 'flex';
}

function closeDisposisiReviewModal() {
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
    const modal = document.getElementById('disposisiReviewModal');
    if (modal) modal.style.display = 'none';
}

function confirmAndSubmitToKepsta() {
    const agendaEl = document.getElementById('inNomorAgenda');
    const tanggalEl = document.getElementById('inTanggalAgenda');
    const nomorSuratEl = document.getElementById('inNomorSurat');
    const dariEl = document.getElementById('inDari');
    const halEl = document.getElementById('inHalDisposisi');

    const missing = [];

    if (!uploadedPdfs || uploadedPdfs.length === 0) {
        missing.push('Berkas Lampiran PDF (Belum ada berkas PDF diunggah)');
    }
    if (!agendaEl || !agendaEl.value.trim()) {
        missing.push('Nomor Agenda');
    }
    if (!tanggalEl || !tanggalEl.value.trim()) {
        missing.push('Tanggal Agenda');
    }
    if (!nomorSuratEl || !nomorSuratEl.value.trim()) {
        missing.push('Nomor Surat');
    }
    if (!dariEl || !dariEl.value.trim()) {
        missing.push('Dari (Pengirim)');
    }
    if (!halEl || !halEl.value.trim()) {
        missing.push('Hal / Perihal Disposisi');
    }

    if (missing.length > 0) {
        openValidationWarningModal(missing);
        return;
    }

    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
    document.getElementById('inActionType').value = 'approve';
    document.getElementById('uploadLampiranForm').submit();
}

function openValidationWarningModal(missingList) {
    const listContainer = document.getElementById('validationWarningList');
    if (listContainer) {
        listContainer.innerHTML = '<ul style="margin: 0; padding-left: 20px;">' +
            missingList.map(item => `<li><strong>${escapeHtml(item)}</strong></li>`).join('') +
            '</ul>';
    }
    const modal = document.getElementById('validationWarningModal');
    if (modal) modal.style.display = 'flex';
}

function closeValidationWarningModal() {
    const modal = document.getElementById('validationWarningModal');
    if (modal) modal.style.display = 'none';
    closeDisposisiReviewModal();

    const agendaEl = document.getElementById('inNomorAgenda');
    const nomorSuratEl = document.getElementById('inNomorSurat');
    const dariEl = document.getElementById('inDari');
    const halEl = document.getElementById('inHalDisposisi');

    if (!uploadedPdfs || uploadedPdfs.length === 0) {
        const fileBtn = document.getElementById('inUploadedPdf');
        if (fileBtn) fileBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else if (agendaEl && !agendaEl.value.trim()) {
        agendaEl.focus();
    } else if (nomorSuratEl && !nomorSuratEl.value.trim()) {
        nomorSuratEl.focus();
    } else if (dariEl && !dariEl.value.trim()) {
        dariEl.focus();
    } else if (halEl && !halEl.value.trim()) {
        halEl.focus();
    }
}

async function printDisposisiReviewDocument() {
    const modal = document.getElementById('disposisiReviewModal');
    if (!modal) return;

    // 1. Sync form text fields (agenda, tanggal, nomor_surat, dari, hal) to disposisi preview
    const agendaEl = document.getElementById('inNomorAgenda');
    const tanggalEl = document.getElementById('inTanggalAgenda');
    const nomorSuratEl = document.getElementById('inNomorSurat');
    const dariEl = document.getElementById('inDari');
    const halEl = document.getElementById('inHalDisposisi');

    const modalAgenda = document.getElementById('modalAgendaText');
    const modalTanggal = document.getElementById('modalTanggalText');
    const modalNomorSurat = document.getElementById('modalNomorSuratText');
    const modalDari = document.getElementById('modalDariText');
    const modalHal = document.getElementById('modalHalText');

    if (modalAgenda && agendaEl) modalAgenda.textContent = agendaEl.value || '';
    if (modalTanggal && tanggalEl) modalTanggal.textContent = tanggalEl.value || '';
    if (modalNomorSurat && nomorSuratEl) modalNomorSurat.textContent = nomorSuratEl.value || '';
    if (modalDari && dariEl) modalDari.textContent = dariEl.value || '';
    if (modalHal && halEl) modalHal.textContent = halEl.value || '';

    const rawSifat = '<?= strtolower(trim($rawSifat)) ?>';
    const svgCheckHtml = `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible; display: block; position: absolute; top: -5px; left: -2px; pointer-events: none;"><path d="M 3.5 11 Q 6.8 14 9.5 18 Q 16 8 24 -1.5" stroke="#003399" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
    
    const elBiasa = document.getElementById('modalChkBiasa');
    const elSegera = document.getElementById('modalChkSegera');
    const elPenting = document.getElementById('modalChkPenting');
    const elRahasia = document.getElementById('modalChkRahasia');

    if (elBiasa) elBiasa.innerHTML = (rawSifat === 'biasa') ? svgCheckHtml : '';
    if (elSegera) elSegera.innerHTML = (rawSifat === 'segera') ? svgCheckHtml : '';
    if (elPenting) elPenting.innerHTML = (rawSifat === 'penting') ? svgCheckHtml : '';
    if (elRahasia) elRahasia.innerHTML = (rawSifat === 'rahasia') ? svgCheckHtml : '';

    // 2. Ensure all PDF lampirans are rendered to data URLs first
    for (const pdfItem of uploadedPdfs) {
        if (!pdfItem.renderedPages || Object.keys(pdfItem.renderedPages).length === 0) {
            await renderPdfItemCanvases(pdfItem);
        }
    }

    let papersHtml = '';

    // 3. Build printable pages for PDF lampirans FIRST
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

    // 4. Build printable page for Lembar Disposisi Sekretaris
    const dispoPaper = modal.querySelector('.a4-paper-preview');
    if (dispoPaper) {
        const cloned = dispoPaper.cloneNode(true);
        cloned.querySelectorAll('.no-print').forEach(el => el.remove());

        papersHtml += `<div class="printable-a4-page" style="padding: 1.2cm !important; margin: 0 auto !important; width: 210mm !important; height: 297mm !important; max-height: 297mm !important; box-sizing: border-box !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; page-break-after: avoid !important; break-after: avoid !important; background: #ffffff !important;">${cloned.innerHTML}</div>`;
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

    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Lembar Disposisi & Lampiran</title>
            <base href="<?= (defined('BASE_URL') ? BASE_URL : '') ?>/">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
            <style>
                @page { size: A4 portrait; margin: 0; }
                html, body { font-family: Arial, sans-serif; background: #ffffff; color: #000000; margin: 0 !important; padding: 0 !important; width: 210mm !important; height: auto !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                img { max-width: 100% !important; display: inline-block !important; visibility: visible !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                .printable-a4-page { background: #FFFFFF !important; border: none !important; box-shadow: none !important; margin: 0 auto !important; width: 210mm !important; height: 297mm !important; max-height: 297mm !important; box-sizing: border-box !important; page-break-after: always !important; break-after: page !important; page-break-inside: avoid !important; break-inside: avoid !important; overflow: hidden !important; }
                .printable-a4-page.printable-lampiran-page { padding: 0 !important; border: none !important; box-shadow: none !important; overflow: hidden !important; width: 210mm !important; height: 297mm !important; background: #ffffff !important; display: block !important; }
                .printable-a4-page.printable-lampiran-page img,
                .printable-a4-page.printable-lampiran-page .pdf-img-item { width: 100% !important; height: 100% !important; max-width: 100% !important; max-height: 297mm !important; object-fit: contain !important; display: block !important; margin: 0 auto !important; border: none !important; box-shadow: none !important; transform: none !important; }
                .printable-a4-page:last-child { page-break-after: avoid !important; break-after: avoid !important; }
                .a4-paper-preview { background: #FFFFFF !important; border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; max-width: 100% !important; height: auto !important; min-height: 0 !important; box-sizing: border-box !important; }
                .a4-paper-preview > div { border: 1.5px solid #000000 !important; padding: 16px 20px !important; min-height: 100% !important; box-sizing: border-box !important; background: #FFFFFF !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; }
                .no-print { display: none !important; }
                .pdf-canvas-item { display: none !important; }
                .pdf-img-item { display: block !important; visibility: visible !important; width: 100% !important; height: auto !important; }
                @media print {
                    body { background: #fff !important; }
                    img { display: inline-block !important; visibility: visible !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                    .printable-a4-page { border: none !important; box-shadow: none !important; margin: 0 auto !important; }
                    .printable-a4-page.printable-lampiran-page { padding: 0 !important; border: none !important; box-shadow: none !important; }
                    .a4-paper-preview { border: none !important; box-shadow: none !important; padding: 0 !important; }
                    .a4-paper-preview > div { border: 1.5px solid #000000 !important; padding: 16px 20px !important; }
                }
            </style>
        </head>
        <body>
            ${papersHtml}
            <script>
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

document.addEventListener('DOMContentLoaded', function() {
    if (window.existingPdfLampirans && Array.isArray(window.existingPdfLampirans) && window.existingPdfLampirans.length > 0) {
        let pendingExisting = window.existingPdfLampirans.length;
        window.existingPdfLampirans.forEach((path, index) => {
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
                        isExisting: true,
                        path: path
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
                        isExisting: true,
                        path: path
                    });
                })
                .finally(() => {
                    pendingExisting--;
                    if (pendingExisting === 0) {
                        renderPdfFileList();
                        syncPdfFileInput();
                        syncDisposisiPreview();
                    }
                });
        });
    } else {
        syncDisposisiPreview();
        renderPdfFileList();
    }
});

window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const disModal = document.getElementById('disposisiReviewModal');
        if (disModal && disModal.style.display !== 'none') {
            closeDisposisiReviewModal();
            return;
        }
        const revModal = document.getElementById('revisiModal');
        if (revModal && revModal.style.display !== 'none') {
            closeRevisiModal();
            return;
        }
        const valModal = document.getElementById('validationWarningModal');
        if (valModal && valModal.style.display !== 'none') {
            closeValidationWarningModal();
            return;
        }
    }
});
</script>
