<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Model.php';
require_once __DIR__ . '/../models/Surat.php';
require_once __DIR__ . '/../models/Disposisi.php';
require_once __DIR__ . '/../models/SuratLog.php';

try {
    $suratModel = new Surat();
    $dispoModel = new Disposisi();

    $suratId = $suratModel->createSurat([
        'type' => 'izin_prinsip',
        'user_id' => 1,
        'perihal' => 'Permohonan Disposisi Pelaksanaan Liputan Khusus Kunjungan Kerja Menteri Komdigi RI di Surabaya',
        'kepada' => 'Kepala TVRI Stasiun Jawa Timur',
        'lampiran' => '1 (satu) Berkas Rundown & Proposal',
        'tanggal_surat' => date('Y-m-d'),
        'isi_surat' => "Sehubungan dengan agenda Kunjungan Kerja Menteri Komunikasi dan Digital RI di Wilayah Surabaya dan Sidoarjo pada tanggal 20 - 22 September 2026, bersama ini kami mengajukan permohonan Izin Prinsip dan Petunjuk Disposisi untuk penugasan Tim Liputan Khusus Berita dan Media Baru LPP TVRI Stasiun Jawa Timur.\n\nDemikian permohonan ini kami sampaikan, atas perhatian dan petunjuk Bapak Kepala Stasiun kami ucapkan terima kasih.",
        'meta_data' => [
            'nomor_agenda' => '1852/INT/09/2026',
            'tanggal_agenda' => date('d F Y'),
            'dari' => 'Kepala Sub Bagian Tata Usaha TVRI Jawa Timur',
            'sifat_surat' => 'segera',
            'kategori_surat' => 'Surat Dinas',
            'penandatangan_nama' => 'Maya Sulfiana, S.Kom',
            'penandatangan_nip' => 'NIP. 198612252011012016'
        ],
        'status' => 'sekre_approved'
    ]);

    $suratModel->updateSurat($suratId, [
        'nomor_surat' => 'B-108/TU/TVRI-JATIM/' . date('Y'),
        'tu_user_id' => 2,
        'tu_signature' => '/assets/img/ttd_tu_stamp.png',
        'tu_signed_at' => date('Y-m-d H:i:s')
    ]);

    $dispoModel->createOrUpdate($suratId, [
        'sekre_user_id' => 3,
        'sifat_surat' => 'segera',
        'catatan_sekre' => 'Mohon petunjuk dan arahan dari Pak Kepala Stasiun untuk koordinasi liputan.',
        'sekre_approved_at' => date('Y-m-d H:i:s')
    ]);

    SuratLog::log($suratId, 1, 'Surat Izin Prinsip Dibuat & Dikirim ke TU', 'submitted_to_tu', 'Surat dibuat oleh Admin Pembuat Surat.');
    SuratLog::log($suratId, 2, 'Nomor Surat, TTD & Paraf TU Dibubuhkan', 'tu_signed', 'Tata Usaha membubuhkan Nomor Surat B-108/TU/TVRI-JATIM/' . date('Y') . ' dan TTD Digital.');
    SuratLog::log($suratId, 3, 'Disetujui Sekretaris & Disposisi Dibuat', 'sekre_approved', 'Sekretaris menyetujui surat (No Agenda: 1852/INT/09/2026) dan meneruskan Lembar Disposisi ke Kepala Stasiun.');

    echo "SUCCESS: Dummy Kepsta data created with Surat ID = {$suratId}\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
