<?php $isModal = isset($_GET['modal']) && $_GET['modal'] === '1'; ?>

<?php if ($isModal): ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Dokumen Surat</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #F8FAFC; color: #000; margin: 0; padding: 20px 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .page-break { page-break-after: always; break-after: page; }
        img { max-width: 100% !important; display: inline-block !important; visibility: visible !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        
        @media screen and (max-width: 768px) {
            body { padding: 6px 4px !important; }
            .modal-print-wrapper { padding: 2px !important; width: 100% !important; }
        }

        @media print {
            @page { size: A4 portrait; margin: 0; }
            body { background: #fff !important; padding: 0 !important; margin: 0 !important; }
            .btn-primary-custom, .btn-secondary-custom, h1, .no-print { display: none !important; }
            .modal-print-wrapper { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
            img { display: inline-block !important; visibility: visible !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>
    <div class="modal-print-wrapper" style="max-width: 1360px; margin: 0 auto; background: transparent; padding: 10px; box-sizing: border-box;">
        <?php include __DIR__ . '/admin_review_template.php'; ?>
    </div>
</body>
</html>
<?php else: ?>
<style>
    img { max-width: 100% !important; display: inline-block !important; visibility: visible !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    @media print {
        @page { size: A4 portrait; margin: 0; }
        body { background: #fff !important; padding: 0 !important; margin: 0 !important; }
        .btn-primary-custom, .btn-secondary-custom, h1, .no-print { display: none !important; }
        .printable-wrapper { padding: 0 !important; border: none !important; box-shadow: none !important; background: transparent !important; margin: 0 !important; }
        img { display: inline-block !important; visibility: visible !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>
<div style="max-width: 1360px; margin: 0 auto;">
    <div class="page-header-banner no-print" style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 8px; display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-file-earmark-text-fill" style="color: #003399;"></i> Detail Dokumen Surat
            </h1>
        </div>

        <div style="display: flex; gap: 10px;">
            <button onclick="window.print();" class="btn-primary-custom">
                <i class="bi bi-printer-fill"></i> Cetak / Save PDF
            </button>

            <?php if (!empty($surat['gdrive_web_link'])): ?>
                <a href="<?= htmlspecialchars($surat['gdrive_web_link']) ?>" target="_blank" class="btn-secondary-custom" style="background: #ecfdf5; color: #047857; border-color: #a7f3d0;">
                    <i class="bi bi-google"></i> Google Drive Link
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Printable Preview Canvas Wrapper -->
    <div class="printable-wrapper" style="background: #ffffff; padding: 40px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
        <?php include __DIR__ . '/pdf_template.php'; ?>
    </div>

    <!-- Bottom Action Bar for Printing Document -->
    <div style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 18px 24px; background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);" class="no-print">
        <a href="<?= BASE_URL ?>/surat" class="btn-btn btn-btn-secondary" style="padding: 10px 20px;">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Surat
        </a>

        <button onclick="window.print();" class="btn-btn btn-btn-primary" style="background: #003399; border-color: #003399; font-weight: 700; padding: 10px 24px; font-size: 14.5px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
            <i class="bi bi-printer-fill"></i> Cetak / Save PDF Surat
        </button>
    </div>
</div>
<?php endif; ?>
