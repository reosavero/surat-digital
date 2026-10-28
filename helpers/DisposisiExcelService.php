<?php

/**
 * DisposisiExcelService Helper - Surat Digital TVRI Jawa Timur
 * Automates monthly Excel (.xlsx) file generation for completed Disposisi
 * Provides management, data viewing, and export for Sekretariat role.
 */

class DisposisiExcelService
{
    private static array $bulanIndo = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    public static function getBulanName(int $month): string
    {
        return self::$bulanIndo[$month] ?? date('F', mktime(0, 0, 0, $month, 1));
    }

    /**
     * Format date to match 1-Oct-2026 style (handling Indonesian month names if needed)
     */
    public static function formatTanggal(?string $rawDate): string
    {
        if (empty($rawDate)) return '-';
        $rawDate = trim($rawDate);

        $bulanMap = [
            'januari' => 'january', 'februari' => 'february', 'maret' => 'march',
            'april' => 'april', 'mei' => 'may', 'juni' => 'june',
            'juli' => 'july', 'agustus' => 'august', 'september' => 'september',
            'oktober' => 'october', 'november' => 'november', 'desember' => 'december',
            'okt' => 'oct', 'agt' => 'aug', 'agu' => 'aug', 'nop' => 'nov', 'des' => 'dec'
        ];
        $dateForParsing = str_ireplace(array_keys($bulanMap), array_values($bulanMap), strtolower($rawDate));
        $timestamp = strtotime($dateForParsing);
        if ($timestamp) {
            return date('j-M-Y', $timestamp);
        }
        return $rawDate;
    }


    /**
     * Separate checklist items into Unit Penerima and Disposisi
     */
    public static function separateChecklist(array $checklist): array
    {
        $disposisiKeywords = [
            'diteliti', 'dipertimbangkan', 'diketahui', 'mewakili', 'menghadiri', 'mengikuti',
            'dikoordinasikan', 'ditampung', 'peringatkan', 'pendekatan', 'pendapat', 'analisa',
            'saran', 'konsep jawaban', 'sambutan', 'konsep laporan', 'data diolah', 'ditindaklanjuti',
            'diagendakan', 'dijadwalkan', 'harap dibantu', 'file'
        ];

        $unitPenerima = [];
        $disposisi = [];

        foreach ($checklist as $item) {
            $itemTrim = trim((string)$item);
            if ($itemTrim === '') continue;

            $lower = strtolower($itemTrim);
            $isDispo = false;
            foreach ($disposisiKeywords as $kw) {
                if (str_contains($lower, $kw)) {
                    $isDispo = true;
                    break;
                }
            }

            if ($isDispo) {
                $disposisi[] = $itemTrim;
            } else {
                $unitPenerima[] = $itemTrim;
            }
        }

        return [
            'unit_penerima' => implode(', ', $unitPenerima),
            'disposisi' => implode(', ', $disposisi),
            'unit_penerima_arr' => $unitPenerima,
            'disposisi_arr' => $disposisi,
        ];
    }

