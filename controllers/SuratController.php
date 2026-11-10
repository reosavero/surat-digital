<?php

/**
 * Surat Controller - Surat Digital TVRI Jawa Timur
 * Handles 5-role workflow, approvals, signature submissions, PDF export, and status tracking
 */

class SuratController extends Controller
{
    private Surat $suratModel;
    private Disposisi $dispoModel;

    public function __construct()
    {
        if (!Session::isLoggedIn()) {
            $this->redirect('/login');
            exit;
        }
        $this->suratModel = new Surat();
        $this->dispoModel = new Disposisi();
    }

    // List all letters based on active user role
    public function index(): void
    {
        $role = Session::get('user_role', 'admin');
        if (in_array($role, ['kapel_bidang', 'kapel'])) {
            if (!function_exists('isKapelSdm') || !isKapelSdm()) {
                $this->redirect('/disposisi');
                return;
            }
        }
        $userId = (int) Session::get('user_id');
        $statusFilter = Security::sanitize($_GET['status'] ?? 'all');

        $suratList = $this->suratModel->getSuratListForRole($role, $userId, $statusFilter);
        $stats = $this->suratModel->getStatsForRole($role, $userId);

        $this->view('surat/index', [
            'title' => 'Daftar Surat - Surat Digital TVRI Jawa Timur',
            'suratList' => $suratList,
            'stats' => $stats,
            'role' => $role,
            'statusFilter' => $statusFilter
        ]);
    }

    // Admin: Show list of letters needing revision
    public function revisiList(): void
    {
        $role = Session::get('user_role', 'admin');
        $userId = (int) Session::get('user_id');

        $suratList = $this->suratModel->getSuratListForRole($role, $userId, 'revisi');
        $stats = $this->suratModel->getStatsForRole($role, $userId);

        $this->view('surat/revisi_list', [
            'title' => 'Daftar Surat Perlu Revisi - Surat Digital TVRI Jawa Timur',
            'suratList' => $suratList,
            'stats' => $stats,
            'role' => $role
        ]);
    }

    // Generic edit endpoint that routes to appropriate form based on letter category
    public function editSurat(string|int $id = 0): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['admin', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak.');
            $this->redirect('/dashboard');
            return;
        }

        $userId = (int) Session::get('user_id');
        $suratId = (int) $id;
        $surat = $this->suratModel->getById($suratId);

        if (!$surat || ($role !== 'superadmin' && (int)$surat['user_id'] !== $userId)) {
            Session::setFlash('error', 'Surat tidak ditemukan atau Anda tidak memiliki akses.');
            $this->redirect('/surat/revisi');
            return;
        }

        $sType = $surat['type'] ?? '';
        $sKat = $surat['meta']['kategori_surat'] ?? '';
        $isCuti = Surat::isCutiSurat($surat);

