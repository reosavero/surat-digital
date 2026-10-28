<?php

/**
 * PdfGenerator Helper - Surat Digital TVRI Jawa Timur
 * Generates official multi-page PDF/Printable document
 * Page 1: Surat Disposisi (if includeDisposisi = true)
 * Page 2+: Surat Admin (Surat Izin Prinsip / Surat Izin Produksi / Nota Dinas)
 */

class PdfGenerator
{
    public static function generateMergedPdf(int $suratId, bool $includeDisposisi = true): string
    {
        $suratModel = new Surat();
        $surat = $suratModel->getById($suratId);

        $dispoModel = new Disposisi();
        $disposisi = $dispoModel->getBySuratId($suratId);

        $uploadDir = __DIR__ . '/../public/uploads/pdf';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $prefix = $includeDisposisi ? 'SURAT_DISPOSISI_' : 'SURAT_KTU_';
        $perihalClean = !empty($surat['perihal']) ? preg_replace('/[^A-Za-z0-9_\-]/', '_', trim($surat['perihal'])) : '';
        $perihalClean = preg_replace('/_+/', '_', $perihalClean);
        $perihalClean = trim($perihalClean, '_');

        if (!empty($perihalClean)) {
            if (strlen($perihalClean) > 75) {
                $perihalClean = substr($perihalClean, 0, 75);
            }
            $filename = $prefix . $perihalClean . '.pdf';
        } else {
            $filename = $prefix . 'DOKUMEN.pdf';
        }


        $fullPath = $uploadDir . '/' . $filename;
        $tempHtmlPath = $uploadDir . '/' . uniqid('pdf_html_') . '.html';

        // Render merged document HTML
        ob_start();
        include __DIR__ . '/../views/pages/surat/pdf_template.php';
        $html = ob_get_clean();

        // Save temporary HTML file for conversion
        file_put_contents($tempHtmlPath, $html);

        // Convert HTML to real binary PDF using headless Edge / Chrome if available on Windows
        $converted = self::convertHtmlToRealPdf($tempHtmlPath, $fullPath);

        if (!$converted) {
            // Fallback to HTML container if headless browser is not available
            file_put_contents($fullPath, $html);
        }

        @unlink($tempHtmlPath);

        // Relative path for database
        $relativePath = '/uploads/pdf/' . $filename;

        // Update surat pdf_path if merged or not set
        if ($includeDisposisi || empty($surat['pdf_path'])) {
            $suratModel->updateSurat($suratId, ['pdf_path' => $relativePath]);
        }

        return $fullPath;
    }


    private static function convertHtmlToRealPdf(string $htmlPath, string $pdfOutputPath): bool
    {
        $edgePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
        if (!file_exists($edgePath)) {
            $edgePath = 'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe';
        }
        if (!file_exists($edgePath)) {
            $edgePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
        }
        if (!file_exists($edgePath)) {
            $edgePath = 'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe';
        }

        if (!file_exists($edgePath)) {
            return false;
        }

        $tempProfileDir = sys_get_temp_dir() . '/edge_pdf_profile_' . uniqid();
        @mkdir($tempProfileDir, 0777, true);

        $htmlUrl = 'file:///' . str_replace('\\', '/', realpath($htmlPath));
        $cmd = sprintf(
            '"%s" --headless --disable-gpu --no-pdf-header-footer --print-to-pdf="%s" --virtual-time-budget=10000 --run-all-compositor-stages-before-draw --enable-local-file-accesses --allow-file-access-from-files --disable-web-security --user-data-dir="%s" "%s"',
            $edgePath,
            $pdfOutputPath,
            $tempProfileDir,
            $htmlUrl
        );

        @exec($cmd);
        @rmdir($tempProfileDir);

        return file_exists($pdfOutputPath) && filesize($pdfOutputPath) > 1000;
    }
}