    /**
     * Get all available periods (months and years) that have completed disposisi
     */
    public static function getPeriods(): array
    {
        $rows = Database::fetchAll(
            "SELECT 
                YEAR(COALESCE(d.kepsta_signed_at, s.tanggal_surat, s.created_at)) as thn,
                MONTH(COALESCE(d.kepsta_signed_at, s.tanggal_surat, s.created_at)) as bln,
                COUNT(d.id) as total_surat,
                MAX(d.updated_at) as last_updated
             FROM disposisi d
             JOIN surat s ON d.surat_id = s.id
             WHERE (s.status = 'completed' OR d.kepsta_signed_at IS NOT NULL)
             GROUP BY thn, bln
             ORDER BY thn DESC, bln DESC"
        );

        $currentYear = (int)date('Y');
        $currentMonth = (int)date('n');
        $hasCurrentPeriod = false;

        $deletedPeriods = self::getDeletedPeriods();
        $foundPeriodKeys = [];

        $periods = [];
        foreach ($rows as $r) {
            $y = (int)$r['thn'];
            $m = (int)$r['bln'];
            $periodKey = sprintf('%04d-%02d', $y, $m);

            if ($y === $currentYear && $m === $currentMonth) {
                $hasCurrentPeriod = true;
            }

            // Jika periode sudah dihapus oleh user, lewati agar card tidak muncul
            if (in_array($periodKey, $deletedPeriods, true)) {
                continue;
            }

            $foundPeriodKeys[$periodKey] = true;
            $bulanName = self::getBulanName($m);
            $filename = "Rekap_Disposisi_{$bulanName}_{$y}.xlsx";
            $fullPath = __DIR__ . '/../public/uploads/excel/' . $filename;
            $webPath = '/uploads/excel/' . $filename;

            $periods[] = [
                'year' => $y,
                'month' => $m,
                'month_name' => $bulanName,
                'period_label' => "{$bulanName} {$y}",
                'period_key' => $periodKey,
                'total_surat' => (int)$r['total_surat'],
                'filename' => $filename,
                'file_path' => $webPath,
                'file_exists' => file_exists($fullPath),
                'file_size' => file_exists($fullPath) ? self::formatBytes(filesize($fullPath)) : '-',
                'last_updated' => $r['last_updated'] ? date('d M Y H:i', strtotime($r['last_updated'])) : '-'
            ];
        }

        // Periksa file fisik yang mungkin ada di direktori uploads/excel
        $dirsToCheck = [
            __DIR__ . '/../public/uploads/excel/',
            __DIR__ . '/../uploads/excel/'
        ];
        foreach ($dirsToCheck as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . 'Rekap_Disposisi_*_*.xlsx');
                if ($files) {
                    foreach ($files as $f) {
                        $bn = basename($f);
                        if (preg_match('/^Rekap_Disposisi_([A-Za-z]+)_(\d{4})\.xlsx$/i', $bn, $matches)) {
                            $nameMonth = strtolower($matches[1]);
                            $fYear = (int)$matches[2];
                            $fMonth = null;
                            foreach (self::$bulanIndo as $mNum => $mName) {
                                if (strtolower($mName) === $nameMonth) {
                                    $fMonth = $mNum;
                                    break;
                                }
                            }
                            if ($fMonth !== null) {
                                $pkey = sprintf('%04d-%02d', $fYear, $fMonth);
                                if (!isset($foundPeriodKeys[$pkey]) && !in_array($pkey, $deletedPeriods, true)) {
                                    $foundPeriodKeys[$pkey] = true;
                                    $bName = self::getBulanName($fMonth);
                                    $webPath = '/uploads/excel/' . $bn;
                                    if ($fYear === $currentYear && $fMonth === $currentMonth) {
                                        $hasCurrentPeriod = true;
                                    }
                                    $periods[] = [
                                        'year' => $fYear,
                                        'month' => $fMonth,
                                        'month_name' => $bName,
                                        'period_label' => "{$bName} {$fYear}",
                                        'period_key' => $pkey,
                                        'total_surat' => 0,
                                        'filename' => $bn,
                                        'file_path' => $webPath,
                                        'file_exists' => true,
                                        'file_size' => self::formatBytes(filesize($f)),
                                        'last_updated' => date('d M Y H:i', filemtime($f))
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        // Pastikan bulan berjalan selalu ada di daftar jika belum dihapus
        $currentPeriodKey = sprintf('%04d-%02d', $currentYear, $currentMonth);
        if (!$hasCurrentPeriod && !in_array($currentPeriodKey, $deletedPeriods, true)) {
            $bulanName = self::getBulanName($currentMonth);
            $filename = "Rekap_Disposisi_{$bulanName}_{$currentYear}.xlsx";
            $fullPath = __DIR__ . '/../public/uploads/excel/' . $filename;
            $webPath = '/uploads/excel/' . $filename;

            $periods[] = [
                'year' => $currentYear,
                'month' => $currentMonth,
                'month_name' => $bulanName,
                'period_label' => "{$bulanName} {$currentYear}",
                'period_key' => $currentPeriodKey,
                'total_surat' => 0,
                'filename' => $filename,
                'file_path' => $webPath,
                'file_exists' => file_exists($fullPath),
                'file_size' => file_exists($fullPath) ? self::formatBytes(filesize($fullPath)) : '-',
                'last_updated' => '-'
            ];
        }

        // Urutkan periode terbaru ke terlama
        usort($periods, fn($a, $b) => strcmp($b['period_key'], $a['period_key']));

        return $periods;
    }

    /**
     * Ambil daftar periode ('YYYY-MM') yang telah dihapus
     */
    public static function getDeletedPeriods(): array
    {
        try {
            $val = Setting::get('excel_deleted_periods', '[]');
            $arr = json_decode((string)$val, true);
            return is_array($arr) ? $arr : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Hapus card dan file fisik Excel untuk periode tertentu
     */
    public static function deletePeriod(int $year, int $month): bool
    {
        $bulanName = self::getBulanName($month);
        $filename = "Rekap_Disposisi_{$bulanName}_{$year}.xlsx";

        $paths = [
            __DIR__ . '/../public/uploads/excel/' . $filename,
            __DIR__ . '/../uploads/excel/' . $filename
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        // Catat ke setting deleted periods agar card tidak dimunculkan kembali
        $periodKey = sprintf('%04d-%02d', $year, $month);
        $deleted = self::getDeletedPeriods();
        if (!in_array($periodKey, $deleted, true)) {
            $deleted[] = $periodKey;
            Setting::set('excel_deleted_periods', json_encode(array_values($deleted)));
        }

        return true;
    }

    /**
     * Pulihkan periode yang dihapus jika disinkronkan ulang
     */
    public static function restorePeriod(int $year, int $month): bool
    {
        $periodKey = sprintf('%04d-%02d', $year, $month);
        $deleted = self::getDeletedPeriods();
        if (($k = array_search($periodKey, $deleted, true)) !== false) {
            unset($deleted[$k]);
            Setting::set('excel_deleted_periods', json_encode(array_values($deleted)));
        }
        return true;
    }

    /**
     * Get detailed disposisi rows for a specific year and month
     */
    public static function getDisposisiDataForPeriod(int $year, int $month): array
    {
        $sql = "SELECT d.*, s.id as surat_id, s.kode_surat, s.type, s.nomor_surat, s.perihal, 
                       s.tanggal_surat, s.status, s.pdf_path, s.gdrive_file_id, s.gdrive_web_link, 
                       s.meta_data, s.created_at as surat_created_at
                FROM disposisi d
                JOIN surat s ON d.surat_id = s.id
                WHERE (s.status = 'completed' OR d.kepsta_signed_at IS NOT NULL)
                  AND YEAR(COALESCE(d.kepsta_signed_at, s.tanggal_surat, s.created_at)) = ?
                  AND MONTH(COALESCE(d.kepsta_signed_at, s.tanggal_surat, s.created_at)) = ?
                ORDER BY COALESCE(d.kepsta_signed_at, d.updated_at, d.created_at) ASC, d.id ASC";

        $records = Database::fetchAll($sql, [$year, $month]);
        $rows = [];

        foreach ($records as $r) {
            $meta = !empty($r['meta_data']) ? json_decode($r['meta_data'], true) : [];
            $checklist = !empty($r['checklist_json']) ? json_decode($r['checklist_json'], true) : [];
            if (!is_array($checklist)) $checklist = [];

            // 1. Nomor Agenda
            $nomorAgenda = !empty($meta['nomor_agenda']) ? trim($meta['nomor_agenda']) : (!empty($r['nomor_agenda']) ? trim($r['nomor_agenda']) : '-');

            // 2. Tanggal (1-Oct-2026 format matching image)
            $rawDate = !empty($meta['tanggal_agenda']) ? $meta['tanggal_agenda'] : (!empty($r['tanggal_surat']) ? $r['tanggal_surat'] : (!empty($r['kepsta_signed_at']) ? $r['kepsta_signed_at'] : $r['surat_created_at']));
            $formattedDate = self::formatTanggal($rawDate);


            // 3. Nomor Surat
            $nomorSurat = !empty($r['nomor_surat']) ? trim($r['nomor_surat']) : (!empty($meta['nomor_surat_disposisi']) ? trim($meta['nomor_surat_disposisi']) : (!empty($meta['nomor_surat']) ? trim($meta['nomor_surat']) : ($r['kode_surat'] ?? '-')));

            // 4. Dari
            $dari = !empty($meta['dari']) ? trim($meta['dari']) : 'Kepala Sub Bagian Tata Usaha';

            // 5. Hal
            $hal = !empty($meta['hal_disposisi']) ? trim($meta['hal_disposisi']) : (!empty($meta['perihal_disposisi']) ? trim($meta['perihal_disposisi']) : (!empty($r['perihal']) ? trim($r['perihal']) : '-'));

            // 6. Sifat
            $rawSifat = !empty($r['sifat_surat']) ? $r['sifat_surat'] : (!empty($meta['sifat_disposisi']) ? $meta['sifat_disposisi'] : (!empty($meta['sifat_surat']) ? $meta['sifat_surat'] : 'Biasa'));
            $sifat = Disposisi::getSifatSuratLabel($rawSifat);

            // 7. Unit Penerima & 8. Disposisi
            $separated = self::separateChecklist($checklist);
            $unitPenerima = !empty($separated['unit_penerima']) ? $separated['unit_penerima'] : '-';
            $disposisiItem = !empty($separated['disposisi']) ? $separated['disposisi'] : '-';

            // 9. Catatan (Kepsta notes or Sekre notes)
            $catatanList = [];
            if (!empty($r['catatan_kepsta'])) {
                $catatanList[] = trim($r['catatan_kepsta']);
            }
            if (!empty($r['catatan_sekre']) && $r['catatan_sekre'] !== ($r['catatan_kepsta'] ?? '')) {
                $catatanList[] = "[Sekre: " . trim($r['catatan_sekre']) . "]";
            }
            $catatan = !empty($catatanList) ? implode(' | ', $catatanList) : '-';

            // 10. Link File (Google Drive URL from uploaded PDF)
            $baseUrl = defined('BASE_URL') ? BASE_URL : '';
            $linkFile = '';
            $isGdrive = false;

            if (!empty($r['gdrive_web_link'])) {
                $linkFile = $r['gdrive_web_link'];
                $isGdrive = true;
            } elseif (!empty($r['gdrive_file_id']) && !str_starts_with($r['gdrive_file_id'], 'gdrive_')) {
                $linkFile = 'https://drive.google.com/file/d/' . $r['gdrive_file_id'] . '/view?usp=sharing';
                $isGdrive = true;
            } elseif (!empty($meta['gdrive_web_link'])) {
                $linkFile = $meta['gdrive_web_link'];
                $isGdrive = true;
            } elseif (!empty($r['pdf_path'])) {
                $linkFile = $baseUrl . $r['pdf_path'];
            } else {
                $linkFile = $baseUrl . '/surat/detail/' . $r['surat_id'];
            }


            $rows[] = [
                'surat_id' => (int)$r['surat_id'],
                'disposisi_id' => (int)$r['id'],
                'nomor_agenda' => $nomorAgenda,
                'tanggal' => $formattedDate,
                'raw_date' => $rawDate,
                'nomor_surat' => $nomorSurat,
                'dari' => $dari,
                'hal' => $hal,
                'sifat' => $sifat,
                'unit_penerima' => $unitPenerima,
                'disposisi' => $disposisiItem,
                'catatan' => $catatan,
                'link_file' => $linkFile,
                'is_gdrive' => $isGdrive
            ];
        }

        return $rows;
    }

    /**
     * Generate or regenerate monthly XLSX file
     */
    public static function generateExcelForPeriod(int $year, int $month): string
    {
        $rows = self::getDisposisiDataForPeriod($year, $month);
        $bulanName = self::getBulanName($month);
        $filename = "Rekap_Disposisi_{$bulanName}_{$year}.xlsx";

        $dirPublic = __DIR__ . '/../public/uploads/excel';
        $dirUploads = __DIR__ . '/../uploads/excel';

        if (!file_exists($dirPublic)) mkdir($dirPublic, 0777, true);
        if (!file_exists($dirUploads)) mkdir($dirUploads, 0777, true);

        $outputPath = $dirPublic . '/' . $filename;
        $outputPathRoot = $dirUploads . '/' . $filename;

        self::buildXlsxFile($rows, $outputPath, "Rekap {$bulanName} {$year}");

        // Also mirror to root uploads/excel
        if (file_exists($outputPath)) {
            @copy($outputPath, $outputPathRoot);
        }

        return $outputPath;
    }

    /**
     * Synchronize a specific completed surat to its monthly Excel file
     */
    public static function syncSuratToExcel(int $suratId): ?string
    {
        $surat = (new Surat())->getById($suratId);
        if (!$surat) return null;

        $dispo = (new Disposisi())->getBySuratId($suratId);

        $dateStr = !empty($dispo['kepsta_signed_at']) ? $dispo['kepsta_signed_at'] : (!empty($surat['tanggal_surat']) ? $surat['tanggal_surat'] : date('Y-m-d'));
        $time = strtotime($dateStr) ?: time();

        $year = (int)date('Y', $time);
        $month = (int)date('n', $time);

        return self::generateExcelForPeriod($year, $month);
    }

    /**
     * Pure PHP XLSX builder using ZipArchive
     */
    private static function buildXlsxFile(array $dataRows, string $filePath, string $sheetTitle = 'Rekap Disposisi'): void
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Tidak dapat membuat file XLSX di: " . $filePath);
        }

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $safeSheetName = htmlspecialchars(mb_substr($sheetTitle, 0, 31), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="' . $safeSheetName . '" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // 5. xl/styles.xml
        // Fill 2: Mint Green (#BFE3D5) matching image header
        // Font 1: Bold Calibri 11pt
        // Font 2: Hyperlink Blue underline
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="3">
    <font><name val="Calibri"/><sz val="10"/><color rgb="FF000000"/></font>
    <font><b/><name val="Calibri"/><sz val="11"/><color rgb="FF000000"/></font>
    <font><u/><name val="Calibri"/><sz val="10"/><color rgb="FF0563C1"/></font>
  </fonts>
  <fills count="3">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFBFE3D5"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFB0BEC5"/></left>
      <right style="thin"><color rgb="FFB0BEC5"/></right>
      <top style="thin"><color rgb="FFB0BEC5"/></top>
      <bottom style="thin"><color rgb="FFB0BEC5"/></bottom>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="4">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center" wrapText="1"/>
    </xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">
      <alignment vertical="top" wrapText="1"/>
    </xf>
    <xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">
      <alignment vertical="top" wrapText="1"/>
    </xf>
  </cellXfs>
</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        // 6. xl/worksheets/sheet1.xml
        $headers = [
            'Nomor Agenda', 'Tanggal', 'Nomor Surat', 'Dari', 'Hal',
            'Sifat', 'Unit Penerima', 'Disposisi', 'Catatan', 'Link File'
        ];
        $colWidths = [22, 16, 28, 28, 42, 14, 28, 28, 30, 60];

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";
        $sheetXml .= '  <cols>' . "\n";
        foreach ($colWidths as $i => $w) {
            $colNum = $i + 1;
            $sheetXml .= '    <col min="' . $colNum . '" max="' . $colNum . '" width="' . $w . '" customWidth="1"/>' . "\n";
        }
        $sheetXml .= '  </cols>' . "\n";
        $sheetXml .= '  <sheetData>' . "\n";

        // Row 1: Header
        $sheetXml .= '    <row r="1" ht="28" customHeight="1">' . "\n";
        foreach ($headers as $c => $h) {
            $ref = self::colLetter($c) . '1';
            $safeH = htmlspecialchars($h, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $sheetXml .= '      <c r="' . $ref . '" s="1" t="inlineStr"><is><t>' . $safeH . '</t></is></c>' . "\n";
        }
        $sheetXml .= '    </row>' . "\n";

        // Data Rows
        $rowNum = 2;
        foreach ($dataRows as $item) {
            $rowValues = [
                $item['nomor_agenda'] ?? '-',
                $item['tanggal'] ?? '-',
                $item['nomor_surat'] ?? '-',
                $item['dari'] ?? '-',
                $item['hal'] ?? '-',
                $item['sifat'] ?? '-',
                $item['unit_penerima'] ?? '-',
                $item['disposisi'] ?? '-',
                $item['catatan'] ?? '-',
                $item['link_file'] ?? '-'
            ];

            $sheetXml .= '    <row r="' . $rowNum . '" ht="35" customHeight="1">' . "\n";
            foreach ($rowValues as $c => $val) {
                $ref = self::colLetter($c) . $rowNum;
                // Column 9 is Link File (Visible URL text hyperlink to Google Drive PDF)
                if ($c === 9 && !empty($val) && (str_starts_with($val, 'http://') || str_starts_with($val, 'https://'))) {
                    $safeUrl = str_replace('"', '""', $val);
                    $sheetXml .= '      <c r="' . $ref . '" s="3"><f>HYPERLINK("' . $safeUrl . '", "' . $safeUrl . '")</f></c>' . "\n";
                } else {
                    $safeVal = htmlspecialchars((string)$val, ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $sheetXml .= '      <c r="' . $ref . '" s="2" t="inlineStr"><is><t>' . $safeVal . '</t></is></c>' . "\n";
                }
            }
            $sheetXml .= '    </row>' . "\n";
            $rowNum++;
        }


        $sheetXml .= '  </sheetData>' . "\n";
        $sheetXml .= '</worksheet>';

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();
    }

    private static function colLetter(int $colIdx): string
    {
        $letters = '';
        while ($colIdx >= 0) {
            $letters = chr($colIdx % 26 + 65) . $letters;
            $colIdx = intdiv($colIdx, 26) - 1;
        }
        return $letters;
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
