<?php

/**
 * ExcelDisposisiController - Surat Digital TVRI Jawa Timur
 * Manages monthly Excel spreadsheet rekap for Disposisi (Sekretaris role)
 */

class ExcelDisposisiController extends Controller
{
    private function checkAccess(): bool
    {
        if (!Session::isLoggedIn()) {
            $this->redirect('/login');
            return false;
        }

        $userRole = Session::get('user_role', '');
        if (!in_array($userRole, ['sekretaris', 'sekre', 'superadmin', 'admin'])) {
            Session::setFlash('error', 'Akses ditolak. Fitur kelola rekap Excel disposisi khusus untuk Sekretariat.');
            $this->redirect('/dashboard');
            return false;
        }

        return true;
    }

    /**
     * Halaman Utama: Daftar Card per Bulan dan Tahun
     */
    public function index(): void
    {
        if (!$this->checkAccess()) return;

        $selectedYear = isset($_GET['year']) && is_numeric($_GET['year']) ? (int)$_GET['year'] : null;
        $search = isset($_GET['q']) ? trim($_GET['q']) : '';

        $allPeriods = DisposisiExcelService::getPeriods();

        // Extract available years for filter
        $availableYears = array_unique(array_map(fn($p) => (int)$p['year'], $allPeriods));
        rsort($availableYears);

        // Filter periods if specified
        $filteredPeriods = $allPeriods;
        if ($selectedYear !== null) {
            $filteredPeriods = array_filter($filteredPeriods, fn($p) => (int)$p['year'] === $selectedYear);
        }

        if ($search !== '') {
            $lowerSearch = strtolower($search);
            $filteredPeriods = array_filter($filteredPeriods, function($p) use ($lowerSearch) {
                return str_contains(strtolower($p['period_label']), $lowerSearch) ||
                       str_contains(strtolower($p['filename']), $lowerSearch);
            });
        }

        // Auto-generate files for periods that don't exist yet on disk
        foreach ($filteredPeriods as &$p) {
            $fullPath = __DIR__ . '/../public' . $p['file_path'];
            if (!$p['file_exists'] && $p['total_surat'] > 0) {
                try {
                    DisposisiExcelService::generateExcelForPeriod($p['year'], $p['month']);
                    $p['file_exists'] = true;
                    $p['file_size'] = file_exists($fullPath) ? number_format(filesize($fullPath) / 1024, 1) . ' KB' : '-';
                } catch (Throwable $e) {
                    error_log("Failed auto-generating excel: " . $e->getMessage());
                }
            }
        }
        unset($p);

        $totalAllSurat = array_sum(array_map(fn($p) => $p['total_surat'], $allPeriods));

        $this->view('excel_disposisi/index', [
            'title' => 'Kelola Excel Disposisi - Sekretariat TVRI Jawa Timur',
            'periods' => $filteredPeriods,
            'allPeriods' => $allPeriods,
            'availableYears' => $availableYears,
            'selectedYear' => $selectedYear,
            'search' => $search,
            'totalAllSurat' => $totalAllSurat
        ]);
    }