        if ($isCuti || $sType === 'nota_dinas' || $sKat === 'Surat Nota Dinas' || $sKat === 'Surat Cuti') {
            $this->redirect('/surat-cuti?id=' . $suratId);
        } else {
            $this->redirect('/surat-nota-dinas?id=' . $suratId);
        }
    }

    // Admin: Show create or edit Surat Izin Produksi form
    public function createProduksi(): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['admin', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak.');
            $this->redirect('/dashboard');
            return;
        }

        $userId = (int) Session::get('user_id');
        $userModel = new User();
        $currentUser = $userModel->findById($userId);
        $kppUsers = $userModel->getKppUsers();

        $suratId = (int) ($_GET['id'] ?? 0);
        $surat = null;
        if ($suratId > 0) {
            $surat = $this->suratModel->getById($suratId);
            if (!$surat || ($role !== 'superadmin' && (int)$surat['user_id'] !== $userId)) {
                Session::setFlash('error', 'Surat tidak ditemukan atau Anda tidak memiliki akses.');
                $this->redirect('/surat/revisi');
                return;
            }

            // Auto-redirect if letter category is Surat Cuti or Nota Dinas
            $sType = $surat['type'] ?? '';
            $sKat = $surat['meta']['kategori_surat'] ?? '';
            $isCuti = Surat::isCutiSurat($surat);
            if ($isCuti || $sType === 'nota_dinas' || $sKat === 'Surat Nota Dinas' || $sKat === 'Surat Cuti') {
                $this->redirect('/surat-cuti?id=' . $suratId);
                return;
            }
        }

        $this->view('surat/create_produksi', [
            'title' => ($surat ? 'Revisi Surat Nota Dinas' : 'Buat Surat Nota Dinas') . ' - Surat Digital TVRI Jawa Timur',
            'currentUser' => $currentUser,
            'kppUsers' => $kppUsers,
            'surat' => $surat
        ]);
    }

    // Admin: Store Surat Izin Produksi
    public function storeProduksi(): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['admin', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak.');
            $this->redirect('/dashboard');
            return;
        }

        $perihal = Security::sanitize($_POST['perihal'] ?? $_POST['perihal_dinas'] ?? $_POST['perihal_permohonan'] ?? '');
        $kepada = Security::sanitize($_POST['kepada'] ?? 'Kepala TVRI Stasiun Jawa Timur');
        $lampiran = Security::sanitize($_POST['lampiran'] ?? '');
        $tanggal = Security::sanitize($_POST['tanggal_surat'] ?? date('Y-m-d'));
        $isiSurat = $_POST['isi_surat'] ?? $_POST['isi_surat_dinas'] ?? '';
        $kppUserId = (int) ($_POST['kpp_user_id'] ?? 0);

        // Handle TTD Pemohon if provided
        $pemohonSignaturePathProd = '';
        $pemohonSigDataProd = $_POST['pemohon_signature_data'] ?? '';
        if (!empty($pemohonSigDataProd) && str_contains($pemohonSigDataProd, 'data:image')) {
            $dataSigP = explode(',', $pemohonSigDataProd);
            if (isset($dataSigP[1])) {
                $decodedSigP = base64_decode($dataSigP[1]);
                $uploadDirSigP = __DIR__ . '/../public/uploads/signatures';
                $uploadDirSigPRoot = __DIR__ . '/../uploads/signatures';
                if (!file_exists($uploadDirSigP)) mkdir($uploadDirSigP, 0777, true);
                if (!file_exists($uploadDirSigPRoot)) mkdir($uploadDirSigPRoot, 0777, true);
                $filenameSigP = 'ttd_pemohon_' . time() . '_' . mt_rand(1000, 9999) . '.png';
                file_put_contents($uploadDirSigP . '/' . $filenameSigP, $decodedSigP);
                file_put_contents($uploadDirSigPRoot . '/' . $filenameSigP, $decodedSigP);
                $pemohonSignaturePathProd = '/uploads/signatures/' . $filenameSigP;
            }
        } elseif (!empty($pemohonSigDataProd)) {
            $pemohonSignaturePathProd = Security::sanitize($pemohonSigDataProd);
        }

        // Handle uploaded PDF attachment(s) if provided (Multi-PDF support)
        $pdfAttachmentPaths = [];
        if (isset($_FILES['lampiran_pdf'])) {
            $files = $_FILES['lampiran_pdf'];
            if (is_array($files['name'])) {
                $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if (isset($files['error'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpPath = $files['tmp_name'][$i];
                        $fileName = $files['name'][$i];
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        if ($fileExt === 'pdf') {
                            $uploadDirPdf = __DIR__ . '/../public/uploads/pdf';
                            $uploadDirPdfRoot = __DIR__ . '/../uploads/pdf';
                            if (!file_exists($uploadDirPdf)) mkdir($uploadDirPdf, 0777, true);
                            if (!file_exists($uploadDirPdfRoot)) mkdir($uploadDirPdfRoot, 0777, true);
                            $newPdfName = 'lampiran_pdf_' . time() . '_' . mt_rand(1000, 9999) . '_' . $i . '.pdf';
                            move_uploaded_file($tmpPath, $uploadDirPdf . '/' . $newPdfName);
                            @copy($uploadDirPdf . '/' . $newPdfName, $uploadDirPdfRoot . '/' . $newPdfName);
                            $pdfAttachmentPaths[] = '/uploads/pdf/' . $newPdfName;
                        }
                    }
                }
            } elseif (isset($files['error']) && $files['error'] === UPLOAD_ERR_OK) {
                $tmpPath = $files['tmp_name'];
                $fileName = $files['name'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if ($fileExt === 'pdf') {
                    $uploadDirPdf = __DIR__ . '/../public/uploads/pdf';
                    $uploadDirPdfRoot = __DIR__ . '/../uploads/pdf';
                    if (!file_exists($uploadDirPdf)) mkdir($uploadDirPdf, 0777, true);
                    if (!file_exists($uploadDirPdfRoot)) mkdir($uploadDirPdfRoot, 0777, true);
                    $newPdfName = 'lampiran_pdf_' . time() . '_' . mt_rand(1000, 9999) . '.pdf';
                    move_uploaded_file($tmpPath, $uploadDirPdf . '/' . $newPdfName);
                    @copy($uploadDirPdf . '/' . $newPdfName, $uploadDirPdfRoot . '/' . $newPdfName);
                    $pdfAttachmentPaths[] = '/uploads/pdf/' . $newPdfName;
                }
            }
        }

        $gdriveWebLink = Security::sanitize($_POST['gdrive_web_link'] ?? '');
        if ($gdriveWebLink === 'https://drive.google.com' || str_contains($gdriveWebLink, 'drive.google.com/drive/folders')) {
            $gdriveWebLink = '';
        }

        $tuAccount = User::getSingleUserByRole('tata_usaha');
        $defaultTuNama = $tuAccount['name'] ?? 'Maya Sulfiana, S.Kom';
        $defaultTuNip = !empty($tuAccount['nip']) ? (str_starts_with($tuAccount['nip'], 'NIP.') ? $tuAccount['nip'] : 'NIP. ' . $tuAccount['nip']) : 'NIP. 198612252011012016';
        $defaultTuJabatan = $tuAccount['jabatan'] ?? 'Kepala Sub Bagian Tata Usaha TVRI Stasiun Jawa Timur';

        $isDetailAcara = isset($_POST['is_detail_acara']) ? (!empty($_POST['is_detail_acara']) && (string)$_POST['is_detail_acara'] !== '0') : false;
        $hasSuratTugas = isset($_POST['has_surat_tugas']) ? (!empty($_POST['has_surat_tugas']) && (string)$_POST['has_surat_tugas'] !== '0') : false;
        $isTglHunting = !empty($_POST['is_tgl_hunting']) || (!empty($_POST['tgl_hunting']) && trim($_POST['tgl_hunting']) !== '');
        $tglHunting = Security::sanitize($_POST['tgl_hunting'] ?? '');
        if ($isTglHunting && empty($tglHunting)) {
            $tglHunting = '-';
        }

        $meta = [
            'kategori_surat' => 'Surat Permohonan & Dinas',
            'is_detail_acara' => $isDetailAcara ? 1 : 0,
            'has_surat_tugas' => $hasSuratTugas ? 1 : 0,
            'is_tgl_hunting' => $isTglHunting ? 1 : 0,
            'admin_gdrive_web_link' => $gdriveWebLink,
            'gdrive_web_link' => $gdriveWebLink,
            'sifat_surat' => Security::sanitize($_POST['sifat_surat'] ?? 'Biasa'),
            'dari' => Security::sanitize($_POST['dari'] ?? 'Kepala Sub Bagian Tata Usaha'),
            'penandatangan_nama' => Security::sanitize($_POST['penandatangan_nama'] ?? $defaultTuNama),
            'penandatangan_nip' => Security::sanitize($_POST['penandatangan_nip'] ?? $defaultTuNip),
            'penandatangan_jabatan' => Security::sanitize($_POST['penandatangan_jabatan'] ?? $defaultTuJabatan),
            'pembuka_surat' => Security::sanitize($_POST['pembuka_surat'] ?? ''),
            'mata_acara' => Security::sanitize($_POST['mata_acara'] ?? ''),
            'nama_program' => Security::sanitize($_POST['nama_program'] ?? ''),
            'tanggal_mulai' => Security::sanitize($_POST['tanggal_mulai'] ?? ''),
            'tgl_hunting' => $isTglHunting ? $tglHunting : '',
            'tgl_siaran' => Security::sanitize($_POST['tgl_siaran'] ?? ''),
            'lokasi_syuting' => Security::sanitize($_POST['lokasi_syuting'] ?? ''),
            'paket' => Security::sanitize($_POST['paket'] ?? ''),
            'sumber_biaya' => Security::sanitize($_POST['sumber_biaya'] ?? ''),
            'tim_produksi' => $_POST['tim_produksi'] ?? '',
            'kru_table_json' => $_POST['kru_table_json'] ?? '',
            'pemohon_nama' => Security::sanitize($_POST['pemohon_nama'] ?? ''),
            'pemohon_nip' => Security::sanitize($_POST['pemohon_nip'] ?? ''),
            'pemohon_jabatan' => Security::sanitize($_POST['pemohon_jabatan'] ?? ''),
            'pemohon_unit' => Security::sanitize($_POST['pemohon_unit'] ?? ''),
            'pemohon_signature' => $pemohonSignaturePathProd,
            'lampiran_pdf_path' => $pdfAttachmentPaths[0] ?? '',
            'lampiran_pdf_paths' => $pdfAttachmentPaths,
            'lampiran_page_rotations' => json_decode($_POST['lampiran_page_rotations'] ?? '{}', true) ?? [],
            'kepada_permohonan' => Security::sanitize($_POST['kepada_permohonan'] ?? 'Yth. Kepala Sub Bagian Tata Usaha'),
            'kepada_sub_permohonan' => Security::sanitize($_POST['kepada_sub_permohonan'] ?? 'TVRI Stasiun Jawa Timur'),
            'kepada_sub' => Security::sanitize($_POST['kepada_sub'] ?? 'TVRI Stasiun Jawa Timur'),
            'perihal_permohonan' => Security::sanitize($_POST['perihal_permohonan'] ?? $perihal),
            'isi_surat_permohonan' => $_POST['isi_surat_permohonan'] ?? $_POST['pembuka_surat'] ?? '',
            'isi_surat_dinas' => $_POST['isi_surat_dinas'] ?? $isiSurat,
            'penutup_surat' => Security::sanitize($_POST['penutup_surat'] ?? $_POST['penutup_permohonan'] ?? '')
        ];

        if (empty($perihal) || empty($kepada)) {
            Session::setFlash('error', 'Perihal dan Tujuan (Kepada) wajib diisi.');
            $this->redirect('/surat-nota-dinas');
            return;
        }

        $nomorSurat = Security::sanitize($_POST['nomor_surat'] ?? '');
        $userId = (int) Session::get('user_id');
        $suratIdPost = (int) ($_POST['surat_id'] ?? 0);

        if ($suratIdPost > 0) {
            $existingSurat = $this->suratModel->getById($suratIdPost);
            if ($existingSurat && !empty($existingSurat['meta'])) {
                if (empty($pdfAttachmentPaths) && !empty($existingSurat['meta']['lampiran_pdf_paths'])) {
                    $meta['lampiran_pdf_paths'] = $existingSurat['meta']['lampiran_pdf_paths'];
                    $meta['lampiran_pdf_path'] = $existingSurat['meta']['lampiran_pdf_path'] ?? ($existingSurat['meta']['lampiran_pdf_paths'][0] ?? '');
                }
            }
            // Reset signatures & revision notes in meta when resubmitting a revised letter
            unset($meta['kpp_paraf'], $meta['paraf_kpp'], $meta['kpp_signature'], $meta['tu_signature'], $meta['sekre_signature'], $meta['kepsta_signature'], $meta['catatan_revisi']);

            $this->suratModel->updateSurat($suratIdPost, [
                'kpp_user_id' => $kppUserId ?: null,
                'perihal' => $perihal,
                'kepada' => $kepada,
                'lampiran' => $lampiran,
                'tanggal_surat' => $tanggal,
                'isi_surat' => $isiSurat,
                'meta_data' => $meta,
                'gdrive_web_link' => $gdriveWebLink ?: null,
                'kpp_signature' => null,
                'kpp_signed_at' => null,
                'tu_signature' => null,
                'tu_signed_at' => null,
                'pdf_path' => null,
                'status' => 'submitted_to_kpp'
            ]);

            if (!empty($nomorSurat)) {
                $this->suratModel->updateSurat($suratIdPost, ['nomor_surat' => $nomorSurat]);
            }

            SuratLog::log($suratIdPost, $userId, 'Surat Nota Dinas Direvisi & Dikirim Ulang ke KPP Bidang', 'submitted_to_kpp', 'Surat Nota Dinas telah diperbarui oleh Admin Pembuat Surat dan dikirim ulang ke KPP Bidang.');
            ActivityLog::log('REVISE_SURAT_NOTA_DINAS', 'Memperbarui Surat Nota Dinas perihal "' . $perihal . '"', $userId);

            Session::setFlash('success', 'Surat Nota Dinas berhasil direvisi dan dikirim ulang ke KPP Bidang!');
            $this->redirect('/surat/revisi');
            return;
        }

        $nomorSurat = Security::sanitize($_POST['nomor_surat'] ?? '');
        $userId = (int) Session::get('user_id');
        $suratId = $this->suratModel->createSurat([
            'type' => 'izin_produksi',
            'user_id' => $userId,
            'kpp_user_id' => $kppUserId ?: null,
            'perihal' => $perihal,
            'kepada' => $kepada,
            'lampiran' => $lampiran,
            'tanggal_surat' => $tanggal,
            'isi_surat' => $isiSurat,
            'meta_data' => $meta,
            'gdrive_web_link' => $gdriveWebLink ?: null,
            'status' => 'submitted_to_kpp'
        ]);

        if (!empty($nomorSurat)) {
            $this->suratModel->updateSurat($suratId, ['nomor_surat' => $nomorSurat]);
        }

        SuratLog::log($suratId, $userId, 'Surat Nota Dinas Dibuat & Dikirim ke KPP Bidang', 'submitted_to_kpp', 'Surat Nota Dinas berhasil dibuat dan dikirim ke KPP Bidang.');
        ActivityLog::log('CREATE_SURAT_NOTA_DINAS', 'Membuat Surat Nota Dinas perihal "' . $perihal . '"', $userId);

        Session::setFlash('success', 'Surat Nota Dinas berhasil dibuat dan dikirim ke KPP Bidang!');
        $this->redirect('/surat');
    }

    // Admin: Show create or edit Surat Nota Dinas form
    public function createNotaDinas(): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['admin', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak. Hanya Admin Pembuat Surat yang dapat membuat surat.');
            $this->redirect('/dashboard');
            return;
        }

        $userId = (int) Session::get('user_id');
        $userModel = new User();
        $currentUser = $userModel->findById($userId);
        $kppUsers = $userModel->getKppUsers();
        $kapelSdmUser = User::getKapelSdmUser();

        $suratId = (int) ($_GET['id'] ?? 0);
        $surat = null;
        if ($suratId > 0) {
            $surat = $this->suratModel->getById($suratId);
            if (!$surat || ($role !== 'superadmin' && (int)$surat['user_id'] !== $userId)) {
                Session::setFlash('error', 'Surat tidak ditemukan atau Anda tidak memiliki akses.');
                $this->redirect('/surat/revisi');
                return;
            }

            // Auto-redirect if letter category is Izin Produksi and NOT Surat Cuti
            $sType = $surat['type'] ?? '';
            $sKat = $surat['meta']['kategori_surat'] ?? '';
            $isCuti = Surat::isCutiSurat($surat);
            if (!$isCuti && ($sType === 'izin_produksi' || str_contains(strtolower($sKat), 'produksi'))) {
                $this->redirect('/surat-nota-dinas?id=' . $suratId);
                return;
            }
        }

        $this->view('surat/create_nota_dinas', [
            'title' => ($surat ? 'Revisi Surat Cuti' : 'Buat Surat Cuti') . ' - Surat Digital TVRI Jawa Timur',
            'currentUser' => $currentUser,
            'kppUsers' => $kppUsers,
            'kapelSdmUser' => $kapelSdmUser,
            'surat' => $surat
        ]);
    }

    // Admin: Store Surat Nota Dinas
    public function storeNotaDinas(): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['admin', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak.');
            $this->redirect('/dashboard');
            return;
        }

        $perihal = Security::sanitize($_POST['perihal'] ?? '');
        $kepada = Security::sanitize($_POST['kepada'] ?? 'Kepala TVRI Stasiun Jawa Timur');
        $lampiran = Security::sanitize($_POST['lampiran'] ?? '');
        $tanggal = Security::sanitize($_POST['tanggal_surat'] ?? date('Y-m-d'));
        $isiSurat = $_POST['isi_surat'] ?? '';
        $kppUserId = (int) ($_POST['kpp_user_id'] ?? 0);
        if ($kppUserId <= 0) {
            Session::setFlash('error', 'Silakan pilih KPP Bidang penandatangan surat terlebih dahulu.');
            $suratIdPost = (int) ($_POST['surat_id'] ?? 0);
            $this->redirect('/surat-cuti' . ($suratIdPost > 0 ? '?id=' . $suratIdPost : ''));
            return;
        }

        // Handle TTD Pemohon if provided
        $pemohonSignaturePathND = '';
        $pemohonSigDataND = $_POST['pemohon_signature_data'] ?? '';
        if (!empty($pemohonSigDataND) && str_contains($pemohonSigDataND, 'data:image')) {
            $dataSigND = explode(',', $pemohonSigDataND);
            if (isset($dataSigND[1])) {
                $decodedSigND = base64_decode($dataSigND[1]);
                $uploadDirSigND = __DIR__ . '/../public/uploads/signatures';
                $uploadDirSigNDRoot = __DIR__ . '/../uploads/signatures';
                if (!file_exists($uploadDirSigND)) mkdir($uploadDirSigND, 0777, true);
                if (!file_exists($uploadDirSigNDRoot)) mkdir($uploadDirSigNDRoot, 0777, true);
                $filenameSigND = 'ttd_pemohon_' . time() . '_' . mt_rand(1000, 9999) . '.png';
                file_put_contents($uploadDirSigND . '/' . $filenameSigND, $decodedSigND);
                file_put_contents($uploadDirSigNDRoot . '/' . $filenameSigND, $decodedSigND);
                $pemohonSignaturePathND = '/uploads/signatures/' . $filenameSigND;
            }
        } elseif (!empty($pemohonSigDataND)) {
            $pemohonSignaturePathND = Security::sanitize($pemohonSigDataND);
        }

        if (!empty($pemohonSignaturePathND) && function_exists('makeSignatureBlue')) {
            $pemohonSignaturePathND = makeSignatureBlue($pemohonSignaturePathND);
        }

        // Handle uploaded PDF attachment(s) if provided (Multi-PDF support)
        $pdfAttachmentPaths = [];
        if (isset($_FILES['lampiran_pdf'])) {
            $files = $_FILES['lampiran_pdf'];
            if (is_array($files['name'])) {
                $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if (isset($files['error'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpPath = $files['tmp_name'][$i];
                        $fileName = $files['name'][$i];
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        if ($fileExt === 'pdf') {
                            $uploadDirPdf = __DIR__ . '/../public/uploads/pdf';
                            $uploadDirPdfRoot = __DIR__ . '/../uploads/pdf';
                            if (!file_exists($uploadDirPdf)) mkdir($uploadDirPdf, 0777, true);
                            if (!file_exists($uploadDirPdfRoot)) mkdir($uploadDirPdfRoot, 0777, true);
                            $newPdfName = 'lampiran_pdf_' . time() . '_' . mt_rand(1000, 9999) . '_' . $i . '.pdf';
                            move_uploaded_file($tmpPath, $uploadDirPdf . '/' . $newPdfName);
                            @copy($uploadDirPdf . '/' . $newPdfName, $uploadDirPdfRoot . '/' . $newPdfName);
                            $pdfAttachmentPaths[] = '/uploads/pdf/' . $newPdfName;
                        }
                    }
                }
            } elseif (isset($files['error']) && $files['error'] === UPLOAD_ERR_OK) {
                $tmpPath = $files['tmp_name'];
                $fileName = $files['name'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if ($fileExt === 'pdf') {
                    $uploadDirPdf = __DIR__ . '/../public/uploads/pdf';
                    $uploadDirPdfRoot = __DIR__ . '/../uploads/pdf';
                    if (!file_exists($uploadDirPdf)) mkdir($uploadDirPdf, 0777, true);
                    if (!file_exists($uploadDirPdfRoot)) mkdir($uploadDirPdfRoot, 0777, true);
                    $newPdfName = 'lampiran_pdf_' . time() . '_' . mt_rand(1000, 9999) . '.pdf';
                    move_uploaded_file($tmpPath, $uploadDirPdf . '/' . $newPdfName);
                    @copy($uploadDirPdf . '/' . $newPdfName, $uploadDirPdfRoot . '/' . $newPdfName);
                    $pdfAttachmentPaths[] = '/uploads/pdf/' . $newPdfName;
                }
            }
        }

        $gdriveWebLink = Security::sanitize($_POST['gdrive_web_link'] ?? '');
        if ($gdriveWebLink === 'https://drive.google.com' || str_contains($gdriveWebLink, 'drive.google.com/drive/folders')) {
            $gdriveWebLink = '';
        }

        $tuAccount = User::getSingleUserByRole('tata_usaha');
        $defaultTuNama = $tuAccount['name'] ?? 'Maya Sulfiana, S.Kom';
        $defaultTuNip = !empty($tuAccount['nip']) ? (str_starts_with($tuAccount['nip'], 'NIP.') ? $tuAccount['nip'] : 'NIP. ' . $tuAccount['nip']) : 'NIP. 198612252011012016';
        $defaultTuJabatan = $tuAccount['jabatan'] ?? 'Kepala Sub Bagian Tata Usaha TVRI Stasiun Jawa Timur';

        $meta = [
            'kategori_surat' => Security::sanitize($_POST['kategori_surat'] ?? 'Surat Cuti'),
            'admin_gdrive_web_link' => $gdriveWebLink,
            'gdrive_web_link' => $gdriveWebLink,
            'sifat_surat' => Security::sanitize($_POST['sifat_surat'] ?? 'Biasa'),
            'dari' => Security::sanitize($_POST['dari'] ?? 'Kepala Sub Bagian Tata Usaha'),
            'penandatangan_nama' => Security::sanitize($_POST['penandatangan_nama'] ?? $defaultTuNama),
            'penandatangan_nip' => Security::sanitize($_POST['penandatangan_nip'] ?? $defaultTuNip),
            'penandatangan_jabatan' => Security::sanitize($_POST['penandatangan_jabatan'] ?? $defaultTuJabatan),
            'pemohon_nama' => Security::sanitize($_POST['pemohon_nama'] ?? ''),
            'pemohon_nopeg' => Security::sanitize($_POST['pemohon_nopeg'] ?? ''),
            'pemohon_jabatan' => Security::sanitize($_POST['pemohon_jabatan'] ?? ''),
            'pemohon_hp' => Security::sanitize($_POST['pemohon_hp'] ?? ''),
            'pemohon_signature' => $pemohonSignaturePathND,
            'isi_surat_dinas' => $isiSurat,
            'penutup_surat' => Security::sanitize($_POST['penutup_surat'] ?? ''),
            'lampiran_pdf_path' => $pdfAttachmentPaths[0] ?? '',
            'lampiran_pdf_paths' => $pdfAttachmentPaths,
            'lampiran_page_rotations' => json_decode($_POST['lampiran_page_rotations'] ?? '{}', true) ?? []
        ];

        if (empty($perihal) || empty($kepada)) {
            Session::setFlash('error', 'Perihal dan Tujuan (Kepada) wajib diisi.');
            $this->redirect('/surat-nota-dinas');
            return;
        }

        $nomorSurat = Security::sanitize($_POST['nomor_surat'] ?? '');
        $userId = (int) Session::get('user_id');
        $suratIdPost = (int) ($_POST['surat_id'] ?? 0);

        if ($suratIdPost > 0) {
            $existingSurat = $this->suratModel->getById($suratIdPost);
            if ($existingSurat && !empty($existingSurat['meta'])) {
                if (empty($pdfAttachmentPaths) && !empty($existingSurat['meta']['lampiran_pdf_paths'])) {
                    $meta['lampiran_pdf_paths'] = $existingSurat['meta']['lampiran_pdf_paths'];
                    $meta['lampiran_pdf_path'] = $existingSurat['meta']['lampiran_pdf_path'] ?? ($existingSurat['meta']['lampiran_pdf_paths'][0] ?? '');
                }
                if (empty($meta['pemohon_signature']) && !empty($existingSurat['meta']['pemohon_signature'])) {
                    $meta['pemohon_signature'] = $existingSurat['meta']['pemohon_signature'];
                }
            }
            // Reset signatures & revision notes in meta when resubmitting a revised letter
            unset(
                $meta['kpp_paraf'], $meta['paraf_kpp'], $meta['kapel_paraf'], $meta['sdm_paraf'],
                $meta['kpp_signature'], $meta['tu_signature'], $meta['sekre_signature'],
                $meta['kepsta_signature'], $meta['catatan_revisi']
            );

            $this->suratModel->updateSurat($suratIdPost, [
                'type' => 'nota_dinas',
                'kpp_user_id' => $kppUserId,
                'perihal' => $perihal,
                'kepada' => $kepada,
                'lampiran' => $lampiran,
                'tanggal_surat' => $tanggal,
                'isi_surat' => $isiSurat,
                'meta_data' => $meta,
                'gdrive_web_link' => $gdriveWebLink ?: null,
                'kpp_signature' => null,
                'kpp_signed_at' => null,
                'tu_signature' => null,
                'tu_signed_at' => null,
                'pdf_path' => null,
                'status' => 'submitted_to_kpp'
            ]);

            if (!empty($nomorSurat)) {
                $this->suratModel->updateSurat($suratIdPost, ['nomor_surat' => $nomorSurat]);
            }

            SuratLog::log($suratIdPost, $userId, 'Surat Cuti Direvisi & Dikirim Ulang ke KPP Bidang', 'submitted_to_kpp', 'Surat Cuti telah diperbarui oleh Admin Pembuat Surat dan dikirim ulang ke KPP Bidang.');
            ActivityLog::log('REVISE_SURAT_CUTI', 'Memperbarui Surat Cuti perihal "' . $perihal . '"', $userId);

            Session::setFlash('success', 'Surat Cuti berhasil direvisi dan dikirim ulang ke KPP Bidang!');
            $this->redirect('/surat/revisi');
            return;
        }

        $suratId = $this->suratModel->createSurat([
            'type' => 'nota_dinas',
            'user_id' => $userId,
            'kpp_user_id' => $kppUserId,
            'perihal' => $perihal,
            'kepada' => $kepada,
            'lampiran' => $lampiran,
            'tanggal_surat' => $tanggal,
            'isi_surat' => $isiSurat,
            'meta_data' => $meta,
            'gdrive_web_link' => $gdriveWebLink ?: null,
            'status' => 'submitted_to_kpp'
        ]);

        if (!empty($nomorSurat)) {
            $this->suratModel->updateSurat($suratId, ['nomor_surat' => $nomorSurat]);
        }

        SuratLog::log($suratId, $userId, 'Surat Cuti Dibuat & Dikirim ke KPP Bidang', 'submitted_to_kpp', 'Surat Cuti berhasil dibuat dan dikirim ke KPP Bidang.');
        ActivityLog::log('CREATE_SURAT_CUTI', 'Membuat Surat Cuti perihal "' . $perihal . '"', $userId);

        Session::setFlash('success', 'Surat Cuti berhasil dibuat dan dikirim ke KPP Bidang!');
        $this->redirect('/surat');
    }

    // KPP Bidang / Kapel SDM: Process & Sign form
    public function kppProcess(int $id): void
    {
        $role = Session::get('user_role', 'admin');
        $isKapelSdmUser = function_exists('isKapelSdm') && isKapelSdm();
        if (!in_array($role, ['kpp_bidang', 'kpp', 'superadmin']) && !$isKapelSdmUser) {
            Session::setFlash('error', 'Akses ditolak. Fitur ini khusus KPP Bidang dan Kapel SDM.');
            $this->redirect('/surat/detail/' . $id);
            return;
        }

        $surat = $this->suratModel->getById($id);
        if (!$surat) {
            Session::setFlash('error', 'Surat tidak ditemukan.');
            $this->redirect('/surat');
            return;
        }

        $isCuti = Surat::isCutiSurat($surat);
        $title = $isCuti 
            ? ($isKapelSdmUser ? 'Paraf Surat Cuti - Kapel Pengelola SDM & RB' : 'Paraf Surat Cuti - KPP Bidang')
            : ($isKapelSdmUser ? 'Paraf Nota Dinas - Kapel Pengelola SDM & RB' : 'TTD Permohonan & Paraf Nota Dinas - KPP Bidang');

        $this->view('surat/kpp_process', [
            'title' => $title,
            'surat' => $surat
        ]);
    }

    // KPP Bidang / Kapel SDM: Submit Signature on Surat Permohonan & Paraf on Nota Dinas
    public function kppSubmit(int $id): void
    {
        $role = Session::get('user_role', 'admin');
        $isKapelSdmUser = function_exists('isKapelSdm') && isKapelSdm();
        if (!in_array($role, ['kpp_bidang', 'kpp', 'superadmin']) && !$isKapelSdmUser) {
            Session::setFlash('error', 'Akses ditolak.');
            $this->redirect('/dashboard');
            return;
        }

        $surat = $this->suratModel->getById($id);
        if (!$surat) {
            Session::setFlash('error', 'Surat tidak ditemukan.');
            $this->redirect('/dashboard');
            return;
        }

        $actionType = $_POST['action_type'] ?? 'submit';
        $currentUserId = (int) Session::get('user_id');

        if ($actionType === 'revisi') {
            $catatanRevisi = Security::sanitize($_POST['catatan_revisi'] ?? '');
            if (empty($catatanRevisi)) {
                Session::setFlash('error', 'Catatan revisi wajib diisi jika memilih revisi.');
                $this->redirect('/surat/kpp-process/' . $id);
                return;
            }

            $meta = $surat['meta'] ?? [];
            $meta['catatan_revisi'] = $catatanRevisi;

            $this->suratModel->updateSurat($id, [
                'status' => 'revisi',
                'meta_data' => $meta
            ]);

            $actorName = $isKapelSdmUser ? 'Kapel Pengelola SDM & RB' : 'KPP Bidang';
            SuratLog::log($id, $currentUserId, 'Permintaan Revisi oleh ' . $actorName, 'revisi', $actorName . ' mengembalikan surat ke Admin Pembuat Surat untuk direvisi. Catatan: ' . $catatanRevisi);
            ActivityLog::log('PROCESS_KPP_REVISI', 'Meminta revisi surat perihal "' . ($surat['perihal'] ?? '') . '" (Catatan: ' . $catatanRevisi . ')', $currentUserId);

            Session::setFlash('warning', 'Surat berhasil dikembalikan ke Admin Pembuat Surat untuk direvisi.');
            $this->redirect('/dashboard');
            return;
        }

        $isCuti = Surat::isCutiSurat($surat);
        $meta = $surat['meta'] ?? [];

        if ($isCuti) {
            if ($isKapelSdmUser) {
                // Kapel SDM: Paraf di kiri paraf KPP, lalu diteruskan ke Tata Usaha
                $kapelParafData = $_POST['kapel_paraf_data'] ?? $_POST['kpp_paraf_data'] ?? '';
                $kapelParafPath = !empty($meta['kapel_paraf']) ? $meta['kapel_paraf'] : (!empty($meta['sdm_paraf']) ? $meta['sdm_paraf'] : '');

                if (!empty($kapelParafData) && str_contains($kapelParafData, 'data:image')) {
                    $dataP = explode(',', $kapelParafData);
                    $decodedP = base64_decode($dataP[1]);
                    $uploadDirP = __DIR__ . '/../public/uploads/paraf';
                    $uploadDirPRoot = __DIR__ . '/../uploads/paraf';
                    if (!file_exists($uploadDirP)) mkdir($uploadDirP, 0777, true);
                    if (!file_exists($uploadDirPRoot)) mkdir($uploadDirPRoot, 0777, true);
                    $filenameP = 'paraf_kapel_sdm_' . $id . '_' . time() . '.png';
                    file_put_contents($uploadDirP . '/' . $filenameP, $decodedP);
                    file_put_contents($uploadDirPRoot . '/' . $filenameP, $decodedP);
                    $kapelParafPath = '/uploads/paraf/' . $filenameP;
                } elseif (!empty($kapelParafData) && !str_contains($kapelParafData, 'data:image')) {
                    $kapelParafPath = $kapelParafData;
                }

                if (!empty($kapelParafPath) && function_exists('makeSignatureBlue')) {
                    $kapelParafPath = makeSignatureBlue($kapelParafPath);
                }

                $meta['kapel_paraf'] = $kapelParafPath;
                $meta['sdm_paraf'] = $kapelParafPath;
                $meta['sdm_user_id'] = $currentUserId;
                $meta['sdm_signed_at'] = date('Y-m-d H:i:s');

                $halCuti = trim($surat['perihal'] ?? '');
                if (empty($halCuti)) {
                    $halCuti = 'Permohonan Cuti';
                }
                $halClean = preg_replace('/\.pdf$/i', '', $halCuti);
                $halClean = trim(preg_replace('/[\/\\:*?"<>|]/', ' ', $halClean));
                $halClean = preg_replace('/\s+/', ' ', $halClean);
                if (str_starts_with($halClean, '(') && str_ends_with($halClean, ')')) {
                    $halClean = trim(substr($halClean, 1, -1));
                }
                if (empty($halClean)) {
                    $halClean = 'Permohonan Cuti';
                }

                $nomorSurat = trim($surat['nomor_surat'] ?? $meta['nomor_surat'] ?? $meta['nomor_surat_disposisi'] ?? '');
                if (!empty($nomorSurat)) {
                    $gdriveFilename = $nomorSurat . ' ' . $halClean . '.pdf';
                    $nomorSuratDisk = trim(preg_replace('/[\/\\:*?"<>|]/', '-', $nomorSurat));
                    $nomorSuratDisk = preg_replace('/-+/', '-', $nomorSuratDisk);
                    $pdfFilename = $nomorSuratDisk . ' ' . $halClean . '.pdf';
                } else {
                    $gdriveFilename = 'Surat ' . $halClean . '.pdf';
                    $pdfFilename = $gdriveFilename;
                }

                $pdfPathRel = '/uploads/pdf/' . $pdfFilename;
                $meta['pdf_path'] = $pdfPathRel;

                $this->suratModel->updateSurat($id, [
                    'meta_data' => $meta,
                    'pdf_path' => $pdfPathRel,
                    'status' => 'kpp_signed'
                ]);

                // Generate PDF & Barcode serta Upload ke Google Drive KTU oleh Kapel SDM
                if (class_exists('PdfGenerator') && !empty($pdfFilename)) {
                    try {
                        // 1. Generate physical PDF pertama
                        $pdfFullPath = PdfGenerator::generateMergedPdf($id, false, $pdfFilename);

                        // 2. Upload ke Google Drive KTU
                        $ktuFolderId = Setting::get('gdrive_ktu_folder_id', '1m1SVmXboA73OFJKSK-BjfRtYXMdxlfm7');
                        $suratObj = $this->suratModel->getById($id);
                        $uploadName = !empty($gdriveFilename) ? $gdriveFilename : $pdfFilename;

                        $gdriveFileId = $surat['gdrive_file_id'] ?? $meta['gdrive_file_id'] ?? '';
                        $gdriveWebLink = $surat['gdrive_web_link'] ?? $meta['gdrive_web_link'] ?? '';

                        if (empty($gdriveFileId) || str_starts_with($gdriveFileId, 'gdrive_')) {
                            $gdriveResult = GoogleDriveService::uploadPdf($pdfFullPath, $uploadName, $suratObj['kode_surat'] ?? '', $ktuFolderId);
                            if (!empty($gdriveResult['success']) && !empty($gdriveResult['web_link'])) {
                                $gdriveWebLink = $gdriveResult['web_link'];
                                $gdriveFileId = $gdriveResult['file_id'] ?? '';
                                $meta['gdrive_file_id'] = $gdriveFileId;
                                $meta['gdrive_web_link'] = $gdriveWebLink;
                            }
                        } else {
                            GoogleDriveService::updatePdfContent($gdriveFileId, $pdfFullPath);
                        }

                        // 3. Barcode mengarah ke Google Drive KTU atau PDF lokal
                        $barcodeTargetLink = !empty($gdriveWebLink) 
                            ? $gdriveWebLink 
                            : ((defined('BASE_URL') ? rtrim(BASE_URL, '/') : '') . $pdfPathRel);

                        if (function_exists('generateNotaDinasBarcode')) {
                            generateNotaDinasBarcode($id, $meta, $suratObj, $barcodeTargetLink);
                        }

                        $meta['pdf_path'] = $pdfPathRel;
                        $meta['barcode_target_link'] = $barcodeTargetLink;

                        $this->suratModel->updateSurat($id, [
                            'gdrive_file_id' => $gdriveFileId ?: null,
                            'gdrive_web_link' => $gdriveWebLink ?: null,
                            'pdf_path' => $pdfPathRel,
                            'meta_data' => $meta,
                            'status' => 'kpp_signed'
                        ]);

                        // 4. Re-generate PDF agar barcode Google Drive tertempel pada PDF fisik
                        $pdfFullPath = PdfGenerator::generateMergedPdf($id, false, $pdfFilename);
                        if (!empty($gdriveFileId) && !str_starts_with($gdriveFileId, 'gdrive_')) {
                            GoogleDriveService::updatePdfContent($gdriveFileId, $pdfFullPath);
                        }
                    } catch (\Throwable $e) {
                        error_log('Error generating & uploading Surat Cuti PDF on kapelSdmSubmit: ' . $e->getMessage());
                    }
                }

                $logFilename = $gdriveFilename ?: ($pdfFilename ?: '');
                SuratLog::log($id, $currentUserId, 'Paraf Kapel SDM Dibubuhkan & Diteruskan ke Tata Usaha', 'kpp_signed', 'Kapel Pengelola SDM & RB telah membubuhkan Paraf Digital pada Surat Cuti. File PDF "' . $logFilename . '" dan Barcode telah tergenerate, surat diteruskan ke Tata Usaha.');
                ActivityLog::log('PROCESS_KAPEL_SDM', 'Membubuhkan Paraf Kapel SDM pada surat cuti perihal "' . ($surat['perihal'] ?? '') . '" dan meneruskan ke Tata Usaha', $currentUserId);

                Session::setFlash('success', 'Paraf Surat Cuti berhasil disimpan. Surat diteruskan ke Tata Usaha.');
                $this->redirect('/dashboard');
                return;
            } else {
                // KPP Bidang: Input paraf di kiri nama KTU, lalu diteruskan ke Kapel SDM (tanpa generate file PDF dan barcode)
                $kppParafData = $_POST['kpp_paraf_data'] ?? '';
                $parafPath = !empty($meta['kpp_paraf']) ? $meta['kpp_paraf'] : (!empty($meta['paraf_kpp']) ? $meta['paraf_kpp'] : '');

                if (!empty($kppParafData) && str_contains($kppParafData, 'data:image')) {
                    $dataP = explode(',', $kppParafData);
                    $decodedP = base64_decode($dataP[1]);
                    $uploadDirP = __DIR__ . '/../public/uploads/paraf';
                    $uploadDirPRoot = __DIR__ . '/../uploads/paraf';
                    if (!file_exists($uploadDirP)) mkdir($uploadDirP, 0777, true);
                    if (!file_exists($uploadDirPRoot)) mkdir($uploadDirPRoot, 0777, true);
                    $filenameP = 'paraf_kpp_' . $id . '_' . time() . '.png';
                    file_put_contents($uploadDirP . '/' . $filenameP, $decodedP);
                    file_put_contents($uploadDirPRoot . '/' . $filenameP, $decodedP);
                    $parafPath = '/uploads/paraf/' . $filenameP;
                } elseif (!empty($kppParafData) && !str_contains($kppParafData, 'data:image')) {
                    $parafPath = $kppParafData;
                }

                if (!empty($parafPath) && function_exists('makeSignatureBlue')) {
                    $parafPath = makeSignatureBlue($parafPath);
                }

                $meta['kpp_paraf'] = $parafPath;
                $meta['paraf_kpp'] = $parafPath;
                $meta['kpp_signature'] = $parafPath;
                $meta['kpp_bidang_user_id'] = $currentUserId;
                $meta['kpp_signed_at'] = date('Y-m-d H:i:s');

                $targetKppUserId = !empty($surat['kpp_user_id']) ? (int)$surat['kpp_user_id'] : $currentUserId;
                $this->suratModel->updateSurat($id, [
                    'kpp_user_id' => $targetKppUserId,
                    'kpp_signature' => $parafPath,
                    'kpp_signed_at' => date('Y-m-d H:i:s'),
                    'meta_data' => $meta,
                    'status' => 'submitted_to_sdm'
                ]);

                SuratLog::log($id, $currentUserId, 'Paraf KPP Bidang Dibubuhkan & Diteruskan ke Kapel SDM', 'submitted_to_sdm', 'KPP Bidang telah membubuhkan Paraf Digital pada Surat Cuti. Surat diteruskan ke Kapel Pengelola SDM & RB.');
                ActivityLog::log('PROCESS_KPP', 'Membubuhkan Paraf KPP Bidang pada surat cuti perihal "' . ($surat['perihal'] ?? '') . '" dan meneruskan ke Kapel SDM', $currentUserId);

                Session::setFlash('success', 'Paraf Surat Cuti berhasil disimpan. Surat diteruskan ke Kapel Pengelola SDM & RB.');
                $this->redirect('/dashboard');
                return;
            }
        }

        $meta = $surat['meta'] ?? [];
        $signatureData = $_POST['kpp_signature_data'] ?? '';
        $parafData = $_POST['kpp_paraf_data'] ?? '';

        $signaturePath = !empty($surat['kpp_signature']) && $surat['kpp_signature'] !== '/assets/img/ttd_tu_stamp.png' 
            ? $surat['kpp_signature'] 
            : (!empty($meta['kpp_signature']) && $meta['kpp_signature'] !== '/assets/img/ttd_tu_stamp.png' ? $meta['kpp_signature'] : '');

        // Save canvas drawing for TTD KPP Bidang on Surat Permohonan if provided
        if (!empty($signatureData) && str_contains($signatureData, 'data:image')) {
            $data = explode(',', $signatureData);
            $decoded = base64_decode($data[1]);
            $uploadDir = __DIR__ . '/../public/uploads/signatures';
            $uploadDirRoot = __DIR__ . '/../uploads/signatures';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            if (!file_exists($uploadDirRoot)) mkdir($uploadDirRoot, 0777, true);
            $filename = 'ttd_kpp_' . $id . '_' . time() . '.png';
            file_put_contents($uploadDir . '/' . $filename, $decoded);
            file_put_contents($uploadDirRoot . '/' . $filename, $decoded);
            $signaturePath = '/uploads/signatures/' . $filename;
        } elseif (!empty($signatureData) && !str_contains($signatureData, 'data:image') && $signatureData !== '/assets/img/ttd_tu_stamp.png') {
            $signaturePath = $signatureData;
        }

        // Save canvas drawing for Paraf KPP Bidang on Nota Dinas if provided
        $parafPath = !empty($meta['kpp_paraf']) ? $meta['kpp_paraf'] : (!empty($meta['tu_paraf']) ? $meta['tu_paraf'] : '');
        if (!empty($parafData) && str_contains($parafData, 'data:image')) {
            $dataP = explode(',', $parafData);
            $decodedP = base64_decode($dataP[1]);
            $uploadDirP = __DIR__ . '/../public/uploads/paraf';
            $uploadDirPRoot = __DIR__ . '/../uploads/paraf';
            if (!file_exists($uploadDirP)) mkdir($uploadDirP, 0777, true);
            if (!file_exists($uploadDirPRoot)) mkdir($uploadDirPRoot, 0777, true);
            $filenameP = 'paraf_kpp_' . $id . '_' . time() . '.png';
            file_put_contents($uploadDirP . '/' . $filenameP, $decodedP);
            file_put_contents($uploadDirPRoot . '/' . $filenameP, $decodedP);
            $parafPath = '/uploads/paraf/' . $filenameP;
        } elseif (!empty($parafData) && !str_contains($parafData, 'data:image')) {
            $parafPath = $parafData;
        }

        if (empty($parafPath) && !empty($signaturePath)) {
            $parafPath = $signaturePath;
        }

        $meta['kpp_signature'] = $signaturePath;
        $meta['kpp_paraf'] = $parafPath;
        $meta['paraf_kpp'] = $parafPath;
        $meta['tu_paraf'] = $parafPath;

        $kppUserId = !empty($surat['kpp_user_id']) ? (int)$surat['kpp_user_id'] : $currentUserId;
        if (empty($kppUserId)) {
            $kppUserId = $currentUserId;
        }

        // Auto-generate barcode & PDF file untuk Surat Nota Dinas saat diteruskan ke Tata Usaha
        $isCuti = Surat::isCutiSurat($surat);
        $hasSuratTugas = !empty($meta['surat_tugas']) || !empty($meta['has_surat_tugas']);
        $isNotaDinas = !$isCuti && empty($meta['surat_tugas']);

        $pdfPathRel = null;
        $pdfFilename = null;
        $gdriveFilename = null;
        if ($isNotaDinas) {
            $halDinas = trim($surat['perihal'] ?? '');
            if (empty($halDinas)) {
                $halDinas = 'Nota Dinas';
            }
            $halClean = preg_replace('/\.pdf$/i', '', $halDinas);
            $halClean = trim(preg_replace('/[\/\\:*?"<>|]/', ' ', $halClean));
            $halClean = preg_replace('/\s+/', ' ', $halClean);
            if (str_starts_with($halClean, '(') && str_ends_with($halClean, ')')) {
                $halClean = trim(substr($halClean, 1, -1));
            }
            if (empty($halClean)) {
                $halClean = 'Nota Dinas';
            }

            $nomorSurat = trim($surat['nomor_surat'] ?? $meta['nomor_surat'] ?? $meta['nomor_surat_disposisi'] ?? '');

            if (!empty($nomorSurat)) {
                // Format Google Drive KTU: "nomor surat hal"
                $gdriveFilename = $nomorSurat . ' ' . $halClean . '.pdf';
                // Format file lokal Windows: karakter ilegal path diganti '-' agar aman di filesystem dan web server
                $nomorSuratDisk = trim(preg_replace('/[\/\\:*?"<>|]/', '-', $nomorSurat));
                $nomorSuratDisk = preg_replace('/-+/', '-', $nomorSuratDisk);
                $pdfFilename = $nomorSuratDisk . ' ' . $halClean . '.pdf';
            } else {
                if (!preg_match('/^surat\b/i', $halClean)) {
                    $gdriveFilename = 'Surat ' . $halClean . '.pdf';
                } else {
                    $gdriveFilename = $halClean . '.pdf';
                }
                $pdfFilename = $gdriveFilename;
            }

            $pdfPathRel = '/uploads/pdf/' . $pdfFilename;
            $meta['pdf_path'] = $pdfPathRel;
        }

        $updateData = [
            'kpp_user_id' => $kppUserId,
            'kpp_signature' => $signaturePath,
            'kpp_signed_at' => date('Y-m-d H:i:s'),
            'meta_data' => $meta,
            'status' => 'kpp_signed'
        ];
        if (!empty($pdfPathRel)) {
            $updateData['pdf_path'] = $pdfPathRel;
        }

        $this->suratModel->updateSurat($id, $updateData);

        // Generate physical PDF document containing Surat Permohonan, Nota Dinas & Lampiran, lalu upload ke Google Drive KTU
        if ($isNotaDinas && class_exists('PdfGenerator') && !empty($pdfFilename)) {
            try {
                // 1. Generate physical PDF pertama
                $pdfFullPath = PdfGenerator::generateMergedPdf($id, false, $pdfFilename);

                // 2. Upload ke Google Drive KTU
                $ktuFolderId = Setting::get('gdrive_ktu_folder_id', '1m1SVmXboA73OFJKSK-BjfRtYXMdxlfm7');
                $suratObj = $this->suratModel->getById($id);
                $uploadName = !empty($gdriveFilename) ? $gdriveFilename : $pdfFilename;
                $gdriveResult = GoogleDriveService::uploadPdf($pdfFullPath, $uploadName, $suratObj['kode_surat'] ?? '', $ktuFolderId);

                $gdriveWebLink = '';
                $gdriveFileId = '';
                if (!empty($gdriveResult['success']) && !empty($gdriveResult['web_link'])) {
                    $gdriveWebLink = $gdriveResult['web_link'];
                    $gdriveFileId = $gdriveResult['file_id'] ?? '';
                    $meta['gdrive_file_id'] = $gdriveFileId;
                    $meta['gdrive_web_link'] = $gdriveWebLink;
                }

                // 3. Barcode mengarah ke Google Drive KTU
                $barcodeTargetLink = !empty($gdriveWebLink) 
                    ? $gdriveWebLink 
                    : ((defined('BASE_URL') ? rtrim(BASE_URL, '/') : '') . $pdfPathRel);

                if (function_exists('generateNotaDinasBarcode')) {
                    generateNotaDinasBarcode($id, $meta, $suratObj, $barcodeTargetLink);
                }

                $meta['pdf_path'] = $pdfPathRel;
                $meta['barcode_target_link'] = $barcodeTargetLink;

                $this->suratModel->updateSurat($id, [
                    'gdrive_file_id' => $gdriveFileId ?: null,
                    'gdrive_web_link' => $gdriveWebLink ?: null,
                    'pdf_path' => $pdfPathRel,
                    'meta_data' => $meta
                ]);

                // 4. Re-generate PDF agar barcode Google Drive tertempel pada PDF fisik
                $pdfFullPath = PdfGenerator::generateMergedPdf($id, false, $pdfFilename);
                if (!empty($gdriveFileId) && !str_starts_with($gdriveFileId, 'gdrive_')) {
                    GoogleDriveService::updatePdfContent($gdriveFileId, $pdfFullPath);
                }
            } catch (\Throwable $e) {
                error_log('Error generating & uploading Nota Dinas PDF on kppSubmit: ' . $e->getMessage());
            }
        }

        $actorName = $isKapelSdmUser ? 'Kapel Pengelola SDM & RB' : 'KPP Bidang';
        $logFilename = $gdriveFilename ?: ($pdfFilename ?: '');
        SuratLog::log($id, $currentUserId, 'Paraf Nota Dinas ' . $actorName . ' Dibubuhkan', 'kpp_signed', $actorName . ' telah membubuhkan Paraf Digital pada Surat Nota Dinas. File PDF "' . $logFilename . '" dan Barcode telah terunggah ke Google Drive KTU, surat diteruskan ke Tata Usaha.');
        ActivityLog::log('PROCESS_KPP', 'Membubuhkan Paraf ' . $actorName . ' pada surat perihal "' . ($surat['perihal'] ?? '') . '" dan mengunggah ke Google Drive KTU serta meneruskan ke Tata Usaha', $currentUserId);

        Session::setFlash('success', 'Paraf Surat Nota Dinas berhasil disimpan. Surat dikirim ke Tata Usaha.');
        $this->redirect('/dashboard');
    }

    // Tata Usaha: Process & Sign form
    public function tuProcess(int $id): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['tata_usaha', 'tu', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak. Fitur ini khusus Tata Usaha.');
            $this->redirect('/surat/detail/' . $id);
            return;
        }

        $surat = $this->suratModel->getById($id);
        if (!$surat) {
            Session::setFlash('error', 'Surat tidak ditemukan.');
            $this->redirect('/surat');
            return;
        }

        // Auto-generate barcode & PDF untuk Surat Nota Dinas / Surat Cuti jika sudah diteruskan ke TU namun barcode/PDF belum tergenerate
        $isCuti = Surat::isCutiSurat($surat);
        $meta = $surat['meta'] ?? [];
        if (empty($meta['surat_tugas'])) {
            $isKppSigned = !empty($meta['barcode_generated']) 
                || in_array(($surat['status'] ?? ''), ['submitted_to_sdm', 'kpp_signed', 'tu_signed', 'sekre_approved', 'completed', 'disposisi_completed'])
                || !empty($surat['kpp_signed_at'])
                || !empty($meta['kpp_signed_at']);

            if ($isKppSigned) {
                $halDinas = trim($surat['perihal'] ?? '');
                if (empty($halDinas)) {
                    $halDinas = $isCuti ? 'Permohonan Cuti' : 'Nota Dinas';
                }
                $halClean = preg_replace('/\.pdf$/i', '', $halDinas);
                $halClean = trim(preg_replace('/[\/\\:*?"<>|]/', ' ', $halClean));
                $halClean = preg_replace('/\s+/', ' ', $halClean);
                if (str_starts_with($halClean, '(') && str_ends_with($halClean, ')')) {
                    $halClean = trim(substr($halClean, 1, -1));
                }
                if (empty($halClean)) {
                    $halClean = $isCuti ? 'Permohonan Cuti' : 'Nota Dinas';
                }

                $isTuSigned = in_array(($surat['status'] ?? ''), ['tu_signed', 'sekre_approved', 'completed', 'disposisi_completed']) || !empty($surat['tu_signed_at']);
                $signSuffix = $isTuSigned ? ' sign' : '';

                $nomorSurat = trim($surat['nomor_surat'] ?? $meta['nomor_surat'] ?? $meta['nomor_surat_disposisi'] ?? '');
                if (!empty($nomorSurat)) {
                    $nomorSuratDisk = trim(preg_replace('/[\/\\:*?"<>|]/', '-', $nomorSurat));
                    $nomorSuratDisk = preg_replace('/-+/', '-', $nomorSuratDisk);
                    $pdfFilename = $nomorSuratDisk . ' ' . $halClean . $signSuffix . '.pdf';
                } elseif (!preg_match('/^surat\b/i', $halClean)) {
                    $pdfFilename = 'Surat ' . $halClean . $signSuffix . '.pdf';
                } else {
                    $pdfFilename = $halClean . $signSuffix . '.pdf';
                }
                $pdfPathRel = '/uploads/pdf/' . $pdfFilename;

                $pdfFileExists = file_exists(__DIR__ . '/../public' . $pdfPathRel) || file_exists(__DIR__ . '/..' . $pdfPathRel);
                $barcodeFileExists = !empty($meta['barcode_path']) && (file_exists(__DIR__ . '/../public' . $meta['barcode_path']) || file_exists(__DIR__ . '/..' . $meta['barcode_path']));
                $needsBarcode = empty($meta['barcode_generated']) || empty($meta['barcode_target_link']) || !$barcodeFileExists;

                $metaChanged = false;
                if ($needsBarcode && function_exists('generateNotaDinasBarcode')) {
                    $targetLink = !empty($meta['barcode_target_link']) 
                        ? $meta['barcode_target_link'] 
                        : (!empty($surat['gdrive_web_link']) 
                            ? $surat['gdrive_web_link'] 
                            : ((defined('BASE_URL') ? rtrim(BASE_URL, '/') : '') . $pdfPathRel));
                    generateNotaDinasBarcode($id, $meta, $surat, $targetLink);
                    $meta['pdf_path'] = $pdfPathRel;
                    $metaChanged = true;
                }

                if (!$pdfFileExists || $needsBarcode) {
                    if (class_exists('PdfGenerator')) {
                        try {
                            PdfGenerator::generateMergedPdf($id, false, $pdfFilename);
                            $meta['pdf_path'] = $pdfPathRel;
                            $metaChanged = true;
                        } catch (\Throwable $e) {
                            error_log('Error generating Nota Dinas PDF on tuProcess: ' . $e->getMessage());
                        }
                    }
                }

                if ($metaChanged || empty($surat['pdf_path']) || $surat['pdf_path'] !== $pdfPathRel) {
                    $this->suratModel->updateSurat($id, [
                        'meta_data' => $meta,
                        'pdf_path' => $pdfPathRel
                    ]);
                    $surat['meta'] = $meta;
                    $surat['pdf_path'] = $pdfPathRel;
                }
            }
        }

        $disposisi = $this->dispoModel->getBySuratId($id);

        $this->view('surat/tu_process', [
            'title' => (!empty($surat['meta']['surat_tugas']) ? 'Verifikasi & Paraf Surat Tugas - Tata Usaha' : 'TTD Nota Dinas Tata Usaha - Surat Digital'),
            'surat' => $surat,
            'disposisi' => $disposisi
        ]);
    }

    // Tata Usaha: Submit TTD Nota Dinas
    public function tuSubmit(int $id): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['tata_usaha', 'tu', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak.');
            $this->redirect('/dashboard');
            return;
        }

        $surat = $this->suratModel->getById($id);
        if (!$surat) {
            Session::setFlash('error', 'Surat tidak ditemukan.');
            $this->redirect('/dashboard');
            return;
        }

        $meta = $surat['meta'] ?? [];
        $tuUserId = (int) Session::get('user_id');

        // Check if this is the Surat Tugas workflow submission
        $isSuratTugasSubmit = !empty($_POST['is_surat_tugas_submit']) || (!empty($meta['surat_tugas']) && in_array($surat['status'], ['submitted_to_tu', 'kpp_signed']));
        if ($isSuratTugasSubmit) {
            $actionType = $_POST['action_type'] ?? 'submit';

            // Handle Revisi Surat Tugas oleh Tata Usaha -> Kembalikan ke Halaman Surat Tugas Admin
            if ($actionType === 'revisi') {
                $catatanRevisi = Security::sanitize($_POST['catatan_revisi'] ?? '');
                if (empty($catatanRevisi)) {
                    Session::setFlash('error', 'Catatan revisi wajib diisi jika memilih revisi.');
                    $this->redirect('/surat/tu-process/' . $id);
                    return;
                }

                if (!isset($meta['surat_tugas'])) $meta['surat_tugas'] = [];
                $meta['catatan_revisi'] = $catatanRevisi;
                $meta['catatan_revisi_st'] = $catatanRevisi;
                $meta['surat_tugas_revisi_by'] = 'Tata Usaha';
                $meta['surat_tugas_status'] = 'revisi';
                $meta['surat_tugas']['status'] = 'revisi';
                $meta['surat_tugas']['tu_paraf'] = '';

                $this->suratModel->updateSurat($id, [
                    'meta_data' => $meta,
                    'status' => 'completed'
                ]);

                SuratLog::log($id, $tuUserId, 'Permintaan Revisi Surat Tugas oleh Tata Usaha', 'revisi', 'Tata Usaha mengembalikan Surat Tugas ke Admin Pembuat Surat untuk direvisi. Catatan: ' . $catatanRevisi);
                ActivityLog::log('REVISI_SURAT_TUGAS_TU', 'Tata Usaha meminta revisi Surat Tugas perihal "' . ($surat['perihal'] ?? '') . '" ke Admin (Catatan: ' . $catatanRevisi . ')', $tuUserId);

                Session::setFlash('warning', 'Surat Tugas berhasil dikembalikan ke Admin Pembuat Surat untuk direvisi.');
                $this->redirect('/dashboard');
                return;
            }

            $parafData = $_POST['tu_paraf_data'] ?? '';
            $existingParaf = $meta['surat_tugas']['tu_paraf'] ?? '';

            if (empty($parafData) && empty($existingParaf)) {
                Session::setFlash('error', 'Silakan bubuhkan paraf Tata Usaha di samping nama Kepala Stasiun terlebih dahulu.');
                $this->redirect('/surat/tu-process/' . $id);
                return;
            }

            $parafPath = $existingParaf;
            if (!empty($parafData) && str_contains($parafData, 'data:image')) {
                $data = explode(',', $parafData);
                $decoded = base64_decode($data[1]);
                $uploadDir = __DIR__ . '/../public/uploads/signatures';
                $uploadDirRoot = __DIR__ . '/../uploads/signatures';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                if (!file_exists($uploadDirRoot)) mkdir($uploadDirRoot, 0777, true);
                $filename = 'paraf_tu_st_' . $id . '_' . time() . '.png';
                file_put_contents($uploadDir . '/' . $filename, $decoded);
                file_put_contents($uploadDirRoot . '/' . $filename, $decoded);
                $parafPath = '/uploads/signatures/' . $filename;
            } elseif (!empty($parafData)) {
                $parafPath = $parafData;
            }

            if (!isset($meta['surat_tugas'])) $meta['surat_tugas'] = [];
            $meta['surat_tugas']['tu_paraf'] = $parafPath;
            $meta['surat_tugas_status'] = 'tu_signed';

            $this->suratModel->updateSurat($id, [
                'meta_data' => $meta,
                'status' => 'sekre_approved'
            ]);

            SuratLog::log($id, $tuUserId, 'Paraf Surat Tugas oleh Tata Usaha', 'sekre_approved', 'Tata Usaha telah membubuhkan paraf pada Surat Tugas dan meneruskannya ke Kepala Stasiun.');
            ActivityLog::log('PARAF_SURAT_TUGAS_TU', 'Memaraf Surat Tugas perihal "' . ($surat['perihal'] ?? '') . '" dan meneruskan ke Kepala Stasiun', $tuUserId);

            Session::setFlash('success', 'Surat Tugas berhasil diparaf dan diteruskan ke Kepala Stasiun!');
            $this->redirect('/dashboard');
            return;
        }

        $actionType = $_POST['action_type'] ?? 'submit';

        if ($actionType === 'revisi') {
            $catatanRevisi = Security::sanitize($_POST['catatan_revisi'] ?? '');
            if (empty($catatanRevisi)) {
                Session::setFlash('error', 'Catatan revisi wajib diisi jika memilih revisi.');
                $this->redirect('/surat/tu-process/' . $id);
                return;
            }

            $meta = $surat['meta'] ?? [];
            $meta['catatan_revisi'] = $catatanRevisi;

            $this->suratModel->updateSurat($id, [
                'status' => 'revisi',
                'meta_data' => $meta
            ]);

            SuratLog::log($id, $tuUserId, 'Permintaan Revisi oleh Tata Usaha', 'revisi', 'Tata Usaha (TU) mengembalikan surat ke Admin Pembuat Surat untuk direvisi. Catatan: ' . $catatanRevisi);
            ActivityLog::log('PROCESS_TU_REVISI', 'Meminta revisi surat perihal "' . ($surat['perihal'] ?? '') . '" (Catatan: ' . $catatanRevisi . ')', $tuUserId);

            Session::setFlash('warning', 'Surat berhasil dikembalikan ke Admin Pembuat Surat untuk direvisi.');
            $this->redirect('/dashboard');
            return;
        }

        $nomorSurat = Security::sanitize($_POST['nomor_surat'] ?? $surat['nomor_surat'] ?? '');
        $signatureData = $_POST['tu_signature_data'] ?? '';
        $existingTuSig = (!empty($surat['tu_signature']) && $surat['tu_signature'] !== '/assets/img/ttd_tu_stamp.png') ? $surat['tu_signature'] : '';

        $meta = $surat['meta'] ?? [];
        $tuTtePdfPath = $meta['tu_tte_pdf_path'] ?? '';
        $isTuTteUploaded = false;

        // Handle uploaded TTE PDF from User TU if provided
        if (isset($_FILES['tu_tte_pdf']) && $_FILES['tu_tte_pdf']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['tu_tte_pdf']['tmp_name'];
            $fileName = $_FILES['tu_tte_pdf']['name'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if ($fileExt === 'pdf') {
                $uploadDirPdf = __DIR__ . '/../public/uploads/pdf';
                $uploadDirPdfRoot = __DIR__ . '/../uploads/pdf';
                if (!file_exists($uploadDirPdf)) mkdir($uploadDirPdf, 0777, true);
                if (!file_exists($uploadDirPdfRoot)) mkdir($uploadDirPdfRoot, 0777, true);
                $newPdfName = 'tte_tu_' . $id . '_' . time() . '_' . mt_rand(1000, 9999) . '.pdf';
                move_uploaded_file($tmpPath, $uploadDirPdf . '/' . $newPdfName);
                @copy($uploadDirPdf . '/' . $newPdfName, $uploadDirPdfRoot . '/' . $newPdfName);
                $tuTtePdfPath = '/uploads/pdf/' . $newPdfName;
                $isTuTteUploaded = true;
            }
        } elseif (!empty($_POST['has_tu_tte_uploaded']) && $_POST['has_tu_tte_uploaded'] === '1' && !empty($meta['tu_tte_pdf_path'])) {
            $tuTtePdfPath = $meta['tu_tte_pdf_path'];
            $isTuTteUploaded = true;
        }

        if (!$isTuTteUploaded && empty($signatureData) && empty($existingTuSig)) {
            Session::setFlash('error', 'Silakan masukkan Tanda Tangan Digital (TTD) Tata Usaha atau Upload Surat TTE terlebih dahulu.');
            $this->redirect('/surat/tu-process/' . $id);
            return;
        }

        $tuUserId = (int) Session::get('user_id');
        $signaturePath = $existingTuSig;

        // Save canvas drawing for TTD TU on Nota Dinas if provided
        if (!empty($signatureData) && str_contains($signatureData, 'data:image')) {
            $data = explode(',', $signatureData);
            $decoded = base64_decode($data[1]);
            $uploadDir = __DIR__ . '/../public/uploads/signatures';
            $uploadDirRoot = __DIR__ . '/../uploads/signatures';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            if (!file_exists($uploadDirRoot)) mkdir($uploadDirRoot, 0777, true);
            $filename = 'ttd_tu_' . $id . '_' . time() . '.png';
            file_put_contents($uploadDir . '/' . $filename, $decoded);
            file_put_contents($uploadDirRoot . '/' . $filename, $decoded);
            $signaturePath = '/uploads/signatures/' . $filename;
        } elseif (!empty($signatureData) && !str_contains($signatureData, 'data:image')) {
            $signaturePath = $signatureData;
        }

        $isSuratCuti = Surat::isCutiSurat($surat);
        if ($isSuratCuti) {
            unset($meta['disposisi_completed'], $meta['disposisi_forwarded_to_recipients']);
        }

        if ($isTuTteUploaded) {
            $meta['tu_tte_pdf_path'] = $tuTtePdfPath;
            $meta['is_tu_tte_uploaded'] = true;
        } else {
            $meta['is_tu_tte_uploaded'] = false;
        }

        $updateData = [
            'nomor_surat' => $nomorSurat,
            'tu_user_id' => $tuUserId,
            'tu_signature' => $signaturePath ?: null,
            'tu_signed_at' => date('Y-m-d H:i:s'),
            'meta_data' => $meta,
            'status' => 'tu_signed'
        ];

        if ($isTuTteUploaded) {
            $updateData['pdf_path'] = $tuTtePdfPath;
        }

        $this->suratModel->updateSurat($id, $updateData);

        // Generate PDF Nota Dinas & Upload ke Google Drive KTU
        $gdriveSuccess = false;
        $gdriveErrMsg = '';
        try {
            $suratObj = $this->suratModel->getById($id);

            // Format nama file yang diunggah ke Google Drive KTU: "nomor surat (hal) sign"
            $halDinas = trim($suratObj['perihal'] ?? $surat['perihal'] ?? '');
            if (empty($halDinas)) {
                $halDinas = $isSuratCuti ? 'Permohonan Cuti' : 'Nota Dinas';
            }
            $halClean = preg_replace('/\.pdf$/i', '', $halDinas);
            $halClean = trim(preg_replace('/[\/\\:*?"<>|]/', ' ', $halClean));
            $halClean = preg_replace('/\s+/', ' ', $halClean);
            if (str_starts_with($halClean, '(') && str_ends_with($halClean, ')')) {
                $halClean = trim(substr($halClean, 1, -1));
            }
            if (empty($halClean)) {
                $halClean = $isSuratCuti ? 'Permohonan Cuti' : 'Nota Dinas';
            }

            $nomorSuratVal = trim($suratObj['nomor_surat'] ?? $surat['nomor_surat'] ?? $meta['nomor_surat'] ?? $meta['nomor_surat_disposisi'] ?? $nomorSurat ?? '');

            if (!empty($nomorSuratVal)) {
                // Format Google Drive KTU: "nomor surat hal sign"
                $filename = $nomorSuratVal . ' ' . $halClean . ' sign.pdf';
                // Format file lokal Windows: karakter ilegal path diganti '-' agar aman di filesystem dan web server
                $nomorSuratDisk = trim(preg_replace('/[\/\\:*?"<>|]/', '-', $nomorSuratVal));
                $nomorSuratDisk = preg_replace('/-+/', '-', $nomorSuratDisk);
                $pdfFilenameDisk = $nomorSuratDisk . ' ' . $halClean . ' sign.pdf';
            } else {
                if (!preg_match('/^surat\b/i', $halClean)) {
                    $filename = 'Surat ' . $halClean . ' sign.pdf';
                } else {
                    $filename = $halClean . ' sign.pdf';
                }
                $pdfFilenameDisk = $filename;
            }

            if ($isTuTteUploaded && !empty($tuTtePdfPath)) {
                $cleanPath = ltrim($tuTtePdfPath, '/');
                $pdfFullPath = __DIR__ . '/../public/' . $cleanPath;
                if (!file_exists($pdfFullPath)) {
                    $pdfFullPath = __DIR__ . '/../' . $cleanPath;
                }
            } else {
                $pdfFullPath = PdfGenerator::generateMergedPdf($id, false, $pdfFilenameDisk);
                $this->suratModel->updateSurat($id, ['pdf_path' => '/uploads/pdf/' . basename($pdfFullPath)]);
            }

            $ktuFolderId = Setting::get('gdrive_ktu_folder_id', '1m1SVmXboA73OFJKSK-BjfRtYXMdxlfm7');

            $gdriveResult = GoogleDriveService::uploadPdf($pdfFullPath, $filename, $suratObj['kode_surat'], $ktuFolderId);

            $gdriveWebLink = '';
            $gdriveFileId = '';
            if (!empty($gdriveResult['success'])) {
                $gdriveWebLink = $gdriveResult['web_link'] ?? '';
                $gdriveFileId = $gdriveResult['file_id'] ?? '';
                $meta['gdrive_file_id'] = $gdriveFileId;
                $meta['gdrive_web_link'] = $gdriveWebLink;
                $gdriveSuccess = true;
            } else {
                $gdriveErrMsg = $gdriveResult['message'] ?? '';
            }

            // Update barcode_target_link dan regenerate QR barcode agar barcode mengarah ke file PDF yang sudah ada tanda sign TU
            $barcodeTargetLink = !empty($gdriveWebLink) 
                ? $gdriveWebLink 
                : ((defined('BASE_URL') ? rtrim(BASE_URL, '/') : '') . '/uploads/pdf/' . basename($pdfFullPath));

            if (function_exists('generateNotaDinasBarcode')) {
                generateNotaDinasBarcode($id, $meta, $suratObj, $barcodeTargetLink);
            }

            $meta['barcode_target_link'] = $barcodeTargetLink;
            $meta['pdf_path'] = '/uploads/pdf/' . basename($pdfFullPath);

            $this->suratModel->updateSurat($id, [
                'gdrive_file_id' => $gdriveFileId ?: null,
                'gdrive_web_link' => $gdriveWebLink ?: null,
                'pdf_path' => '/uploads/pdf/' . basename($pdfFullPath),
                'meta_data' => $meta
            ]);

            // Re-generate PDF sekarang setelah gdrive_web_link dan barcode tersimpan di database dan meta,
            // agar QR Barcode pada PDF langsung tergenerate mengarah ke file Google Drive KTU / PDF lokal bertanda sign
            $pdfFullPath = PdfGenerator::generateMergedPdf($id, false, $pdfFilenameDisk);
            $meta['pdf_path'] = '/uploads/pdf/' . basename($pdfFullPath);
            $this->suratModel->updateSurat($id, [
                'pdf_path' => '/uploads/pdf/' . basename($pdfFullPath),
                'meta_data' => $meta
            ]);

            if (!empty($gdriveFileId) && !str_starts_with($gdriveFileId, 'gdrive_')) {
                GoogleDriveService::updatePdfContent($gdriveFileId, $pdfFullPath);
            }
        } catch (Throwable $e) {
            $gdriveErrMsg = $e->getMessage();
        }

        $jenisSuratLabel = $isSuratCuti ? 'Surat Cuti' : 'Surat Nota Dinas';
        $logDetails = $isTuTteUploaded 
            ? 'Tata Usaha mengunggah Surat TTE (PDF) dan meneruskan ke Sekretaris. File "' . ($filename ?: '') . '" terunggah ke Google Drive KTU.'
            : 'Tata Usaha telah membubuhkan TTD Digital. File PDF "' . ($filename ?: '') . '" terunggah ke Google Drive KTU dan surat diteruskan ke Sekretaris.';

        SuratLog::log($id, $tuUserId, 'Tata Usaha Memproses Surat ke Sekretaris', 'tu_signed', $logDetails);
        ActivityLog::log('PROCESS_TU', 'Membubuhkan TTD / Upload Surat TTE TU pada surat perihal "' . ($surat['perihal'] ?? '') . '"', $tuUserId);

        if ($gdriveSuccess) {
            Session::setFlash('success', "{$jenisSuratLabel} berhasil ditandatangani dan diteruskan ke Sekretaris! Dokumen PDF \"" . ($filename ?: '') . '" telah tergenerate dan terunggah ke Google Drive KTU.');
        } else {
            $warnText = !empty($gdriveErrMsg) ? " ({$gdriveErrMsg})" : '';
            Session::setFlash('warning', "{$jenisSuratLabel} berhasil diproses dan diteruskan ke Sekretaris! Dokumen PDF \"" . ($filename ?: '') . '" telah tergenerate' . ($warnText ? ' (upload Drive KTU: ' . $warnText . ')' : '.'));
        }

        $this->redirect('/dashboard');
        return;
    }

    // Sekretaris: Approve / Revisi & Disposisi Form
    public function sekreProcess(int $id): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['sekretaris', 'sekre', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak. Fitur ini khusus Sekretaris.');
            $this->redirect('/surat/detail/' . $id);
            return;
        }

        $surat = $this->suratModel->getById($id);
        $disposisi = $this->dispoModel->getBySuratId($id);

        // Pastikan barcode mengarah ke file PDF yang sudah ada keterangan sign (upload TU)
        $meta = $surat['meta'] ?? [];
        $isTuSigned = in_array(($surat['status'] ?? ''), ['tu_signed', 'sekre_approved', 'kepsta_approved', 'completed', 'disposisi_completed']) || !empty($surat['tu_signed_at']);
        $gdriveLink = $surat['gdrive_web_link'] ?? $meta['gdrive_web_link'] ?? '';
        $isRealGdrive = !empty($gdriveLink) && !str_contains($gdriveLink, '/folders/') && $gdriveLink !== 'https://drive.google.com';

        if ($isTuSigned && $isRealGdrive && (($meta['barcode_target_link'] ?? '') !== $gdriveLink)) {
            if (function_exists('generateNotaDinasBarcode')) {
                generateNotaDinasBarcode($id, $meta, $surat, $gdriveLink);
                $meta['barcode_target_link'] = $gdriveLink;
                $this->suratModel->updateSurat($id, ['meta_data' => $meta]);
                $surat['meta'] = $meta;
                $surat['meta_data'] = $meta;
            }
        }

        $this->view('surat/sekre_process', [
            'title' => (!empty($surat['meta']['surat_tugas']) ? 'Review & Teruskan Surat Tugas - Sekretaris' : 'Review & Buat Disposisi - Sekretaris'),
            'surat' => $surat,
            'disposisi' => $disposisi
        ]);
    }

    // Sekretaris: Submit Approve or Revisi
    public function sekreSubmit(int $id): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['sekretaris', 'sekre', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak.');
            $this->redirect('/dashboard');
            return;
        }

        $surat = $this->suratModel->getById($id);
        if (!$surat) {
            Session::setFlash('error', 'Surat tidak ditemukan.');
            $this->redirect('/dashboard');
            return;
        }

        $meta = $surat['meta'] ?? [];
        $sekreUserId = (int) Session::get('user_id');

        // Check if this is the Surat Tugas workflow submission
        $isSuratTugasSubmit = !empty($_POST['is_surat_tugas_submit']) || (!empty($meta['surat_tugas']) && in_array($surat['status'], ['kepsta_approved', 'tu_signed']));
        if ($isSuratTugasSubmit) {
            $actionType = $_POST['action_type'] ?? 'complete';

            // Handle Revisi Surat Tugas oleh Sekretaris -> Kembalikan ke Halaman Surat Tugas Admin
            if ($actionType === 'revisi') {
                $catatanRevisi = Security::sanitize($_POST['catatan_revisi'] ?? '');
                if (empty($catatanRevisi)) {
                    Session::setFlash('error', 'Catatan revisi wajib diisi jika memilih revisi.');
                    $this->redirect('/surat/sekre-process/' . $id);
                    return;
                }

                if (!isset($meta['surat_tugas'])) $meta['surat_tugas'] = [];
                $meta['catatan_revisi'] = $catatanRevisi;
                $meta['catatan_revisi_st'] = $catatanRevisi;
                $meta['surat_tugas_revisi_by'] = 'Sekretaris';
                $meta['surat_tugas_status'] = 'revisi';
                $meta['surat_tugas']['status'] = 'revisi';
                $meta['surat_tugas']['tu_paraf'] = ''; // Reset paraf agar TU memparaf ulang setelah admin merevisi
                $meta['surat_tugas']['kepsta_signature'] = ''; // Reset tanda tangan kepsta

                $this->suratModel->updateSurat($id, [
                    'meta_data' => $meta,
                    'status' => 'completed' // Kembalikan ke completed agar muncul di halaman Surat Tugas Admin (getSuratTugasForAdmin)
                ]);

                SuratLog::log($id, $sekreUserId, 'Permintaan Revisi Surat Tugas oleh Sekretaris', 'revisi', 'Sekretaris mengembalikan Surat Tugas ke Admin Pembuat Surat untuk direvisi. Catatan: ' . $catatanRevisi);
                ActivityLog::log('REVISI_SURAT_TUGAS_SEKRE', 'Sekretaris meminta revisi Surat Tugas perihal "' . ($surat['perihal'] ?? '') . '" ke Admin (Catatan: ' . $catatanRevisi . ')', $sekreUserId);

                Session::setFlash('warning', 'Surat Tugas berhasil dikembalikan ke Admin Pembuat Surat untuk direvisi.');
                $this->redirect('/dashboard');
                return;
            }

            if (!isset($meta['surat_tugas'])) $meta['surat_tugas'] = [];
            $meta['surat_tugas_status'] = 'completed';
            $meta['surat_tugas']['status'] = 'completed';

            // Handle upload stempel oleh Sekretaris pada Surat Tugas
            $stempelData = trim($_POST['sekre_stempel_data'] ?? '');
            if (!empty($stempelData) && str_contains($stempelData, 'data:image')) {
                $data = explode(',', $stempelData);
                $decoded = base64_decode($data[1]);
                $uploadDir = __DIR__ . '/../public/uploads/signatures';
                $uploadDirRoot = __DIR__ . '/../uploads/signatures';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                if (!file_exists($uploadDirRoot)) mkdir($uploadDirRoot, 0777, true);
                $filename = 'stempel_st_' . $id . '_' . time() . '.png';
                file_put_contents($uploadDir . '/' . $filename, $decoded);
                file_put_contents($uploadDirRoot . '/' . $filename, $decoded);
                $meta['surat_tugas']['stempel'] = '/uploads/signatures/' . $filename;
                $meta['surat_tugas']['stempel_uploaded_at'] = date('Y-m-d H:i:s');
            } elseif (!empty($stempelData)) {
                $meta['surat_tugas']['stempel'] = $stempelData;
            }

            $meta['disposisi_completed'] = true;
            $meta['disposisi_forwarded_to_recipients'] = true;
            $meta['disposisi_forwarded_to_admin'] = true;

            $this->suratModel->updateSurat($id, [
                'meta_data' => $meta,
                'status' => 'completed'
            ]);

            SuratLog::log($id, $sekreUserId, 'Surat Tugas Selesai & Diteruskan ke Disposisi', 'completed', 'Sekretaris telah memeriksa Surat Tugas. Surat Tugas selesai dan diteruskan ke Lembar Disposisi penerima dan admin pembuat surat.');
            ActivityLog::log('COMPLETE_SURAT_TUGAS_SEKRE', 'Memeriksa dan menyelesaikan Surat Tugas perihal "' . ($surat['perihal'] ?? '') . '" ke Disposisi penerima dan admin', $sekreUserId);

            // Generate Merged PDF & Upload to Google Drive & Sync to Rekap Disposisi Excel
            $gdriveSuccess = false;
            $gdriveErrMsg = '';
            try {
                $pdfFullPath = PdfGenerator::generateMergedPdf($id, true);
                $kepstaFolderId = Setting::get('gdrive_kepsta_folder_id', '1q43jnW7Tw0ghLY7rrx-OPI3aTQAronrB');
                $suratObj = $this->suratModel->getById($id);
                $disposisiObj = $this->dispoModel->getBySuratId($id);

                // Format nama file yang diunggah ke Google Drive: "surat tugas hal"
                $halDisposisi = trim($disposisiObj['hal'] ?? $meta['hal_disposisi'] ?? $suratObj['perihal'] ?? '');
                $halClean = preg_replace('/\.pdf$/i', '', $halDisposisi);
                $halClean = trim(preg_replace('/[\/\\:*?"<>|]/', ' ', $halClean));
                $halClean = preg_replace('/\s+/', ' ', $halClean);

                if (empty($halClean) || strtolower($halClean) === 'disposisi') {
                    $filename = 'Surat Tugas.pdf';
                } elseif (preg_match('/^surat\s+tugas\b/i', $halClean)) {
                    $filename = $halClean . '.pdf';
                } else {
                    $halWithoutSurat = preg_replace('/^surat\s+/i', '', $halClean);
                    $filename = 'Surat Tugas ' . $halWithoutSurat . '.pdf';
                }

                $gdriveResult = GoogleDriveService::uploadPdf($pdfFullPath, $filename, $suratObj['kode_surat'], $kepstaFolderId);

                if (!empty($gdriveResult['success'])) {
                    $meta['gdrive_file_id'] = $gdriveResult['file_id'];
                    $meta['gdrive_web_link'] = $gdriveResult['web_link'];
                    if (function_exists('generateNotaDinasBarcode')) {
                        generateNotaDinasBarcode($id, $meta, $suratObj, $gdriveResult['web_link']);
                    }
                    $this->suratModel->updateSurat($id, [
                        'gdrive_file_id' => $gdriveResult['file_id'],
                        'gdrive_web_link' => $gdriveResult['web_link'],
                        'pdf_path' => '/uploads/pdf/' . basename($pdfFullPath),
                        'meta_data' => $meta
                    ]);
                    $gdriveSuccess = true;

                    // Re-generate Merged PDF now that gdrive_web_link is set in database and meta,
                    // so the QR Barcode in the PDF points directly to the Google Drive file (matching Excel Link File)
                    $pdfFullPath = PdfGenerator::generateMergedPdf($id, true);
                    if (!empty($gdriveResult['file_id']) && !str_starts_with($gdriveResult['file_id'], 'gdrive_')) {
                        GoogleDriveService::updatePdfContent($gdriveResult['file_id'], $pdfFullPath);
                    }
                } else {
                    $gdriveErrMsg = $gdriveResult['message'] ?? '';
                }
            } catch (Throwable $e) {
                $gdriveErrMsg = $e->getMessage();
            }

            // Sync Rekap Disposisi Excel bulanan
            try {
                DisposisiExcelService::syncSuratToExcel($id);
            } catch (Throwable $e) {
                error_log("Disposisi Excel error: " . $e->getMessage());
            }

            if ($gdriveSuccess) {
                Session::setFlash('success', 'Surat Tugas berhasil diperiksa dan diselesaikan! Surat telah diteruskan ke halaman disposisi penerima dan admin, PDF Gabungan terunggah ke Google Drive (' . $filename . ') & data tercatat di Rekap Excel.');
            } else {
                $warnText = !empty($gdriveErrMsg) ? " ({$gdriveErrMsg})" : '';
                Session::setFlash('warning', 'Surat Tugas berhasil diperiksa dan diselesaikan! Surat telah diteruskan ke halaman disposisi penerima dan admin, namun PDF Gabungan belum terunggah ke Google Drive' . $warnText);
            }

            $this->redirect('/dashboard');
            return;
        }

        $actionType = $_POST['action_type'] ?? 'approve';

        // Handle final checking & forwarding from Kepsta by Sekretaris
        if ($surat['status'] === 'kepsta_approved') {
            if ($actionType === 'revisi') {
                $catatanRevisi = Security::sanitize($_POST['catatan_revisi'] ?? '');
                if (empty($catatanRevisi)) {
                    Session::setFlash('error', 'Catatan revisi wajib diisi jika memilih revisi.');
                    $this->redirect('/surat/sekre-process/' . $id);
                    return;
                }

                $meta['catatan_revisi'] = $catatanRevisi;
                unset($meta['sifat_disposisi'], $meta['sifat_surat_disposisi'], $meta['kepsta_signature'], $meta['kepsta_signed_at']);

                $this->dispoModel->resetKepstaDisposisi($id);

                $this->suratModel->updateSurat($id, [
                    'status' => 'sekre_approved',
                    'meta_data' => $meta
                ]);

                SuratLog::log($id, $sekreUserId, 'Permintaan Revisi Disposisi oleh Sekretaris', 'sekre_approved', 'Sekretaris mengembalikan Lembar Disposisi ke Kepala Stasiun untuk direvisi. Catatan: ' . $catatanRevisi);
                ActivityLog::log('PROCESS_SEKRE_REVISI_KEPSTA', 'Sekretaris meminta revisi disposisi ke Kepala Stasiun untuk surat perihal "' . ($surat['perihal'] ?? '') . '"', $sekreUserId);

                Session::setFlash('warning', 'Surat dan Lembar Disposisi berhasil dikembalikan ke Kepala Stasiun untuk direvisi.');
                $this->redirect('/dashboard');
                return;
            }

            // Khusus Surat Berjenis UPLOAD LAMPIRAN:
            $isFromUploadLampiran = !empty($meta['from_upload_lampiran']) || (($surat['type'] ?? '') === 'upload_lampiran');
            if ($isFromUploadLampiran) {
                $meta = $surat['meta'] ?? [];
                $meta['disposisi_completed'] = true;
                $meta['disposisi_forwarded_to_recipients'] = true;

                $this->suratModel->updateSurat($id, [
                    'status' => 'completed',
                    'meta_data' => $meta
                ]);

                // Generate Merged PDF (Lembar Disposisi + Berkas Lampiran PDF) & Upload to Google Drive Kepsta & Sync to Rekap Disposisi Excel
                $gdriveSuccess = false;
                $gdriveErrMsg = '';
                $filename = '';
                try {
                    $pdfFullPath = PdfGenerator::generateMergedPdf($id, true);
                    $kepstaFolderId = Setting::get('gdrive_kepsta_folder_id', '1q43jnW7Tw0ghLY7rrx-OPI3aTQAronrB');
                    $suratObj = $this->suratModel->getById($id);
                    $disposisiObj = $this->dispoModel->getBySuratId($id);

                    // Format nama file yang diunggah ke Google Drive: "Surat Luar {hal di disposisi}" (tanpa tanda kurung)
                    $halDisposisi = trim($disposisiObj['hal'] ?? $meta['hal_disposisi'] ?? $suratObj['perihal'] ?? '');
                    if (empty($halDisposisi)) {
                        $halDisposisi = 'Disposisi';
                    }
                    $halClean = preg_replace('/\.pdf$/i', '', $halDisposisi);
                    $halClean = trim(preg_replace('/[\/\\:*?"<>|]/', ' ', $halClean));
                    $halClean = trim($halClean, "() \t\n\r\0\x0B");
                    $halClean = preg_replace('/\s+/', ' ', $halClean);
                    if (empty($halClean)) {
                        $halClean = 'Disposisi';
                    }
                    $filename = 'Surat Luar ' . $halClean . '.pdf';

                    $gdriveResult = GoogleDriveService::uploadPdf($pdfFullPath, $filename, $suratObj['kode_surat'], $kepstaFolderId);

                    if (!empty($gdriveResult['success'])) {
                        $meta['gdrive_file_id'] = $gdriveResult['file_id'];
                        $meta['gdrive_web_link'] = $gdriveResult['web_link'];

                        // Re-generate barcode agar barcode pada Surat Nota Dinas mengarah langsung ke file PDF di Google Drive ini (bukan lagi ke Google Drive KTU)
                        if (function_exists('generateNotaDinasBarcode')) {
                            generateNotaDinasBarcode($id, $meta, $suratObj, $gdriveResult['web_link']);
                        }

                        $this->suratModel->updateSurat($id, [
                            'gdrive_file_id' => $gdriveResult['file_id'],
                            'gdrive_web_link' => $gdriveResult['web_link'],
                            'pdf_path' => '/uploads/pdf/' . basename($pdfFullPath),
                            'meta_data' => $meta
                        ]);
                        $gdriveSuccess = true;

                        // Re-generate Merged PDF now that barcode and gdrive_web_link are updated,
                        // so the QR Barcode in the PDF physically points to the Google Drive PDF file
                        $pdfFullPath = PdfGenerator::generateMergedPdf($id, true);
                        if (!empty($gdriveResult['file_id']) && !str_starts_with($gdriveResult['file_id'], 'gdrive_')) {
                            GoogleDriveService::updatePdfContent($gdriveResult['file_id'], $pdfFullPath);
                        }
                    } else {
                        $gdriveErrMsg = $gdriveResult['message'] ?? '';
                    }
                } catch (Throwable $e) {
                    $gdriveErrMsg = $e->getMessage();
                }

                // Sync Rekap Disposisi Excel bulanan
                try {
                    DisposisiExcelService::syncSuratToExcel($id);
                } catch (Throwable $e) {
                    error_log("Disposisi Excel error: " . $e->getMessage());
                }

                SuratLog::log($id, $sekreUserId, 'Disposisi Surat Luar Selesai & Diteruskan', 'completed', 'Sekretaris telah selesai melakukan pengecekan lembar disposisi dari Kepala Stasiun. Berkas PDF final telah diunggah ke Google Drive dengan nama "' . $filename . '", dicatat di Excel Disposisi, dan diteruskan ke penerima disposisi.');
                ActivityLog::log('PROCESS_SEKRE_COMPLETE_UPLOAD_LAMPIRAN', 'Sekretaris menyelesaikan & meneruskan lembar disposisi Surat Luar perihal "' . ($surat['perihal'] ?? $halClean) . '"', $sekreUserId);

                if ($gdriveSuccess) {
                    Session::setFlash('success', 'Surat Luar berhasil diselesaikan dan diteruskan! PDF terunggah ke Google Drive (' . $filename . '), tercatat di Rekap Excel, dan terdistribusi ke penerima disposisi.');
                } else {
                    $warnText = !empty($gdriveErrMsg) ? " ({$gdriveErrMsg})" : '';
                    Session::setFlash('warning', 'Surat Luar berhasil diselesaikan dan diteruskan! Tercatat di Rekap Excel dan masuk ke halaman disposisi penerima, namun berkas PDF belum terunggah ke Google Drive' . $warnText);
                }

                $this->redirect('/dashboard');
                return;
            }

            // Khusus Surat Berjenis NOTA DINAS & SURAT CUTI:
            // Surat langsung final (generate merged PDF disposisi + surat cuti / nota dinas, upload ke Google Drive, catat ke Rekap Excel, dan terdistribusi ke penerima disposisi)
            $katSurat = $meta['kategori_surat'] ?? '';
            $isSuratCuti = (class_exists('Surat') && Surat::isCutiSurat($surat))
                || ($katSurat === 'Surat Cuti'
                || ($surat['type'] ?? '') === 'cuti'
                || ($surat['type'] ?? '') === 'surat-cuti'
                || str_contains(strtolower($katSurat), 'cuti')
                || str_contains(strtolower($surat['perihal'] ?? ''), 'cuti')
                || (isset($meta['isi_surat_dinas']) && str_contains(strtolower($meta['isi_surat_dinas']), 'cuti'))
                || (isset($surat['isi_surat']) && str_contains(strtolower($surat['isi_surat']), 'cuti'))
                || (!empty($surat['kpp_user_id']) && (int)$surat['kpp_user_id'] === 56));

            $hasSuratTugas = !$isSuratCuti && (
                isset($meta['has_surat_tugas'])
                    ? (!empty($meta['has_surat_tugas']) && (string)$meta['has_surat_tugas'] !== '0')
                    : (!empty($meta['surat_tugas']) || (!$isFromUploadLampiran && ($surat['type'] ?? '') !== 'nota_dinas'))
            );

            $isNotaDinas = !$isFromUploadLampiran && (
                $isSuratCuti || 
                !$hasSuratTugas ||
                in_array(($surat['type'] ?? ''), ['nota_dinas', 'izin_produksi']) || 
                in_array(($meta['kategori_surat'] ?? ''), ['Surat Nota Dinas', 'Surat Cuti', 'Surat Permohonan & Dinas'])
            );
            if ($isNotaDinas) {
                $meta = $surat['meta'] ?? [];
                $meta['disposisi_completed'] = true;
                $meta['disposisi_forwarded_to_recipients'] = true;
                $meta['disposisi_forwarded_to_admin'] = true;
                if ($isSuratCuti) {
                    unset($meta['has_surat_tugas'], $meta['surat_tugas']);
                }

                $this->suratModel->updateSurat($id, [
                    'status' => 'completed',
                    'meta_data' => $meta
                ]);

                // Generate Merged PDF (Disposisi + Surat Cuti / Nota Dinas + Lampiran) & Upload to Google Drive Kepsta & Sync to Rekap Disposisi Excel
                $gdriveSuccess = false;
                $gdriveErrMsg = '';
                $filename = '';
                try {
                    $pdfFullPath = PdfGenerator::generateMergedPdf($id, true);
                    $this->suratModel->updateSurat($id, ['pdf_path' => '/uploads/pdf/' . basename($pdfFullPath)]);

                    $kepstaFolderId = Setting::get('gdrive_kepsta_folder_id', '1q43jnW7Tw0ghLY7rrx-OPI3aTQAronrB');
                    $suratObj = $this->suratModel->getById($id);
                    $disposisiObj = $this->dispoModel->getBySuratId($id);

                    // Format nama file yang diunggah ke Google Drive: "Surat (hal dari surat disposisi)"
                    $halDisposisi = trim($disposisiObj['hal'] ?? $meta['hal_disposisi'] ?? $suratObj['perihal'] ?? '');
                    if (empty($halDisposisi)) {
                        $halDisposisi = $isSuratCuti ? 'Permohonan Cuti Tahunan' : 'Disposisi';
                    }
                    $halClean = preg_replace('/\.pdf$/i', '', $halDisposisi);
                    $halClean = trim(preg_replace('/[\/\\:*?"<>|]/', ' ', $halClean));
                    $halClean = preg_replace('/\s+/', ' ', $halClean);
                    if (!preg_match('/^surat\b/i', $halClean)) {
                        $filename = 'Surat ' . $halClean . '.pdf';
                    } else {
                        $filename = $halClean . '.pdf';
                    }

                    $gdriveResult = GoogleDriveService::uploadPdf($pdfFullPath, $filename, $suratObj['kode_surat'], $kepstaFolderId);

                    if (!empty($gdriveResult['success'])) {
                        $meta['gdrive_file_id'] = $gdriveResult['file_id'];
                        $meta['gdrive_web_link'] = $gdriveResult['web_link'];

                        // Re-generate barcode agar barcode pada Surat Nota Dinas mengarah langsung ke file PDF di Google Drive ini (bukan lagi ke Google Drive KTU)
                        if (function_exists('generateNotaDinasBarcode')) {
                            generateNotaDinasBarcode($id, $meta, $suratObj, $gdriveResult['web_link']);
                        }

                        $this->suratModel->updateSurat($id, [
                            'gdrive_file_id' => $gdriveResult['file_id'],
                            'gdrive_web_link' => $gdriveResult['web_link'],
                            'pdf_path' => '/uploads/pdf/' . basename($pdfFullPath),
                            'meta_data' => $meta
                        ]);
                        $gdriveSuccess = true;

                        // Re-generate Merged PDF now that barcode and gdrive_web_link are updated,
                        // so the QR Barcode in the PDF physically points to the Google Drive PDF file
                        $pdfFullPath = PdfGenerator::generateMergedPdf($id, true);
                        if (!empty($gdriveResult['file_id']) && !str_starts_with($gdriveResult['file_id'], 'gdrive_')) {
                            GoogleDriveService::updatePdfContent($gdriveResult['file_id'], $pdfFullPath);
                        }
                    } else {
                        $gdriveErrMsg = $gdriveResult['message'] ?? '';
                    }
                } catch (Throwable $e) {
                    $gdriveErrMsg = $e->getMessage();
                }

                // Sync Rekap Disposisi Excel bulanan
                try {
                    DisposisiExcelService::syncSuratToExcel($id);
                } catch (Throwable $e) {
                    error_log("Disposisi Excel error: " . $e->getMessage());
                }

                $logTitle = $isSuratCuti ? 'Disposisi Surat Cuti Selesai & Diteruskan' : 'Disposisi Surat Nota Dinas Selesai & Diteruskan';
                $logDesc = $isSuratCuti
                    ? 'Sekretaris telah selesai melakukan pengecekan lembar disposisi dari Kepala Stasiun. Berkas PDF Surat Cuti final beserta Barcode telah diunggah ke Google Drive (' . $filename . '), dicatat di Excel Disposisi, dan diteruskan ke halaman disposisi penerima serta Admin Pembuat Surat.'
                    : 'Sekretaris telah selesai melakukan pengecekan lembar disposisi dari Kepala Stasiun. Berkas PDF final telah diunggah ke Google Drive, dicatat di Excel Disposisi, dan diteruskan ke penerima disposisi serta Admin Pembuat Surat.';
                $actLogKey = $isSuratCuti ? 'PROCESS_SEKRE_COMPLETE_CUTI' : 'PROCESS_SEKRE_COMPLETE_NOTA_DINAS';

                SuratLog::log($id, $sekreUserId, $logTitle, 'completed', $logDesc);
                ActivityLog::log($actLogKey, 'Sekretaris menyelesaikan & meneruskan lembar disposisi perihal "' . ($surat['perihal'] ?? '') . '"', $sekreUserId);

                if ($gdriveSuccess) {
                    $succMsg = $isSuratCuti 
                        ? 'Surat Cuti berhasil diselesaikan dan diteruskan! Berkas PDF dan Barcode telah tergenerate dan terunggah ke Google Drive Surat Digital (' . $filename . '), tercatat di Rekap Excel, dan masuk ke halaman disposisi penerima serta Admin Pembuat Surat.'
                        : 'Surat Nota Dinas berhasil diselesaikan dan diteruskan! PDF Gabungan terunggah ke Google Drive, tercatat di Rekap Excel, dan masuk ke halaman disposisi penerima serta Admin.';
                    Session::setFlash('success', $succMsg);
                } else {
                    $warnText = !empty($gdriveErrMsg) ? " ({$gdriveErrMsg})" : '';
                    $failMsg = $isSuratCuti
                        ? 'Surat Cuti berhasil diselesaikan dan diteruskan! Dokumen masuk ke halaman disposisi penerima serta Admin Pembuat Surat, namun berkas PDF belum terunggah ke Google Drive' . $warnText
                        : 'Surat Nota Dinas berhasil diselesaikan dan diteruskan! Namun berkas PDF belum terunggah ke Google Drive' . $warnText;
                    Session::setFlash('warning', $failMsg);
                }

                $this->redirect('/dashboard');
                return;
            }

            // Final Forward: Update Surat status to completed & forward to Admin Pembuat Surat for Surat Tugas (khusus izin_produksi dan lainnya)
            $meta = $surat['meta'] ?? [];
            $meta['disposisi_forwarded_to_admin'] = true;
            $this->suratModel->updateSurat($id, [
                'status' => 'completed',
                'meta_data' => $meta
            ]);

            // Log Action
            SuratLog::log($id, $sekreUserId, 'Disposisi Selesai Dicek & Diteruskan ke Admin Pembuat Surat', 'completed', 'Sekretaris telah selesai melakukan pengecekan lembar disposisi dari Kepala Stasiun dan meneruskannya ke Admin Pembuat Surat untuk pembuatan Surat Tugas.');
            ActivityLog::log('PROCESS_SEKRE_FORWARD_ADMIN', 'Sekretaris meneruskan lembar disposisi surat perihal "' . ($surat['perihal'] ?? '') . '" ke Admin Pembuat Surat', $sekreUserId);

            Session::setFlash('success', 'Surat disposisi berhasil dicek dan diteruskan ke Admin Pembuat Surat untuk pembuatan Surat Tugas.');
            $this->redirect('/dashboard');
            return;
        }

        if ($actionType === 'revisi') {
            $catatanRevisi = Security::sanitize($_POST['catatan_revisi'] ?? '');
            if (empty($catatanRevisi)) {
                Session::setFlash('error', 'Catatan revisi wajib diisi jika memilih revisi.');
                $this->redirect('/surat/sekre-process/' . $id);
                return;
            }

            $surat = $this->suratModel->getById($id);
            $meta = $surat['meta'] ?? [];
            $meta['catatan_revisi'] = $catatanRevisi;

            $this->suratModel->updateSurat($id, [
                'status' => 'revisi',
                'meta_data' => $meta
            ]);

            SuratLog::log($id, $sekreUserId, 'Permintaan Revisi oleh Sekretaris', 'revisi', 'Sekretaris mengembalikan surat ke Admin Pembuat Surat untuk direvisi. Catatan: ' . $catatanRevisi);
            ActivityLog::log('PROCESS_SEKRE_REVISI', 'Sekretaris meminta revisi ke Admin Pembuat Surat untuk surat perihal "' . ($surat['perihal'] ?? '') . '"', $sekreUserId);

            Session::setFlash('warning', 'Surat berhasil dikembalikan ke Admin Pembuat Surat untuk direvisi.');
            $this->redirect('/dashboard');
            return;
        }

        if ($actionType === 'delete' || $actionType === 'hapus') {
            $surat = $this->suratModel->getById($id);
            $perihalSurat = $surat['perihal'] ?? '';

            ActivityLog::log('DELETE_SURAT', 'Sekretaris menghapus surat perihal "' . $perihalSurat . '"', $sekreUserId);

            $this->suratModel->deleteSurat($id);

            Session::setFlash('success', 'Surat berhasil dihapus dari database.');
            $this->redirect('/dashboard');
            return;
        }

        // Approve & Create Disposisi
        $nomorAgenda = Security::sanitize($_POST['nomor_agenda'] ?? '');
        $tanggalAgenda = Security::sanitize($_POST['tanggal_agenda'] ?? '');
        $halDisposisi = Security::sanitize($_POST['hal_disposisi'] ?? $_POST['hal'] ?? '');
        $catatanSekre = Security::sanitize($_POST['catatan_sekre'] ?? '');

        $surat = $this->suratModel->getById($id);
        $meta = $surat['meta'] ?? [];

        // Handle uploaded PDF attachment(s) if provided by Sekretaris
        $pdfAttachmentPaths = $meta['lampiran_pdf_paths'] ?? [];
        if (!is_array($pdfAttachmentPaths)) {
            $pdfAttachmentPaths = !empty($meta['lampiran_pdf_path']) ? [$meta['lampiran_pdf_path']] : [];
        }

        if (isset($_FILES['lampiran_pdf'])) {
            $files = $_FILES['lampiran_pdf'];
            $newUploaded = [];
            if (is_array($files['name'])) {
                $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if (isset($files['error'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpPath = $files['tmp_name'][$i];
                        $fileName = $files['name'][$i];
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        if ($fileExt === 'pdf') {
                            $uploadDirPdf = __DIR__ . '/../public/uploads/pdf';
                            $uploadDirPdfRoot = __DIR__ . '/../uploads/pdf';
                            if (!file_exists($uploadDirPdf)) mkdir($uploadDirPdf, 0777, true);
                            if (!file_exists($uploadDirPdfRoot)) mkdir($uploadDirPdfRoot, 0777, true);
                            $newPdfName = 'lampiran_sekre_' . $id . '_' . time() . '_' . mt_rand(1000, 9999) . '_' . $i . '.pdf';
                            move_uploaded_file($tmpPath, $uploadDirPdf . '/' . $newPdfName);
                            @copy($uploadDirPdf . '/' . $newPdfName, $uploadDirPdfRoot . '/' . $newPdfName);
                            $newUploaded[] = '/uploads/pdf/' . $newPdfName;
                        }
                    }
                }
            } elseif (isset($files['error']) && $files['error'] === UPLOAD_ERR_OK) {
                $tmpPath = $files['tmp_name'];
                $fileName = $files['name'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if ($fileExt === 'pdf') {
                    $uploadDirPdf = __DIR__ . '/../public/uploads/pdf';
                    $uploadDirPdfRoot = __DIR__ . '/../uploads/pdf';
                    if (!file_exists($uploadDirPdf)) mkdir($uploadDirPdf, 0777, true);
                    if (!file_exists($uploadDirPdfRoot)) mkdir($uploadDirPdfRoot, 0777, true);
                    $newPdfName = 'lampiran_sekre_' . $id . '_' . time() . '_' . mt_rand(1000, 9999) . '.pdf';
                    move_uploaded_file($tmpPath, $uploadDirPdf . '/' . $newPdfName);
                    @copy($uploadDirPdf . '/' . $newPdfName, $uploadDirPdfRoot . '/' . $newPdfName);
                    $newUploaded[] = '/uploads/pdf/' . $newPdfName;
                }
            }

            if (!empty($newUploaded)) {
                $pdfAttachmentPaths = array_merge($pdfAttachmentPaths, $newUploaded);
            }
        }

        $meta['lampiran_pdf_paths'] = array_values(array_unique($pdfAttachmentPaths));
        if (!empty($meta['lampiran_pdf_paths'])) {
            $meta['lampiran_pdf_path'] = $meta['lampiran_pdf_paths'][0];
        }

        if (!empty($nomorAgenda)) {
            $meta['nomor_agenda'] = $nomorAgenda;
        }
        if (!empty($tanggalAgenda)) {
            $meta['tanggal_agenda'] = $tanggalAgenda;
        }
        $meta['hal_disposisi'] = $halDisposisi;

        $dispoDataSave = [
            'sekre_user_id' => $sekreUserId,
            'catatan_sekre' => $catatanSekre,
            'sekre_approved_at' => date('Y-m-d H:i:s')
        ];
        if (!empty($halDisposisi)) {
            $dispoDataSave['hal'] = $halDisposisi;
        }

        $this->dispoModel->createOrUpdate($id, $dispoDataSave);

        $this->suratModel->updateSurat($id, [
            'status' => 'sekre_approved',
            'meta_data' => $meta
        ]);

        SuratLog::log($id, $sekreUserId, 'Disetujui Sekretaris & Disposisi Dibuat', 'sekre_approved', 'Sekretaris menyetujui surat (No Agenda: ' . $nomorAgenda . ', Hal: ' . $halDisposisi . ') dan membuat Lembar Disposisi untuk Kepala Stasiun.');
        ActivityLog::log('PROCESS_SEKRE', 'Sekretaris menyetujui & membuat Lembar Disposisi surat perihal "' . ($surat['perihal'] ?? '') . '"', $sekreUserId);

        Session::setFlash('success', 'Surat disetujui & Lembar Disposisi diteruskan ke Kepala Stasiun!');
        $this->redirect('/dashboard');
    }

    // Kepala Stasiun: Checklist, Note & Signature process
    public function kepstaProcess(int $id): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['kepala_stasiun', 'kepsta', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak. Fitur ini khusus Kepala Stasiun.');
            $this->redirect('/surat/detail/' . $id);
            return;
        }

        $surat = $this->suratModel->getById($id);
        $disposisi = $this->dispoModel->getBySuratId($id);

        // Pastikan barcode mengarah ke file PDF yang sudah ada keterangan sign (upload TU)
        $meta = $surat['meta'] ?? [];
        $isTuSigned = in_array(($surat['status'] ?? ''), ['tu_signed', 'sekre_approved', 'kepsta_approved', 'completed', 'disposisi_completed']) || !empty($surat['tu_signed_at']);
        $gdriveLink = $surat['gdrive_web_link'] ?? $meta['gdrive_web_link'] ?? '';
        $isRealGdrive = !empty($gdriveLink) && !str_contains($gdriveLink, '/folders/') && $gdriveLink !== 'https://drive.google.com';

        if ($isTuSigned && $isRealGdrive && (($meta['barcode_target_link'] ?? '') !== $gdriveLink)) {
            if (function_exists('generateNotaDinasBarcode')) {
                generateNotaDinasBarcode($id, $meta, $surat, $gdriveLink);
                $meta['barcode_target_link'] = $gdriveLink;
                $this->suratModel->updateSurat($id, ['meta_data' => $meta]);
                $surat['meta'] = $meta;
                $surat['meta_data'] = $meta;
            }
        }

        $this->view('surat/kepsta_process', [
            'title' => (!empty($surat['meta']['surat_tugas']) ? 'Tanda Tangan Dokumen Surat Tugas - Kepala Stasiun' : 'Disposisi & TTD Kepala Stasiun - Surat Digital'),
            'surat' => $surat,
            'disposisi' => $disposisi,
            'defaultChecklist' => Disposisi::getDefaultKepstaChecklistItems()
        ]);
    }

    // Kepala Stasiun: Submit Disposisi & TTD + Auto Generate PDF & Google Drive Upload
    public function kepstaSubmit(int $id): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['kepala_stasiun', 'kepsta', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak.');
            $this->redirect('/dashboard');
            return;
        }

        $surat = $this->suratModel->getById($id);
        if (!$surat) {
            Session::setFlash('error', 'Surat tidak ditemukan.');
            $this->redirect('/dashboard');
            return;
        }

        $meta = $surat['meta'] ?? [];
        $kepstaUserId = (int) Session::get('user_id');

        // Check if this is the Surat Tugas workflow submission
        $isSuratTugasSubmit = !empty($_POST['is_surat_tugas_submit']) || (!empty($meta['surat_tugas']) && in_array($surat['status'], ['sekre_approved']));
        if ($isSuratTugasSubmit) {
            $actionType = $_POST['action_type'] ?? 'submit';

            if ($actionType === 'revisi') {
                $catatanRevisi = Security::sanitize($_POST['catatan_revisi'] ?? '');
                if (empty($catatanRevisi)) {
                    Session::setFlash('error', 'Catatan revisi wajib diisi jika memilih revisi.');
                    $this->redirect('/surat/kepsta-process/' . $id);
                    return;
                }

                if (!isset($meta['surat_tugas'])) $meta['surat_tugas'] = [];
                $meta['catatan_revisi'] = $catatanRevisi;
                $meta['catatan_revisi_st'] = $catatanRevisi;
                $meta['surat_tugas_revisi_by'] = 'Kepala Stasiun';
                $meta['surat_tugas_status'] = 'revisi';
                $meta['surat_tugas']['status'] = 'revisi';
                $meta['surat_tugas']['tu_paraf'] = '';
                $meta['surat_tugas']['kepsta_signature'] = '';

                $this->suratModel->updateSurat($id, [
                    'meta_data' => $meta,
                    'status' => 'completed'
                ]);

                SuratLog::log($id, $kepstaUserId, 'Permintaan Revisi Surat Tugas oleh Kepala Stasiun', 'revisi', 'Kepala Stasiun mengembalikan Surat Tugas ke Admin Pembuat Surat untuk direvisi. Catatan: ' . $catatanRevisi);
                ActivityLog::log('REVISI_SURAT_TUGAS_KEPSTA', 'Kepala Stasiun meminta revisi Surat Tugas perihal "' . ($surat['perihal'] ?? '') . '" ke Admin (Catatan: ' . $catatanRevisi . ')', $kepstaUserId);

                Session::setFlash('warning', 'Surat Tugas berhasil dikembalikan ke Admin Pembuat Surat untuk direvisi.');
                $this->redirect('/dashboard');
                return;
            }

            $signatureData = $_POST['kepsta_signature_data'] ?? '';
            $existingSig = $meta['surat_tugas']['kepsta_signature'] ?? '';

            if (empty($signatureData) && empty($existingSig)) {
                Session::setFlash('error', 'Silakan bubuhkan tanda tangan Kepala Stasiun pada Surat Tugas terlebih dahulu.');
                $this->redirect('/surat/kepsta-process/' . $id);
                return;
            }

            $signaturePath = $existingSig ?: '/assets/img/ttd_kepsta_stamp.png';
            if (!empty($signatureData) && str_contains($signatureData, 'data:image')) {
                $data = explode(',', $signatureData);
                $decoded = base64_decode($data[1]);
                $uploadDir = __DIR__ . '/../public/uploads/signatures';
                $uploadDirRoot = __DIR__ . '/../uploads/signatures';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                if (!file_exists($uploadDirRoot)) mkdir($uploadDirRoot, 0777, true);
                $filename = 'ttd_kepsta_st_' . $id . '_' . time() . '.png';
                file_put_contents($uploadDir . '/' . $filename, $decoded);
                file_put_contents($uploadDirRoot . '/' . $filename, $decoded);
                $signaturePath = '/uploads/signatures/' . $filename;
            } elseif (!empty($signatureData) && !str_contains($signatureData, 'data:image')) {
                $signaturePath = $signatureData;
            }

            if (!isset($meta['surat_tugas'])) $meta['surat_tugas'] = [];
            $meta['surat_tugas']['kepsta_signature'] = $signaturePath;
            $meta['surat_tugas']['kepsta_signed_at'] = date('Y-m-d H:i:s');
            $meta['surat_tugas_status'] = 'kepsta_signed';

            $this->suratModel->updateSurat($id, [
                'meta_data' => $meta,
                'status' => 'kepsta_approved'
            ]);

            SuratLog::log($id, $kepstaUserId, 'Tanda Tangan Surat Tugas oleh Kepala Stasiun', 'kepsta_approved', 'Kepala Stasiun telah menandatangani Surat Tugas dan meneruskannya ke Sekretaris untuk pengecekan.');
            ActivityLog::log('SIGN_SURAT_TUGAS_KEPSTA', 'Menandatangani Surat Tugas perihal "' . ($surat['perihal'] ?? '') . '" dan meneruskan ke Sekretaris', $kepstaUserId);

            Session::setFlash('success', 'Surat Tugas berhasil ditandatangani dan diteruskan ke Sekretaris!');
            $this->redirect('/dashboard');
            return;
        }

        $checklist = $_POST['checklist'] ?? [];
        $sifatSurat = !empty($_POST['sifat_surat']) ? Security::sanitize($_POST['sifat_surat']) : null;
        $catatanKepsta = Security::sanitize($_POST['catatan_kepsta'] ?? '');
        $catatanSekretariat = Security::sanitize($_POST['catatan_sekretariat'] ?? '');
        $signatureData = $_POST['kepsta_signature_data'] ?? '';
        $kepstaUserId = (int) Session::get('user_id');

        if (empty($checklist) || empty($signatureData) || $signatureData === '/assets/img/ttd_kepsta_stamp.png') {
            Session::setFlash('error', 'Silakan melakukan centang pada penerima surat disposisi dan isi TTD terlebih dahulu.');
            $this->redirect('/surat/kepsta-process/' . $id);
            return;
        }

        $signaturePath = '/assets/img/ttd_kepsta_stamp.png'; // default Kepsta signature stamp

        if (!empty($signatureData) && str_contains($signatureData, 'data:image')) {
            $data = explode(',', $signatureData);
            $decoded = base64_decode($data[1]);
            $uploadDir = __DIR__ . '/../public/uploads/signatures';
            $uploadDirRoot = __DIR__ . '/../uploads/signatures';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            if (!file_exists($uploadDirRoot)) mkdir($uploadDirRoot, 0777, true);
            $filename = 'ttd_kepsta_' . $id . '_' . time() . '.png';
            file_put_contents($uploadDir . '/' . $filename, $decoded);
            file_put_contents($uploadDirRoot . '/' . $filename, $decoded);
            $signaturePath = '/uploads/signatures/' . $filename;
        }

        $dispoParams = [
            'kepsta_user_id' => $kepstaUserId,
            'sifat_surat' => $sifatSurat,
            'checklist' => $checklist,
            'catatan_kepsta' => $catatanKepsta,
            'kepsta_signature' => $signaturePath,
            'kepsta_signed_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($catatanSekretariat)) {
            $dispoParams['catatan_sekre'] = $catatanSekretariat;
        }

        // Update meta in surat for sifat_disposisi
        $suratObj = $this->suratModel->getById($id);
        $metaObj = $suratObj['meta'] ?? [];
        $isFromUploadLampiran = !empty($metaObj['from_upload_lampiran']) || (($suratObj['type'] ?? '') === 'upload_lampiran');
        $isCutiSurat = (class_exists('Surat') && Surat::isCutiSurat($suratObj));
        $isNotaDinasDoc = !$isCutiSurat && (
            ($suratObj['type'] ?? '') === 'nota_dinas' ||
            ($suratObj['type'] ?? '') === 'izin_produksi' ||
            ($suratObj['type'] ?? '') === 'izin_prinsip' ||
            in_array(($metaObj['kategori_surat'] ?? ''), ['Surat Nota Dinas', 'Surat Permohonan & Dinas']) ||
            str_contains(strtolower($metaObj['kategori_surat'] ?? ''), 'nota dinas') ||
            str_contains(strtolower($metaObj['kategori_surat'] ?? ''), 'produksi')
        );

        if (empty($sifatSurat)) {
            if (!$isNotaDinasDoc && !$isCutiSurat && !$isFromUploadLampiran) {
                $sifatSurat = !empty($metaObj['sifat_disposisi']) ? $metaObj['sifat_disposisi'] : (!empty($metaObj['sifat_surat']) ? $metaObj['sifat_surat'] : 'Biasa');
                $dispoParams['sifat_surat'] = $sifatSurat;
            } else {
                $dispoParams['sifat_surat'] = null;
            }
        }
        $metaObj['sifat_disposisi'] = $sifatSurat;
        $metaObj['sifat_surat_disposisi'] = $sifatSurat;
        unset($metaObj['catatan_revisi']);

        // 1. Update Disposisi with Kepsta checklist & signature & sifat
        $this->dispoModel->createOrUpdate($id, $dispoParams);

        // 2. Update Surat status to kepsta_approved and meta_data (Diteruskan ke Sekre untuk pengecekan)
        $this->suratModel->updateSurat($id, ['status' => 'kepsta_approved', 'meta_data' => $metaObj]);

        // 3. Log Action
        SuratLog::log($id, $kepstaUserId, 'Disposisi & TTD Kepsta Selesai (Diteruskan ke Sekre)', 'kepsta_approved', 'Kepala Stasiun telah mencentang tabel disposisi, membubuhkan Tanda Tangan Digital, dan meneruskan surat ke Sekretaris untuk pengecekan.');
        ActivityLog::log('PROCESS_KEPSTA', 'Kepala Stasiun menyetujui, TTD & meneruskan surat perihal "' . ($surat['perihal'] ?? '') . '" ke Sekretaris', $kepstaUserId);

        Session::setFlash('success', 'Persetujuan Disposisi & TTD Kepala Stasiun berhasil disimpan dan diteruskan ke Sekretaris untuk pengecekan.');
        $this->redirect('/dashboard');
        return;
    }

    // Disposisi Process Review & Print View for KPP Bidang, Kapel Bidang, and other users
    public function disposisiProcess(int $id): void
    {
        if (!Session::isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $surat = $this->suratModel->getById($id);
        if (!$surat) {
            Session::setFlash('error', 'Surat tidak ditemukan.');
            $this->redirect('/disposisi');
            return;
        }

        $userRole = Session::get('user_role', 'admin');
        $userId = (int) Session::get('user_id');

        // Access control:
        // - Admin hanya dapat mengakses lembar surat untuk surat yang dibuatnya sendiri
        // - User non-admin (penerima disposisi) dapat mengakses lembar disposisi:
        //   * Untuk Nota Dinas & Upload Lampiran: setelah diselesaikan oleh Sekretaris (status completed)
        //   * Untuk Surat Izin Produksi: setelah Surat Tugas selesai disetujui & diteruskan oleh Kepsta
        $meta = $surat['meta'] ?? [];
        $isFromUploadLampiran = !empty($meta['from_upload_lampiran']) || (($surat['type'] ?? '') === 'upload_lampiran');
        $isSuratCuti = (class_exists('Surat') && Surat::isCutiSurat($surat));
        $isNotaDinas = $isSuratCuti || in_array(($surat['type'] ?? ''), ['nota_dinas', 'izin_produksi']) || in_array(($meta['kategori_surat'] ?? ''), ['Surat Nota Dinas', 'Surat Cuti', 'Surat Permohonan & Dinas']);

        $disposisi = $this->dispoModel->getBySuratId($id);
        $hasSuratTugas = !$isSuratCuti && (
            isset($meta['has_surat_tugas'])
                ? (!empty($meta['has_surat_tugas']) && (string)$meta['has_surat_tugas'] !== '0')
                : (!empty($meta['surat_tugas']) || (!$isFromUploadLampiran && ($surat['type'] ?? '') !== 'nota_dinas'))
        );
        $isStCompleted = (($meta['surat_tugas_status'] ?? '') === 'completed') || (($meta['surat_tugas']['status'] ?? '') === 'completed');

        if ($userRole === 'admin') {
            $isCreator = ((int)$surat['user_id'] === $userId);
            $isRecipient = false;
            if (!$isCreator && $disposisi && !empty($disposisi['checklist'])) {
                $userObj = (new User())->findById($userId);
                $adminKeys = array_filter(array_unique([
                    $userObj['divisi'] ?? '',
                    $userObj['jabatan'] ?? '',
                    $userObj['name'] ?? ''
                ]));
                foreach ($adminKeys as $key) {
                    foreach ($disposisi['checklist'] as $checkedItem) {
                        $itemTrim = trim(html_entity_decode((string)$checkedItem, ENT_QUOTES, 'UTF-8'));
                        $keyTrim = trim($key);
                        if (strcasecmp($itemTrim, $keyTrim) === 0 || str_contains(strtolower($itemTrim), strtolower($keyTrim)) || str_contains(strtolower($keyTrim), strtolower($itemTrim))) {
                            $isRecipient = true;
                            break 2;
                        }
                    }
                }
            }
            if (!$isCreator && !$isFromUploadLampiran && !$isRecipient) {
                Session::setFlash('error', 'Halaman ini hanya dapat diakses oleh Admin pembuat surat terkait atau penerima disposisi.');
                $this->redirect($isNotaDinas ? '/disposisi' : '/surat-tugas');
                return;
            }
        } elseif ($userRole !== 'superadmin') {
            $hasCompletedSuratTugas = ($surat['status'] === 'completed') && (
                $hasSuratTugas ? $isStCompleted : ($isNotaDinas || $isFromUploadLampiran)
            );
            if (!$hasCompletedSuratTugas) {
                Session::setFlash('warning', 'Lembar disposisi belum dapat diakses karena masih menunggu penerbitan Surat Tugas dari Kepala Stasiun.');
                $this->redirect('/disposisi');
                return;
            }
        }

        $this->view('surat/disposisi_process', [
            'title' => 'Review & Cetak Disposisi - Surat Digital TVRI Jawa Timur',
            'surat' => $surat,
            'disposisi' => $disposisi
        ]);
    }

    // Surat Tugas Process Review & Creation View for Admin
    public function suratTugasProcess(int $id): void
    {
        if (!Session::isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $surat = $this->suratModel->getById($id);
        if (!$surat) {
            Session::setFlash('error', 'Surat tidak ditemukan.');
            $this->redirect('/surat-tugas');
            return;
        }

        $userRole = Session::get('user_role', 'admin');
        $userId = (int) Session::get('user_id');

        $meta = $surat['meta'] ?? [];
        $isFromUploadLampiran = !empty($meta['from_upload_lampiran']) || (($surat['type'] ?? '') === 'upload_lampiran');
        $isSuratCuti = (class_exists('Surat') && Surat::isCutiSurat($surat));
        $isNotaDinas = $isSuratCuti || in_array(($surat['type'] ?? ''), ['nota_dinas', 'izin_produksi']) || in_array(($meta['kategori_surat'] ?? ''), ['Surat Nota Dinas', 'Surat Cuti', 'Surat Permohonan & Dinas']);
        $hasSuratTugas = !$isSuratCuti && (
            isset($meta['has_surat_tugas'])
                ? (!empty($meta['has_surat_tugas']) && (string)$meta['has_surat_tugas'] !== '0')
                : (!empty($meta['surat_tugas']) || (!$isFromUploadLampiran && ($surat['type'] ?? '') !== 'nota_dinas'))
        );
        $isStCompleted = (($meta['surat_tugas_status'] ?? '') === 'completed') || (($meta['surat_tugas']['status'] ?? '') === 'completed');

        // Surat Nota Dinas tanpa Surat Tugas, Surat Cuti, dan Upload Lampiran langsung masuk ke lembar Disposisi (tidak melalui Surat Tugas)
        if (!$hasSuratTugas || $isSuratCuti || $isFromUploadLampiran || ($surat['type'] ?? '') === 'nota_dinas') {
            $this->redirect('/surat/disposisi-process/' . $id);
            return;
        }

        if ($userRole === 'admin') {
            if ((int)$surat['user_id'] !== $userId) {
                Session::setFlash('error', 'Halaman ini hanya dapat diakses oleh Admin pembuat surat terkait.');
                $this->redirect('/surat-tugas');
                return;
            }
        } elseif ($userRole !== 'superadmin') {
            $hasCompletedSuratTugas = ($surat['status'] === 'completed') && (
                $hasSuratTugas ? $isStCompleted : $isNotaDinas
            );
            if (!$hasCompletedSuratTugas) {
                Session::setFlash('warning', 'Lembar surat tugas belum dapat diakses karena masih menunggu penerbitan dari Kepala Stasiun.');
                $this->redirect('/surat-tugas');
                return;
            }
        }

        $disposisi = $this->dispoModel->getBySuratId($id);

        $this->view('surat/disposisi_process', [
            'title' => 'Review & Buat Dokumen Surat Tugas - Surat Digital TVRI Jawa Timur',
            'surat' => $surat,
            'disposisi' => $disposisi,
            'isSuratTugasProcess' => true
        ]);
    }

    // Save Surat Tugas Data from Disposisi Process (Role Admin)
    public function saveSuratTugas(int $id): void
    {
        if (!Session::isLoggedIn()) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            $this->redirect('/login');
            return;
        }

        $surat = $this->suratModel->getById($id);
        if (!$surat) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Surat tidak ditemukan']);
                exit;
            }
            Session::setFlash('error', 'Surat tidak ditemukan.');
            $this->redirect('/surat-tugas');
            return;
        }

        $userRole = Session::get('user_role', 'admin');
        if ($userRole !== 'admin') {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Hanya admin yang dapat menyimpan Surat Tugas']);
                exit;
            }
            Session::setFlash('error', 'Hanya admin yang dapat menyimpan Surat Tugas.');
            $this->redirect('/disposisi');
            return;
        }

        $meta = $surat['meta'] ?? [];
        $existingSt = $meta['surat_tugas'] ?? [];
        $meta['surat_tugas'] = [
            'nomor_surat' => trim($_POST['st_nomor_surat'] ?? '') ?: ($surat['nomor_surat'] ?? ''),
            'isi_surat' => trim($_POST['st_isi_surat'] ?? ''),
            'is_detail_acara' => !empty($_POST['st_is_detail_acara']),
            'mata_acara' => trim($_POST['st_mata_acara'] ?? ''),
            'lokasi' => trim($_POST['st_lokasi'] ?? ''),
            'judul' => trim($_POST['st_judul'] ?? ''),
            'tgl_produksi' => trim($_POST['st_tgl_produksi'] ?? ''),
            'is_tgl_hunting' => !empty($_POST['st_is_tgl_hunting']),
            'tgl_hunting' => trim($_POST['st_tgl_hunting'] ?? ''),
            'paket' => trim($_POST['st_paket'] ?? ''),
            'tgl_siaran' => trim($_POST['st_tgl_siaran'] ?? ''),
            'sumber_biaya' => trim($_POST['st_sumber_biaya'] ?? ''),
            'is_kru_table' => !empty($_POST['st_is_kru_table']),
            'kru_table_json' => $_POST['st_kru_table_json'] ?? '',
            'demikian' => trim($_POST['st_demikian'] ?? ''),
            'tgl_ttd' => trim($_POST['st_tgl_ttd'] ?? ''),
            'status' => 'submitted_to_tu',
            'tu_paraf' => '',
            'kepsta_signature' => ''
        ];
        $meta['has_surat_tugas'] = true;
        $meta['surat_tugas_status'] = 'submitted_to_tu';
        unset($meta['catatan_revisi'], $meta['catatan_revisi_st'], $meta['surat_tugas_revisi_by']);

        $this->suratModel->updateSurat($id, [
            'meta_data' => $meta,
            'status' => 'submitted_to_tu'
        ]);

        $adminUserId = (int) Session::get('user_id');
        SuratLog::log($id, $adminUserId, 'Surat Tugas Diteruskan ke Tata Usaha', 'submitted_to_tu', 'Admin meneruskan Surat Tugas ke Tata Usaha untuk diparaf.');
        ActivityLog::log('FORWARD_SURAT_TUGAS_ADMIN', 'Meneruskan Surat Tugas perihal "' . ($surat['perihal'] ?? '') . '" ke Tata Usaha', $adminUserId);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            Session::setFlash('success', 'Surat Tugas berhasil dikirim ke Tata Usaha!');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true, 
                'message' => 'Surat Tugas berhasil dikirim ke Tata Usaha!',
                'redirect_url' => BASE_URL . '/surat-tugas'
            ]);
            exit;
        }

        Session::setFlash('success', 'Surat Tugas berhasil dikirim ke Tata Usaha!');
        $this->redirect('/surat-tugas');
    }

    // Status Tracking Progress View (Redirected to /surat)
    public function tracking(int $id): void
    {
        $this->redirect('/surat');
    }

    // Detail View
    public function detail(int $id): void
    {
        $surat = $this->suratModel->getById($id);
        if (!$surat) {
            Session::setFlash('error', 'Surat tidak ditemukan.');
            $this->redirect('/surat');
            return;
        }

        $role = Session::get('user_role', 'admin');
        $disposisi = $this->dispoModel->getBySuratId($id);
        $logs = SuratLog::getLogs($id);

        if (isset($_GET['modal']) && $_GET['modal'] === '1') {
            $this->layout = '';
        }

        $this->view('surat/detail', [
            'title' => 'Detail Surat - ' . $surat['kode_surat'],
            'surat' => $surat,
            'disposisi' => $disposisi,
            'logs' => $logs,
            'role' => $role
        ]);
    }

    // History Page for Sekretaris & Superadmin
    public function history(): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['superadmin'])) {
            Session::setFlash('error', 'Akses ditolak.');
            $this->redirect('/dashboard');
            return;
        }

        $yearParam = isset($_GET['year']) ? (int)$_GET['year'] : 0;
        $monthParam = isset($_GET['month']) ? (int)$_GET['month'] : 0;

        $monthsIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        if ($yearParam > 0 && $monthParam >= 1 && $monthParam <= 12) {
            // Level 3: List Surat grouped by date
            $suratList = $this->suratModel->getCompletedHistoryByMonth($yearParam, $monthParam);

            $this->view('surat/history', [
                'title' => 'History Disposisi Surat - ' . ($monthsIndo[$monthParam] ?? '') . ' ' . $yearParam,
                'level' => 'letters',
                'selectedYear' => $yearParam,
                'selectedMonth' => $monthParam,
                'monthName' => $monthsIndo[$monthParam] ?? '',
                'suratList' => $suratList
            ]);
        } elseif ($yearParam > 0) {
            // Level 2: List Months with completed letters for selected year
            $monthsList = $this->suratModel->getCompletedHistoryMonths($yearParam);

            $this->view('surat/history', [
                'title' => 'History Disposisi Surat - Tahun ' . $yearParam,
                'level' => 'months',
                'selectedYear' => $yearParam,
                'monthsList' => $monthsList,
                'monthsIndo' => $monthsIndo
            ]);
        } else {
            // Level 1: List Years with completed letters
            $yearsList = $this->suratModel->getCompletedHistoryYears();

            $this->view('surat/history', [
                'title' => 'History Disposisi Surat - Pilih Tahun',
                'level' => 'years',
                'yearsList' => $yearsList
            ]);
        }
    }

    // AJAX Endpoint to save/update page rotation for lampiran PDFs
    public function updateRotation(): void
    {
        header('Content-Type: application/json');
        $suratId = (int) ($_POST['surat_id'] ?? 0);
        $rotationsJson = $_POST['lampiran_page_rotations'] ?? '{}';
        $rotations = json_decode($rotationsJson, true) ?? [];

        if ($suratId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID Surat tidak valid']);
            exit;
        }

        $surat = $this->suratModel->getById($suratId);
        if (!$surat) {
            echo json_encode(['success' => false, 'message' => 'Surat tidak ditemukan']);
            exit;
        }

        $meta = $surat['meta'] ?? [];
        $meta['lampiran_page_rotations'] = array_merge($meta['lampiran_page_rotations'] ?? [], $rotations);
        $this->suratModel->updateSurat($suratId, ['meta_data' => $meta]);

        echo json_encode(['success' => true, 'message' => 'Rotasi halaman berhasil disimpan']);
        exit;
    }

    // Sekretaris: Show dedicated Upload Lampiran & Disposisi page
    public function uploadLampiranForm(string|int $id = 0): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['sekretaris', 'sekre'])) {
            Session::setFlash('error', 'Akses ditolak. Fitur ini khusus Sekretaris.');
            $this->redirect('/dashboard');
            return;
        }

        $userId = (int) Session::get('user_id');
        $suratList = $this->suratModel->getSuratListForRole('sekretaris', $userId);

        $selectedId = (int) ($id ?: ($_GET['id'] ?? 0));

        $selectedSurat = null;
        $disposisi = null;
        if ($selectedId > 0) {
            $selectedSurat = $this->suratModel->getById($selectedId);
            if ($selectedSurat) {
                $disposisi = $this->dispoModel->getBySuratId($selectedId);
            }
        }

        $this->view('surat/upload_lampiran', [
            'title' => 'Upload Lampiran & Lembar Disposisi Sekretaris - Surat Digital TVRI Jawa Timur',
            'suratList' => $suratList,
            'selectedSurat' => $selectedSurat,
            'disposisi' => $disposisi
        ]);
    }

    // Sekretaris: Submit Upload Lampiran & Lembar Disposisi
    public function uploadLampiranSubmit(): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['sekretaris', 'sekre'])) {
            Session::setFlash('error', 'Akses ditolak.');
            $this->redirect('/dashboard');
            return;
        }

        $suratId = (int) ($_POST['surat_id'] ?? 0);
        $sekreUserId = (int) Session::get('user_id');
        $actionType = $_POST['action_type'] ?? 'approve';
        
        $surat = null;
        $meta = [];
        if ($suratId > 0) {
            $surat = $this->suratModel->getById($suratId);
            if ($surat) {
                $meta = $surat['meta'] ?? [];
            }
        }

        // Handle uploaded PDF attachment(s) if provided
        $pdfAttachmentPaths = [];
        if (isset($_POST['existing_pdf_paths']) && is_array($_POST['existing_pdf_paths'])) {
            foreach ($_POST['existing_pdf_paths'] as $path) {
                $cleanPath = Security::sanitize($path);
                if (!empty($cleanPath)) {
                    $pdfAttachmentPaths[] = $cleanPath;
                }
            }
        } elseif (!isset($_POST['existing_pdf_paths']) && !isset($_FILES['lampiran_pdf'])) {
            $pdfAttachmentPaths = $meta['lampiran_pdf_paths'] ?? [];
            if (!is_array($pdfAttachmentPaths)) {
                $pdfAttachmentPaths = !empty($meta['lampiran_pdf_path']) ? [$meta['lampiran_pdf_path']] : [];
            }
        }

        if (isset($_FILES['lampiran_pdf'])) {
            $files = $_FILES['lampiran_pdf'];
            $newUploaded = [];
            if (is_array($files['name'])) {
                $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if (isset($files['error'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpPath = $files['tmp_name'][$i];
                        $fileName = $files['name'][$i];
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        if ($fileExt === 'pdf') {
                            $uploadDirPdf = __DIR__ . '/../public/uploads/pdf';
                            $uploadDirPdfRoot = __DIR__ . '/../uploads/pdf';
                            if (!file_exists($uploadDirPdf)) mkdir($uploadDirPdf, 0777, true);
                            if (!file_exists($uploadDirPdfRoot)) mkdir($uploadDirPdfRoot, 0777, true);
                            $newPdfName = 'lampiran_sekre_' . ($suratId ?: 'standalone') . '_' . time() . '_' . mt_rand(1000, 9999) . '_' . $i . '.pdf';
                            move_uploaded_file($tmpPath, $uploadDirPdf . '/' . $newPdfName);
                            @copy($uploadDirPdf . '/' . $newPdfName, $uploadDirPdfRoot . '/' . $newPdfName);
                            $newUploaded[] = '/uploads/pdf/' . $newPdfName;
                        }
                    }
                }
            } elseif (isset($files['error']) && $files['error'] === UPLOAD_ERR_OK) {
                $tmpPath = $files['tmp_name'];
                $fileName = $files['name'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if ($fileExt === 'pdf') {
                    $uploadDirPdf = __DIR__ . '/../public/uploads/pdf';
                    $uploadDirPdfRoot = __DIR__ . '/../uploads/pdf';
                    if (!file_exists($uploadDirPdf)) mkdir($uploadDirPdf, 0777, true);
                    if (!file_exists($uploadDirPdfRoot)) mkdir($uploadDirPdfRoot, 0777, true);
                    $newPdfName = 'lampiran_sekre_' . ($suratId ?: 'standalone') . '_' . time() . '_' . mt_rand(1000, 9999) . '.pdf';
                    move_uploaded_file($tmpPath, $uploadDirPdf . '/' . $newPdfName);
                    @copy($uploadDirPdf . '/' . $newPdfName, $uploadDirPdfRoot . '/' . $newPdfName);
                    $newUploaded[] = '/uploads/pdf/' . $newPdfName;
                }
            }

            if (!empty($newUploaded)) {
                $pdfAttachmentPaths = array_merge($pdfAttachmentPaths, $newUploaded);
            }
        }

        if ($suratId > 0 && $surat) {
            $nomorAgenda = Security::sanitize($_POST['nomor_agenda'] ?? '');
            $tanggalAgenda = Security::sanitize($_POST['tanggal_agenda'] ?? '');
            $nomorSurat = Security::sanitize($_POST['nomor_surat'] ?? '');
            $dari = Security::sanitize($_POST['dari'] ?? '');
            $halDisposisi = Security::sanitize($_POST['hal_disposisi'] ?? $_POST['hal'] ?? '');
            $catatanSekre = Security::sanitize($_POST['catatan_sekre'] ?? '');

            $meta['lampiran_pdf_paths'] = array_values(array_unique($pdfAttachmentPaths));
            if (!empty($meta['lampiran_pdf_paths'])) {
                $meta['lampiran_pdf_path'] = $meta['lampiran_pdf_paths'][0];
            }
            if (!empty($nomorAgenda)) $meta['nomor_agenda'] = $nomorAgenda;
            if (!empty($tanggalAgenda)) $meta['tanggal_agenda'] = $tanggalAgenda;
            if (!empty($nomorSurat)) {
                $meta['nomor_surat_disposisi'] = $nomorSurat;
                $meta['nomor_surat'] = $nomorSurat;
                $this->suratModel->updateSurat($suratId, ['nomor_surat' => $nomorSurat]);
            }
            if (!empty($dari)) $meta['dari'] = $dari;
            $meta['hal_disposisi'] = $halDisposisi;
            $meta['from_upload_lampiran'] = true;

            // Update status to sekre_approved so it is forwarded to Kepsta dashboard
            $updateData = [
                'status' => 'sekre_approved',
                'meta_data' => $meta
            ];
            if (!empty($nomorSurat)) {
                $updateData['nomor_surat'] = $nomorSurat;
            }
            if (!empty($halDisposisi)) {
                $updateData['perihal'] = $halDisposisi;
            }
            $this->suratModel->updateSurat($suratId, $updateData);

            $dispoDataSave = [
                'sekre_user_id' => $sekreUserId,
                'catatan_sekre' => $catatanSekre,
                'sekre_approved_at' => date('Y-m-d H:i:s')
            ];
            if (!empty($halDisposisi)) {
                $dispoDataSave['hal'] = $halDisposisi;
            }

            $this->dispoModel->createOrUpdate($suratId, $dispoDataSave);

            SuratLog::log($suratId, $sekreUserId, 'Disetujui Sekretaris & Lembar Disposisi Dibuat', 'sekre_approved', 'Sekretaris mengunggah lampiran PDF, mengisi Lembar Disposisi, dan meneruskan surat ke Kepala Stasiun.');
            ActivityLog::log('PROCESS_SEKRE_UPLOAD', 'Sekretaris menyetujui & meneruskan surat perihal "' . ($surat['perihal'] ?? $halDisposisi) . '" ke Kepsta', $sekreUserId);

            Session::setFlash('success', 'Berkas lampiran PDF & Lembar Disposisi Sekretaris berhasil dikirim ke Kepala Stasiun!');
            $this->redirect('/dashboard');
            return;
        } else {
            // Standalone creation from upload-lampiran page if no prior surat selected
            $nomorAgenda = Security::sanitize($_POST['nomor_agenda'] ?? '');
            $tanggalAgenda = Security::sanitize($_POST['tanggal_agenda'] ?? '');
            $nomorSurat = Security::sanitize($_POST['nomor_surat'] ?? '');
            $dari = Security::sanitize($_POST['dari'] ?? '');
            $halDisposisi = Security::sanitize($_POST['hal_disposisi'] ?? $_POST['hal'] ?? '');
            $catatanSekre = Security::sanitize($_POST['catatan_sekre'] ?? '');

            $meta['lampiran_pdf_paths'] = array_values(array_unique($pdfAttachmentPaths));
            if (!empty($meta['lampiran_pdf_paths'])) {
                $meta['lampiran_pdf_path'] = $meta['lampiran_pdf_paths'][0];
            }
            if (!empty($nomorAgenda)) $meta['nomor_agenda'] = $nomorAgenda;
            if (!empty($tanggalAgenda)) $meta['tanggal_agenda'] = $tanggalAgenda;
            if (!empty($nomorSurat)) {
                $meta['nomor_surat_disposisi'] = $nomorSurat;
                $meta['nomor_surat'] = $nomorSurat;
            }
            if (!empty($dari)) $meta['dari'] = $dari;
            $meta['hal_disposisi'] = $halDisposisi;
            $meta['from_upload_lampiran'] = true;

            $newSuratId = $this->suratModel->createSurat([
                'type' => 'nota_dinas',
                'user_id' => $sekreUserId,
                'perihal' => $halDisposisi ?: 'Surat Disposisi Sekretaris',
                'kepada' => 'Kepala TVRI Stasiun Jawa Timur',
                'lampiran' => !empty($pdfAttachmentPaths) ? count($pdfAttachmentPaths) . ' Berkas PDF' : '1 (lembar)',
                'tanggal_surat' => date('Y-m-d'),
                'isi_surat' => $halDisposisi ?: 'Surat Disposisi Sekretaris',
                'nomor_surat' => $nomorSurat ?: null,
                'meta_data' => $meta,
                'status' => 'sekre_approved'
            ]);
            if (!empty($nomorSurat)) {
                $this->suratModel->updateSurat($newSuratId, ['nomor_surat' => $nomorSurat]);
            }

            $dispoDataSave = [
                'sekre_user_id' => $sekreUserId,
                'catatan_sekre' => $catatanSekre,
                'sekre_approved_at' => date('Y-m-d H:i:s')
            ];
            if (!empty($halDisposisi)) {
                $dispoDataSave['hal'] = $halDisposisi;
            }

            $this->dispoModel->createOrUpdate($newSuratId, $dispoDataSave);

            SuratLog::log($newSuratId, $sekreUserId, 'Disetujui Sekretaris & Lembar Disposisi Dibuat', 'sekre_approved', 'Sekretaris mengunggah lampiran PDF, mengisi Lembar Disposisi, dan meneruskan surat ke Kepala Stasiun.');
            ActivityLog::log('PROCESS_SEKRE_UPLOAD', 'Sekretaris menyetujui & meneruskan surat perihal "' . ($halDisposisi) . '" ke Kepsta', $sekreUserId);

            Session::setFlash('success', 'Berkas lampiran PDF & Lembar Disposisi Sekretaris berhasil dikirim ke Kepala Stasiun!');
            $this->redirect('/dashboard');
            return;
        }
    }
}
