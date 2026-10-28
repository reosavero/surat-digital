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
            $this->redirect('/disposisi');
            return;
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

        if ($sType === 'nota_dinas' || $sKat === 'Surat Nota Dinas') {
            $this->redirect('/surat-nota-dinas?id=' . $suratId);
        } elseif ($sType === 'izin_produksi' || str_contains(strtolower($sKat), 'produksi')) {
            $this->redirect('/surat-produksi?id=' . $suratId);
        } else {
            $this->redirect('/surat-prinsip?id=' . $suratId);
        }
    }

    // Admin: Show create or edit Surat Izin Prinsip form
    public function createPrinsip(): void
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

        $suratId = (int) ($_GET['id'] ?? 0);
        $surat = null;
        if ($suratId > 0) {
            $surat = $this->suratModel->getById($suratId);
            if (!$surat || ($role !== 'superadmin' && (int)$surat['user_id'] !== $userId)) {
                Session::setFlash('error', 'Surat tidak ditemukan atau Anda tidak memiliki akses.');
                $this->redirect('/surat/revisi');
                return;
            }

            // Auto-redirect if letter category is Nota Dinas or Izin Produksi
            $sType = $surat['type'] ?? '';
            $sKat = $surat['meta']['kategori_surat'] ?? '';
            if ($sType === 'nota_dinas' || $sKat === 'Surat Nota Dinas') {
                $this->redirect('/surat-nota-dinas?id=' . $suratId);
                return;
            } elseif ($sType === 'izin_produksi' || str_contains(strtolower($sKat), 'produksi')) {
                $this->redirect('/surat-produksi?id=' . $suratId);
                return;
            }
        }

        $this->view('surat/create_prinsip', [
            'title' => ($surat ? 'Revisi Surat Izin Prinsip' : 'Buat Surat Izin Prinsip') . ' - Surat Digital TVRI Jawa Timur',
            'currentUser' => $currentUser,
            'kppUsers' => $kppUsers,
            'surat' => $surat
        ]);
    }

    // Admin: Store Surat Izin Prinsip
    public function storePrinsip(): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['admin', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak.');
            $this->redirect('/dashboard');
            return;
        }

        $perihal = Security::sanitize($_POST['perihal'] ?? $_POST['perihal_dinas'] ?? $_POST['perihal_permohonan'] ?? '');
        $kepada = Security::sanitize($_POST['kepada'] ?? 'Kepala TVRI Stasiun Jawa Timur');
        $lampiran = Security::sanitize($_POST['lampiran'] ?? '3 (tiga) Lembar');
        $tanggal = Security::sanitize($_POST['tanggal_surat'] ?? date('Y-m-d'));
        $isiSurat = $_POST['isi_surat'] ?? $_POST['isi_surat_dinas'] ?? '';
        $kppUserId = (int) ($_POST['kpp_user_id'] ?? 0);

        // Handle TTD Pemohon if provided
        $pemohonSignaturePath = '';
        $pemohonSigData = $_POST['pemohon_signature_data'] ?? '';
        if (!empty($pemohonSigData) && str_contains($pemohonSigData, 'data:image')) {
            $dataSig = explode(',', $pemohonSigData);
            if (isset($dataSig[1])) {
                $decodedSig = base64_decode($dataSig[1]);
                $uploadDirSig = __DIR__ . '/../public/uploads/signatures';
                $uploadDirSigRoot = __DIR__ . '/../uploads/signatures';
                if (!file_exists($uploadDirSig)) mkdir($uploadDirSig, 0777, true);
                if (!file_exists($uploadDirSigRoot)) mkdir($uploadDirSigRoot, 0777, true);
                $filenameSig = 'ttd_pemohon_' . time() . '_' . mt_rand(1000, 9999) . '.png';
                file_put_contents($uploadDirSig . '/' . $filenameSig, $decodedSig);
                file_put_contents($uploadDirSigRoot . '/' . $filenameSig, $decodedSig);
                $pemohonSignaturePath = '/uploads/signatures/' . $filenameSig;
            }
        } elseif (!empty($pemohonSigData)) {
            $pemohonSignaturePath = Security::sanitize($pemohonSigData);
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

        $tuAccount = User::getSingleUserByRole('tata_usaha');
        $defaultTuNama = $tuAccount['name'] ?? 'Maya Sulfiana, S.Kom';
        $defaultTuNip = !empty($tuAccount['nip']) ? (str_starts_with($tuAccount['nip'], 'NIP.') ? $tuAccount['nip'] : 'NIP. ' . $tuAccount['nip']) : 'NIP. 198612252011012016';
        $defaultTuJabatan = $tuAccount['jabatan'] ?? 'Kepala Sub Bagian Tata Usaha TVRI Stasiun Jawa Timur';

        $meta = [
            'kategori_surat' => 'Surat Permohonan & Dinas',
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
            'pemohon_signature' => $pemohonSignaturePath,
            'lampiran_pdf_path' => $pdfAttachmentPaths[0] ?? '',
            'lampiran_pdf_paths' => $pdfAttachmentPaths,
            'lampiran_page_rotations' => json_decode($_POST['lampiran_page_rotations'] ?? '{}', true) ?? [],
            'kepada_permohonan' => Security::sanitize($_POST['kepada_permohonan'] ?? 'Yth. Kepala Sub Bagian Tata Usaha'),
            'kepada_sub_permohonan' => Security::sanitize($_POST['kepada_sub_permohonan'] ?? 'TVRI Stasiun Jawa Timur'),
            'kepada_sub' => Security::sanitize($_POST['kepada_sub'] ?? 'TVRI Stasiun Jawa Timur'),
            'perihal_permohonan' => Security::sanitize($_POST['perihal_permohonan'] ?? $perihal),
            'isi_surat_permohonan' => $_POST['isi_surat_permohonan'] ?? $isiSurat,
            'isi_surat_dinas' => $_POST['isi_surat_dinas'] ?? $isiSurat,
            'tujuan_kegiatan' => Security::sanitize($_POST['tujuan_kegiatan'] ?? ''),
            'lokasi_kegiatan' => Security::sanitize($_POST['lokasi_kegiatan'] ?? ''),
            'waktu_kegiatan' => Security::sanitize($_POST['waktu_kegiatan'] ?? ''),
            'penutup_surat' => Security::sanitize($_POST['penutup_surat'] ?? $_POST['penutup_permohonan'] ?? '')
        ];

        if (empty($perihal) || empty($kepada)) {
            Session::setFlash('error', 'Perihal dan Tujuan (Kepada) wajib diisi.');
            $this->redirect('/surat-prinsip');
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

            SuratLog::log($suratIdPost, $userId, 'Surat Izin Prinsip Direvisi & Dikirim Ulang ke KPP Bidang', 'submitted_to_kpp', 'Surat Izin Prinsip telah diperbarui oleh Admin Pembuat Surat dan dikirim ulang ke KPP Bidang.');
            ActivityLog::log('REVISE_SURAT_PRINSIP', 'Memperbarui Surat Izin Prinsip perihal "' . $perihal . '"', $userId);

            Session::setFlash('success', 'Surat Izin Prinsip berhasil direvisi dan dikirim ulang ke KPP Bidang!');
            $this->redirect('/surat/revisi');
            return;
        }

        $suratId = $this->suratModel->createSurat([
            'type' => 'izin_prinsip',
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

        SuratLog::log($suratId, $userId, 'Surat Izin Prinsip Dibuat & Dikirim ke KPP Bidang', 'submitted_to_kpp', 'Surat berhasil dibuat oleh Admin Pembuat Surat dan diteruskan ke KPP Bidang untuk TTD & Paraf.');
        ActivityLog::log('CREATE_SURAT_PRINSIP', 'Membuat Surat Izin Prinsip perihal "' . $perihal . '"', $userId);

        Session::setFlash('success', 'Surat Izin Prinsip berhasil dibuat dan dikirim ke KPP Bidang!');
        $this->redirect('/surat');
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

            // Auto-redirect if letter category is Nota Dinas or Izin Prinsip
            $sType = $surat['type'] ?? '';
            $sKat = $surat['meta']['kategori_surat'] ?? '';
            if ($sType === 'nota_dinas' || $sKat === 'Surat Nota Dinas') {
                $this->redirect('/surat-nota-dinas?id=' . $suratId);
                return;
            } elseif ($sType === 'izin_prinsip' || (str_contains(strtolower($sKat), 'prinsip') && !str_contains(strtolower($sKat), 'produksi'))) {
                $this->redirect('/surat-prinsip?id=' . $suratId);
                return;
            }
        }

        $this->view('surat/create_produksi', [
            'title' => ($surat ? 'Revisi Surat Izin Produksi' : 'Buat Surat Izin Produksi') . ' - Surat Digital TVRI Jawa Timur',
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
        $lampiran = Security::sanitize($_POST['lampiran'] ?? '3 (tiga)');
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

        $tuAccount = User::getSingleUserByRole('tata_usaha');
        $defaultTuNama = $tuAccount['name'] ?? 'Maya Sulfiana, S.Kom';
        $defaultTuNip = !empty($tuAccount['nip']) ? (str_starts_with($tuAccount['nip'], 'NIP.') ? $tuAccount['nip'] : 'NIP. ' . $tuAccount['nip']) : 'NIP. 198612252011012016';
        $defaultTuJabatan = $tuAccount['jabatan'] ?? 'Kepala Sub Bagian Tata Usaha TVRI Stasiun Jawa Timur';

        $meta = [
            'kategori_surat' => 'Surat Permohonan & Dinas',
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
            'isi_surat_permohonan' => $_POST['isi_surat_permohonan'] ?? $_POST['pembuka_surat'] ?? 'Sehubungan dengan adanya permintaan kerjasama dari bidang PU dan Promo, maka kami mengajukan permohonan produksi acara sebagai berikut :',
            'isi_surat_dinas' => $_POST['isi_surat_dinas'] ?? $isiSurat,
            'penutup_surat' => Security::sanitize($_POST['penutup_surat'] ?? $_POST['penutup_permohonan'] ?? 'Demikian permohonan ini kami sampaikan, atas perhatian dan kerjasamanya kami ucapkan terima kasih.')
        ];

        if (empty($perihal) || empty($kepada)) {
            Session::setFlash('error', 'Perihal dan Tujuan (Kepada) wajib diisi.');
            $this->redirect('/surat-produksi');
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

            SuratLog::log($suratIdPost, $userId, 'Surat Izin Produksi Direvisi & Dikirim Ulang ke KPP Bidang', 'submitted_to_kpp', 'Surat Izin Produksi telah diperbarui oleh Admin Pembuat Surat dan dikirim ulang ke KPP Bidang.');
            ActivityLog::log('REVISE_SURAT_PRODUKSI', 'Memperbarui Surat Izin Produksi perihal "' . $perihal . '"', $userId);

            Session::setFlash('success', 'Surat Izin Produksi berhasil direvisi dan dikirim ulang ke KPP Bidang!');
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

        SuratLog::log($suratId, $userId, 'Surat Izin Produksi Dibuat & Dikirim ke KPP Bidang', 'submitted_to_kpp', 'Surat Izin Produksi berhasil dibuat dan dikirim ke KPP Bidang.');
        ActivityLog::log('CREATE_SURAT_PRODUKSI', 'Membuat Surat Izin Produksi perihal "' . $perihal . '"', $userId);

        Session::setFlash('success', 'Surat Izin Produksi berhasil dibuat dan dikirim ke KPP Bidang!');
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

        $suratId = (int) ($_GET['id'] ?? 0);
        $surat = null;
        if ($suratId > 0) {
            $surat = $this->suratModel->getById($suratId);
            if (!$surat || ($role !== 'superadmin' && (int)$surat['user_id'] !== $userId)) {
                Session::setFlash('error', 'Surat tidak ditemukan atau Anda tidak memiliki akses.');
                $this->redirect('/surat/revisi');
                return;
            }

            // Auto-redirect if letter category is Izin Produksi or Izin Prinsip
            $sType = $surat['type'] ?? '';
            $sKat = $surat['meta']['kategori_surat'] ?? '';
            if ($sType === 'izin_produksi' || str_contains(strtolower($sKat), 'produksi')) {
                $this->redirect('/surat-produksi?id=' . $suratId);
                return;
            } elseif ($sType === 'izin_prinsip' || (str_contains(strtolower($sKat), 'prinsip') && !str_contains(strtolower($sKat), 'produksi'))) {
                $this->redirect('/surat-prinsip?id=' . $suratId);
                return;
            }
        }

        $this->view('surat/create_nota_dinas', [
            'title' => ($surat ? 'Revisi Surat Nota Dinas' : 'Buat Surat Nota Dinas') . ' - Surat Digital TVRI Jawa Timur',
            'currentUser' => $currentUser,
            'kppUsers' => $kppUsers,
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

        $perihal = Security::sanitize($_POST['perihal'] ?? 'Permohonan Cuti Tahunan');
        $kepada = Security::sanitize($_POST['kepada'] ?? 'Kepala TVRI Stasiun Jawa Timur');
        $lampiran = Security::sanitize($_POST['lampiran'] ?? '1 (lembar)');
        $tanggal = Security::sanitize($_POST['tanggal_surat'] ?? date('Y-m-d'));
        $isiSurat = $_POST['isi_surat'] ?? '';
        $kppUserId = (int) ($_POST['kpp_user_id'] ?? 0);

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

        $tuAccount = User::getSingleUserByRole('tata_usaha');
        $defaultTuNama = $tuAccount['name'] ?? 'Maya Sulfiana, S.Kom';
        $defaultTuNip = !empty($tuAccount['nip']) ? (str_starts_with($tuAccount['nip'], 'NIP.') ? $tuAccount['nip'] : 'NIP. ' . $tuAccount['nip']) : 'NIP. 198612252011012016';
        $defaultTuJabatan = $tuAccount['jabatan'] ?? 'Kepala Sub Bagian Tata Usaha TVRI Stasiun Jawa Timur';

        $meta = [
            'kategori_surat' => 'Surat Nota Dinas',
            'admin_gdrive_web_link' => $gdriveWebLink,
            'gdrive_web_link' => $gdriveWebLink,
            'sifat_surat' => Security::sanitize($_POST['sifat_surat'] ?? 'Biasa'),
            'dari' => Security::sanitize($_POST['dari'] ?? 'Kepala Sub Bagian Tata Usaha'),
            'penandatangan_nama' => Security::sanitize($_POST['penandatangan_nama'] ?? $defaultTuNama),
            'penandatangan_nip' => Security::sanitize($_POST['penandatangan_nip'] ?? $defaultTuNip),
            'penandatangan_jabatan' => Security::sanitize($_POST['penandatangan_jabatan'] ?? $defaultTuJabatan),
            'pemohon_nama' => Security::sanitize($_POST['pemohon_nama'] ?? 'Bagus Mahendra Putra'),
            'pemohon_nopeg' => Security::sanitize($_POST['pemohon_nopeg'] ?? '202205 29091996'),
            'pemohon_jabatan' => Security::sanitize($_POST['pemohon_jabatan'] ?? 'Staf Umum/Pengemudi'),
            'pemohon_hp' => Security::sanitize($_POST['pemohon_hp'] ?? '0856-4582-3054'),
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

        $suratId = $this->suratModel->createSurat([
            'type' => 'nota_dinas',
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

        SuratLog::log($suratId, $userId, 'Surat Nota Dinas Dibuat & Dikirim ke KPP Bidang', 'submitted_to_kpp', 'Surat Nota Dinas berhasil dibuat dan dikirim ke KPP Bidang untuk Paraf.');
        ActivityLog::log('CREATE_SURAT_NOTA_DINAS', 'Membuat Surat Nota Dinas perihal "' . $perihal . '"', $userId);

        Session::setFlash('success', 'Surat Nota Dinas berhasil dibuat dan dikirim ke KPP Bidang!');
        $this->redirect('/surat');
    }

    // KPP Bidang: Process & Sign form
    public function kppProcess(int $id): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['kpp_bidang', 'kpp', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak. Fitur ini khusus KPP Bidang.');
            $this->redirect('/surat/detail/' . $id);
            return;
        }

        $surat = $this->suratModel->getById($id);
        if (!$surat) {
            Session::setFlash('error', 'Surat tidak ditemukan.');
            $this->redirect('/surat');
            return;
        }

        $this->view('surat/kpp_process', [
            'title' => 'TTD Permohonan & Paraf Nota Dinas - KPP Bidang',
            'surat' => $surat
        ]);
    }

    // KPP Bidang: Submit Signature on Surat Permohonan & Paraf on Nota Dinas
    public function kppSubmit(int $id): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['kpp_bidang', 'kpp', 'superadmin'])) {
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
        $kppUserId = (int) Session::get('user_id');

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

            SuratLog::log($id, $kppUserId, 'Permintaan Revisi oleh KPP Bidang', 'revisi', 'KPP Bidang mengembalikan surat ke Admin Pembuat Surat untuk direvisi. Catatan: ' . $catatanRevisi);
            ActivityLog::log('PROCESS_KPP_REVISI', 'Meminta revisi surat perihal "' . ($surat['perihal'] ?? '') . '" (Catatan: ' . $catatanRevisi . ')', $kppUserId);

            Session::setFlash('warning', 'Surat berhasil dikembalikan ke Admin Pembuat Surat untuk direvisi.');
            $this->redirect('/dashboard');
            return;
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

        $this->suratModel->updateSurat($id, [
            'kpp_user_id' => $kppUserId,
            'kpp_signature' => $signaturePath,
            'kpp_signed_at' => date('Y-m-d H:i:s'),
            'meta_data' => $meta,
            'status' => 'kpp_signed'
        ]);

        SuratLog::log($id, $kppUserId, 'TTD Permohonan & Paraf Nota Dinas KPP Bidang Dibubuhkan', 'kpp_signed', 'KPP Bidang telah membubuhkan TTD Digital pada Surat Permohonan dan Paraf Digital pada Surat Nota Dinas. Surat diteruskan ke Tata Usaha.');
        ActivityLog::log('PROCESS_KPP', 'Membubuhkan TTD & Paraf KPP Bidang pada surat perihal "' . ($surat['perihal'] ?? '') . '"', $kppUserId);

        Session::setFlash('success', 'TTD Surat Permohonan & Paraf Nota Dinas berhasil disimpan. Surat dikirim ke Tata Usaha.');
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

        $this->view('surat/tu_process', [
            'title' => 'TTD Nota Dinas Tata Usaha - Surat Digital',
            'surat' => $surat
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

        $actionType = $_POST['action_type'] ?? 'submit';
        $tuUserId = (int) Session::get('user_id');

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

        // Auto-generate or upload TTE PDF to KTU Google Drive folder
        $gdriveSuccess = false;
        $gdriveErrMsg = '';
        try {
            $ktuFolderId = Setting::get('gdrive_ktu_folder_id', '1m1SVmXboA73OFJKSK-BjfRtYXMdxlfm7');

            if ($isTuTteUploaded && !empty($tuTtePdfPath)) {
                // TU uploaded a TTE PDF — use that file directly for Google Drive
                $ttePdfAbsPath = __DIR__ . '/../public' . $tuTtePdfPath;
                if (!file_exists($ttePdfAbsPath)) {
                    $ttePdfAbsPath = __DIR__ . '/..' . $tuTtePdfPath;
                }
                if (file_exists($ttePdfAbsPath)) {
                    // Build a clean filename from perihal
                    $perihalClean = !empty($surat['perihal']) ? preg_replace('/[^A-Za-z0-9_\-]/', '_', trim($surat['perihal'])) : '';
                    $perihalClean = preg_replace('/_+/', '_', trim($perihalClean, '_'));
                    $perihalClean = strlen($perihalClean) > 60 ? substr($perihalClean, 0, 60) : $perihalClean;
                    $filenameKtu = 'SURAT_TTE_' . ($perihalClean ? $perihalClean : 'DOKUMEN') . '.pdf';

                    $gdriveResultKtu = GoogleDriveService::uploadPdf($ttePdfAbsPath, $filenameKtu, $surat['kode_surat'], $ktuFolderId);
                    if (!empty($gdriveResultKtu['success'])) {
                        $this->suratModel->updateSurat($id, [
                            'gdrive_file_id' => $gdriveResultKtu['file_id'],
                            'gdrive_web_link' => $gdriveResultKtu['web_link']
                        ]);
                        $gdriveSuccess = true;
                    } else {
                        $gdriveErrMsg = $gdriveResultKtu['message'] ?? '';
                    }
                }
            } else {
                // No TTE upload — generate PDF from template and upload
                $pdfFullPathKtu = PdfGenerator::generateMergedPdf($id, false);
                $filenameKtu = basename($pdfFullPathKtu);
                $gdriveResultKtu = GoogleDriveService::uploadPdf($pdfFullPathKtu, $filenameKtu, $surat['kode_surat'], $ktuFolderId);
                if (!empty($gdriveResultKtu['success'])) {
                    $this->suratModel->updateSurat($id, [
                        'gdrive_file_id' => $gdriveResultKtu['file_id'],
                        'gdrive_web_link' => $gdriveResultKtu['web_link']
                    ]);
                    $gdriveSuccess = true;
                } else {
                    $gdriveErrMsg = $gdriveResultKtu['message'] ?? '';
                }
            }
        } catch (Throwable $e) {
            $gdriveErrMsg = $e->getMessage();
        }

        $logDetails = $isTuTteUploaded 
            ? 'Tata Usaha mengunggah Surat TTE (PDF) dan meneruskan ke Sekretaris.'
            : 'Tata Usaha telah membubuhkan TTD Digital pada Surat Nota Dinas. Surat diteruskan ke Sekretaris.';

        SuratLog::log($id, $tuUserId, 'Tata Usaha Memproses Surat ke Sekretaris', 'tu_signed', $logDetails);
        ActivityLog::log('PROCESS_TU', 'Membubuhkan TTD / Upload Surat TTE TU pada surat perihal "' . ($surat['perihal'] ?? '') . '"', $tuUserId);

        if ($gdriveSuccess) {
            Session::setFlash('success', 'Surat berhasil diproses, PDF tersimpan di Google Drive KTU, dan dikirim ke Sekretaris.');
        } else {
            $warnText = !empty($gdriveErrMsg) ? " ({$gdriveErrMsg})" : '';
            Session::setFlash('warning', 'Surat berhasil diproses dan dikirim ke Sekretaris. Namun berkas PDF belum terunggah ke Google Drive' . $warnText);
        }
        $this->redirect('/dashboard');
    }

    // Sekretaris: Approve / Revisi & Disposisi Form
    public function sekreProcess(int $id): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['sekretaris', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak. Fitur ini khusus Sekretaris.');
            $this->redirect('/surat/detail/' . $id);
            return;
        }

        $surat = $this->suratModel->getById($id);
        $disposisi = $this->dispoModel->getBySuratId($id);

        $this->view('surat/sekre_process', [
            'title' => 'Review & Buat Disposisi - Sekretaris',
            'surat' => $surat,
            'disposisi' => $disposisi
        ]);
    }

    // Sekretaris: Submit Approve or Revisi
    public function sekreSubmit(int $id): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['sekretaris', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak.');
            $this->redirect('/dashboard');
            return;
        }

        $actionType = $_POST['action_type'] ?? 'approve';
        $sekreUserId = (int) Session::get('user_id');

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
        if (!in_array($role, ['kepala_stasiun', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak. Fitur ini khusus Kepala Stasiun.');
            $this->redirect('/surat/detail/' . $id);
            return;
        }

        $surat = $this->suratModel->getById($id);
        $disposisi = $this->dispoModel->getBySuratId($id);

        $this->view('surat/kepsta_process', [
            'title' => 'Disposisi & TTD Kepala Stasiun - Surat Digital',
            'surat' => $surat,
            'disposisi' => $disposisi,
            'defaultChecklist' => Disposisi::getDefaultKepstaChecklistItems()
        ]);
    }

    // Kepala Stasiun: Submit Disposisi & TTD + Auto Generate PDF & Google Drive Upload
    public function kepstaSubmit(int $id): void
    {
        $role = Session::get('user_role', 'admin');
        if (!in_array($role, ['kepala_stasiun', 'superadmin'])) {
            Session::setFlash('error', 'Akses ditolak.');
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
        $metaObj['sifat_disposisi'] = $sifatSurat ?? '';
        $metaObj['sifat_surat_disposisi'] = $sifatSurat ?? '';

        // 1. Update Disposisi with Kepsta checklist & signature & sifat
        $this->dispoModel->createOrUpdate($id, $dispoParams);

        // 2. Update Surat status to completed and meta_data
        $this->suratModel->updateSurat($id, ['status' => 'completed', 'meta_data' => $metaObj]);

        // 3. Generate Merged PDF (Page 1 = Disposisi, Page 2+ = Admin Surat / Lampiran)
        $gdriveSuccess = false;
        $gdriveErrMsg = '';
        try {
            $pdfFullPath = PdfGenerator::generateMergedPdf($id, true);
            $kepstaFolderId = Setting::get('gdrive_kepsta_folder_id', '1q43jnW7Tw0ghLY7rrx-OPI3aTQAronrB');
            $surat = $this->suratModel->getById($id);
            $filename = basename($pdfFullPath);
            $gdriveResult = GoogleDriveService::uploadPdf($pdfFullPath, $filename, $surat['kode_surat'], $kepstaFolderId);

            if (!empty($gdriveResult['success'])) {
                $this->suratModel->updateSurat($id, [
                    'gdrive_file_id' => $gdriveResult['file_id'],
                    'gdrive_web_link' => $gdriveResult['web_link']
                ]);
                $gdriveSuccess = true;
            } else {
                $gdriveErrMsg = $gdriveResult['message'] ?? '';
            }
        } catch (Throwable $e) {
            $gdriveErrMsg = $e->getMessage();
        }

        // 4. Otomatis generate / sinkronkan Rekap Disposisi Excel bulanan
        try {
            DisposisiExcelService::syncSuratToExcel($id);
        } catch (Throwable $e) {
            error_log("Disposisi Excel error: " . $e->getMessage());
        }

        // 5. Log Action
        SuratLog::log($id, $kepstaUserId, 'Disetujui & Diberi TTD oleh Kepsta (Selesai & Google Drive)', 'completed', 'Kepala Stasiun telah mencentang tabel disposisi, memberikan instruksi, dan membubuhkan Tanda Tangan Digital. Dokumen PDF gabungan otomatis diunggah ke Google Drive.');
        ActivityLog::log('PROCESS_KEPSTA', 'Kepala Stasiun menyetujui, TTD & generate PDF gabungan surat perihal "' . ($surat['perihal'] ?? '') . '"', $kepstaUserId);

        if ($gdriveSuccess) {
            Session::setFlash('success', 'Persetujuan Disposisi & TTD Kepala Stasiun berhasil! PDF Gabungan terunggah ke Google Drive & data otomatis tercatat di Rekap Excel bulanan.');
        } else {
            $warnText = !empty($gdriveErrMsg) ? " ({$gdriveErrMsg})" : '';
            Session::setFlash('warning', 'Persetujuan Disposisi & TTD Kepala Stasiun berhasil disimpan! Namun PDF Gabungan belum terunggah ke Google Drive' . $warnText);
        }

        $this->redirect('/dashboard');
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

        $disposisi = $this->dispoModel->getBySuratId($id);

        $this->view('surat/disposisi_process', [
            'title' => 'Review & Cetak Disposisi - Surat Digital TVRI Jawa Timur',
            'surat' => $surat,
            'disposisi' => $disposisi
        ]);
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
            $this->suratModel->updateSurat($suratId, [
                'status' => 'sekre_approved',
                'meta_data' => $meta
            ]);

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
            if (!empty($nomorSurat)) $meta['nomor_surat_disposisi'] = $nomorSurat;
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