    /**
     * Halaman Detail / Spreadsheet View untuk bulan & tahun terpilih
     */
    public function detail(): void
    {
        if (!$this->checkAccess()) return;

        $year = isset($_GET['year']) && is_numeric($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) && is_numeric($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

        $periodKey = sprintf('%04d-%02d', $year, $month);
        if (in_array($periodKey, DisposisiExcelService::getDeletedPeriods(), true)) {
            Session::setFlash('error', 'Periode ini telah dihapus dari daftar rekap disposisi.');
            $this->redirect('/excel-disposisi');
            return;
        }

        $rows = DisposisiExcelService::getDisposisiDataForPeriod($year, $month);
        $bulanName = DisposisiExcelService::getBulanName($month);
        $filename = "Rekap_Disposisi_{$bulanName}_{$year}.xlsx";

        $fullPath = __DIR__ . '/../public/uploads/excel/' . $filename;
        if (!file_exists($fullPath) && count($rows) > 0) {
            try {
                DisposisiExcelService::generateExcelForPeriod($year, $month);
            } catch (Throwable $e) {
                error_log("Generate excel error: " . $e->getMessage());
            }
        }

        $fileExists = file_exists($fullPath);
        $fileSize = $fileExists ? number_format(filesize($fullPath) / 1024, 1) . ' KB' : '-';

        $this->view('excel_disposisi/view', [
            'title' => "Rekap Disposisi {$bulanName} {$year} - Surat Digital TVRI Jawa Timur",
            'year' => $year,
            'month' => $month,
            'monthName' => $bulanName,
            'periodLabel' => "{$bulanName} {$year}",
            'filename' => $filename,
            'fileExists' => $fileExists,
            'fileSize' => $fileSize,
            'rows' => $rows
        ]);
    }

    /**
     * Download Binary XLSX File
     */
    public function download(): void
    {
        if (!$this->checkAccess()) return;

        $year = isset($_GET['year']) && is_numeric($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) && is_numeric($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

        $periodKey = sprintf('%04d-%02d', $year, $month);
        if (in_array($periodKey, DisposisiExcelService::getDeletedPeriods(), true)) {
            Session::setFlash('error', 'File Excel untuk periode ini telah dihapus.');
            $this->redirect('/excel-disposisi');
            return;
        }

        $bulanName = DisposisiExcelService::getBulanName($month);
        $filename = "Rekap_Disposisi_{$bulanName}_{$year}.xlsx";

        // Generate or ensure up to date
        try {
            $filePath = DisposisiExcelService::generateExcelForPeriod($year, $month);
        } catch (Throwable $e) {
            Session::setFlash('error', 'Gagal memproses file Excel: ' . $e->getMessage());
            $this->redirect('/excel-disposisi');
            return;
        }

        if (!file_exists($filePath)) {
            Session::setFlash('error', 'File Excel tidak ditemukan.');
            $this->redirect('/excel-disposisi');
            return;
        }

        // Clean output buffer before sending binary file
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        readfile($filePath);
        exit;
    }

    /**
     * Sinkronkan Ulang File Excel
     */
    public function sync(): void
    {
        if (!$this->checkAccess()) return;

        $year = isset($_POST['year']) && is_numeric($_POST['year']) ? (int)$_POST['year'] : null;
        $month = isset($_POST['month']) && is_numeric($_POST['month']) ? (int)$_POST['month'] : null;

        try {
            if ($year && $month) {
                DisposisiExcelService::restorePeriod($year, $month);
                DisposisiExcelService::generateExcelForPeriod($year, $month);
                $bulanName = DisposisiExcelService::getBulanName($month);
                Session::setFlash('success', "File Excel untuk periode {$bulanName} {$year} berhasil disinkronkan & diperbarui!");
                $this->redirect("/excel-disposisi/view?year={$year}&month={$month}");
                return;
            } else {
                $periods = DisposisiExcelService::getPeriods();
                $count = 0;
                foreach ($periods as $p) {
                    if ($p['total_surat'] > 0) {
                        DisposisiExcelService::generateExcelForPeriod($p['year'], $p['month']);
                        $count++;
                    }
                }
                Session::setFlash('success', "Berhasil menyinkronkan {$count} file Excel rekap disposisi bulanan!");
                $this->redirect('/excel-disposisi');
                return;
            }
        } catch (Throwable $e) {
            Session::setFlash('error', 'Terjadi kesalahan saat sinkronisasi: ' . $e->getMessage());
            $this->redirect('/excel-disposisi');
        }
    }

    /**
     * Hapus Card dan File Excel untuk Bulan yang Sudah Terlewat
     */
    public function delete(): void
    {
        if (!$this->checkAccess()) return;

        $year = isset($_REQUEST['year']) && is_numeric($_REQUEST['year']) ? (int)$_REQUEST['year'] : null;
        $month = isset($_REQUEST['month']) && is_numeric($_REQUEST['month']) ? (int)$_REQUEST['month'] : null;

        if (!$year || !$month) {
            Session::setFlash('error', 'Parameter tahun dan bulan tidak valid.');
            $this->redirect('/excel-disposisi');
            return;
        }

        // Validasi: tombol dan fungsi hapus hanya untuk bulan yang sudah terlewat
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('n');
        $isPassed = ($year < $currentYear) || ($year === $currentYear && $month < $currentMonth);

        if (!$isPassed) {
            Session::setFlash('error', 'Hanya card dan file Excel dari bulan yang sudah terlewat yang dapat dihapus.');
            $this->redirect('/excel-disposisi');
            return;
        }

        try {
            $bulanName = DisposisiExcelService::getBulanName($month);
            DisposisiExcelService::deletePeriod($year, $month);

            if (class_exists('ActivityLog')) {
                ActivityLog::log('DELETE_EXCEL_DISPOSISI', "Sekretaris menghapus card dan file Excel rekap disposisi periode {$bulanName} {$year}");
            }

            Session::setFlash('success', "Card dan file Excel rekap disposisi periode {$bulanName} {$year} berhasil dihapus.");
        } catch (Throwable $e) {
            Session::setFlash('error', 'Gagal menghapus card dan file Excel: ' . $e->getMessage());
        }

        $this->redirect('/excel-disposisi');
    }
}
